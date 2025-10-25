<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStockCodesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'product_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true], // FK para products.id
            'code' => ['type' => 'VARCHAR', 'constraint' => '255'], // Código/Voucher (criptografado)
            'is_sold' => ['type' => 'BOOLEAN', 'default' => false], // Já foi vendido?
            'sold_at' => ['type' => 'DATETIME', 'null' => true], // Data da venda
            'ml_order_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true], // ID da ordem ML (rastreio)
            'ml_buyer_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true], // ID do comprador ML (rastreio)
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE'); // Chave estrangeira
        $this->forge->addKey('ml_order_id');
        $this->forge->createTable('stock_codes'); // Nome da tabela: stock_codes
    }

    public function down()
    {
        $this->forge->dropTable('stock_codes');
    }
}