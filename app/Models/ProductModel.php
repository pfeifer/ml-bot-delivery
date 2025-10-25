<?php
namespace App\Models;
use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table = 'products'; // Tabela correspondente
    protected $primaryKey = 'id';
    protected $allowedFields = ['ml_item_id', 'title', 'product_type', 'delivery_data']; // Campos permitidos

    // Habilitar timestamps (created_at, updated_at)
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}