<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MessageTemplateModel; // Importar o model
use App\Models\MlCredentialsModel;  // <-- ADICIONAR
use App\Libraries\MercadoLivreAuth; // <-- ADICIONAR
use Config\Services;                  // <-- ADICIONAR

class MercadoLivreController extends BaseController
{
    protected $templateModel;
    protected $credentialsModel; // <-- ADICIONAR

    // Propriedades para gerenciamento do token (copiadas do WebhookController)
    private ?string $accessToken = null;
    private ?object $credentials = null;

    public function __construct()
    {
        $this->templateModel = new MessageTemplateModel();
        $this->credentialsModel = new MlCredentialsModel(); // <-- ADICIONAR
        helper(['form']);
    }

    /**
     * Exibe a página principal de configurações do Mercado Livre com TABS.
     */
    public function index()
    {
        // Carrega os dados para a primeira tab (Mensagens)
        $data['templates'] = $this->templateModel->orderBy('name', 'ASC')->findAll();
        
        // --- INÍCIO DA NOVA LÓGICA ---
        // Carrega os dados para a segunda tab (Credenciais/User Info)
        $data['seller_info'] = $this->fetchSellerData();
        // Também passamos as credenciais do DB (para mostrar o status do token)
        $data['credentials'] = $this->getDbCredentials(); 
        // --- FIM DA NOVA LÓGICA ---

        // Carrega a view principal que contém as TABS
        return view('admin/mercadolivre/settings', $data);
    }

    /**
     * Busca os dados do vendedor (/users/me) da API do ML.
     */
    private function fetchSellerData(): ?object
    {
        $token = $this->getAccessToken();
        if (!$token) {
            log_message('error', "[MercadoLivreController] Não foi possível obter Access Token para buscar /users/me.");
            return null;
        }

        try {
            $httpClient = Services::curlrequest(['baseURI' => 'https://api.mercadolibre.com/', 'timeout' => 10, 'http_errors' => false]);
            $response = $httpClient->get('users/me', [
                'headers' => ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json']
            ]);

            if ($response->getStatusCode() !== 200) {
                log_message('error', "[MercadoLivreController] Falha ao buscar /users/me. Status: " . $response->getStatusCode() . " Body: " . $response->getBody());
                return null;
            }

            $userData = json_decode($response->getBody());
            if (json_last_error() !== JSON_ERROR_NONE) {
                log_message('error', "[MercadoLivreController] Falha ao decodificar JSON do /users/me.");
                return null;
            }
            
            // Sucesso! Retorna o objeto com os dados do usuário
            return $userData;

        } catch (\Throwable $e) {
            log_message('error', "[MercadoLivreController] Exceção em fetchSellerData: " . $e->getMessage());
            return null;
        }
    }

    // --- MÉTODOS DE AUTENTICAÇÃO (COPIADOS DO WEBHOOKCONTROLLER) ---

    /**
     * Obtém as credenciais do banco de dados (uma única vez por requisição).
     */
    private function getDbCredentials(): ?object
    {
        if ($this->credentials === null) {
            $this->credentials = $this->credentialsModel->getCredentials('default');

            if ($this->credentials === null) {
                log_message('warning', "[MercadoLivreController] Nenhum registro encontrado em ml_credentials para key_name='default'.");
                // Não tenta inserir, apenas retorna nulo
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
                log_message('info', "[MercadoLivreController] Access Token expirado. Tentando refresh...");

                if (MercadoLivreAuth::refreshToken()) {
                    log_message('info', "[MercadoLivreController] Refresh Token bem-sucedido. Recarregando credenciais.");
                    $this->credentials = null; // Força recarregar
                    $dbCredentials = $this->getDbCredentials();
                    if ($dbCredentials && !empty($dbCredentials->access_token)) {
                        $this->accessToken = $dbCredentials->access_token;
                        log_message('debug', '[MercadoLivreController] Novo Access Token carregado.');
                    } else {
                        log_message('error', "[MercadoLivreController] Falha ao carregar o novo Access Token do banco após o refresh.");
                        $this->accessToken = null;
                    }
                } else {
                    log_message('error', "[MercadoLivreController] Falha ao executar MercadoLivreAuth::refreshToken().");
                    $this->accessToken = null;
                }
            } else {
                $this->accessToken = $dbCredentials->access_token;
                log_message('debug', '[MercadoLivreController] Access Token válido obtido do banco.');
            }
        } else {
             log_message('error', "[MercadoLivreController] Access Token não encontrado no DB.");
            $this->accessToken = null;
        }
        return $this->accessToken;
    }
}