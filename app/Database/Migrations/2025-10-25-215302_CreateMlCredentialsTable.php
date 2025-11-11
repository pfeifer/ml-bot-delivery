<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql; // Importar RawSql

/**
 * Cria a tabela 'ml_credentials' com todos os campos necessários
 * para armazenar múltiplas aplicações (client_id, client_secret, etc.)
 * e um switch (is_active).
 */
class CreateMlCredentialsTable extends Migration
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
            'app_name' => [ // Nome amigável (ex: "Loja Principal", "Loja Teste")
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => false,
            ],
            'client_id' => [ // O APP_ID do ML
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => false,
            ],
            'client_secret' => [ // O Client Secret (será guardado encriptado)
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => false,
            ],
            'redirect_uri' => [ // A URL de callback
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => false,
            ],
            'is_active' => [ // O "switch" (0 = inativo, 1 = ativo)
                'type'    => 'BOOLEAN',
                'default' => false,
                'null'    => false,
            ],
            'seller_id' => [ // ID do Vendedor vinculado
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
            'token_updated_at' => [ // Quando o token foi atualizado/obtido
                'type' => 'DATETIME',
                'null' => true,
            ],
            'last_updated' => [ // Campo de auditoria
                 'type' => 'DATETIME',
                 'null' => true,
             ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type'      => 'DATETIME',
                'null'      => true,
                'on update' => new RawSql('CURRENT_TIMESTAMP'), // Atualiza sozinho
            ],
        ]);
        
        $this->forge->addKey('id', true); // Chave Primária
        $this->forge->addKey('app_name'); // Índice para busca
        $this->forge->addKey('is_active'); // Índice para o "switch"
        $this->forge->addKey('seller_id'); // Índice
        
        $this->forge->createTable('ml_credentials');
    }

    public function down()
    {
        $this->forge->dropTable('ml_credentials');
    }
}