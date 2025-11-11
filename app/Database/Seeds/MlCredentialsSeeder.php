<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Config\Services; // Importar Services para usar o Encrypter

class MlCredentialsSeeder extends Seeder
{
    public function run()
    {
        // --- CAPTURA AS INFORMAÇÕES DO ARQUIVO .env ---
        $appName             = env('ml.app_name', 'App Padrão');
        $clientId            = env('ml.client_id');
        $clientSecret        = env('ml.client_secret'); // Chave secreta crua
        $redirectUri         = env('ml.redirect_uri'); // Ex: http://localhost:8080/admin/mercadolivre/callback
        
        $initialAccessToken  = env('ml.access_token');
        $initialRefreshToken = env('ml.refresh_token');
        $yourSellerId        = env('ml.seller_id');
        $expiresInSeconds    = env('ml.expires_in') ?: 21600;

        // Validação básica
        if (empty($clientId) || empty($clientSecret) || empty($redirectUri)) {
            echo "ERRO: As variáveis ml.client_id, ml.client_secret e ml.redirect_uri DEVEM estar definidas no .env para o seeder.\n";
            return;
        }

        // Pega o serviço de encriptação
        $encrypter = Services::encrypter();
        
        // Encripta o client_secret
        $encryptedClientSecret = $encrypter->encrypt($clientSecret);

        $keyName = $appName; // Usamos o app_name como chave de busca

        // Verifica se já existe um registro com esse app_name
        $existing = $this->db->table('ml_credentials')->where('app_name', $keyName)->get()->getRow();

        $data = [
            'client_id'        => $clientId,
            'client_secret'    => $encryptedClientSecret, // Salva o valor encriptado
            'redirect_uri'     => $redirectUri,
            'seller_id'        => empty($yourSellerId) ? null : (int)$yourSellerId,
            'access_token'     => empty($initialAccessToken) ? null : $initialAccessToken,
            'refresh_token'    => empty($initialRefreshToken) ? null : $initialRefreshToken,
            'expires_in'       => (int)$expiresInSeconds,
            'token_updated_at' => empty($initialAccessToken) ? null : date('Y-m-d H:i:s'),
            'last_updated'     => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            // Se já existe, atualiza (não sobrescreve tokens se já existirem)
            echo "Registro '{$keyName}' encontrado. Atualizando dados do .env (exceto tokens existentes)...\n";
            
            // Remove dados de token se eles já existirem no DB
            if (!empty($existing->refresh_token)) {
                unset($data['access_token']);
                unset($data['refresh_token']);
                unset($data['expires_in']);
                unset($data['token_updated_at']);
            }
            
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->table('ml_credentials')->where('app_name', $keyName)->update($data);
            echo "Registro '{$keyName}' atualizado com sucesso.\n";
        } else {
            // Se não existe, insere e marca como ATIVO
            echo "Registro '{$keyName}' não encontrado. Inserindo dados do .env...\n";
            $data['app_name']   = $keyName;
            $data['is_active']  = true; // Ativa o primeiro registro
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->table('ml_credentials')->insert($data);
            echo "Registro '{$keyName}' inserido com sucesso e marcado como ATIVO.\n";
        }

        echo "Seeder MlCredentialsSeeder concluído.\n";
    }
}