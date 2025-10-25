<?php
namespace App\Models;
use CodeIgniter\Model;

class StockCodeModel extends Model
{
    protected $table = 'stock_codes';
    protected $primaryKey = 'id';
    protected $allowedFields = ['product_id', 'code', 'is_sold', 'sold_at', 'ml_order_id', 'ml_buyer_id']; // Campos permitidos
    // Timestamps não são usados aqui por padrão, mas você pode adicionar se precisar
    // protected $useTimestamps = false; 
    protected $returnType = 'object';
}