<?php
namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\StockCodeModel;
use App\Models\MlCredentialsModel; // << Certifique-se que o use está aqui
use CodeIgniter\API\ResponseTrait;
use Config\Services;
// Adicione esta linha para usar a classe MercadoLivreAuth
use App\Libraries\MercadoLivreAuth; // << ADICIONADO IMPORT

class WebhookController extends BaseController
{
    use ResponseTrait;

    private ?int $sellerId = null;
    private ?string $accessToken = null;
    private ?object $credentials = null; // Para guardar as credenciais do DB

    /**
     * Obtém as credenciais do banco de dados (uma única vez por requisição).
     */
    private function getDbCredentials(): ?object
    {
        if ($this->credentials === null) {
            $credentialsModel = new MlCredentialsModel();
            // Busca pela chave 'default' ou a chave que você usa
            $this->credentials = $credentialsModel->getCredentials('default');

            // Se não existir o registro no banco, tenta criar um vazio
            if ($this->credentials === null) {
                log_message('warning', "Nenhum registro encontrado em ml_credentials para key_name='default'. Tentando inserir registro inicial.");
                $insertedId = $credentialsModel->insert(['key_name' => 'default'], true); // Tenta inserir
                if ($insertedId) {
                    $this->credentials = $credentialsModel->find($insertedId); // Busca o registro recém-criado
                } else {
                    log_message('error', "Falha ao inserir registro inicial em ml_credentials.");
                }
            }
        }
        return $this->credentials;
    }

    /**
     * Obtém o Access Token, priorizando o DB e implementando refresh se necessário.
     */
    private function getAccessToken(): ?string
    {
        // Se já temos o token nesta requisição, retorna ele
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $dbCredentials = $this->getDbCredentials();

        if ($dbCredentials && !empty($dbCredentials->access_token)) {
            // Verificar se o token expirou
            $tokenUpdatedAt = strtotime($dbCredentials->token_updated_at ?? '1970-01-01');
            $expiresIn = $dbCredentials->expires_in ?? 0;
            $now = time();

            // Se (tempo_atualização + duração - margem_segurança) < agora, então expirou
            if (($tokenUpdatedAt + $expiresIn - 300) < $now) { // 300s = 5 minutos de margem
                log_message('info', "Access Token expirado ou próximo de expirar. Tentando refresh...");

                // ***** INÍCIO: CHAMADA DA LÓGICA DE REFRESH TOKEN *****
                if (MercadoLivreAuth::refreshToken()) { // Chama o método estático da Library
                    log_message('info', "Refresh Token bem-sucedido via MercadoLivreAuth::refreshToken(). Recarregando credenciais.");
                    // Força recarregar as credenciais do banco na próxima chamada a getDbCredentials
                    $this->credentials = null;
                    $dbCredentials = $this->getDbCredentials(); // Recarrega
                    // Verifica se agora temos um token válido
                    if ($dbCredentials && !empty($dbCredentials->access_token)) {
                        $this->accessToken = $dbCredentials->access_token;
                        log_message('debug', 'Novo Access Token carregado após refresh: ' . substr($this->accessToken, 0, 10) . '...');
                    } else {
                        log_message('error', "Falha ao carregar o novo Access Token do banco após o refresh.");
                        $this->accessToken = null; // Garante que está nulo
                    }
                } else {
                    log_message('error', "Falha ao executar MercadoLivreAuth::refreshToken(). O token não foi atualizado.");
                    $this->accessToken = null; // Garante que está nulo
                }
                // ***** FIM: CHAMADA DA LÓGICA DE REFRESH TOKEN *****

            } else {
                // Token ainda válido
                $this->accessToken = $dbCredentials->access_token;
                log_message('debug', 'Access Token válido obtido do banco: ' . substr($this->accessToken, 0, 10) . '...');
            }
        } else {
            // Se não encontrou token inicial no banco E NÃO conseguiu renovar (caso tenha tentado)
             if ($dbCredentials && empty($dbCredentials->refresh_token)) {
                 log_message('error', "Access Token não encontrado no banco de dados e SEM Refresh Token para tentar renovar. Faça a autenticação inicial.");
             } else {
                 log_message('error', "Access Token não encontrado no banco de dados e/ou falha ao obter/renovar.");
             }
            $this->accessToken = null;
        }

        // Retorna o token obtido (ou null se falhou)
        return $this->accessToken;
    }


