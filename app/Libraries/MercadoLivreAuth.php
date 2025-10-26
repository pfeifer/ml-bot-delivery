<?php

namespace App\Libraries;

use Config\Services;
use Throwable;
use App\Models\MlCredentialsModel; // Importa o novo Model

class MercadoLivreAuth
{
    /**
     * Tenta atualizar o Access Token usando o Refresh Token,
     * salvando os novos tokens no banco de dados.
     * Retorna true em sucesso, false em falha.
     */
    public static function refreshToken(): bool
    {
        log_message('info', '[ML Auth] Iniciando tentativa de refresh do token via DB...');

        $credentialsModel = new MlCredentialsModel();
        $currentCreds = $credentialsModel->getCredentials(); // Busca 'default'

        $clientId = env('ml.client_id'); // Ainda lê do .env
        $clientSecret = env('ml.client_secret'); // Ainda lê do .env
        $refreshToken = $currentCreds->refresh_token ?? null; // Pega do DB

        if (empty($clientId) || empty($clientSecret)) {
            log_message('error', '[ML Auth] Client ID ou Client Secret não encontrados no .env. Impossível atualizar.');
            return false;
        }
        if (empty($refreshToken)) {
            log_message('error', '[ML Auth] Refresh Token não encontrado no Banco de Dados (key=default). Impossível atualizar.');
            return false;
        }

        // Cliente HTTP sem o token de portador
        $httpClient = Services::curlrequest([
            'base_uri' => 'https://api.mercadolibre.com',
            'timeout' => 15,

            // <-- CORREÇÃO 1: Impede que o cliente lance exceções em erros 4xx/5xx
            // Isso permite que nossa lógica if($statusCode === 200) funcione
            'http_errors' => false,

            'headers' => [
                // <-- CORREÇÃO 2: Removido 'Content-Type'
                // O 'form_params' (abaixo) já cuida disso automaticamente.

                'Accept' => 'application/json', // Mantido: Dizemos que QUEREMOS JSON de volta
            ],
            // Desabilitar verificação SSL se estiver em ambiente local com problemas
            // 'verify' => false,
        ]);

        $payload = [
            'grant_type' => 'refresh_token',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
        ];

        try {
            log_message('info', '[ML Auth] Enviando requisição POST para /oauth/token');

            // Seu uso de 'form_params' está PERFEITO!
            $response = $httpClient->post('/oauth/token', ['form_params' => $payload]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody();
            $data = json_decode($body);

            if ($statusCode === 200 && isset($data->access_token) && isset($data->refresh_token)) {
                log_message('info', '[ML Auth] Token atualizado com sucesso pela API!');
                log_message('debug', '[ML Auth] Novo Access Token: ' . substr($data->access_token, 0, 15) . '...');
                log_message('debug', '[ML Auth] Novo Refresh Token: ' . substr($data->refresh_token, 0, 15) . '...');

                // Salva no Banco de Dados usando o Model
                if (
                    $credentialsModel->updateTokens(
                        $data->access_token,
                        $data->refresh_token,
                        $data->expires_in ?? null // Pega expires_in se existir
                    )
                ) {
                    log_message('info', '[ML Auth] Tokens atualizados no Banco de Dados com sucesso.');
                    // Limpar cache, se houver cache dos tokens
                    // cache()->delete('ml_access_token');
                    return true;
                } else {
                    log_message('error', '[ML Auth] Falha ao salvar os novos tokens no Banco de Dados!');
                    return false;
                }

            } else {
                // Com 'http_errors' => false, esta lógica agora será executada
                log_message('error', "[ML Auth] Falha ao atualizar token via API. Status: {$statusCode}, Resposta: {$body}");

                if (isset($data->error) && $data->error === 'invalid_grant') {
                    log_message('critical', '[ML Auth] Refresh Token inválido ou expirado! Reautenticação manual necessária.');
                    // TODO: Notificar o administrador
                }
                return false;
            }

        } catch (Throwable $e) {
            // Isso agora pegará apenas exceções de rede (timeout, etc.)
            // e não mais os erros 400 da API.
            log_message('error', "[ML Auth] Exceção durante o refresh do token: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtém o Access Token atual do Banco de Dados.
     * Pode implementar cache aqui para performance.
     * @return string|null
     */
    public static function getAccessToken(): ?string
    {
        // Exemplo com cache simples (opcional)
        // if (! $token = cache('ml_access_token')) {
        // 	$credentialsModel = new MlCredentialsModel();
        // 	$creds = $credentialsModel->getCredentials();
        // 	$token = $creds->access_token ?? null;
        // 	if ($token) {
        // 		// Cache por 5 horas (um pouco menos que as 6h de expiração)
        // 		cache()->save('ml_access_token', $token, 5 * 3600);
        // 	}
        // }
        // return $token;

        // Sem cache:
        $credentialsModel = new MlCredentialsModel();
        $creds = $credentialsModel->getCredentials();
        return $creds->access_token ?? null;
    }
}