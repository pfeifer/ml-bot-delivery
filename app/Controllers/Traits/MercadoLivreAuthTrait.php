<?php

namespace App\Controllers\Traits;

use App\Libraries\MercadoLivreAuth;
use App\Models\MlCredentialsModel;
use Config\Services;

// ... (docblock) ...
trait MercadoLivreAuthTrait
{
    private ?string $accessToken = null;
    private ?object $credentials = null;
    private ?int $sellerId = null;

    /**
     * (MODIFICADO) Obtém as credenciais ATIVAS do banco de dados.
     *
     * @return object|null
     */
    private function getDbCredentials(): ?object // O nome do método é mantido por compatibilidade interna
    {
        if ($this->credentials === null) {
            if (!property_exists($this, 'credentialsModel') || !$this->credentialsModel instanceof MlCredentialsModel) {
                log_message('critical', 'MercadoLivreAuthTrait: $this->credentialsModel não foi instanciado no controller.');
                return null;
            }

            // A MUDANÇA PRINCIPAL ESTÁ AQUI
            $this->credentials = $this->credentialsModel->getActiveCredentials();

            if ($this->credentials === null) {
                log_message('error', "[MercadoLivreAuthTrait] Nenhuma credencial ATIVA (is_active = 1) encontrada em ml_credentials. Verifique o painel de Configurações do ML.");
            }
        }
        return $this->credentials;
    }

    /**
     * Obtém o Access Token, priorizando o DB e implementando refresh se necessário.
     *
     * @return string|null
     */
    private function getAccessToken(): ?string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $dbCredentials = $this->getDbCredentials(); // <-- Isto agora pega o ATIVO

        if ($dbCredentials && !empty($dbCredentials->access_token)) {
            $tokenUpdatedAt = strtotime($dbCredentials->token_updated_at ?? '1970-01-01');
            $expiresIn = $dbCredentials->expires_in ?? 0;
            $now = time();

            if (($tokenUpdatedAt + $expiresIn - 300) < $now) {
                log_message('info', "[MercadoLivreAuthTrait] Access Token expirado para (ID: {$dbCredentials->id}). Tentando refresh...");

                // O refresh (estático) agora também usará a credencial ativa (ver Passo 5)
                if (MercadoLivreAuth::refreshToken()) {
                    log_message('info', "[MercadoLivreAuthTrait] Refresh Token bem-sucedido. Recarregando credenciais.");
                    $this->credentials = null;
                    $dbCredentials = $this->getDbCredentials(); // Recarrega (deve vir o novo)
                    
                    if ($dbCredentials && !empty($dbCredentials->access_token)) {
                        $this->accessToken = $dbCredentials->access_token;
                        log_message('debug', '[MercadoLivreAuthTrait] Novo Access Token carregado.');
                    } else {
                        log_message('error', "[MercadoLivreAuthTrait] Falha ao carregar o novo Access Token do banco após o refresh.");
                        $this->accessToken = null;
                    }
                } else {
                    log_message('error', "[MercadoLivreAuthTrait] Falha ao executar MercadoLivreAuth::refreshToken().");
                    $this->accessToken = null;
                }
            } else {
                $this->accessToken = $dbCredentials->access_token;
                log_message('debug', '[MercadoLivreAuthTrait] Access Token válido obtido do banco.');
            }
        } else {
             if ($dbCredentials && empty($dbCredentials->refresh_token)) {
                log_message('error', "[MercadoLivreAuthTrait] Access Token não encontrado no DB e SEM Refresh Token. É preciso autorizar o app.");
            } else {
                log_message('error', "[MercadoLivreAuthTrait] Access Token não encontrado e/ou falha ao renovar.");
            }
            $this->accessToken = null;
        }
        return $this->accessToken;
    }

    /**
     * Obtém o Seller ID.
     *
     * @return int|null
     */
    private function getSellerId(): ?int
    {
        // ... (lógica interna do getSellerId) ...
        // (MODIFICADO O FINAL DO MÉTODO fetchAndSaveSellerId)
    }

    /**
     * Auxiliar para buscar Seller ID da API e salvar no DB.
     *
     * @return int|null
     */
    private function fetchAndSaveSellerId(): ?int
    {
        $token = $this->getAccessToken();
        if (!$token) {
            log_message('error', "[MercadoLivreAuthTrait::fetchAndSaveSellerId] Não foi possível obter Access Token.");
            return null;
        }
        
        $dbCredentials = $this->getDbCredentials(); // Pega as credenciais ativas
        if (!$dbCredentials) {
            log_message('error', "[MercadoLivreAuthTrait::fetchAndSaveSellerId] Não foi possível encontrar credenciais ativas.");
            return null;
        }
        
        try {
            // ... (lógica do $httpClient e $response) ...
            $httpClient = Services::curlrequest(['baseURI' => 'https://api.mercadolibre.com/', 'timeout' => 10, 'http_errors' => false]);
            $response = $httpClient->get('users/me', [
                'headers' => ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json']
            ]);

            if ($response->getStatusCode() !== 200) {
                 log_message('error', "[MercadoLivreAuthTrait::fetchAndSaveSellerId] Falha ao buscar /users/me. Status: " . $response->getStatusCode() . " Body: " . $response->getBody());
                return null;
            }
            
            $userData = json_decode($response->getBody());
            // ... (validação do json) ...
            
            $fetchedSellerId = (int) $userData->id;
            log_message('info', "[MercadoLivreAuthTrait::fetchAndSaveSellerId] Seller ID obtido da API: " . $fetchedSellerId . ". Salvando no DB...");

            // (MODIFICADO) Salva o Seller ID no registro de credencial ATIVO
            if ($this->credentialsModel->saveSellerId($dbCredentials->id, $fetchedSellerId)) {
                log_message('info', "[MercadoLivreAuthTrait::fetchAndSaveSellerId] Seller ID {$fetchedSellerId} salvo no DB para o App ID {$dbCredentials->id}.");
                if ($this->credentials) {
                    $this->credentials->seller_id = $fetchedSellerId;
                }
                return $fetchedSellerId;
            } 
            
            log_message('error', "[MercadoLivreAuthTrait::fetchAndSaveSellerId] Falha ao salvar Seller ID {$fetchedSellerId} no DB.");
            return null;

        } catch (\Throwable $e) {
            log_message('error', "[MercadoLivreAuthTrait::fetchAndSaveSellerId] Exceção: " . $e->getMessage());
            return null;
        }
    }
}