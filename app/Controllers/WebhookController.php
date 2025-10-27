<?php
namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\StockCodeModel;
use App\Models\MlCredentialsModel; // << Certifique-se que o use está aqui
use CodeIgniter\API\ResponseTrait;
use Config\Services;

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
     * TODO: Implementar a lógica real de refresh token.
     */
    private function getAccessToken(): ?string
    {
        if ($this->accessToken === null) {
            $dbCredentials = $this->getDbCredentials();

            if ($dbCredentials && !empty($dbCredentials->access_token)) {
                // Verificar se o token expirou
                $tokenUpdatedAt = strtotime($dbCredentials->token_updated_at ?? '1970-01-01');
                $expiresIn = $dbCredentials->expires_in ?? 0;
                $now = time();

                // Se (tempo_atualização + duração - margem_segurança) < agora, então expirou
                if (($tokenUpdatedAt + $expiresIn - 300) < $now) { // 300s = 5 minutos de margem
                    log_message('info', "Access Token expirado. Tentando refresh...");
                    // ***** INÍCIO: LÓGICA DE REFRESH TOKEN (IMPLEMENTAR) *****
                    // 1. Pegar $dbCredentials->refresh_token
                    // 2. Fazer chamada POST para a API do ML para obter novo access_token e refresh_token
                    //    - URL: https://api.mercadolibre.com/oauth/token
                    //    - Headers: 'Accept: application/json', 'Content-Type: application/x-www-form-urlencoded'
                    //    - Body (form-urlencoded):
                    //        grant_type=refresh_token
                    //        client_id=SEU_APP_ID
                    //        client_secret=SEU_CLIENT_SECRET
                    //        refresh_token=REFRESH_TOKEN_DO_BANCO
                    // 3. Se sucesso:
                    //    - Extrair novo access_token, refresh_token (pode vir um novo), expires_in da resposta
                    //    - Atualizar no banco: $credentialsModel->updateTokens(novo_access, novo_refresh, novo_expires_in);
                    //    - Atualizar $this->accessToken e $this->credentials
                    // 4. Se falha:
                    //    - Logar erro, lançar exceção ou retornar null
                    log_message('error', "Lógica de Refresh Token NÃO IMPLEMENTADA.");
                    return null; // Retorna null se expirou e não conseguiu refresh
                    // ***** FIM: LÓGICA DE REFRESH TOKEN *****
                } else {
                    // Token ainda válido
                    $this->accessToken = $dbCredentials->access_token;
                }
            } else {
                // Se não tem token no DB, tenta pegar do .env como último recurso (ou lança erro)
                log_message('warning', "Access Token não encontrado no banco de dados. Tentando obter do .env (MERCADOLIBRE_ACCESS_TOKEN).");
                $this->accessToken = getenv('MERCADOLIBRE_ACCESS_TOKEN');
                if (empty($this->accessToken)) {
                    log_message('error', "Access Token não encontrado no DB nem no .env.");
                    return null;
                }
            }
        }
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
            $sellerIdFromApi = $this->fetchAndSaveSellerId(); // Não precisa mais passar parâmetros
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
        $token = $this->getAccessToken();
        if (!$token)
            return null;

        try {
            $httpClient = Services::curlrequest(['baseURI' => 'https://api.mercadolibre.com/', 'timeout' => 5, 'http_errors' => false]);
            $response = $httpClient->get('users/me', ['headers' => ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json']]);

            if ($response->getStatusCode() !== 200) {
                log_message('error', "Falha ao buscar /users/me para salvar Seller ID. Status: " . $response->getStatusCode() . " Body: " . $response->getBody());
                return null;
            }
            $userData = json_decode($response->getBody());
            if (json_last_error() !== JSON_ERROR_NONE || !isset($userData->id)) {
                log_message('error', "Falha ao decodificar JSON ou ID ausente na resposta de /users/me para salvar Seller ID. Body: " . $response->getBody());
                return null;
            }

            $fetchedSellerId = (int) $userData->id;
            log_message('info', "Seller ID obtido da API: " . $fetchedSellerId . ". Atualizando/Salvando no banco de dados...");

            // Salva usando a nova função do model
            $credentialsModel = new MlCredentialsModel();
            if ($credentialsModel->saveSellerId($fetchedSellerId, 'default')) { // Salva para a chave 'default'
                // Atualiza a propriedade local de credenciais se ela já foi carregada
                if ($this->credentials) {
                    $this->credentials->seller_id = $fetchedSellerId;
                }
                return $fetchedSellerId;
            } else {
                log_message('error', "Falha ao salvar Seller ID {$fetchedSellerId} no banco de dados.");
                return null;
            }

        } catch (\Throwable $e) {
            log_message('error', "Exceção ao buscar e salvar Seller ID: " . $e->getMessage());
            return null;
        }
    }


    public function handle()
    {
        // ... (O resto do método handle continua igual, usando $this->getAccessToken() e $this->getSellerId()) ...
        $json = $this->request->getJSON();
        log_message('info', 'Webhook ML recebido: ' . json_encode($json));

        if (isset($json->topic) && $json->topic === 'orders_v2') {
            $orderId = null;
            try {
                $resourceParts = explode('/', $json->resource);
                $orderId = end($resourceParts);

                if (empty($orderId)) {
                    throw new \Exception("Não foi possível extrair o ID do pedido do resource: " . $json->resource);
                }

                // *** Obter Access Token e Seller ID (AGORA USA O BANCO PRIMEIRO) ***
                $currentAccessToken = $this->getAccessToken();
                $currentSellerId = $this->getSellerId(); // Chama a função que busca no DB/API

                if (!$currentAccessToken || !$currentSellerId) {
                    throw new \Exception("Não foi possível obter Access Token ou Seller ID. Verifique os logs, a configuração do .env e o banco de dados.");
                }
                // ***************************************

                // b. Fazer chamada GET à API do Mercado Livre para buscar detalhes do pedido.
                $httpClient = Services::curlrequest([
                    'baseURI' => 'https://api.mercadolibre.com/',
                    'timeout' => 10,
                    'http_errors' => false,
                ]);

                log_message('info', "Buscando detalhes do pedido ML ID: {$orderId}");
                $response = $httpClient->get('orders/' . $orderId, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $currentAccessToken, // Usa o token obtido
                        'Accept' => 'application/json',
                    ]
                ]);

                if ($response->getStatusCode() !== 200) {
                    // Se o erro for 401 (Unauthorized), pode ser token inválido/expirado
                    if ($response->getStatusCode() === 401) {
                        log_message('error', "Erro 401 ao buscar pedido {$orderId}. Token pode estar inválido ou expirado. Tentar refresh na próxima vez.");
                        // Aqui você pode invalidar o token no DB ou apenas logar
                        // Ex: $credModel = new MlCredentialsModel(); $credModel->updateTokens('', '', null); // Limpa token inválido
                    }
                    throw new \Exception("Falha ao buscar detalhes do pedido ML {$orderId}. Status: " . $response->getStatusCode() . " Body: " . $response->getBody());
                }

                $orderData = json_decode($response->getBody());

                // ... (resto do código como estava antes: verificação de pagamento, busca de produto,
                //      lógica de entrega, envio de mensagem usando $currentSellerId e $currentAccessToken) ...
                // c. Verificar se o status do pagamento é 'approved'.
                if (!isset($orderData->payments[0]->status) || $orderData->payments[0]->status !== 'approved') {
                    log_message('info', "Pedido {$orderId} não está com pagamento aprovado. Status Pagamento: " . ($orderData->payments[0]->status ?? 'N/A') . ", Status Pedido: " . ($orderData->status ?? 'N/A'));
                    return $this->response->setStatusCode(200);
                }
                log_message('info', "Pedido {$orderId} com pagamento APROVADO. Iniciando processamento de entrega.");

                // d. Pegar o ml_item_id do item vendido e ID do comprador
                if (!isset($orderData->order_items[0]->item->id)) {
                    throw new \Exception("Não foi possível encontrar o ID do item no pedido ML: " . $orderId);
                }
                $mlItemId = $orderData->order_items[0]->item->id;
                $buyerId = $orderData->buyer->id ?? null;

                if (empty($buyerId)) {
                    log_message('warning', "Não foi possível obter o ID do comprador para o pedido {$orderId}. A mensagem não será enviada.");
                }

                // e. Buscar produto no banco de dados local
                $productModel = new ProductModel();
                $product = $productModel->where('ml_item_id', $mlItemId)->first();

                if (!$product) {
                    throw new \Exception("Produto com ML Item ID {$mlItemId} não encontrado no banco de dados local para o pedido {$orderId}.");
                }
                log_message('info', "Produto ID {$product->id} (Tipo: {$product->product_type}) encontrado para o ML Item ID {$mlItemId}.");

                $productId = $product->id;
                $deliveryContent = null;
                $stockCodeId = null;

                // f. Lógica de entrega baseada no tipo de produto
                if ($product->product_type === 'unique_code') {
                    $stockCodeModel = new StockCodeModel();

                    $availableCode = $stockCodeModel
                        ->where('product_id', $productId)
                        ->where('is_sold', false)
                        ->groupStart()
                        ->where('expires_at IS NULL')
                        ->orWhere('expires_at >=', date('Y-m-d'))
                        ->groupEnd()
                        ->orderBy('created_at', 'ASC')
                        ->first();

                    if ($availableCode) {
                        $stockCodeId = $availableCode->id;
                        $updateData = [
                            'is_sold' => true,
                            'sold_at' => date('Y-m-d H:i:s'),
                            'ml_order_id' => $orderId,
                            'ml_buyer_id' => $buyerId,
                        ];
                        if (!$stockCodeModel->update($stockCodeId, $updateData)) {
                            throw new \Exception("Falha ao marcar o código {$stockCodeId} como vendido para o pedido {$orderId}.");
                        }

                        try {
                            $deliveryContent = service('encrypter')->decrypt($availableCode->code);
                        } catch (\Throwable $decryptError) {
                            log_message('error', "Erro ao DESCRIPTOGRAFAR código ID {$stockCodeId} para pedido {$orderId}: " . $decryptError->getMessage());
                            throw new \Exception("Erro crítico: Não foi possível descriptografar o código {$stockCodeId} vendido para o pedido {$orderId}.");
                        }
                        log_message('info', "Código {$stockCodeId} (Produto {$productId}) marcado como vendido e separado para entrega no pedido {$orderId}.");

                    } else {
                        log_message('error', "ALERTA DE ESTOQUE: Estoque esgotado ou expirado para product_id: {$productId} (ML: {$mlItemId}) no pedido {$orderId}");
                        // Implementar notificação ao admin aqui!
                    }

                } elseif ($product->product_type === 'static_link') {
                    if (empty($product->delivery_data)) {
                        throw new \Exception("Link estático não configurado para o produto {$productId} (ML: {$mlItemId}) no pedido {$orderId}");
                    }
                    try {
                        $deliveryContent = service('encrypter')->decrypt($product->delivery_data);
                    } catch (\Throwable $decryptError) {
                        log_message('error', "Erro ao DESCRIPTOGRAFAR link estático para produto ID {$productId}, pedido {$orderId}: " . $decryptError->getMessage());
                        throw new \Exception("Erro crítico: Não foi possível descriptografar o link estático do produto {$productId} para o pedido {$orderId}.");
                    }
                    log_message('info', "Link estático (Produto {$productId}) separado para entrega no pedido {$orderId}.");
                } else {
                    throw new \Exception("Tipo de produto desconhecido: {$product->product_type} para produto ID {$productId} no pedido {$orderId}");
                }

                // g. Fazer chamada POST à API do Mercado Livre para enviar mensagem.
                if ($deliveryContent && $buyerId) {
                    $messageText = "Olá! Obrigado pela sua compra do produto '" . ($product->title ?? $mlItemId) . "'.\n\nSegue seu código/link:\n\n{$deliveryContent}\n\nQualquer dúvida, estamos à disposição!";

                    $messagePayload = json_encode([
                        'from' => ['user_id' => $currentSellerId], // Usa o ID obtido
                        'to' => ['user_id' => $buyerId],
                        'text' => $messageText,
                    ]);

                    $packId = $orderData->pack_id ?? $orderId;
                    $messageEndpoint = 'messages/packs/' . $packId . '/sellers/' . $currentSellerId; // Usa o ID obtido

                    // Reutiliza o $httpClient ou cria um novo se necessário
                    log_message('info', "Enviando mensagem para Pedido {$orderId} / Comprador {$buyerId} no endpoint: {$messageEndpoint}");
                    $messageResponse = $httpClient->post($messageEndpoint, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $currentAccessToken, // Usa o token obtido
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                        ],
                        'body' => $messagePayload
                    ]);

                    if ($messageResponse->getStatusCode() >= 300) {
                        if ($messageResponse->getStatusCode() === 401) {
                            log_message('error', "Erro 401 ao ENVIAR MENSAGEM para pedido {$orderId}. Token pode estar inválido ou expirado.");
                            // Invalidar token no DB?
                        }
                        log_message('error', "Falha ao enviar mensagem para pedido {$orderId}. Status: " . $messageResponse->getStatusCode() . " Body: " . $messageResponse->getBody());
                    } else {
                        log_message('info', "Mensagem enviada com sucesso para pedido {$orderId}. Resposta: " . $messageResponse->getBody());
                    }

                } elseif (!$deliveryContent) {
                    log_message('warning', "Nenhum conteúdo de entrega foi gerado ou definido para o pedido {$orderId}. Mensagem não enviada.");
                } elseif (!$buyerId) {
                    log_message('warning', "ID do comprador ausente para o pedido {$orderId}. Mensagem não enviada.");
                }

                log_message('info', "Processamento do webhook para pedido {$orderId} concluído.");


            } catch (\Throwable $e) {
                log_message('error', "[WebhookController::handle] ERRO AO PROCESSAR PEDIDO: " . $e->getMessage() . " | Pedido ML ID (se disponível): " . ($orderId ?? 'N/A') . " | Trace: " . $e->getTraceAsString() . " | JSON Recebido: " . json_encode($json));
                // Notificação ao admin
            }
        } else {
            log_message('warning', 'Webhook ML recebido com tópico desconhecido ou ausente: ' . ($json->topic ?? 'N/A'));
        }
        return $this->response->setStatusCode(200);
    }
} // Fim da classe