    /**
     * Obtém o Seller ID, priorizando o banco de dados.
     * Se não encontrar no DB, busca na API /users/me e salva no DB.
     */
    private function getSellerId(): ?int
    {
        if ($this->sellerId !== null) {
            return $this->sellerId;
        }

        $dbCredentials = $this->getDbCredentials();

        if ($dbCredentials && !empty($dbCredentials->seller_id)) {
            $this->sellerId = (int) $dbCredentials->seller_id;
            log_message('debug', "Seller ID obtido do banco de dados: " . $this->sellerId);
            return $this->sellerId;
        } else {
            log_message('warning', "Seller ID não encontrado no banco de dados. Tentando buscar via API /users/me...");
            $sellerIdFromApi = $this->fetchAndSaveSellerId(); // Chama a função auxiliar
            if ($sellerIdFromApi) {
                $this->sellerId = $sellerIdFromApi;
                return $this->sellerId;
            } else {
                log_message('error', "Não foi possível obter o Seller ID nem do DB nem da API.");
                return null;
            }
        }
    }

    /**
     * Função auxiliar para buscar Seller ID da API e salvar/atualizar no DB.
     */
    private function fetchAndSaveSellerId(): ?int
    {
        // Usa getAccessToken() que já tem a lógica de refresh
        $token = $this->getAccessToken();
        if (!$token) {
             log_message('error', "fetchAndSaveSellerId: Não foi possível obter Access Token válido para buscar Seller ID.");
            return null;
        }


        try {
            $httpClient = Services::curlrequest(['baseURI' => 'https://api.mercadolibre.com/', 'timeout' => 10, 'http_errors' => false]); // Aumentei timeout
            $response = $httpClient->get('users/me', ['headers' => ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json']]);

            if ($response->getStatusCode() !== 200) {
                 if ($response->getStatusCode() === 401) {
                     log_message('error', "fetchAndSaveSellerId: Erro 401 ao buscar /users/me. O token pode ter expirado durante o processo ou é inválido.");
                 } else {
                    log_message('error', "fetchAndSaveSellerId: Falha ao buscar /users/me. Status: " . $response->getStatusCode() . " Body: " . $response->getBody());
                 }
                return null;
            }

            $userData = json_decode($response->getBody());
            if (json_last_error() !== JSON_ERROR_NONE || !isset($userData->id)) {
                log_message('error', "fetchAndSaveSellerId: Falha ao decodificar JSON ou ID ausente na resposta de /users/me. Body: " . $response->getBody());
                return null;
            }

            $fetchedSellerId = (int) $userData->id;
            log_message('info', "Seller ID obtido da API: " . $fetchedSellerId . ". Atualizando/Salvando no banco de dados...");

            // Salva usando a nova função do model
            $credentialsModel = new MlCredentialsModel();
            if ($credentialsModel->saveSellerId($fetchedSellerId, 'default')) { // Salva para a chave 'default'
                log_message('info', "Seller ID {$fetchedSellerId} salvo/atualizado no banco com sucesso.");
                // Atualiza a propriedade local de credenciais se ela já foi carregada antes
                if ($this->credentials) {
                    $this->credentials->seller_id = $fetchedSellerId;
                }
                return $fetchedSellerId;
            } else {
                log_message('error', "fetchAndSaveSellerId: Falha ao salvar Seller ID {$fetchedSellerId} no banco de dados.");
                return null;
            }

        } catch (\Throwable $e) {
            log_message('error', "fetchAndSaveSellerId: Exceção ao buscar e salvar Seller ID: " . $e->getMessage());
            return null;
        }
    }


    public function handle()
    {
        $json = $this->request->getJSON();
        // Log verboso pode ser útil no início, mas considere reduzir em produção
        // log_message('info', 'Webhook ML recebido: ' . json_encode($json));
        log_message('info', 'Webhook ML recebido: Tópico=' . ($json->topic ?? 'N/A') . ', Resource=' . ($json->resource ?? 'N/A'));


        // Verifica se o tópico é 'orders_v2'
        if (isset($json->topic) && $json->topic === 'orders_v2') {
            $orderId = null; // Inicializa para ter escopo fora do try
            try {
                // Extrai o ID do pedido do campo 'resource'
                $resourceParts = explode('/', $json->resource ?? '');
                $orderId = end($resourceParts);

                if (empty($orderId) || !is_numeric($orderId)) { // Verifica se é numérico também
                    throw new \Exception("Não foi possível extrair um ID de pedido válido do resource: " . ($json->resource ?? 'N/A'));
                }

                log_message('info', "Pedido ID {$orderId} recebido via webhook. Iniciando processamento...");


                // *** Obter Access Token e Seller ID ***
                // Chamam os métodos que agora incluem a lógica de refresh e busca/salvamento do Seller ID
                $currentAccessToken = $this->getAccessToken();
                $currentSellerId = $this->getSellerId();

                // Se não conseguiu token ou seller id, não pode continuar
                if (!$currentAccessToken || !$currentSellerId) {
                    throw new \Exception("Pedido {$orderId}: Não foi possível obter Access Token ou Seller ID válidos após tentativas. Verifique logs anteriores e configuração.");
                }
                // ***************************************

                // b. Buscar detalhes do pedido na API do ML
                $httpClient = Services::curlrequest([
                    'baseURI' => 'https://api.mercadolibre.com/',
                    'timeout' => 15, // Aumentar um pouco pode ajudar
                    'http_errors' => false, // Continuar tratando erros manualmente
                ]);

                log_message('info', "Pedido {$orderId}: Buscando detalhes na API...");
                $response = $httpClient->get('orders/' . $orderId, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $currentAccessToken,
                        'Accept' => 'application/json',
                    ]
                ]);

                // Tratar falha na busca do pedido
                if ($response->getStatusCode() !== 200) {
                     if ($response->getStatusCode() === 401) {
                         log_message('error', "Pedido {$orderId}: Erro 401 ao buscar detalhes. Token pode ter se tornado inválido entre a obtenção e o uso. Ou problema de permissão.");
                     } elseif ($response->getStatusCode() === 404) {
                         log_message('warning', "Pedido {$orderId}: Erro 404 ao buscar detalhes. Pedido não encontrado na API do ML. Pode ser um pedido antigo ou teste.");
                         // Em caso de 404, talvez não seja um erro crítico, apenas sair.
                         return $this->response->setStatusCode(200); // Retorna 200 para o ML não reenviar
                     }
                    // Outros erros
                    throw new \Exception("Pedido {$orderId}: Falha ao buscar detalhes na API do ML. Status: " . $response->getStatusCode() . " Body: " . $response->getBody());
                }

                $orderData = json_decode($response->getBody());
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception("Pedido {$orderId}: Falha ao decodificar JSON da resposta da API de pedidos. Body: " . $response->getBody());
                }


                // c. Verificar status do pagamento
                // Iterar sobre os pagamentos para garantir que pelo menos um está 'approved'
                $paymentApproved = false;
                if (isset($orderData->payments) && is_array($orderData->payments)) {
                    foreach ($orderData->payments as $payment) {
                        if (isset($payment->status) && $payment->status === 'approved') {
                            $paymentApproved = true;
                            break;
                        }
                    }
                }

                if (!$paymentApproved) {
                    $paymentStatus = $orderData->payments[0]->status ?? 'N/A'; // Pega o status do primeiro pagamento para log
                    log_message('info', "Pedido {$orderId} não está com pagamento aprovado. Status Pagamento: {$paymentStatus}, Status Pedido: " . ($orderData->status ?? 'N/A') . ". Ignorando entrega.");
                    return $this->response->setStatusCode(200); // OK para o ML, mas não fazemos nada
                }
                log_message('info', "Pedido {$orderId}: Pagamento APROVADO. Continuando processamento.");

                // d. Pegar ID do item e ID do comprador
                if (!isset($orderData->order_items[0]->item->id)) {
                    // Log mais detalhado se a estrutura for inesperada
                    log_message('error', "Pedido {$orderId}: Estrutura inesperada nos dados do pedido. Não foi possível encontrar order_items[0]->item->id. Dados: " . json_encode($orderData->order_items ?? null));
                    throw new \Exception("Pedido {$orderId}: Não foi possível encontrar o ID do item nos dados do pedido.");
                }
                $mlItemId = $orderData->order_items[0]->item->id;
                $buyerId = $orderData->buyer->id ?? null; // ID do comprador

                if (empty($buyerId)) {
                    // Pode acontecer em alguns casos (ex: carrinho abandonado?), logar mas talvez não seja erro fatal se a entrega não depender disso
                    log_message('warning', "Pedido {$orderId}: Não foi possível obter o ID do comprador. A mensagem pós-venda não será enviada.");
                } else {
                     log_message('info', "Pedido {$orderId}: Comprador ID {$buyerId}, Item ML ID {$mlItemId}.");
                }


                // e. Buscar produto correspondente no banco de dados local
                $productModel = new ProductModel();
                $product = $productModel->where('ml_item_id', $mlItemId)->first();

                if (!$product) {
                    // Isso é um problema de configuração -> ERRO
                    log_message('error', "Pedido {$orderId}: Produto com ML Item ID {$mlItemId} não encontrado no banco de dados local. VERIFICAR CADASTRO.");
                    // Notificar admin seria bom aqui
                    throw new \Exception("Produto com ML Item ID {$mlItemId} não encontrado no banco de dados local para o pedido {$orderId}.");
                }
                log_message('info', "Pedido {$orderId}: Produto local ID {$product->id} (Tipo: {$product->product_type}) encontrado.");

                $productId = $product->id;
                $deliveryContent = null; // Conteúdo a ser entregue (código ou link descriptografado)
                $stockCodeId = null;     // ID do código de estoque, se aplicável


                // f. Lógica de entrega baseada no tipo de produto
                // --- Entrega de Código Único ---
                if ($product->product_type === 'unique_code') {
                    $stockCodeModel = new StockCodeModel();

                    // Transação para garantir atomicidade ao buscar e marcar como vendido
                    $this->db = \Config\Database::connect(); // Garante que temos a conexão DB
                    $this->db->transStart();

                    $availableCode = $stockCodeModel
                        ->where('product_id', $productId)
                        ->where('is_sold', false)
                        ->groupStart()
                            ->where('expires_at IS NULL') // Códigos sem data de expiração
                            ->orWhere('expires_at >=', date('Y-m-d')) // Ou com data válida
                        ->groupEnd()
                        ->orderBy('created_at', 'ASC') // Pega o mais antigo disponível
                        ->lockForUpdate() // IMPORTANTE: Trava a linha para evitar condição de corrida
                        ->first();

                    if ($availableCode) {
                        $stockCodeId = $availableCode->id;
                        $updateData = [
                            'is_sold'     => true,
                            'sold_at'     => date('Y-m-d H:i:s'),
                            'ml_order_id' => $orderId,
                            'ml_buyer_id' => $buyerId, // Mesmo que seja null, registra
                        ];
                        // Atualiza o código específico que foi encontrado e travado
                        $updated = $stockCodeModel->update($stockCodeId, $updateData);

                         if (!$updated) {
                             $this->db->transRollback(); // Desfaz a transação
                             throw new \Exception("Pedido {$orderId}: Falha crítica ao marcar o código {$stockCodeId} como vendido no banco de dados.");
                         }

                        // Tenta descriptografar DEPOIS de marcar como vendido
                        try {
                            $deliveryContent = service('encrypter')->decrypt($availableCode->code);
                        } catch (\Throwable $decryptError) {
                            $this->db->transRollback(); // Desfaz, pois não podemos entregar
                            log_message('critical', "Pedido {$orderId}: Erro CRÍTICO ao DESCRIPTOGRAFAR código ID {$stockCodeId} que FOI MARCADO como vendido: " . $decryptError->getMessage() . ". A VENDA FOI REVERTIDA NO BANCO LOCAL.");
                             // Notificação URGENTE ao admin aqui! O código foi vendido mas não pode ser lido.
                            throw new \Exception("Erro crítico: Não foi possível descriptografar o código {$stockCodeId} vendido para o pedido {$orderId}. A operação foi revertida.");
                        }

                        // Se chegou aqui, tudo OK com o código
                         $this->db->transCommit(); // Confirma a transação
                        log_message('info', "Pedido {$orderId}: Código ID {$stockCodeId} (Produto {$productId}) alocado e marcado como vendido com sucesso.");

                    } else {
                         $this->db->transRollback(); // Não achou código, desfaz a transação (embora nada tenha sido alterado)
                        log_message('critical', "ALERTA DE ESTOQUE - Pedido {$orderId}: Estoque esgotado ou expirado para produto ID {$productId} (ML: {$mlItemId}). NENHUM CÓDIGO ENTREGUE.");
                        // Implementar notificação ao admin aqui! URGENTE!
                         // Lançar exceção impede envio de mensagem vazia
                         throw new \Exception("Estoque esgotado para produto ID {$productId} no pedido {$orderId}.");
                    }

                // --- Entrega de Link Estático ---
                } elseif ($product->product_type === 'static_link') {
                    if (empty($product->delivery_data)) {
                        log_message('error', "Pedido {$orderId}: Link estático não configurado para o produto {$productId} (ML: {$mlItemId}). VERIFICAR CADASTRO.");
                        // Notificar admin
                        throw new \Exception("Link estático não configurado para o produto {$productId} no pedido {$orderId}");
                    }
                    try {
                        $deliveryContent = service('encrypter')->decrypt($product->delivery_data);
                        log_message('info', "Pedido {$orderId}: Link estático (Produto {$productId}) descriptografado para entrega.");
                    } catch (\Throwable $decryptError) {
                        log_message('critical', "Pedido {$orderId}: Erro CRÍTICO ao DESCRIPTOGRAFAR link estático para produto ID {$productId}: " . $decryptError->getMessage());
                         // Notificação URGENTE ao admin! O link cadastrado está inválido.
                        throw new \Exception("Erro crítico: Não foi possível descriptografar o link estático do produto {$productId} para o pedido {$orderId}.");
                    }

                // --- Tipo de Produto Desconhecido ---
                } else {
                    log_message('error', "Pedido {$orderId}: Tipo de produto desconhecido '{$product->product_type}' para produto ID {$productId}.");
                    throw new \Exception("Tipo de produto desconhecido: {$product->product_type} para produto ID {$productId} no pedido {$orderId}");
                }

                // g. Enviar mensagem via API do ML se houver conteúdo e comprador
                if ($deliveryContent && $buyerId) {
                    // Monta a mensagem
                    $messageText = "Olá! Agradecemos por sua compra do produto '" . esc($product->title ?: $mlItemId) . "'.\n\n";
                    if ($product->product_type === 'unique_code') {
                        $messageText .= "Segue seu código:\n\n{$deliveryContent}\n\n";
                    } else { // static_link
                        $messageText .= "Segue seu link de acesso:\n\n{$deliveryContent}\n\n";
                    }
                    $messageText .= "Qualquer dúvida, estamos à disposição no campo de mensagens da sua compra.";


                    // Prepara o payload JSON
                    $messagePayload = json_encode([
                        'from' => ['user_id' => $currentSellerId],
                        'to'   => ['user_id' => $buyerId],
                        'text' => $messageText,
                    ]);

                    // Define o endpoint da mensagem (usa pack_id se existir, senão order_id)
                    $packId = $orderData->pack_id ?? $orderId;
                    $messageEndpoint = 'messages/packs/' . $packId . '/sellers/' . $currentSellerId;

                    log_message('info', "Pedido {$orderId}: Enviando mensagem para Comprador {$buyerId} no endpoint: {$messageEndpoint}");

                    // Envia a requisição POST
                    $messageResponse = $httpClient->post($messageEndpoint, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $currentAccessToken,
                            'Content-Type'  => 'application/json',
                            'Accept'        => 'application/json',
                        ],
                        'body' => $messagePayload
                    ]);

                    // Verifica a resposta do envio da mensagem
                    if ($messageResponse->getStatusCode() >= 300) {
                         // Se for 401, o token pode ter expirado *exatamente* neste momento
                         if ($messageResponse->getStatusCode() === 401) {
                            log_message('error', "Pedido {$orderId}: Erro 401 ao ENVIAR MENSAGEM. Token pode ter expirado ou inválido.");
                             // Considerar uma retentativa ou notificação mais específica
                         } elseif ($messageResponse->getStatusCode() === 403) {
                              log_message('error', "Pedido {$orderId}: Erro 403 (Forbidden) ao ENVIAR MENSAGEM. Verificar permissões da aplicação ou se o pack/pedido permite mensagens.");
                         } elseif ($messageResponse->getStatusCode() === 404) {
                             log_message('error', "Pedido {$orderId}: Erro 404 ao ENVIAR MENSAGEM. Endpoint '{$messageEndpoint}' não encontrado. Pack ID ou Seller ID podem estar incorretos.");
                         } else {
                             // Log genérico para outros erros
                            log_message('error', "Pedido {$orderId}: Falha ao enviar mensagem. Status: " . $messageResponse->getStatusCode() . " Body: " . $messageResponse->getBody());
                         }
                         // Mesmo com erro no envio da msg, o código/link já foi processado. Logar é importante.
                         // Poderia tentar reenviar depois, mas aumenta a complexidade.
                    } else {
                        log_message('info', "Pedido {$orderId}: Mensagem enviada com sucesso. Resposta API: " . $messageResponse->getBody());
                    }

                } elseif (!$deliveryContent) {
                    // Este caso agora é tratado pelas exceções de estoque/link
                    log_message('error', "Pedido {$orderId}: CHEGOU AO FIM SEM CONTEÚDO DE ENTREGA. Isso não deveria acontecer se as exceções anteriores funcionaram.");
                } elseif (!$buyerId) {
                    log_message('warning', "Pedido {$orderId}: Conteúdo de entrega '{$deliveryContent}' gerado, mas sem ID do comprador. Mensagem não enviada.");
                }

