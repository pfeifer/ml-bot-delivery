<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MessageTemplateModel;
use App\Models\MlCredentialsModel;
use App\Libraries\MercadoLivreAuth;
use Config\Services;
use App\Controllers\Traits\MercadoLivreAuthTrait;
use Throwable; // Importar

class MercadoLivreController extends BaseController
{
    use MercadoLivreAuthTrait;

    protected $templateModel;
    protected $credentialsModel;

    public function __construct()
    {
        $this->templateModel = new MessageTemplateModel();
        $this->credentialsModel = new MlCredentialsModel();
        helper(['form', 'url', 'security']); // <-- ADICIONE 'url' e 'security'
    }

    /**
     * (MODIFICADO) Exibe a página principal de configurações do ML.
     */
    public function index()
    {
        // Carrega os dados para a tab (Mensagens)
        $data['templates'] = $this->templateModel->orderBy('name', 'ASC')->findAll();

        // (MODIFICADO) Carrega dados para a tab (Credenciais)

        // 1. Busca TODAS as credenciais para a lista/switch
        $data['all_credentials'] = $this->credentialsModel->orderBy('app_name', 'ASC')->findAll();

        // 2. Busca a credencial ATIVA (o Trait já faz isso)
        $data['credentials'] = $this->getDbCredentials();

        // 3. Busca os dados do /users/me (usando o token da credencial ativa)
        $data['seller_info'] = $this->fetchSellerData();

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
            // O Service 'curlrequest' já está carregado via 'use Config\Services;'
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

        } catch (Throwable $e) {
            log_message('error', "[MercadoLivreController] Exceção em fetchSellerData: " . $e->getMessage());
            return null;
        }
    }

    /**
     * (MODIFICADO) Passo 1 do OAuth: Usa as credenciais ATIVAS do DB.
     */
    public function authorize()
    {
        $activeCreds = $this->credentialsModel->getActiveCredentials();

        if (!$activeCreds || empty($activeCreds->client_id) || empty($activeCreds->redirect_uri)) {
            log_message('error', "[OAuth Authorize] Tentativa de autorizar sem credencial ativa ou sem Client ID/Redirect URI no DB.");
            return redirect()->route('admin.mercadolivre.settings')
                ->with('error', 'Nenhuma aplicação ativa configurada com Client ID e Redirect URI no banco de dados.');
        }

        $clientId = $activeCreds->client_id;
        // IMPORTANTE: A URL de callback agora vem do DB
        $redirectUri = $activeCreds->redirect_uri;

        $authUrl = "https://auth.mercadolivre.com.br/authorization";

        $url = $authUrl . '?' . http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
        ]);

        log_message('info', "[OAuth Authorize] Redirecionando usuário para autorização ML (App ID: {$activeCreds->id}).");

        return redirect()->to($url);
    }

    /**
     * (MODIFICADO) Passo 2 do OAuth: Usa as credenciais ATIVAS do DB.
     */
    public function handleCallback()
    {
        $session = session();
        $code = $this->request->getGet('code');
        $error = $this->request->getGet('error');

        if (!empty($error) || empty($code)) {
            // ... (lógica de erro igual) ...
        }

        // 1. Pega a credencial ATIVA
        $activeCreds = $this->credentialsModel->getActiveCredentials();

        if (!$activeCreds || empty($activeCreds->client_id) || empty($activeCreds->client_secret) || empty($activeCreds->redirect_uri)) {
            log_message('critical', "[OAuth Callback] Credencial ativa (ID: {$activeCreds->id}) está incompleta (sem client_id, secret ou redirect_uri).");
            return redirect()->route('admin.mercadolivre.settings')
                ->with('error', 'Configuração da aplicação ativa está incompleta no banco de dados.');
        }

        // 2. Desencripta o Client Secret
        try {
            $clientSecret = Services::encrypter()->decrypt($activeCreds->client_secret);
        } catch (Throwable $e) {
            log_message('critical', "[OAuth Callback] ERRO CRÍTICO: Não foi possível desencriptar o Client Secret do App ID {$activeCreds->id}. " . $e->getMessage());
            return redirect()->route('admin.mercadolivre.settings')
                ->with('error', 'Erro fatal ao ler o Client Secret da aplicação ativa.');
        }

        // 3. Pega os dados para o POST
        $clientId = $activeCreds->client_id;
        $redirectUri = $activeCreds->redirect_uri;

        try {
            $httpClient = Services::curlrequest([
                'baseURI' => 'https://api.mercadolibre.com',
                'timeout' => 20,
                'http_errors' => false,
            ], null, null, false);

            $payload = [
                'grant_type' => 'authorization_code',
                'client_id' => $clientId,
                'client_secret' => $clientSecret, // Valor desencriptado
                'code' => $code,
                'redirect_uri' => $redirectUri,
            ];

            log_message('info', "[OAuth Callback] Enviando POST para /oauth/token (App ID: {$activeCreds->id}).");

            $response = $httpClient->post('oauth/token', ['form_params' => $payload]);
            $body = $response->getBody();
            $data = json_decode($body);

            // 4. Sucesso! Salva os tokens no registro ATIVO
            if ($response->getStatusCode() === 200 && isset($data->access_token) && isset($data->refresh_token)) {

                log_message('info', "[OAuth Callback] Tokens recebidos com sucesso. Atualizando DB (App ID: {$activeCreds->id}).");

                // Salva os tokens NO ID DA CREDENCIAL ATIVA
                $this->credentialsModel->updateTokens(
                    $activeCreds->id, // <--- AQUI
                    $data->access_token,
                    $data->refresh_token,
                    $data->expires_in ?? 21600
                );

                if (isset($data->user_id)) {
                    // Salva o seller_id NO ID DA CREDENCIAL ATIVA
                    $this->credentialsModel->saveSellerId($activeCreds->id, (int) $data->user_id);
                    log_message('info', "[OAuth Callback] Seller ID {$data->user_id} salvo.");
                }

                $this->accessToken = null;
                $this->credentials = null;

                return redirect()->route('admin.mercadolivre.settings')
                    ->with('success', 'Aplicação autorizada com sucesso! Os tokens foram atualizados.');

            } else {
                // ... (lógica de falha igual) ...
            }

        } catch (Throwable $e) {
            // ... (lógica de exceção igual) ...
        }
    }

    // ==========================================================
    // INÍCIO DOS NOVOS MÉTODOS DE GERENCIAMENTO (CRUD + Switch)
    // ==========================================================

    /**
     * (NOVO) Exibe o formulário (modal) para criar/editar uma credencial.
     */
    public function credentialForm($id = null)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->route('admin.mercadolivre.settings')->with('error', 'Acesso inválido.');
        }

        $data = ['action' => 'create', 'cred' => null];

        if ($id) {
            $cred = $this->credentialsModel->find($id);
            if ($cred) {

                // ================== INÍCIO DA CORREÇÃO 1 ==================
                // NÃO descriptografe o secret. O campo deve sempre vir em branco na edição.
                $cred->client_secret = '';
                // =================== FIM DA CORREÇÃO 1 ====================

                $data['action'] = 'update';
                $data['cred'] = $cred;
            } else {
                return $this->response->setStatusCode(404)->setBody('Credencial não encontrada.');
            }
        }

        // Retorna a view parcial do formulário (precisamos criar este arquivo)
        return view('admin/mercadolivre/form_credentials_modal', $data);
    }

    /**
     * (NOVO) Salva a credencial (nova ou edição).
     */
    public function saveCredentials()
    {
        $id = $this->request->getPost('id');

        // ================== INÍCIO DA CORREÇÃO 2 (Regras) ==================
        // CORREÇÃO: O client_secret só é 'required' se for uma criação (sem $id)
        $rules = [
            'app_name' => 'required|max_length[100]',
            'client_id' => 'required|max_length[100]',
            'client_secret' => ($id ? 'permit_empty' : 'required') . '|max_length[100]',
            'redirect_uri' => 'required|valid_url|max_length[255]',
        ];
        // =================== FIM DA CORREÇÃO 2 (Regras) ====================


        if (!$this->validate($rules)) {
            // Se for AJAX (modal), retorna o formulário com erros
            if ($this->request->isAJAX()) {
                $data = ['action' => $id ? 'update' : 'create', 'cred' => (object) $this->request->getPost()];
                $data['validationErrors'] = $this->validator->getErrors();

                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Erro de validação',
                    'form_html' => view('admin/mercadolivre/form_credentials_modal', $data)
                ]);
            }
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // ================== INÍCIO DA CORREÇÃO 2 (Salvamento) ==================

        // Dados base (sem o secret)
        $data = [
            'app_name' => $this->request->getPost('app_name'),
            'client_id' => $this->request->getPost('client_id'),
            'redirect_uri' => $this->request->getPost('redirect_uri'),
        ];

        // CORREÇÃO: Só encripta e salva o secret se um NOVO valor foi digitado
        $newSecret = $this->request->getPost('client_secret');
        if (!empty($newSecret)) {
            $encrypter = Services::encrypter();
            $data['client_secret'] = $encrypter->encrypt($newSecret);
        }

        // =================== FIM DA CORREÇÃO 2 (Salvamento) ====================

        $message = 'Credencial salva com sucesso!';

        if ($id) {
            // Update
            if (!$this->credentialsModel->update($id, $data)) {
                $message = 'Erro ao atualizar credencial.';
            }
        } else {
            // Insert
            if (!$this->credentialsModel->insert($data)) {
                $message = 'Erro ao salvar nova credencial.';
            }
        }

        // Resposta AJAX de sucesso
        if ($this->request->isAJAX()) {
            session()->setFlashdata('success', $message);
            return $this->response->setJSON(['success' => true, 'message' => $message]);
        }

        return redirect()->route('admin.mercadolivre.settings')->with('success', $message);
    }

    /**
     * (NOVO) Ativa a credencial (o "switch").
     */
    public function activateCredential($id = null)
    {
        $cred = $this->credentialsModel->find($id);
        if (!$cred) {
            return redirect()->route('admin.mercadolivre.settings')->with('error', 'Credencial não encontrada.');
        }

        if ($this->credentialsModel->setActive($id)) {
            // Limpa os tokens da sessão (se houver) para forçar o recarregamento
            $this->accessToken = null;
            $this->credentials = null;
            return redirect()->route('admin.mercadolivre.settings')->with('success', "Aplicação '{$cred->app_name}' foi ativada.");
        }

        return redirect()->route('admin.mercadolivre.settings')->with('error', 'Erro ao tentar ativar a aplicação.');
    }

    /**
     * (NOVO) Exclui uma credencial.
     */
    public function deleteCredential($id = null)
    {
        $cred = $this->credentialsModel->find($id);
        if (!$cred) {
            return redirect()->route('admin.mercadolivre.settings')->with('error', 'Credencial não encontrada.');
        }

        // Não permite excluir a credencial ativa
        if ($cred->is_active) {
            return redirect()->route('admin.mercadolivre.settings')->with('error', 'Não é possível excluir a aplicação ativa. Ative outra aplicação primeiro.');
        }

        if ($this->credentialsModel->delete($id)) {
            return redirect()->route('admin.mercadolivre.settings')->with('success', 'Aplicação excluída com sucesso.');
        }

        return redirect()->route('admin.mercadolivre.settings')->with('error', 'Erro ao excluir a aplicação.');
    }
}