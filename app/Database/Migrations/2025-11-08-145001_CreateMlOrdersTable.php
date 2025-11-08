<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateMlOrdersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [ // Nosso ID interno
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'ml_order_id' => [ // ID do Pedido no ML
                'type'       => 'BIGINT',
                'unsigned'   => true,
                'unique'     => true, // Garantir que só há um registro por pedido
            ],
            'ml_item_id' => [ // ID do Item no ML (principal)
                'type'       => 'VARCHAR',
                'constraint' => '30',
                'null'       => true,
            ],
            'product_id' => [ // ID do nosso produto local (FK para products.id)
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'ml_buyer_id' => [ // ID do Comprador no ML
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'status' => [ // Status do Pedido (paid, pending, cancelled)
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
            ],
            'total_amount' => [ // Valor total
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
            ],
            'currency_id' => [
                'type'       => 'VARCHAR',
                'constraint' => '10',
                'null'       => true,
            ],
            'date_created' => [ // Data de criação no ML
                'type' => 'DATETIME',
                'null' => true,
            ],
            'date_closed' => [ // Data de fechamento/pagamento no ML
                'type' => 'DATETIME',
                'null' => true,
            ],
            'delivery_status' => [ // Status da nossa entrega digital
                'type'       => 'ENUM',
                'constraint' => ['pending', 'delivered', 'failed', 'out_of_stock'],
                'default'    => 'pending',
            ],
            'created_at' => [ // Data de criação no NOSSO DB
                'type'    => 'DATETIME',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [ // Data de atualização no NOSSO DB
                'type' => 'DATETIME',
                'null' => true,
                'on update' => new RawSql('CURRENT_TIMESTAMP'), // Atualiza automaticamente
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('ml_item_id');
        $this->forge->addKey('product_id');
        $this->forge->addKey('status');
        $this->forge->addKey('delivery_status');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('ml_orders');
    }

    public function down()
    {
        $this->forge->dropTable('ml_orders');
    }
}