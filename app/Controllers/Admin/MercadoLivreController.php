<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MessageTemplateModel;
use App\Models\MlCredentialsModel;
use App\Libraries\MercadoLivreAuth;
use Config\Services;
// MELHORIA: Importa o novo Trait
use App\Controllers\Traits\MercadoLivreAuthTrait;

class MercadoLivreController extends BaseController
{
    // MELHORIA: Usa o Trait de autenticação
    use MercadoLivreAuthTrait;

    protected $templateModel;
    protected $credentialsModel; // Requerido pelo Trait

    // REMOVIDO: Propriedades de autenticação movidas para o Trait
    // private ?string $accessToken = null;
    // private ?object $credentials = null;

    public function __construct()
    {
        $this->templateModel = new MessageTemplateModel();
        $this->credentialsModel = new MlCredentialsModel();
        helper(['form']);
    }

    /**
     * Exibe a página principal de configurações do Mercado Livre com TABS.
     */
    public function index()
    {
        // Carrega os dados para a primeira tab (Mensagens)
        $data['templates'] = $this->templateModel->orderBy('name', 'ASC')->findAll();
        
        // --- INÍCIO DA LÓGICA ATUALIZADA ---
        // Carrega os dados para a segunda tab (Credenciais/User Info)
        $data['seller_info'] = $this->fetchSellerData(); // Busca dados do /users/me
        // Também passamos as credenciais do DB (para mostrar o status do token)
        $data['credentials'] = $this->getDbCredentials(); // <-- Agora usa o Trait
        // --- FIM DA LÓGICA ATUALIZADA ---

        // Carrega a view principal que contém as TABS
        return view('admin/mercadolivre/settings', $data);
    }

    /**
     * Busca os dados do vendedor (/users/me) da API do ML para exibição.
     * (Não salva o seller_id, essa lógica está no Trait::getSellerId())
     */
    private function fetchSellerData(): ?object
    {
        // MELHORIA: getAccessToken() agora vem do Trait
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

    // REMOVIDO: Todos os métodos de autenticação (getDbCredentials, getAccessToken)
    // foram movidos para o MercadoLivreAuthTrait
}