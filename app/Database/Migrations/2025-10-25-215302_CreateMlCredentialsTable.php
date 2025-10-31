<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMlCredentialsTable extends Migration // Certifique-se que o nome da classe está correto
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'key_name' => [ // Mantém a chave para identificar o registro (útil se um dia tiver mais contas)
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'unique'     => true,
                'default'    => 'default', // Ou pode deixar nulo se preferir
            ],
            'seller_id' => [
                'type'       => 'BIGINT',
                'unsigned'   => true,
                'null'       => true, // Permite ser nulo até ser preenchido pela API
            ],
            'access_token' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'refresh_token' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'expires_in' => [ // Tempo em segundos retornado pela API
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'token_updated_at' => [ // Quando o token foi atualizado/obtido pela última vez
                'type' => 'DATETIME',
                'null' => true,
            ],
            // Substituí token_expires_at por token_updated_at + expires_in
            // porque a API geralmente retorna 'expires_in' (duração)

            'created_at' => [ // Gerenciado pelo Model agora
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [ // Gerenciado pelo Model agora
                'type' => 'DATETIME',
                'null' => true,
            ],
            // Adicionei last_updated para a lógica do getSellerId()
             'last_updated' => [
                 'type' => 'DATETIME',
                 'null' => true,
             ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('seller_id'); // Adiciona índice para seller_id
        $this->forge->createTable('ml_credentials');
    }

    public function down()
    {
        $this->forge->dropTable('ml_credentials');
    }
}