<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateMessageTemplatesTable extends Migration
{
    public function up()
    {
        // 1. Cria a tabela de templates
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [ // Um nome para identificar o template na admin
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => false,
            ],
            'content' => [ // O conteúdo da mensagem em si
                'type' => 'TEXT',
                'null' => false,
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
                'null'    => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('name');
        $this->forge->createTable('message_templates');

        // 2. AGORA que a tabela 'message_templates' existe,
        //    adicionamos a Foreign Key na tabela 'products'
        $this->forge->addForeignKey(
            'message_template_id',  // Coluna na tabela 'products'
            'message_templates',    // Tabela de destino
            'id',                   // Coluna de destino
            'NO ACTION',            // ON UPDATE
            'SET NULL'              // ON DELETE (Se apagar o template, o produto fica com ID nulo)
        );
    }

    public function down()
    {
        // Remove a FK primeiro
        try {
             $this->forge->dropForeignKey('products', 'products_message_template_id_foreign');
         } catch (\Throwable $e) {
             log_message('info', 'FK products_message_template_id_foreign não encontrada ou erro ao remover: ' . $e->getMessage());
         }

        // Depois apaga a tabela de templates
        $this->forge->dropTable('message_templates');
    }
}