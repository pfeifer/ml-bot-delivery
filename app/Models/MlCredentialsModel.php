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
    
    // 1. ATUALIZAR ALLOWED FIELDS
    protected $allowedFields    = [
        'app_name', // Renomeado
        'client_id',
        'client_secret',
        'redirect_uri',
        'is_active',
        'seller_id', 
        'access_token',
        'refresh_token',
        'expires_in',
        'token_updated_at',
        'last_updated'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // ... (validação, etc.) ...

    /**
     * Busca as credenciais pela chave (antigo).
     * @return object|null
     */
    public function getCredentials(string $key = 'default'): ?object
    {
        // Mantido para compatibilidade, mas o novo padrão é getActiveCredentials
        return $this->where('app_name', $key)->first();
    }
    
    /**
     * (NOVO) Busca a credencial marcada como 'is_active = 1'.
     * @return object|null
     */
    public function getActiveCredentials(): ?object
    {
        return $this->where('is_active', 1)->first();
    }

    /**
     * (NOVO) Define uma credencial como ativa e desativa todas as outras.
     * @param int $id ID da credencial a ser ativada
     * @return bool
     */
    public function setActive(int $id): bool
    {
        $this->db->transStart();
        
        // 1. Desativa todas
        $this->builder()->update(['is_active' => 0]); 
        
        // 2. Ativa a selecionada
        $this->builder()->where('id', $id)->update(['is_active' => 1]);
        
        return $this->db->transComplete();
    }

    /**
     * (MODIFICADO) Atualiza os tokens e o tempo de atualização no banco de dados.
     * Agora usa o ID em vez da $key.
     * @param int $id ID do registro a ser atualizado
     * @param string $accessToken
     * @param string $refreshToken
     * @param int|null $expiresIn
     * @return bool
     */
    public function updateTokens(int $id, string $accessToken, string $refreshToken, ?int $expiresIn = null): bool
    {
        $data = [
            'access_token'     => $accessToken,
            'refresh_token'    => $refreshToken,
            'expires_in'       => $expiresIn,
            'token_updated_at' => date('Y-m-d H:i:s'),
        ];

        return $this->update($id, $data);
    }

     /**
      * (MODIFICADO) Atualiza ou insere o seller_id.
      * Agora usa o ID em vez da $key.
      * @param int $id ID do registro a ser atualizado
      * @param int $sellerId
      * @return bool
      */
     public function saveSellerId(int $id, int $sellerId): bool
     {
         $data = [
             'seller_id'    => $sellerId,
             'last_updated' => date('Y-m-d H:i:s'),
         ];

         return $this->update($id, $data);
     }
}