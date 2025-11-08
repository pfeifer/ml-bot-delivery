<?php
namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\StockCodeModel;
use App\Models\MlCredentialsModel;
use App\Models\MessageTemplateModel;
use App\Models\MlOrderModel;
use CodeIgniter\API\ResponseTrait;
use Config\Services;
// MELHORIA: Importa o novo Trait
use App\Controllers\Traits\MercadoLivreAuthTrait;
use CodeIgniter\Database\RawSql;

class WebhookController extends BaseController
{
    use ResponseTrait;
    // MELHORIA: Usa o Trait de autenticação
    use MercadoLivreAuthTrait;

    // MELHORIA: Propriedades do Trait movidas para o Trait
    // private ?int $sellerId = null; // Movido
    // private ?string $accessToken = null; // Movido
    // private ?object $credentials = null; // Movido

    private $templateModel;
    private $orderModel;
    private $productModel;
    private $stockCodeModel; // <-- MELHORIA: Adicionado
    protected $credentialsModel; // <-- MELHORIA: Adicionado (requerido pelo Trait)

    public function __construct()
    {
        $this->templateModel = new MessageTemplateModel();
        $this->orderModel = new MlOrderModel();
        $this->productModel = new ProductModel();
        $this->stockCodeModel = new StockCodeModel(); // <-- MELHORIA: Instanciado aqui
        $this->credentialsModel = new MlCredentialsModel(); // <-- MELHORIA: Instanciado aqui
    }

    // REMOVIDO: Todos os métodos de autenticação (getDbCredentials, getAccessToken, getSellerId, fetchAndSaveSellerId)
    // foram movidos para o MercadoLivreAuthTrait

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
                $orderId = (int) end($resourceParts); // Cast para int

                if (empty($orderId) || $orderId <= 0) {
                    throw new \Exception("ID de pedido inválido no resource: " . ($json->resource ?? 'N/A'));
                }
                log_message('info', "Pedido ID {$orderId}: Iniciando processamento...");
                
