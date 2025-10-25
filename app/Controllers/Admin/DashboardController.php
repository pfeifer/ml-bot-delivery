<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use App\Models\StockCodeModel;

class DashboardController extends BaseController
{
    public function index()
    {
        // Futuramente, você pode carregar dados aqui (contagens, etc.)
        $productModel = new ProductModel();
        $stockModel = new StockCodeModel();
        // Prepara os dados para enviar para a view
        $data = [
            'product_count' => $productModel->countAllResults(), // Conta todos os produtos
            'stock_count' => $stockModel->where('is_sold', false)->countAllResults() // Conta apenas códigos NÃO vendidos
        ];
        // Passa os dados ($data) para a view
        return view('admin/dashboard', $data);
    }
}