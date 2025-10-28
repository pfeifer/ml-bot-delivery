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
        $currentCreds = $credentialsModel->getCredentials('default'); // Busca 'default'

        // Use a função env() para buscar as credenciais do .env
        $clientId = env('ml.client_id');
        $clientSecret = env('ml.client_secret');
        // Pega o refresh token do banco de dados
        $refreshToken = $currentCreds->refresh_token ?? null;

        if (empty($clientId) || empty($clientSecret)) {
            log_message('error', '[ML Auth] Client ID ou Client Secret não encontrados no .env (ml.client_id, ml.client_secret). Impossível atualizar.');
            return false;
        }
        if (empty($refreshToken)) {
            log_message('error', '[ML Auth] Refresh Token não encontrado no Banco de Dados (key=default). Impossível atualizar.');
            // Você pode querer notificar o admin aqui para reautenticar manualmente
            return false;
        }

        // Cliente HTTP configurado para a API do ML
        $httpClient = Services::curlrequest([
            //'base_uri' => 'https://api.mercadolibre.com',
            'timeout' => 15, // Timeout para a requisição
            'http_errors' => false, // MUITO IMPORTANTE: para tratar erros 4xx/5xx manualmente no código
            'headers' => [
                'Accept' => 'application/json', // Esperamos receber JSON de volta
                // 'Content-Type' é definido automaticamente pelo 'form_params' abaixo
            ],
            // Descomente a linha abaixo se estiver em ambiente local e tiver problemas com certificado SSL
            // 'verify' => false,
        ], null, null, false);

        // Dados a serem enviados no corpo da requisição POST (formato form-urlencoded)
        $payload = [
            'grant_type' => 'refresh_token', // Indica que estamos usando o refresh token
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
        ];

        try {
            log_message('info', '[ML Auth] Enviando requisição POST para /oauth/token para refresh.');
            // Realiza a requisição POST
            // 'form_params' envia os dados como application/x-www-form-urlencoded
            $response = $httpClient->post('https://api.mercadolibre.com/oauth/token', ['form_params' => $payload]);

            $statusCode = $response->getStatusCode(); // Pega o código de status HTTP da resposta
            $body = $response->getBody(); // Pega o corpo da resposta
            $data = json_decode($body); // Tenta decodificar o corpo como JSON

            // Verifica se a requisição foi bem-sucedida (status 200) e se os tokens esperados vieram na resposta
            if ($statusCode === 200 && isset($data->access_token) && isset($data->refresh_token)) {
                log_message('info', '[ML Auth] Token atualizado com sucesso pela API!');
                // Loga apenas uma parte dos tokens por segurança
                log_message('debug', '[ML Auth] Novo Access Token recebido: ' . substr($data->access_token, 0, 15) . '...');
                log_message('debug', '[ML Auth] Novo Refresh Token recebido: ' . substr($data->refresh_token, 0, 15) . '...');

                // Tenta salvar os novos tokens no Banco de Dados usando o Model
                if (
                    $credentialsModel->updateTokens(
                        $data->access_token,
                        $data->refresh_token,
                        $data->expires_in ?? null, // Pega o tempo de expiração 'expires_in' se existir
                        'default' // Especifica a chave 'default' para atualização
                    )
                ) {
                    log_message('info', '[ML Auth] Tokens atualizados no Banco de Dados com sucesso.');
                    // Se você usa cache para os tokens, limpe-o aqui
                    // Ex: cache()->delete('ml_access_token');
                    return true; // Sucesso
                } else {
                    log_message('error', '[ML Auth] Falha ao salvar os novos tokens no Banco de Dados após recebê-los da API!');
                    // Mesmo recebendo da API, se não salvar no DB, considera falha.
                    return false; // Falha
                }

            } else {
                // Se o status não for 200 ou faltar algum token na resposta
                log_message('error', "[ML Auth] Falha ao atualizar token via API. Status: {$statusCode}, Resposta: {$body}");

                // Verifica especificamente se o erro foi 'invalid_grant' ou 'invalid_token' (refresh token inválido/expirado)
                if (isset($data->error) && ($data->error === 'invalid_grant' || $data->error === 'invalid_token')) {
                    log_message('critical', '[ML Auth] Refresh Token inválido, expirado ou revogado! Reautenticação manual é necessária.');
                    // TODO: Implementar notificação ao administrador aqui! É um erro crítico.
                    // Opcional: Limpar os tokens inválidos do banco para evitar tentativas repetidas
                    // $credentialsModel->updateTokens('', '', null, 'default');
                }
                // Outros erros da API (ex: client_id inválido, etc.) serão apenas logados.
                return false; // Falha
            }

            // Captura exceções gerais (problemas de rede, timeout, etc.)
        } catch (Throwable $e) {
            log_message('error', "[ML Auth] Exceção durante a tentativa de refresh do token: " . $e->getMessage());
            return false; // Falha
        }
    }

    /**
     * Obtém o Access Token atual do Banco de Dados. (Método auxiliar, pode não ser necessário fora desta classe)
     * @return string|null
     */
    public static function getAccessToken(): ?string
    {
        $credentialsModel = new MlCredentialsModel();
        $creds = $credentialsModel->getCredentials('default'); // Busca 'default'
        // ATENÇÃO: Este método NÃO verifica a expiração nem tenta o refresh.
        // A lógica de refresh está no WebhookController::getAccessToken()
        // ou poderia ser adicionada aqui se fosse usada em outros lugares.
        return $creds->access_token ?? null;
    }
}