                // 2. Autenticação (Agora usa o Trait)
                $currentAccessToken = $this->getAccessToken();
                $currentSellerId = $this->getSellerId();
                if (!$currentAccessToken || !$currentSellerId) {
                    // BUG CORRIGIDO: A lógica de 'setup' (buscar e salvar ID) foi movida para getSellerId()
                    // Se falhar aqui, é porque a configuração (seeder) não foi feita.
                    throw new \Exception("Pedido {$orderId}: Não foi possível obter Access Token ou Seller ID. Verifique se o Seeder 'MlCredentialsSeeder' foi executado.");
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
                        log_message('warning', "Pedido {$orderId}: Erro 404. Pedido não encontrado na API do ML.");
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
                
                // Lógica "Upsert"
                $existingOrder = $this->orderModel->where('ml_order_id', $orderData->id)->first();
                if($existingOrder) {
                    // Apenas atualiza
                    $this->orderModel->update($existingOrder->id, $orderRecord);
                    // Mantém o delivery_status original se já foi processado
                    $orderRecord['delivery_status'] = $existingOrder->delivery_status;
                } else {
                    // Insere (delivery_status terá o valor default 'pending')
                    $this->orderModel->insert($orderRecord);
                    $orderRecord['delivery_status'] = 'pending'; // Default
                }
                
                log_message('info', "Pedido {$orderId}: Salvo/Atualizado no DB com status '{$orderRecord['status']}'.");

                // 5. Verificar se o Pagamento está Aprovado (Status 'paid')
                if ($orderRecord['status'] !== 'paid') {
                    log_message('info', "Pedido {$orderId}: Status não é 'paid'. Ignorando entrega por enquanto.");
                    return $this->response->setStatusCode(200);
                }

                // 6. Verificar se JÁ FOI ENTREGUE
                if ($orderRecord['delivery_status'] === 'delivered') {
                     log_message('info', "Pedido {$orderId}: Já consta como 'delivered' em nosso DB. Ignorando nova tentativa.");
                     return $this->response->setStatusCode(200);
                }

                log_message('info', "Pedido {$orderId}: Status 'paid' e entrega pendente. Iniciando processo de entrega.");

                // ==========================================================
                // INÍCIO DA LÓGICA DE ENTREGA
                // ==========================================================

                if (!$product) {
                    log_message('error', "Pedido {$orderId}: Produto com ML Item ID {$mlItemId} não encontrado no DB local.");
                    $this->updateOrderDeliveryStatus($orderId, 'failed');
                    // Não lança exceção, apenas falha esta entrega
                    return $this->response->setStatusCode(200); 
                }
                
                $productId = $product->id;
                $buyerId = $orderRecord['ml_buyer_id'];
                $deliveryContent = null;
                $stockCodeId = null;

                if ($product->product_type === 'code') {
                    // MELHORIA: $this->stockCodeModel já está instanciado
                    $this->db = \Config\Database::connect(); // Necessário para transação
                    $this->db->transStart();
                    
                    $availableCode = $this->stockCodeModel // Usa a propriedade
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
                        // MELHORIA: Usa a instância do model para atualizar
                        $updated = $this->stockCodeModel->update($stockCodeId, $updateData);
                        
                        if (!$updated) {
                            $this->db->transRollback();
                            $this->updateOrderDeliveryStatus($orderId, 'failed');
                            throw new \Exception("Pedido {$orderId}: Falha ao marcar código {$stockCodeId} como vendido.");
                        }
                        
                        try {
                            $deliveryContent = service('encrypter')->decrypt($availableCode->code);
                        } catch (\Throwable $decryptError) {
                            $this->db->transRollback(); // Desfaz a marcação de 'vendido'
                            $this->updateOrderDeliveryStatus($orderId, 'failed');
                            // Log mais grave, pois o código foi "vendido" e não pôde ser descriptografado
                            log_message('critical', "Pedido {$orderId}: Erro CRÍTICO ao DESCRIPTOGRAFAR código ID {$stockCodeId} (vendido): " . $decryptError->getMessage() . ". VENDA REVERTIDA.");
                            throw new \Exception("Erro crítico: Não foi possível descriptografar o código {$stockCodeId} (pedido {$orderId}). Operação revertida.");
                        }
                        
                        // Se tudo deu certo até aqui (descriptografou)
                        $this->db->transCommit();
                        log_message('info', "Pedido {$orderId}: Código ID {$stockCodeId} alocado e vendido.");
                    
                    } else { // SEM ESTOQUE
                        $this->db->transRollback(); // Apenas para garantir
                        $this->updateOrderDeliveryStatus($orderId, 'out_of_stock');
                        log_message('critical', "ALERTA DE ESTOQUE - Pedido {$orderId}: Estoque esgotado/expirado para produto ID {$productId} (ML: {$mlItemId}).");
                        // Não lança exceção, apenas falha a entrega
                        return $this->response->setStatusCode(200); 
                    }

                } elseif ($product->product_type === 'link') {
                    if (empty($product->delivery_data)) {
                        $this->updateOrderDeliveryStatus($orderId, 'failed');
                        log_message('error', "Pedido {$orderId}: Link estático não configurado para produto {$productId}.");
                        return $this->response->setStatusCode(200); // Falha, mas OK para ML
                    }
                    try {
                        $deliveryContent = service('encrypter')->decrypt($product->delivery_data);
                        log_message('info', "Pedido {$orderId}: Link estático (Produto {$productId}) descriptografado.");
                    } catch (\Throwable $decryptError) {
                        $this->updateOrderDeliveryStatus($orderId, 'failed');
                        log_message('critical', "Pedido {$orderId}: Erro CRÍTICO ao DESCRIPTOGRAFAR link estático produto ID {$productId}: " . $decryptError->getMessage());
                        return $this->response->setStatusCode(200); // Falha, mas OK para ML
                    }
                } else {
                    $this->updateOrderDeliveryStatus($orderId, 'failed');
                    log_message('error', "Pedido {$orderId}: Tipo de produto desconhecido '{$product->product_type}' (ID {$productId}).");
                    return $this->response->setStatusCode(200); // Falha, mas OK para ML
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
                    // Endpoint correto para enviar mensagens pós-venda
                    $messageEndpoint = 'messages/packs/' . $packId . '/sellers/' . $currentSellerId;
                    log_message('info', "Pedido {$orderId}: Enviando mensagem para Comprador {$buyerId} via {$messageEndpoint}");

                    $messageResponse = $httpClient->post($messageEndpoint, [
                        'headers' => ['Authorization' => 'Bearer ' . $currentAccessToken, 'Content-Type' => 'application/json', 'Accept' => 'application/json'],
                        'body' => $messagePayload
                    ]);

                    if ($messageResponse->getStatusCode() >= 300) {
                        // A entrega falhou no envio da mensagem
                        $this->updateOrderDeliveryStatus($orderId, 'failed');
                        log_message('error', "Pedido {$orderId}: Falha ao enviar mensagem. Status: {$messageResponse->getStatusCode()} Body: {$messageResponse->getBody()}");
                        
                        // BUG CRÍTICO CORRIGIDO: Se for 'code', devemos reverter a venda do código?
                        // Decisão de design: Não reverter. O código foi "gasto". O status 'failed'
                        // alerta o admin para tentar manualmente. Reverter poderia causar venda dupla.
                        
                    } else {
                        // SUCESSO!
                        log_message('info', "Pedido {$orderId}: Mensagem enviada com sucesso.");
                        $this->updateOrderDeliveryStatus($orderId, 'delivered');
                    }

                    // 8. Tentar Marcar como Entregue (ME1) - (Lógica movida para método auxiliar)
                    $this->markAsDelivered($httpClient, $orderId, $orderData, $currentAccessToken);

                } elseif (!$deliveryContent) {
                    log_message('error', "Pedido {$orderId}: Sem conteúdo de entrega (deliveryContent) ao final do processo.");
                } elseif (!$buyerId) {
                    log_message('warning', "Pedido {$orderId}: Conteúdo gerado, mas sem ID do comprador. Mensagem não enviada.");
                }
                
