<?php

namespace App\Libraries;

use Config\Services;
use Throwable; // <-- MELHORIA: Adicionado Use
use App\Models\MlCredentialsModel;

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
        $currentCreds = $credentialsModel->getCredentials('default'); // Busca 'default'

        // Use a função env() para buscar as credenciais do .env
        $clientId = env('ml.client_id');
        $clientSecret = env('ml.client_secret');
        
        if (empty($clientId) || empty($clientSecret)) {
            log_message('error', '[ML Auth] Client ID ou Client Secret não encontrados no .env (ml.client_id, ml.client_secret). Impossível atualizar.');
            return false;
        }

        // Pega o refresh token do banco de dados
        $refreshToken = $currentCreds->refresh_token ?? null;
        if (empty($refreshToken)) {
            log_message('error', '[ML Auth] Refresh Token não encontrado no Banco de Dados (key=default). Impossível atualizar. Execute o Seeder.');
            return false;
        }

        // MELHORIA: Cliente HTTP configurado com baseURI
        $httpClient = Services::curlrequest([
            'baseURI' => 'https://api.mercadolibre.com', // <-- Definido aqui
            'timeout' => 15,
            'http_errors' => false, // MUITO IMPORTANTE: para tratar erros 4xx/5xx
            'headers' => [
                'Accept' => 'application/json',
            ],
            // 'verify' => false, // Descomente em ambiente local se tiver problemas SSL
        ], null, null, false);

        // Dados a serem enviados no corpo da requisição POST (formato form-urlencoded)
        $payload = [
            'grant_type' => 'refresh_token',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
        ];

        try {
            log_message('info', '[ML Auth] Enviando requisição POST para /oauth/token para refresh.');
            
            // MELHORIA: Requisição POST agora usa caminho relativo
            $response = $httpClient->post('oauth/token', ['form_params' => $payload]);
            
            $statusCode = $response->getStatusCode();
            $body = $response->getBody();
            $data = json_decode($body);

            // Verifica se a requisição foi bem-sucedida (status 200) e se os tokens esperados vieram
            if ($statusCode === 200 && isset($data->access_token) && isset($data->refresh_token)) {
                log_message('info', '[ML Auth] Token atualizado com sucesso pela API!');
                
                // Tenta salvar os novos tokens no Banco de Dados
                if (
                    $credentialsModel->updateTokens(
                        $data->access_token,
                        $data->refresh_token,
                        $data->expires_in ?? null,
                        'default'
                    )
                ) {
                    log_message('info', '[ML Auth] Tokens atualizados no Banco de Dados com sucesso.');
                    return true; // Sucesso
                } 
                
                log_message('error', '[ML Auth] Falha ao salvar os novos tokens no Banco de Dados após recebê-los da API!');
                return false; // Falha
                
            } 
            
            // Se o status não for 200 ou faltar algum token
            log_message('error', "[ML Auth] Falha ao atualizar token via API. Status: {$statusCode}, Resposta: {$body}");

            // Verifica especificamente se o erro foi 'invalid_grant' (refresh token inválido/expirado)
            if (isset($data->error) && ($data->error === 'invalid_grant' || $data->error === 'invalid_token')) {
                log_message('critical', '[ML Auth] Refresh Token inválido, expirado ou revogado! Reautenticação manual (via Seeder) é necessária.');
                // TODO: Implementar notificação ao administrador aqui!
            }
            return false; // Falha

        } catch (Throwable $e) {
            log_message('error', "[ML Auth] Exceção durante a tentativa de refresh do token: " . $e->getMessage());
            return false; // Falha
        }
    }

    // REMOVIDO: Método getAccessToken() desnecessário.
    // A lógica de obtenção de token (com verificação de expiração)
    // está agora no MercadoLivreAuthTrait.
}