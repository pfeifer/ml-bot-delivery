<?php

namespace App\Models;

use CodeIgniter\Model;

class MlCredentialsModel extends Model
{
    protected $table            = 'ml_credentials';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'key_name',
        'seller_id', // << Adicionado
        'access_token',
        'refresh_token',
        'expires_in',
        'token_updated_at',
        'last_updated' // << Adicionado
    ];

    // Dates
    protected $useTimestamps = true; // Mantém gerenciando created_at e updated_at
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    // protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Busca as credenciais pela chave (default).
     * @return object|null
     */
    public function getCredentials(string $key = 'default'): ?object
    {
        return $this->where('key_name', $key)->first();
    }

    /**
     * Atualiza os tokens e o tempo de atualização no banco de dados.
     * @param string $accessToken
     * @param string $refreshToken
     * @param int|null $expiresIn
     * @param string $key
     * @return bool
     */
    public function updateTokens(string $accessToken, string $refreshToken, ?int $expiresIn = null, string $key = 'default'): bool
    {
        $data = [
            'access_token'     => $accessToken,
            'refresh_token'    => $refreshToken,
            'expires_in'       => $expiresIn,
            'token_updated_at' => date('Y-m-d H:i:s'), // Marca quando foi atualizado
        ];

        // Tenta encontrar pelo key_name para obter o ID correto para atualização
        $credential = $this->where('key_name', $key)->first();

        if ($credential) {
            // Atualiza a linha existente usando o ID
             return $this->update($credential->id, $data);
        } else {
            // Se não existir, insere (deveria existir após migration/seed)
            log_message('warning', "[MlCredentialsModel] Tentativa de atualizar tokens para chave '{$key}' inexistente. Inserindo nova linha.");
            $data['key_name'] = $key; // Garante que a chave está presente
            return $this->insert($data);
        }
    }

     /**
      * Atualiza ou insere o seller_id para uma chave específica.
      * @param int $sellerId
      * @param string $key
      * @return bool
      */
     public function saveSellerId(int $sellerId, string $key = 'default'): bool
     {
         $data = [
             'seller_id'    => $sellerId,
             'last_updated' => date('Y-m-d H:i:s'),
         ];

         $credential = $this->where('key_name', $key)->first();

         if ($credential) {
             return $this->update($credential->id, $data);
         } else {
             log_message('warning', "[MlCredentialsModel] Tentativa de salvar seller_id para chave '{$key}' inexistente. Inserindo nova linha.");
             $data['key_name'] = $key;
             return $this->insert($data);
         }
     }
}