                log_message('info', "Pedido {$orderId}: Processamento do webhook concluído.");

            } catch (\Throwable $e) {
                // Captura exceções da lógica de entrega (ex: falha na transação, falha grave de descriptografia)
                log_message('error', "[Webhook Error] Pedido ML ID: " . ($orderId ?? 'N/A') . " | Erro: " . $e->getMessage() . " | Arquivo: " . $e->getFile() . " | Linha: " . $e->getLine());
                
                if($orderId) {
                    // Garante que o status seja 'failed' se uma exceção ocorreu
                    $this->updateOrderDeliveryStatus($orderId, 'failed');
                }
            }
        } else {
            log_message('notice', 'Webhook ML recebido com tópico irrelevante ou ausente: ' . ($json->topic ?? 'N/A') . '. Ignorando.');
        }
        
        // Sempre retorna 200 OK para o Mercado Livre, independentemente de falhas internas
        return $this->response->setStatusCode(200);
    }
    
    /**
     * Atualiza o status da *nossa* entrega digital no DB
     *
     * @param int    $orderId ID do Pedido no ML
     * @param string $status  Novo status (pending, delivered, failed, out_of_stock)
     */
    private function updateOrderDeliveryStatus(int $orderId, string $status)
    {
        try {
            // Atualiza o registro baseado no ml_order_id
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
     *
     * @param object $product
     * @param string $deliveryContent
     * @param int    $orderId
     * @return string
     */
    private function prepareMessage(object $product, string $deliveryContent, int $orderId): string
    {
        $templateContent = null;
        $templateToUse = null;

        // 1. Tenta usar o template vinculado ao produto
        if (!empty($product->message_template_id)) {
            $templateToUse = $this->templateModel->find($product->message_template_id);
            if ($templateToUse) {
                log_message('info', "Pedido {$orderId}: Usando template vinculado ID {$product->message_template_id} ('{$templateToUse->name}').");
                $templateContent = $templateToUse->content;
            } else {
                log_message('warning', "Pedido {$orderId}: Template vinculado ID {$product->message_template_id} não encontrado. Buscando template padrão (ID 1).");
            }
        }
        
        // 2. Se não encontrou (ou não tinha), usa o Padrão (ID 1)
        if ($templateContent === null) {
            $templateToUse = $this->templateModel->find(1); // ID 1 é o Padrão
            if ($templateToUse) {
                $templateContent = $templateToUse->content;
                log_message('info', "Pedido {$orderId}: Usando Template Padrão (ID 1) do banco.");
            } else {
                // 3. Fallback de emergência (se nem o ID 1 existir)
                log_message('critical', "Pedido {$orderId}: ERRO CRÍTICO. Template Padrão (ID: 1) não encontrado no banco. O Seeder (DefaultMessageTemplateSeeder) precisa ser executado.");
                $templateContent = "Obrigado por sua compra! Segue seu produto: {delivery_content}";
            }
        }
        
        // Substitui o placeholder pelo conteúdo real
        return str_replace('{delivery_content}', $deliveryContent, $templateContent);
    }

    /**
     * Tenta marcar o pedido como 'delivered' (ME1) (lógica movida)
     *
     * @param mixed  $httpClient Instância do CURLRequest
     * @param int    $orderId
     * @param object $orderData  Dados do pedido da API
     * @param string $accessToken
     */
    private function markAsDelivered($httpClient, int $orderId, object $orderData, string $accessToken)
    {
        log_message('info', "Pedido {$orderId}: Tentando marcar o pedido como 'entregue' (ME1)...");
        try {
            $shipmentId = $orderData->shipping->id ?? null;
            $shippingMode = '';
            $currentStatus = '';

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
                $shipmentId = $shipmentData->id ?? null; // ID do envio
                $shippingMode = $shipmentData->mode ?? ''; // ex: 'me1'
                $currentStatus = $shipmentData->status ?? ''; // ex: 'ready_to_ship'
            
            } else {
                 // Se o ID veio, busca os detalhes do envio para saber o 'mode' e 'status'
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
                
                // Payload para notificar a entrega (para ME1)
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
                    log_message('info', "Pedido {$orderId}: Pedido marcado como 'entregue' (ME1) com sucesso.");
                }
            } elseif ($shippingMode !== 'me1') {
                log_message('info', "Pedido {$orderId}: Pedido não é 'me1' (Modo: {$shippingMode}). Não é necessário marcar como 'entregue'.");
            } elseif ($currentStatus === 'delivered') {
                log_message('info', "Pedido {$orderId}: Pedido (Shipment {$shipmentId}) já estava marcado como 'entregue'.");
            } else {
                log_message('warning', "Pedido {$orderId}: Resposta de 'shipments' não continha um ID válido. Pulando etapa 'entregue'.");
            }
        } catch (\Throwable $e) {
            log_message('error', "Pedido {$orderId}: Exceção ao tentar marcar como 'entregue': " . $e->getMessage());
        }
    }
}