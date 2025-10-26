<?php

namespace App\Models;

use CodeIgniter\Model;

class MlCredentialsModel extends Model
{
    protected $table            = 'ml_credentials';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object'; // Retorna como objeto stdClass
    protected $useSoftDeletes   = false;
    protected $protectFields    = true; // Protege contra mass assignment
    protected $allowedFields    = [
        'key_name',
        'access_token',
        'refresh_token',
        'expires_in',
        'token_updated_at' // Adicionado para rastrear atualização
    ];

    // Dates
    protected $useTimestamps = true; // Habilita created_at e updated_at
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    // protected $deletedField  = 'deleted_at'; // Não usado

    // Validation - Adicione regras se necessário
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
     * Atualiza os tokens no banco de dados.
     * @param string $accessToken
     * @param string $refreshToken
     * @param int|null $expiresIn
     * @param string $key
     * @return bool
     */
    public function updateTokens(string $accessToken, string $refreshToken, ?int $expiresIn = null, string $key = 'default'): bool
    {
        $data = [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $expiresIn,
            'token_updated_at' => date('Y-m-d H:i:s'), // Marca quando foi atualizado
        ];

        // Tenta atualizar a linha existente
        $builder = $this->where('key_name', $key);
        if ($builder->countAllResults(false) > 0) { // Verifica se existe sem resetar a query
            return $builder->set($data)->update();
        } else {
            // Se não existir, insere (deveria existir após migration/seed)
            log_message('warning', "[MlCredentialsModel] Tentativa de atualizar tokens para chave '{$key}' inexistente. Inserindo nova linha.");
            $data['key_name'] = $key; // Garante que a chave está presente
            return $this->insert($data);
        }
    }
}