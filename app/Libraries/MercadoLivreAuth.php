<?php

namespace App\Libraries;

use Config\Services;
use Throwable; 
use App\Models\MlCredentialsModel;

class MercadoLivreAuth
{
    /**
     * (MODIFICADO) Tenta atualizar o Access Token da credencial ATIVA.
     * Retorna true em sucesso, false em falha.
     */
    public static function refreshToken(): bool
    {
        log_message('info', '[ML Auth] Iniciando tentativa de refresh do token ATIVO via DB...');

        $credentialsModel = new MlCredentialsModel();
        
        // 1. Busca a credencial ATIVA
        $currentCreds = $credentialsModel->getActiveCredentials(); 

        if ($currentCreds === null) {
            log_message('error', '[ML Auth] Nenhuma credencial ATIVA (is_active = 1) encontrada. Impossível atualizar.');
            return false;
        }

        // 2. Pega os dados do DB (não mais do .env)
        $clientId = $currentCreds->client_id;
        $clientSecretRaw = $currentCreds->client_secret; // Encriptado
        $refreshToken = $currentCreds->refresh_token;

        if (empty($clientId) || empty($clientSecretRaw)) {
            log_message('error', "[ML Auth] Client ID ou Client Secret não encontrados no DB para o App ID {$currentCreds->id}.");
            return false;
        }
        if (empty($refreshToken)) {
            log_message('error', "[ML Auth] Refresh Token não encontrado no DB para o App ID {$currentCreds->id}. É preciso (re)autorizar.");
            return false;
        }
        
        // 3. Desencripta o Client Secret
        try {
             $clientSecret = Services::encrypter()->decrypt($clientSecretRaw);
        } catch (Throwable $e) {
             log_message('critical', "[ML Auth] ERRO CRÍTICO: Não foi possível desencriptar o Client Secret do App ID {$currentCreds->id}. " . $e->getMessage());
             return false;
        }

        $httpClient = Services::curlrequest([
            'baseURI' => 'https://api.mercadolibre.com',
            'timeout' => 15,
            'http_errors' => false, 
            'headers' => ['Accept' => 'application/json'],
        ], null, null, false);

        $payload = [
            'grant_type' => 'refresh_token',
            'client_id' => $clientId,
            'client_secret' => $clientSecret, // Usa o valor desencriptado
            'refresh_token' => $refreshToken,
        ];

        try {
            log_message('info', "[ML Auth] Enviando requisição POST para /oauth/token (App ID: {$currentCreds->id}).");
            
            $response = $httpClient->post('oauth/token', ['form_params' => $payload]);
            
            $statusCode = $response->getStatusCode();
            $body = $response->getBody();
            $data = json_decode($body);

            if ($statusCode === 200 && isset($data->access_token) && isset($data->refresh_token)) {
                log_message('info', '[ML Auth] Token atualizado com sucesso pela API!');
                
                // 4. Salva no registro ATIVO (usando o ID)
                if (
                    $credentialsModel->updateTokens(
                        $currentCreds->id, // Passa o ID
                        $data->access_token,
                        $data->refresh_token,
                        $data->expires_in ?? null
                    )
                ) {
                    log_message('info', "[ML Auth] Tokens atualizados no DB (App ID: {$currentCreds->id}) com sucesso.");
                    return true;
                } 
                
                log_message('error', '[ML Auth] Falha ao salvar os novos tokens no Banco de Dados!');
                return false;
                
            } 
            
            log_message('error', "[ML Auth] Falha ao atualizar token via API. Status: {$statusCode}, Resposta: {$body}");

            if (isset($data->error) && ($data->error === 'invalid_grant' || $data->error === 'invalid_token')) {
                log_message('critical', "[ML Auth] Refresh Token inválido para o App ID {$currentCreds->id}! Reautenticação manual é necessária.");
            }
            return false;

        } catch (Throwable $e) {
            log_message('error', "[ML Auth] Exceção durante a tentativa de refresh do token: " . $e->getMessage());
            return false;
        }
    }
}