<?php
namespace App\Models;
use CodeIgniter\Model;

class MlOrderModel extends Model
{
    protected $table = 'ml_orders';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'ml_order_id',
        'ml_item_id',
        'product_id',
        'ml_buyer_id',
        'status',
        'total_amount',
        'currency_id',
        'date_created',
        'date_closed',
        'delivery_status',
        // 'created_at' e 'updated_at' são gerenciados pelo DB
    ];
    protected $useTimestamps = false; // Estamos usando o 'on update' do DB
    protected $returnType = 'object';
}