<?php

namespace App\Controllers\Traits;

use App\Libraries\MercadoLivreAuth;
use App\Models\MlCredentialsModel;
use Config\Services;

/**
 * Trait MercadoLivreAuthTrait
 *
 * Centraliza a lógica de obtenção e atualização de tokens
 * do Mercado Livre para ser usada por múltiplos controladores.
 *
 * O Controller que usar este Trait DEVE instanciar:
 * protected $credentialsModel;
 *
 * E em seu __construct():
 * $this->credentialsModel = new MlCredentialsModel();
 */
trait MercadoLivreAuthTrait
{
    private ?string $accessToken = null;
    private ?object $credentials = null;
    private ?int $sellerId = null;

    /**
     * Obtém as credenciais do banco de dados (uma única vez por requisição).
     *
     * @return object|null
     */
    private function getDbCredentials(): ?object
    {
        if ($this->credentials === null) {
            // Garante que o credentialsModel foi instanciado no Controller
            if (!property_exists($this, 'credentialsModel') || !$this->credentialsModel instanceof MlCredentialsModel) {
                log_message('critical', 'MercadoLivreAuthTrait: $this->credentialsModel não foi instanciado no controller.');
                return null;
            }

            $this->credentials = $this->credentialsModel->getCredentials('default');

            if ($this->credentials === null) {
                log_message('error', "[MercadoLivreAuthTrait] Nenhum registro encontrado em ml_credentials para key_name='default'. O Seeder 'MlCredentialsSeeder' deve ser executado.");
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

        $dbCredentials = $this->getDbCredentials();

        if ($dbCredentials && !empty($dbCredentials->access_token)) {
            $tokenUpdatedAt = strtotime($dbCredentials->token_updated_at ?? '1970-01-01');
            $expiresIn = $dbCredentials->expires_in ?? 0;
            $now = time();

            // Verifica se o token expirou (com 5 minutos de margem de segurança)
            if (($tokenUpdatedAt + $expiresIn - 300) < $now) {
                log_message('info', "[MercadoLivreAuthTrait] Access Token expirado. Tentando refresh...");

                if (MercadoLivreAuth::refreshToken()) {
                    log_message('info', "[MercadoLivreAuthTrait] Refresh Token bem-sucedido. Recarregando credenciais.");
                    // Força o recarregamento das credenciais do DB na próxima chamada
                    $this->credentials = null;
                    $dbCredentials = $this->getDbCredentials();
                    
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
                // Token ainda é válido
                $this->accessToken = $dbCredentials->access_token;
                log_message('debug', '[MercadoLivreAuthTrait] Access Token válido obtido do banco.');
            }
        } else {
             if ($dbCredentials && empty($dbCredentials->refresh_token)) {
                log_message('error', "[MercadoLivreAuthTrait] Access Token não encontrado no DB e SEM Refresh Token. Execute o Seeder.");
            } else {
                log_message('error', "[MercadoLivreAuthTrait] Access Token não encontrado e/ou falha ao renovar.");
            }
            $this->accessToken = null;
        }
        return $this->accessToken;
    }

    /**
     * Obtém o Seller ID.
     * Tenta buscar da API e salvar no DB se não estiver presente.
     *
     * @return int|null
     */
    private function getSellerId(): ?int
    {
        if ($this->sellerId !== null) {
            return $this->sellerId;
        }

        $dbCredentials = $this->getDbCredentials();
        
        if ($dbCredentials && !empty($dbCredentials->seller_id)) {
            $this->sellerId = (int) $dbCredentials->seller_id;
            log_message('debug', "[MercadoLivreAuthTrait] Seller ID obtido do DB: " . $this->sellerId);
            return $this->sellerId;
        } 
        
        // Se não encontrou no DB, busca na API (lógica de setup)
        log_message('warning', "[MercadoLivreAuthTrait] Seller ID não encontrado no DB. Buscando via API /users/me para salvar...");
        $sellerIdFromApi = $this->fetchAndSaveSellerId();
        
        if ($sellerIdFromApi) {
            $this->sellerId = $sellerIdFromApi;
            return $this->sellerId;
        }

        log_message('error', "[MercadoLivreAuthTrait] Não foi possível obter o Seller ID.");
        return null;
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
        
        try {
            $httpClient = Services::curlrequest(['baseURI' => 'https://api.mercadolibre.com/', 'timeout' => 10, 'http_errors' => false]);
            $response = $httpClient->get('users/me', [
                'headers' => ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json']
            ]);

            if ($response->getStatusCode() !== 200) {
                log_message('error', "[MercadoLivreAuthTrait::fetchAndSaveSellerId] Falha ao buscar /users/me. Status: " . $response->getStatusCode() . " Body: " . $response->getBody());
                return null;
            }
            
            $userData = json_decode($response->getBody());
            if (json_last_error() !== JSON_ERROR_NONE || !isset($userData->id)) {
                log_message('error', "[MercadoLivreAuthTrait::fetchAndSaveSellerId] Falha ao decodificar JSON ou ID ausente /users/me.");
                return null;
            }
            
            $fetchedSellerId = (int) $userData->id;
            log_message('info', "[MercadoLivreAuthTrait::fetchAndSaveSellerId] Seller ID obtido da API: " . $fetchedSellerId . ". Salvando no DB...");

            if ($this->credentialsModel->saveSellerId($fetchedSellerId, 'default')) {
                log_message('info', "[MercadoLivreAuthTrait::fetchAndSaveSellerId] Seller ID {$fetchedSellerId} salvo no DB.");
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