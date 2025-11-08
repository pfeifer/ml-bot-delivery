<?php
namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\StockCodeModel;
use App\Models\MlCredentialsModel;
use App\Models\MessageTemplateModel;
use App\Models\MlOrderModel; // <-- NOVO MODEL
use CodeIgniter\API\ResponseTrait;
use Config\Services;
use App\Libraries\MercadoLivreAuth;
use CodeIgniter\Database\RawSql; // <-- IMPORTAR

class WebhookController extends BaseController
{
    use ResponseTrait;

    private ?int $sellerId = null;
    private ?string $accessToken = null;
    private ?object $credentials = null;
    private $templateModel;
    private $orderModel; // <-- NOVA PROPRIEDADE
    private $productModel; // <-- NOVA PROPRIEDADE

    public function __construct()
    {
        $this->templateModel = new MessageTemplateModel();
        $this->orderModel = new MlOrderModel(); // <-- INSTANCIAR
        $this->productModel = new ProductModel(); // <-- INSTANCIAR
    }

    /**
     * Obtém as credenciais do banco de dados (uma única vez por requisição).
     */
    private function getDbCredentials(): ?object
    {
        if ($this->credentials === null) {
            $credentialsModel = new MlCredentialsModel();
            $this->credentials = $credentialsModel->getCredentials('default');

            if ($this->credentials === null) {
                log_message('warning', "Nenhum registro encontrado em ml_credentials para key_name='default'. Tentando inserir registro inicial.");
                $insertedId = $credentialsModel->insert(['key_name' => 'default'], true);
                if ($insertedId) {
                    $this->credentials = $credentialsModel->find($insertedId);
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
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $dbCredentials = $this->getDbCredentials();

        if ($dbCredentials && !empty($dbCredentials->access_token)) {
            $tokenUpdatedAt = strtotime($dbCredentials->token_updated_at ?? '1970-01-01');
            $expiresIn = $dbCredentials->expires_in ?? 0;
            $now = time();

            if (($tokenUpdatedAt + $expiresIn - 300) < $now) { // 5 min margem
                log_message('info', "Access Token expirado. Tentando refresh...");

                if (MercadoLivreAuth::refreshToken()) {
                    log_message('info', "Refresh Token bem-sucedido. Recarregando credenciais.");
                    $this->credentials = null;
                    $dbCredentials = $this->getDbCredentials();
                    if ($dbCredentials && !empty($dbCredentials->access_token)) {
                        $this->accessToken = $dbCredentials->access_token;
                        log_message('debug', 'Novo Access Token carregado.');
                    } else {
                        log_message('error', "Falha ao carregar o novo Access Token do banco após o refresh.");
                        $this->accessToken = null;
                    }
                } else {
                    log_message('error', "Falha ao executar MercadoLivreAuth::refreshToken().");
                    $this->accessToken = null;
                }
            } else {
                $this->accessToken = $dbCredentials->access_token;
                log_message('debug', 'Access Token válido obtido do banco.');
            }
        } else {
            if ($dbCredentials && empty($dbCredentials->refresh_token)) {
                log_message('error', "Access Token não encontrado no DB e SEM Refresh Token.");
            } else {
                log_message('error', "Access Token não encontrado e/ou falha ao renovar.");
            }
            $this->accessToken = null;
        }
        return $this->accessToken;
    }
    /**
     * Obtém o Seller ID.
     */
    private function getSellerId(): ?int
    {
        if ($this->sellerId !== null) {
            return $this->sellerId;
        }
        $dbCredentials = $this->getDbCredentials();
        if ($dbCredentials && !empty($dbCredentials->seller_id)) {
            $this->sellerId = (int) $dbCredentials->seller_id;
            log_message('debug', "Seller ID obtido do DB: " . $this->sellerId);
            return $this->sellerId;
        } else {
            log_message('warning', "Seller ID não encontrado no DB. Buscando via API /users/me...");
            $sellerIdFromApi = $this->fetchAndSaveSellerId();
            if ($sellerIdFromApi) {
                $this->sellerId = $sellerIdFromApi;
                return $this->sellerId;
            } else {
                log_message('error', "Não foi possível obter o Seller ID.");
                return null;
            }
        }
    }
    /**
     * Auxiliar para buscar Seller ID da API e salvar no DB.
     */
    private function fetchAndSaveSellerId(): ?int
    {
        $token = $this->getAccessToken();
        if (!$token) {
            log_message('error', "fetchAndSaveSellerId: Não foi possível obter Access Token.");
            return null;
        }
        try {
            $httpClient = Services::curlrequest(['baseURI' => 'https://api.mercadolibre.com/', 'timeout' => 10, 'http_errors' => false]);
            $response = $httpClient->get('users/me', ['headers' => ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json']]);

            if ($response->getStatusCode() !== 200) {
                log_message('error', "fetchAndSaveSellerId: Falha ao buscar /users/me. Status: " . $response->getStatusCode() . " Body: " . $response->getBody());
                return null;
            }
            $userData = json_decode($response->getBody());
            if (json_last_error() !== JSON_ERROR_NONE || !isset($userData->id)) {
                log_message('error', "fetchAndSaveSellerId: Falha ao decodificar JSON ou ID ausente /users/me.");
                return null;
            }
            $fetchedSellerId = (int) $userData->id;
            log_message('info', "Seller ID obtido da API: " . $fetchedSellerId . ". Salvando no DB...");

            $credentialsModel = new MlCredentialsModel();
            if ($credentialsModel->saveSellerId($fetchedSellerId, 'default')) {
                log_message('info', "Seller ID {$fetchedSellerId} salvo no DB.");
                if ($this->credentials) {
                    $this->credentials->seller_id = $fetchedSellerId;
                }
                return $fetchedSellerId;
            } else {
                log_message('error', "fetchAndSaveSellerId: Falha ao salvar Seller ID {$fetchedSellerId} no DB.");
                return null;
            }
        } catch (\Throwable $e) {
            log_message('error', "fetchAndSaveSellerId: Exceção: " . $e->getMessage());
            return null;
        }
    }

    /**
     * MÉTODO PRINCIPAL DO WEBHOOK
     */
    public function handle()
    {
        $json = $this->request->getJSON();
        log_message('info', 'Webhook ML recebido: Tópico=' . ($json->topic ?? 'N/A') . ', Resource=' . ($json->resource ?? 'N/A'));

        if (isset($json->topic) && $json->topic === 'orders_v2') {
            $orderId = null;
            try {
                // 1. Obter ID do Pedido
                $resourceParts = explode('/', $json->resource ?? '');
                $orderId = end($resourceParts);

                if (empty($orderId) || !is_numeric($orderId)) {
                    throw new \Exception("ID de pedido inválido no resource: " . ($json->resource ?? 'N/A'));
                }
                log_message('info', "Pedido ID {$orderId}: Iniciando processamento...");
                
                // 2. Autenticação
                $currentAccessToken = $this->getAccessToken();
                $currentSellerId = $this->getSellerId();
                if (!$currentAccessToken || !$currentSellerId) {
                    throw new \Exception("Pedido {$orderId}: Não foi possível obter Access Token ou Seller ID.");
                }

                // 3. Buscar detalhes do Pedido na API
                $httpClient = Services::curlrequest([
                    'baseURI' => 'https://api.mercadolibre.com/',
                    'timeout' => 15,
                    'http_errors' => false,
                ]);
                $response = $httpClient->get('orders/' . $orderId, [
                    'headers' => ['Authorization' => 'Bearer ' . $currentAccessToken, 'Accept' => 'application/json']
                ]);

                if ($response->getStatusCode() !== 200) {
                    if ($response->getStatusCode() === 404) {
                        log_message('warning', "Pedido {$orderId}: Erro 404. Pedido não encontrado.");
                        return $this->response->setStatusCode(200); // OK para o ML
                    }
                    throw new \Exception("Pedido {$orderId}: Falha ao buscar detalhes na API. Status: " . $response->getStatusCode() . " Body: " . $response->getBody());
                }
                $orderData = json_decode($response->getBody());
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception("Pedido {$orderId}: Falha ao decodificar JSON da API de pedidos.");
                }

                // 4. Extrair dados e Salvar/Atualizar no DB
                $mlItemId = $orderData->order_items[0]->item->id ?? null;
                $product = $this->productModel->where('ml_item_id', $mlItemId)->first();
                
                $orderRecord = [
                    'ml_order_id'   => $orderData->id,
                    'ml_item_id'    => $mlItemId,
                    'product_id'    => $product->id ?? null,
                    'ml_buyer_id'   => $orderData->buyer->id ?? null,
                    'status'        => $orderData->status ?? 'unknown',
                    'total_amount'  => $orderData->total_amount ?? 0.00,
                    'currency_id'   => $orderData->currency_id ?? 'N/A',
                    'date_created'  => date('Y-m-d H:i:s', strtotime($orderData->date_created)),
                    'date_closed'   => $orderData->date_closed ? date('Y-m-d H:i:s', strtotime($orderData->date_closed)) : null,
                    'updated_at'    => new RawSql('CURRENT_TIMESTAMP') // Força atualização
                ];
                
                // Usar 'save' que funciona como 'upsert' (update or insert)
                // Precisamos verificar se já existe
                $existingOrder = $this->orderModel->where('ml_order_id', $orderData->id)->first();
                if($existingOrder) {
                    $this->orderModel->update($existingOrder->id, $orderRecord);
                } else {
                    $this->orderModel->insert($orderRecord);
                }
                
                log_message('info', "Pedido {$orderId}: Salvo/Atualizado no DB com status '{$orderRecord['status']}'.");

                // 5. Verificar se o Pagamento está Aprovado (Status 'paid')
                if ($orderRecord['status'] !== 'paid') {
                    log_message('info', "Pedido {$orderId}: Status não é 'paid'. Ignorando entrega por enquanto.");
                    return $this->response->setStatusCode(200);
                }

                // 6. Verificar se JÁ FOI ENTREGUE
                // Recarrega $existingOrder para garantir que temos o status de entrega
                $existingOrder = $this->orderModel->where('ml_order_id', $orderId)->first();
                if ($existingOrder && $existingOrder->delivery_status === 'delivered') {
                     log_message('info', "Pedido {$orderId}: Já consta como 'delivered' em nosso DB. Ignorando nova tentativa.");
                     return $this->response->setStatusCode(200);
                }

                log_message('info', "Pedido {$orderId}: Status 'paid' e entrega pendente. Iniciando processo de entrega.");

                // ==========================================================
                // INÍCIO DA LÓGICA DE ENTREGA (adaptada do código anterior)
                // ==========================================================

                if (!$product) {
                    log_message('error', "Pedido {$orderId}: Produto com ML Item ID {$mlItemId} não encontrado no DB local.");
                    $this->updateOrderDeliveryStatus($orderId, 'failed');
                    throw new \Exception("Produto {$mlItemId} não encontrado no DB local (pedido {$orderId}).");
                }
                
                $productId = $product->id;
                $buyerId = $orderRecord['ml_buyer_id'];
                $deliveryContent = null;
                $stockCodeId = null;

                if ($product->product_type === 'code') {
                    $stockCodeModel = new StockCodeModel();
                    $this->db = \Config\Database::connect();
                    $this->db->transStart();
                    
                    $availableCode = $stockCodeModel
                        ->where('product_id', $productId)->where('is_sold', false)
                        ->groupStart()->where('expires_at IS NULL')->orWhere('expires_at >=', date('Y-m-d'))->groupEnd()
                        ->orderBy('created_at', 'ASC')->lockForUpdate()->first();
                        
                    if ($availableCode) {
                        $stockCodeId = $availableCode->id;
                        $updateData = [
                            'is_sold' => true,
                            'sold_at' => date('Y-m-d H:i:s'),
                            'ml_order_id' => $orderId,
                            'ml_buyer_id' => $buyerId,
                        ];
                        $updated = $stockCodeModel->update($stockCodeId, $updateData);
                        if (!$updated) {
                            $this->db->transRollback();
                            $this->updateOrderDeliveryStatus($orderId, 'failed');
                            throw new \Exception("Pedido {$orderId}: Falha ao marcar código {$stockCodeId} como vendido.");
                        }
                        
                        try {
                            $deliveryContent = service('encrypter')->decrypt($availableCode->code);
                        } catch (\Throwable $decryptError) {
                            $this->db->transRollback();
                            $this->updateOrderDeliveryStatus($orderId, 'failed');
                            log_message('critical', "Pedido {$orderId}: Erro CRÍTICO ao DESCRIPTOGRAFAR código ID {$stockCodeId} (vendido): " . $decryptError->getMessage() . ". VENDA REVERTIDA.");
                            throw new \Exception("Erro crítico: Não foi possível descriptografar o código {$stockCodeId} (pedido {$orderId}). Operação revertida.");
                        }
                        $this->db->transCommit();
                        log_message('info', "Pedido {$orderId}: Código ID {$stockCodeId} alocado e vendido.");
                    
                    } else { // SEM ESTOQUE
                        $this->db->transRollback();
                        $this->updateOrderDeliveryStatus($orderId, 'out_of_stock');
                        log_message('critical', "ALERTA DE ESTOQUE - Pedido {$orderId}: Estoque esgotado/expirado para produto ID {$productId} (ML: {$mlItemId}).");
                        throw new \Exception("Estoque esgotado para produto ID {$productId} (pedido {$orderId}).");
                    }
                } elseif ($product->product_type === 'link') {
                    if (empty($product->delivery_data)) {
                        $this->updateOrderDeliveryStatus($orderId, 'failed');
                        log_message('error', "Pedido {$orderId}: Link estático não configurado para produto {$productId}.");
                        throw new \Exception("Link estático não configurado (pedido {$orderId})");
                    }
                    try {
                        $deliveryContent = service('encrypter')->decrypt($product->delivery_data);
                        log_message('info', "Pedido {$orderId}: Link estático (Produto {$productId}) descriptografado.");
                    } catch (\Throwable $decryptError) {
                        $this->updateOrderDeliveryStatus($orderId, 'failed');
                        log_message('critical', "Pedido {$orderId}: Erro CRÍTICO ao DESCRIPTOGRAFAR link estático produto ID {$productId}: " . $decryptError->getMessage());
                        throw new \Exception("Erro crítico: descriptografar link estático (pedido {$orderId}).");
                    }
                } else {
                    $this->updateOrderDeliveryStatus($orderId, 'failed');
                    log_message('error', "Pedido {$orderId}: Tipo de produto desconhecido '{$product->product_type}' (ID {$productId}).");
                    throw new \Exception("Tipo de produto desconhecido (pedido {$orderId})");
                }

                // 7. Enviar Mensagem
                if ($deliveryContent && $buyerId) {
                    $messageText = $this->prepareMessage($product, $deliveryContent, $orderId);
                    
                    $messagePayload = json_encode([
                        'from' => ['user_id' => $currentSellerId],
                        'to' => ['user_id' => $buyerId],
                        'text' => $messageText,
                    ]);

                    $packId = $orderData->pack_id ?? $orderId;
                    $messageEndpoint = 'messages/packs/' . $packId . '/sellers/' . $currentSellerId;
                    log_message('info', "Pedido {$orderId}: Enviando mensagem para Comprador {$buyerId} via {$messageEndpoint}");

                    $messageResponse = $httpClient->post($messageEndpoint, [
                        'headers' => ['Authorization' => 'Bearer ' . $currentAccessToken, 'Content-Type' => 'application/json', 'Accept' => 'application/json'],
                        'body' => $messagePayload
                    ]);

                    if ($messageResponse->getStatusCode() >= 300) {
                        $this->updateOrderDeliveryStatus($orderId, 'failed'); // Falha na entrega
                        log_message('error', "Pedido {$orderId}: Falha ao enviar mensagem. Status: {$messageResponse->getStatusCode()} Body: {$messageResponse->getBody()}");
                    } else {
                        log_message('info', "Pedido {$orderId}: Mensagem enviada com sucesso.");
                        $this->updateOrderDeliveryStatus($orderId, 'delivered'); // SUCESSO!
                    }

                    // 8. Tentar Marcar como Entregue (ME1)
                    $this->markAsDelivered($httpClient, $orderId, $orderData, $currentAccessToken);

                } elseif (!$deliveryContent) {
                    log_message('error', "Pedido {$orderId}: Sem conteúdo de entrega ao final.");
                } elseif (!$buyerId) {
                    log_message('warning', "Pedido {$orderId}: Conteúdo gerado, mas sem ID do comprador. Mensagem não enviada.");
                }
                
                log_message('info', "Pedido {$orderId}: Processamento do webhook concluído.");

            } catch (\Throwable $e) {
                log_message('error', "[Webhook Error] Pedido ML ID: " . ($orderId ?? 'N/A') . " | Erro: " . $e->getMessage() . " | Arquivo: " . $e->getFile() . " | Linha: " . $e->getLine());
                
                // Tenta salvar o erro no pedido, se tivermos o ID
                if($orderId) {
                    $this->updateOrderDeliveryStatus($orderId, 'failed');
                }
            }
        } else {
            log_message('notice', 'Webhook ML recebido com tópico irrelevante ou ausente: ' . ($json->topic ?? 'N/A') . '. Ignorando.');
        }
        
        // Sempre retorna 200 OK para o Mercado Livre
        return $this->response->setStatusCode(200);
    }
    
    /**
     * Atualiza o status da *nossa* entrega digital no DB
     */
    private function updateOrderDeliveryStatus(int $orderId, string $status)
    {
        try {
            $this->orderModel
                ->where('ml_order_id', $orderId)
                ->set('delivery_status', $status)
                ->set('updated_at', new RawSql('CURRENT_TIMESTAMP'))
                ->update();
        } catch (\Throwable $e) {
             log_message('error', "Falha ao atualizar delivery_status para '{$status}' no pedido {$orderId}: " . $e->getMessage());
        }
    }

    /**
     * Prepara a mensagem de texto usando o template (lógica movida)
     */
    private function prepareMessage(object $product, string $deliveryContent, int $orderId): string
    {
        $templateContent = null;
        $templateToUse = null;

        if (!empty($product->message_template_id)) {
            $templateToUse = $this->templateModel->find($product->message_template_id);
            if ($templateToUse) {
                log_message('info', "Pedido {$orderId}: Usando template vinculado ID {$product->message_template_id} ('{$templateToUse->name}').");
                $templateContent = $templateToUse->content;
            } else {
                log_message('warning', "Pedido {$orderId}: Template vinculado ID {$product->message_template_id} não encontrado. Buscando template padrão (ID 1).");
            }
        }
        
        if ($templateContent === null) {
            $templateToUse = $this->templateModel->find(1); // ID 1 é o Padrão
            if ($templateToUse) {
                $templateContent = $templateToUse->content;
                log_message('info', "Pedido {$orderId}: Usando Template Padrão (ID 1) do banco.");
            } else {
                log_message('critical', "Pedido {$orderId}: ERRO CRÍTICO. Template Padrão (ID: 1) não encontrado no banco. O Seeder (DefaultMessageTemplateSeeder) precisa ser executado.");
                // Fallback para uma mensagem hardcoded se nem o template 1 existir
                $templateContent = "Obrigado por sua compra! Segue seu produto: {delivery_content}";
            }
        }
        
        return str_replace('{delivery_content}', $deliveryContent, $templateContent);
    }

    /**
     * Tenta marcar o pedido como 'delivered' (ME1) (lógica movida)
     */
    private function markAsDelivered($httpClient, int $orderId, object $orderData, string $accessToken)
    {
        log_message('info', "Pedido {$orderId}: Tentando marcar o pedido como 'entregue' (ME1)...");
        try {
            $shipmentId = $orderData->shipping->id ?? null;
            
            // Se o ID do shipping não veio no pedido principal, busca em /shipments
            if (empty($shipmentId)) {
                $shipmentResponse = $httpClient->get("orders/{$orderId}/shipments", [
                    'headers' => ['Authorization' => 'Bearer ' . $accessToken, 'Accept' => 'application/json']
                ]);
                if ($shipmentResponse->getStatusCode() !== 200) {
                     log_message('warning', "Pedido {$orderId}: Não foi possível obter o shipment_id (Status {$shipmentResponse->getStatusCode()}). Pulando etapa 'entregue'. Body: " . $shipmentResponse->getBody());
                     return;
                }
                $shipmentData = json_decode($shipmentResponse->getBody());
                $shipmentId = $shipmentData->id ?? null;
                $shippingMode = $shipmentData->mode ?? '';
                $currentStatus = $shipmentData->status ?? '';
            } else {
                 // Dados já vieram no objeto $orderData (precisaria buscar /shipments/$shipmentId para ter mode e status)
                 // Vamos simplificar e buscar /shipments/$shipmentId de qualquer forma
                 $shipmentResponse = $httpClient->get("shipments/{$shipmentId}", [
                    'headers' => ['Authorization' => 'Bearer ' . $accessToken, 'Accept' => 'application/json']
                 ]);
                 if ($shipmentResponse->getStatusCode() !== 200) {
                     log_message('warning', "Pedido {$orderId}: Não foi possível obter detalhes do shipment_id {$shipmentId}. Pulando etapa 'entregue'. Body: " . $shipmentResponse->getBody());
                     return;
                 }
                 $shipmentData = json_decode($shipmentResponse->getBody());
                 $shippingMode = $shipmentData->mode ?? '';
                 $currentStatus = $shipmentData->status ?? '';
            }


            if ($shipmentId && $shippingMode === 'me1' && $currentStatus !== 'delivered') {
                log_message('info', "Pedido {$orderId}: Shipment ID {$shipmentId} (ME1) encontrado. Enviando status 'delivered'.");
                
                $deliveryPayload = json_encode([
                    'payload' => [
                        'comment' => 'Pedido entregue (Entrega digital automática)',
                        'date' => date('c') // Formato ISO 8601
                    ],
                    'status' => 'delivered',
                    'substatus' => null
                ]);
                
                $deliveryResponse = $httpClient->post("shipments/{$shipmentId}/seller_notifications", [
                    'headers' => ['Authorization' => 'Bearer ' . $accessToken, 'Content-Type' => 'application/json', 'Accept' => 'application/json'],
                    'body' => $deliveryPayload
                ]);

                if ($deliveryResponse->getStatusCode() >= 300) {
                    log_message('error', "Pedido {$orderId}: Falha ao marcar como 'entregue'. Status: {$deliveryResponse->getStatusCode()} Body: {$deliveryResponse->getBody()}");
                } else {
                    log_message('info', "Pedido {$orderId}: Pedido marcado como 'entregue' com sucesso.");
                }
            } elseif ($shippingMode !== 'me1') {
                log_message('info', "Pedido {$orderId}: Pedido não é 'me1' (Modo: {$shippingMode}). Não é necessário marcar como 'entregue'.");
            } elseif ($currentStatus === 'delivered') {
                log_message('info', "Pedido {$orderId}: Pedido já estava marcado como 'entregue'.");
            } else {
                log_message('warning', "Pedido {$orderId}: Resposta de 'shipments' não continha um ID válido. Pulando etapa 'entregue'.");
            }
        } catch (\Throwable $e) {
            log_message('error', "Pedido {$orderId}: Exceção ao tentar marcar como 'entregue': " . $e->getMessage());
        }
    }
}