<?php
namespace App\Models;
use CodeIgniter\Model;

class StockCodeModel extends Model
{
    protected $table = 'stock_codes';
    protected $primaryKey = 'id';
    // Adicione 'expires_at' e 'created_at' se necessário
    protected $allowedFields = [
        'product_id',
        'code',
        'is_sold',
        'sold_at',
        'ml_order_id',
        'ml_buyer_id',
        'expires_at', // Adicionado
        // 'created_at', // Adicione se não usar valor padrão do DB
    ];
    // Timestamps não são usados aqui por padrão, mas você pode adicionar se precisar
    // protected $useTimestamps = false;
    protected $returnType = 'object';
}