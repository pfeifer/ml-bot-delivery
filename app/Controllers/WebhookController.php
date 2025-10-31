<?php
namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\StockCodeModel;
use App\Models\MlCredentialsModel;
use App\Models\MessageTemplateModel; // << Import
use CodeIgniter\API\ResponseTrait;
use Config\Services;
use App\Libraries\MercadoLivreAuth;

class WebhookController extends BaseController
{
    use ResponseTrait;

    private ?int $sellerId = null;
    private ?string $accessToken = null;
    private ?object $credentials = null;
    private $templateModel; // << Propriedade

    public function __construct() // << Construtor
    {
        $this->templateModel = new MessageTemplateModel();
    }


    // ... (getDbCredentials, getAccessToken, getSellerId, fetchAndSaveSellerId permanecem EXATAMENTE IGUAIS) ...

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

    // --- FIM DOS MÉTODOS DE AUTENTICAÇÃO ---


    public function handle()
    {
        $json = $this->request->getJSON();
        log_message('info', 'Webhook ML recebido: Tópico=' . ($json->topic ?? 'N/A') . ', Resource=' . ($json->resource ?? 'N/A'));

        if (isset($json->topic) && $json->topic === 'orders_v2') {
            $orderId = null;
            try {
                // ... (Toda a lógica de verificação do pedido, pagamento e busca de estoque permanece a mesma) ...
                $resourceParts = explode('/', $json->resource ?? '');
                $orderId = end($resourceParts);

                if (empty($orderId) || !is_numeric($orderId)) {
                    throw new \Exception("ID de pedido inválido no resource: " . ($json->resource ?? 'N/A'));
                }
                log_message('info', "Pedido ID {$orderId}: Iniciando processamento...");
                $currentAccessToken = $this->getAccessToken();
                $currentSellerId = $this->getSellerId();
                if (!$currentAccessToken || !$currentSellerId) {
                    throw new \Exception("Pedido {$orderId}: Não foi possível obter Access Token ou Seller ID.");
                }
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
                        return $this->response->setStatusCode(200);
                    }
                    throw new \Exception("Pedido {$orderId}: Falha ao buscar detalhes na API. Status: " . $response->getStatusCode() . " Body: " . $response->getBody());
                }
                $orderData = json_decode($response->getBody());
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception("Pedido {$orderId}: Falha ao decodificar JSON da API de pedidos.");
                }
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
                    log_message('info', "Pedido {$orderId}: Pagamento não aprovado. Ignorando.");
                    return $this->response->setStatusCode(200);
                }
                log_message('info', "Pedido {$orderId}: Pagamento APROVADO.");
                if (!isset($orderData->order_items[0]->item->id)) {
                    log_message('error', "Pedido {$orderId}: Não foi possível encontrar order_items[0]->item->id.");
                    throw new \Exception("Pedido {$orderId}: ID do item não encontrado.");
                }
                $mlItemId = $orderData->order_items[0]->item->id;
                $buyerId = $orderData->buyer->id ?? null;
                if (empty($buyerId)) {
                    log_message('warning', "Pedido {$orderId}: ID do comprador não encontrado.");
                } else {
                    log_message('info', "Pedido {$orderId}: Comprador ID {$buyerId}, Item ML ID {$mlItemId}.");
                }
                $productModel = new ProductModel();
                $product = $productModel->where('ml_item_id', $mlItemId)->first();
                if (!$product) {
                    log_message('error', "Pedido {$orderId}: Produto com ML Item ID {$mlItemId} não encontrado no DB local.");
                    throw new \Exception("Produto {$mlItemId} não encontrado no DB local (pedido {$orderId}).");
                }
                log_message('info', "Pedido {$orderId}: Produto local ID {$product->id} (Tipo: {$product->product_type}) encontrado.");
                $productId = $product->id;
                $deliveryContent = null;
                $stockCodeId = null;
                if ($product->product_type === 'unique_code') {
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
                            throw new \Exception("Pedido {$orderId}: Falha ao marcar código {$stockCodeId} como vendido.");
                        }
                        try {
                            $deliveryContent = service('encrypter')->decrypt($availableCode->code);
                        } catch (\Throwable $decryptError) {
                            $this->db->transRollback();
                            log_message('critical', "Pedido {$orderId}: Erro CRÍTICO ao DESCRIPTOGRAFAR código ID {$stockCodeId} (vendido): " . $decryptError->getMessage() . ". VENDA REVERTIDA.");
                            throw new \Exception("Erro crítico: Não foi possível descriptografar o código {$stockCodeId} (pedido {$orderId}). Operação revertida.");
                        }
                        $this->db->transCommit();
                        log_message('info', "Pedido {$orderId}: Código ID {$stockCodeId} alocado e vendido.");
                    } else {
                        $this->db->transRollback();
                        log_message('critical', "ALERTA DE ESTOQUE - Pedido {$orderId}: Estoque esgotado/expirado para produto ID {$productId} (ML: {$mlItemId}).");
                        throw new \Exception("Estoque esgotado para produto ID {$productId} (pedido {$orderId}).");
                    }
                } elseif ($product->product_type === 'static_link') {
                    if (empty($product->delivery_data)) {
                        log_message('error', "Pedido {$orderId}: Link estático não configurado para produto {$productId}.");
                        throw new \Exception("Link estático não configurado (pedido {$orderId})");
                    }
                    try {
                        $deliveryContent = service('encrypter')->decrypt($product->delivery_data);
                        log_message('info', "Pedido {$orderId}: Link estático (Produto {$productId}) descriptografado.");
                    } catch (\Throwable $decryptError) {
                        log_message('critical', "Pedido {$orderId}: Erro CRÍTICO ao DESCRIPTOGRAFAR link estático produto ID {$productId}: " . $decryptError->getMessage());
                        throw new \Exception("Erro crítico: descriptografar link estático (pedido {$orderId}).");
                    }
                } else {
                    log_message('error', "Pedido {$orderId}: Tipo de produto desconhecido '{$product->product_type}' (ID {$productId}).");
                    throw new \Exception("Tipo de produto desconhecido (pedido {$orderId})");
                }

                // g. Enviar mensagem via API do ML
                if ($deliveryContent && $buyerId) {

                    // --- INÍCIO: Lógica de Busca do Template ---
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
                        $templateToUse = $this->templateModel->find(1);
                        if ($templateToUse) {
                            $templateContent = $templateToUse->content;
                            log_message('info', "Pedido {$orderId}: Usando Template Padrão (ID 1) do banco.");
                        } else {
                            log_message('critical', "Pedido {$orderId}: ERRO CRÍTICO. Template Padrão (ID: 1) não encontrado no banco. O Seeder (DefaultMessageTemplateSeeder) precisa ser executado.");
                            throw new \Exception("Template Padrão (ID: 1) de mensagem não encontrado para o pedido {$orderId}. Execute o seeder.");
                        }
                    }
                    $messageText = str_replace('{delivery_content}', $deliveryContent, $templateContent);
                    // --- FIM: Lógica de Busca do Template ---

                    $messagePayload = json_encode([
                        'from' => ['user_id' => $currentSellerId],
                        'to' => ['user_id' => $buyerId],
                        'text' => $messageText,
                    ]);

                    $packId = $orderData->pack_id ?? $orderId;
                    $messageEndpoint = 'messages/packs/' . $packId . '/sellers/' . $currentSellerId;

                    log_message('info', "Pedido {$orderId}: Enviando mensagem para Comprador {$buyerId} via {$messageEndpoint}");

                    $messageResponse = $httpClient->post($messageEndpoint, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $currentAccessToken,
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                        ],
                        'body' => $messagePayload
                    ]);

                    if ($messageResponse->getStatusCode() >= 300) {
                        log_message('error', "Pedido {$orderId}: Falha ao enviar mensagem. Status: {$messageResponse->getStatusCode()} Body: {$messageResponse->getBody()}");
                    } else {
                        log_message('info', "Pedido {$orderId}: Mensagem enviada com sucesso.");
                    }
                    // --- INÍCIO: Marcar pedido como entregue ---
                    // Adicionado após o envio da mensagem
                    log_message('info', "Pedido {$orderId}: Tentando marcar o pedido como 'entregue' (ME1)...");
                    try {
                        // 1. Obter o shipment_id
                        $shipmentResponse = $httpClient->get("orders/{$orderId}/shipments", [
                            'headers' => ['Authorization' => 'Bearer ' . $currentAccessToken, 'Accept' => 'application/json']
                        ]);

                        if ($shipmentResponse->getStatusCode() !== 200) {
                            log_message('warning', "Pedido {$orderId}: Não foi possível obter o shipment_id (Status {$shipmentResponse->getStatusCode()}). O pedido pode não ser ME1. Pulando etapa 'entregue'. Body: " . $shipmentResponse->getBody());
                        } else {
                            $shipmentData = json_decode($shipmentResponse->getBody());

                            // 2. Validar se é ME1 e se o ID existe
                            $shipmentId = $shipmentData->id ?? null;
                            $shippingMode = $shipmentData->mode ?? '';
                            $currentStatus = $shipmentData->status ?? '';

                            if ($shipmentId && $shippingMode === 'me1' && $currentStatus !== 'delivered') {
                                log_message('info', "Pedido {$orderId}: Shipment ID {$shipmentId} (ME1) encontrado. Enviando status 'delivered'.");

                                // 3. Montar o payload
                                $deliveryPayload = json_encode([
                                    'payload' => [
                                        'comment' => 'Pedido entregue (Entrega digital automática)',
                                        'date' => date('c') // Formato ISO 8601 (ex: 2025-10-30T14:04:00-03:00)
                                    ],
                                    'status' => 'delivered',
                                    'substatus' => null
                                ]);

                                // 4. Enviar a notificação de status
                                $deliveryResponse = $httpClient->post("shipments/{$shipmentId}/seller_notifications", [
                                    'headers' => [
                                        'Authorization' => 'Bearer ' . $currentAccessToken,
                                        'Content-Type' => 'application/json',
                                        'Accept' => 'application/json',
                                    ],
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
                        }
                    } catch (\Throwable $e) {
                        // Captura exceção específica da tentativa de marcar como entregue
                        log_message('error', "Pedido {$orderId}: Exceção ao tentar marcar como 'entregue': " . $e->getMessage());
                    }
                    // --- FIM: Marcar pedido como entregue ---


                } elseif (!$deliveryContent) {
                    log_message('error', "Pedido {$orderId}: Sem conteúdo de entrega ao final.");
                } elseif (!$buyerId) {
                    log_message('warning', "Pedido {$orderId}: Conteúdo gerado, mas sem ID do comprador. Mensagem não enviada.");
                }

                log_message('info', "Pedido {$orderId}: Processamento do webhook concluído.");

            } catch (\Throwable $e) {
                log_message('error', "[Webhook Error] Pedido ML ID: " . ($orderId ?? 'N/A') . " | Erro: " . $e->getMessage() . " | Arquivo: " . $e->getFile() . " | Linha: " . $e->getLine());
            }

        } else {
            log_message('notice', 'Webhook ML recebido com tópico irrelevante ou ausente: ' . ($json->topic ?? 'N/A') . '. Ignorando.');
        }

        return $this->response->setStatusCode(200);
    }
}