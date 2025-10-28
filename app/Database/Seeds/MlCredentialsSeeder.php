<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MlCredentialsSeeder extends Seeder
{
    public function run()
    {
        // --- CAPTURA AS INFORMAÇÕES DO ARQUIVO .env ---
        // Certifique-se de que estas variáveis estão definidas no seu .env
        $initialAccessToken  = env('ml.access_token');
        $initialRefreshToken = env('ml.refresh_token');
        $yourSellerId        = env('ml.seller_id');
        // Você pode definir o expires_in no .env também, ou manter um padrão
        $expiresInSeconds    = env('ml.expires_in') ?: 21600; // Padrão 6 horas se não definido
        // --- FIM DA CAPTURA ---

        // Validação básica para garantir que as variáveis foram carregadas do .env
        if (empty($initialAccessToken) || empty($initialRefreshToken) || empty($yourSellerId)) {
            echo "ERRO: As variáveis ml.access_token, ml.refresh_token e ml.seller_id devem estar definidas no arquivo .env para executar este seeder.\n";
            return; // Interrompe a execução se alguma variável estiver faltando
        }

        $keyName = 'default';

        // Verifica se já existe um registro com key_name='default'
        $existing = $this->db->table('ml_credentials')->where('key_name', $keyName)->get()->getRow();

        $data = [
            'seller_id'        => (int)$yourSellerId, // Garante que seja inteiro
            'access_token'     => $initialAccessToken,
            'refresh_token'    => $initialRefreshToken,
            'expires_in'       => (int)$expiresInSeconds, // Garante que seja inteiro
            'token_updated_at' => date('Y-m-d H:i:s'), // Marca a hora da execução do seeder
            'last_updated'     => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            // Se já existe, atualiza
            echo "Registro '{$keyName}' encontrado. Atualizando com dados do .env...\n";
            // Adiciona updated_at para a atualização
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->table('ml_credentials')->where('key_name', $keyName)->update($data);
            echo "Registro '{$keyName}' atualizado com sucesso.\n";
        } else {
            // Se não existe, insere um novo registro
            echo "Registro '{$keyName}' não encontrado. Inserindo dados do .env...\n";
            $data['key_name']   = $keyName;
            // Adiciona created_at e updated_at para a inserção
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->table('ml_credentials')->insert($data);
            echo "Registro '{$keyName}' inserido com sucesso.\n";
        }

        echo "Seeder MlCredentialsSeeder concluído.\n";
    }
}