                log_message('info', "Pedido {$orderId}: Processamento do webhook concluído com sucesso.");


            // Captura QUALQUER exceção que ocorrer durante o processo
            } catch (\Throwable $e) {
                // Log detalhado do erro
                log_message('error', "[Webhook Error] Pedido ML ID: " . ($orderId ?? 'N/A') . " | Erro: " . $e->getMessage() . " | Arquivo: " . $e->getFile() . " | Linha: " . $e->getLine());
                // Log do JSON completo pode ser útil para depurar, mas cuidado com dados sensíveis
                // log_message('debug', "[Webhook Error] JSON Recebido: " . json_encode($json));
                // Trace pode ser muito longo, logar se necessário para depuração profunda
                // log_message('debug', "[Webhook Error] Trace: " . $e->getTraceAsString());

                // TODO: Implementar notificação ao administrador sobre a falha.
                // Ex: EmailService::sendAdminNotification("Falha no Webhook ML - Pedido " . ($orderId ?? 'N/A'), $e->getMessage());
            }

        // Se o tópico não for 'orders_v2'
        } else {
            log_message('notice', 'Webhook ML recebido com tópico irrelevante ou ausente: ' . ($json->topic ?? 'N/A') . '. Ignorando.');
        }

        // Sempre retornar status 200 para o Mercado Livre parar de reenviar a notificação
        return $this->response->setStatusCode(200);
    }
} // Fim da classe WebhookController