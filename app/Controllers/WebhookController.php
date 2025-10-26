<?php
namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use App\Models\ProductModel;
use App\Models\StockCodeModel;
use Config\Services; // Para usar o Cliente HTTP e Encriptador
use Throwable; // Para capturar exceções gerais
use App\Libraries\MercadoLivreAuth; // <-- CORREÇÃO 1: Importar a biblioteca de Auth
use CodeIgniter\HTTP\ResponseInterface; // Para type hint

class WebhookController extends BaseController
{
    use ResponseTrait;

    protected ProductModel $productModel;
    protected StockCodeModel $stockCodeModel;
    protected $encrypter;
    // protected string $accessToken; // <-- REMOVIDO: Não usaremos token estático
    protected $db; // Conexão do banco de dados
    protected \CodeIgniter\HTTP\ClientInterface $httpClient; // Cliente HTTP do CodeIgniter

    public function __construct()
    {
        $this->productModel = new ProductModel();
        $this->stockCodeModel = new StockCodeModel();
        $this->encrypter = Services::encrypter();
        $this->db = \Config\Database::connect(); // Obtém a conexão padrão do BD

        // <-- CORREÇÃO 2: Configuração do Cliente HTTP sem token estático
        // O token será adicionado dinamicamente em cada chamada.
        $this->httpClient = Services::curlrequest([
            'base_uri' => 'https://api.mercadolibre.com',
            'timeout' => 15, // Um timeout um pouco maior para a retentativa
            'http_errors' => false, // MUITO IMPORTANTE: para tratar 401 manualmente
            'headers' => [
                // Removemos 'Authorization' daqui
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
        log_message('info', '[Webhook] Cliente HTTP para API ML configurado (sem token estático).');
    }

    /**
     * <-- CORREÇÃO 3: Wrapper para chamadas à API ML
     * Lida com expiração de token (401) e faz uma retentativa automática.
     *
     * @param string $method 'get', 'post', 'put', etc.
     * @param string $url A URL do endpoint (ex: "orders/123")
     * @param array $options Opções do CI4 (ex: ['json' => $data])
     * @return \CodeIgniter\HTTP\ResponseInterface|null Null em falha total
     */
    private function makeApiRequest(string $method, string $url, array $options = []): ?ResponseInterface
    {
        // 1. Pega o token ATUAL do banco de dados
        $accessToken = MercadoLivreAuth::getAccessToken();
        if (empty($accessToken)) {
            log_message('critical', "[Webhook API Request] Access Token do DB está vazio. Reautenticação manual necessária.");
            return null;
        }

        // Adiciona o token de autorização às opções da requisição
        // Sobrescreve 'Authorization' se já existir em $options['headers']
        $options['headers']['Authorization'] = 'Bearer ' . $accessToken;

        // 2. Tenta a primeira chamada
        log_message('debug', "[Webhook API Request] 1ª Tentativa: {$method} {$url}");
        $response = $this->httpClient->{$method}($url, $options);
        $statusCode = $response->getStatusCode();

        // 3. Verifica se o token expirou (401 Unauthorized)
        if ($statusCode === 401) {
            log_message('info', "[Webhook API Request] Token expirou (401). Tentando refresh...");

            // 4. Tenta atualizar o token
            if (MercadoLivreAuth::refreshToken()) {
                log_message('info', "[Webhook API Request] Refresh do token com SUCESSO. Retentando a chamada...");

                // 5. Pega o NOVO token
                $newAccessToken = MercadoLivreAuth::getAccessToken();
                if (empty($newAccessToken)) {
                    log_message('error', "[Webhook API Request] Refresh bem-sucedido, mas getAccessToken() retornou vazio.");
                    return null;
                }

                // Atualiza o token nas opções e tenta de novo
                $options['headers']['Authorization'] = 'Bearer ' . $newAccessToken;

                log_message('debug', "[Webhook API Request] 2ª Tentativa: {$method} {$url} com novo token.");
                $response = $this->httpClient->{$method}($url, $options); // Retenta a chamada

                if ($response->getStatusCode() === 401) {
                    log_message('critical', "[Webhook API Request] FALHA na 2ª tentativa (401). O novo token também é inválido. Reautenticação manual pode ser necessária.");
                }

                // Retorna a resposta da 2ª tentativa (seja 200, 404, 500, etc.)
                return $response;

            } else {
                log_message('critical', "[Webhook API Request] FALHA no refresh do token. Abortando chamada para {$url}.");
                return null; // Falha no refresh, não pode continuar
            }
        }

        // Retorna a resposta da 1ª tentativa (se foi 200, 404, 500, etc.)
        return $response;
    }


    /**
     * Processa a notificação webhook do Mercado Livre (tópico: orders_v2).
     */
    public function handle()
    {
        // ... (seu código de validação inicial está ótimo) ...
        $json = $this->request->getJSON();
        log_message('debug', '[Webhook] Raw Data Recebido: ' . json_encode($json));

        if (!$this->isValidNotification($json)) {
            return $this->respond(null, 400); // Bad Request
        }
        // ...
        if ($json->topic === 'orders_v2') {
            try {
                $orderId = basename($json->resource);
                log_message('info', "[Webhook] Processando Ordem ID: {$orderId}");

                // 3. Buscar detalhes da ordem na API do Mercado Livre
                $orderData = $this->getMLOrderDetails($orderId); // <-- Esta função agora usa o wrapper

                // ... (O restante da sua lógica handle() está PERFEITA) ...

            } catch (Throwable $e) {
                // ...
            }
        }
        // ...
        return $this->respond(null, 200);
    }

    // ... (isValidNotification() está ótimo) ...

    /**
     * Busca os detalhes de uma ordem na API do Mercado Livre.
     * @param string|int $orderId ID da Ordem ML.
     * @return object|null Retorna o objeto da ordem ou null em caso de falha.
     */
    private function getMLOrderDetails($orderId): ?object
    {
        try {
            $url = "orders/{$orderId}";
            log_message('info', "[Webhook] A executar GET para: {$url}");

            // <-- CORREÇÃO 4: Usar o wrapper
            $response = $this->makeApiRequest('get', $url);

            if ($response === null) {
                log_message('error', "[Webhook] Falha na requisição (makeApiRequest) para Ordem {$orderId}.");
                return null;
            }

            $statusCode = $response->getStatusCode();
            $body = $response->getBody();

            if ($statusCode === 200) {
                log_message('debug', "[Webhook] Detalhes da Ordem {$orderId} recebidos: " . $body);
                return json_decode($body); // Retorna como objeto stdClass
            } else {
                // O wrapper já tratou 401, então isso é outro erro (ex: 404 Not Found)
                log_message('error', "[Webhook] Falha ao buscar detalhes da Ordem {$orderId}. Status: {$statusCode}, Body: {$body}");
                return null;
            }
        } catch (Throwable $e) {
            log_message('error', "[Webhook] Exceção ao buscar detalhes da Ordem {$orderId}: " . $e->getMessage());
            return null;
        }
    }

    // ... (prepareDeliveryContent() está PERFEITA, nenhuma mudança necessária) ...
    // ... A lógica de transação e lock está excelente.

    /**
     * Envia o conteúdo para o comprador via API de Mensagens ML.
     * @param object $orderData Objeto da ordem da API ML (stdClass).
     * @param string $messageText Conteúdo da mensagem a ser enviada.
     * @return bool Sucesso ou falha no envio.
     */
    private function deliverContent(object $orderData, string $messageText): bool // Tipo object (stdClass)
    {
        // Tenta usar pack_id se existir, senão order_id
        $resourceId = $orderData->pack_id ?? $orderData->id ?? null;
        $buyerId = $orderData->buyer->id ?? null;
        // Tenta pegar da ordem, senão busca /users/me
        $sellerId = $orderData->seller->id ?? $this->getSellerId(); // <-- getSellerId() também usará o wrapper

        if (!$resourceId || !$buyerId || !$sellerId) {
            log_message('error', "[Webhook] Dados insuficientes para enviar mensagem para Ordem {$orderData->id}. ResourceID:{$resourceId}, BuyerID:{$buyerId}, SellerID:{$sellerId}");
            return false;
        }

        $url = "/messages/packs/{$resourceId}/sellers/{$sellerId}";

        $messageBody = [
            "from" => ["user_id" => (int) $sellerId],
            "to" => ["user_id" => (int) $buyerId],
            "text" => $messageText,
            "tags" => ["delivered_product"] // Tag opcional
        ];

        try {
            log_message('info', "[Webhook] A enviar POST para {$url} para Ordem {$orderData->id}");
            log_message('debug', "[Webhook] Body da Mensagem: " . json_encode($messageBody));

            // <-- CORREÇÃO 5: Usar o wrapper
            $response = $this->makeApiRequest('post', $url, [
                'json' => $messageBody // O cliente HTTP do CI4 trata a serialização JSON
            ]);

            if ($response === null) {
                log_message('error', "[Webhook] Falha na requisição (makeApiRequest) para enviar mensagem (Ordem {$orderData->id}).");
                return false;
            }

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody();

            log_message('debug', "[Webhook] Resposta API Mensagens ML: Status={$statusCode}, Body={$responseBody}");

            // A API de mensagens pode retornar 200 ou 201 para sucesso
            if ($statusCode === 200 || $statusCode === 201) {
                return true;
            } else {
                log_message('error', "[Webhook] Erro ao enviar mensagem ML para Ordem {$orderData->id}. Status: {$statusCode}, Body: {$responseBody}");
                return false;
            }
        } catch (Throwable $e) {
            log_message('error', "[Webhook] Exceção ao enviar mensagem ML para Ordem {$orderData->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtém o ID do vendedor autenticado (cache simples).
     * @return int|null
     */
    private function getSellerId(): ?int
    {
        static $cachedSellerId = null;
        if ($cachedSellerId !== null) {
            return $cachedSellerId > 0 ? $cachedSellerId : null;
        }

        try {
            log_message('info', "[Webhook] Buscando Seller ID em /users/me");

            // <-- CORREÇÃO 6: Usar o wrapper
            $response = $this->makeApiRequest('get', "/users/me");

            if ($response === null) {
                log_message('error', '[Webhook] Falha na requisição (makeApiRequest) para /users/me.');
                $cachedSellerId = 0;
                return null;
            }

            if ($response->getStatusCode() === 200) {
                $userData = json_decode($response->getBody());
                if (isset($userData->id) && is_numeric($userData->id)) {
                    $cachedSellerId = (int) $userData->id;
                    log_message('info', "[Webhook] Seller ID obtido da API: {$cachedSellerId}");
                    return $cachedSellerId;
                } else {
                    log_message('error', '[Webhook] Resposta de /users/me não contém ID numérico. Body: ' . $response->getBody());
                }
            } else {
                log_message('error', '[Webhook] Falha ao obter Seller ID de /users/me. Status: ' . $response->getStatusCode() . ' Body: ' . $response->getBody());
            }

            $cachedSellerId = 0; // Marca como falha
            return null;
        } catch (Throwable $e) {
            log_message('error', '[Webhook] Exceção ao obter Seller ID: ' . $e->getMessage());
            $cachedSellerId = 0; // Marca como falha
            return null;
        }
    }
}