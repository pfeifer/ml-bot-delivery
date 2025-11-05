<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [ 'type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true ],
            'ml_item_id' => [ 'type' => 'VARCHAR', 'constraint' => '30', 'unique' => true ], // ID do anúncio/item no ML
            'title' => [ 'type' => 'VARCHAR', 'constraint' => '255', 'null' => true ], // Título do anúncio ML (referência)
            'product_type' => [ 'type' => 'ENUM', 'constraint' => ['code', 'link'], 'null' => false ], // Tipo: 'code' ou 'link'
            'delivery_data' => [ 'type' => 'TEXT', 'null' => true ], // Link estático (criptografado)
            'message_template_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true, // Permite ser nulo
            ],
            'created_at' => [ 'type' => 'DATETIME', 'null' => true ],
            'updated_at' => [ 'type' => 'DATETIME', 'null' => true ],
        ]);
        $this->forge->addKey('id', true);
        
        // Adicionamos a chave estrangeira aqui, mas sem a constraint ainda,
        // porque a tabela 'message_templates' pode não existir neste ponto da migração.
        // Vamos adicionar a constraint na migration de 'message_templates'.
        $this->forge->addKey('message_template_id'); 
        
        $this->forge->createTable('products'); // Nome da tabela: products
    }

    public function down()
    {
        $this->forge->dropTable('products');
    }
}