<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMlCredentialsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            // 'key_name' pode ser usado se você gerenciar múltiplas contas ML
            // Para uma única conta, podemos usar um valor fixo como 'default'
            'key_name' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'unique' => true, // Garante apenas uma entrada por chave
                'default' => 'default',
            ],
            'access_token' => [
                'type' => 'TEXT', // Access tokens podem ser longos
                'null' => true,
            ],
            'refresh_token' => [
                'type' => 'TEXT', // Refresh tokens também
                'null' => true,
            ],
            'expires_in' => [ // Tempo em segundos até expirar (vindo da API)
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'token_updated_at' => [ // Quando o token foi atualizado pela última vez
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('ml_credentials');

        // Opcional: Inserir uma linha inicial se não existir
        // (Você pode fazer isso manualmente ou via Seeder também)
        $this->db->table('ml_credentials')->insert([
            'key_name' => 'default',
            'access_token' => env('ml.accessToken'), // Pega valor inicial do .env
            'refresh_token' => env('ml.refreshToken'), // Pega valor inicial do .env
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('ml_credentials');
    }
}