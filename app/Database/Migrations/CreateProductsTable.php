<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [ 'type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true ],
            'ml_item_id' => [ 'type' => 'VARCHAR', 'constraint' => '30', 'unique' => true ], // ID do anúncio/item no ML
            'title' => [ 'type' => 'VARCHAR', 'constraint' => '255', 'null' => true ], // Título do anúncio ML (referência)
            'product_type' => [ 'type' => 'ENUM', 'constraint' => ['unique_code', 'static_link'], 'null' => false ], // Tipo: 'unique_code' ou 'static_link'
            'delivery_data' => [ 'type' => 'TEXT', 'null' => true ], // Link estático (criptografado)
            'created_at' => [ 'type' => 'DATETIME', 'null' => true ],
            'updated_at' => [ 'type' => 'DATETIME', 'null' => true ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('ml_item_id');
        $this->forge->createTable('products'); // Nome da tabela: products
    }

    public function down()
    {
        $this->forge->dropTable('products');
    }
}