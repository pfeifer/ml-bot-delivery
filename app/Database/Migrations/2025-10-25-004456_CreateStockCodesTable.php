<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql; // Importar RawSql para usar CURRENT_TIMESTAMP

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
            'created_at' => [ // Novo campo
                'type'    => 'DATETIME',
                'default' => new RawSql('CURRENT_TIMESTAMP'), // Define o valor padrão como a data/hora atual
                'null'    => false,
            ],
            'expires_at' => [ // Novo campo
                'type' => 'DATE', // Ou DATETIME se precisar de hora
                'null' => true,    // Permite que seja nulo se não houver data de expiração
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE'); // Chave estrangeira
        $this->forge->addKey('ml_order_id');
        // Adicionar índices pode ajudar na performance da ordenação
        $this->forge->addKey('created_at');
        $this->forge->addKey('expires_at');
        $this->forge->createTable('stock_codes'); // Nome da tabela: stock_codes
    }

    public function down()
    {
        $this->forge->dropTable('stock_codes');
    }
}