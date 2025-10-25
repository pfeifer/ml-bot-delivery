<?php namespace App\Controllers\Admin;

use App\Controllers\BaseController; // Importante usar o BaseController

class DashboardController extends BaseController
{
    public function index()
    {
        // Futuramente, você pode carregar dados aqui (contagens, etc.)
        // $productModel = new \App\Models\ProductModel();
        // $stockModel = new \App\Models\StockCodeModel();
        // $data['product_count'] = $productModel->countAllResults();
        // $data['stock_count'] = $stockModel->where('is_sold', false)->countAllResults();

        return view('admin/dashboard'); // Carrega a view do dashboard
    }
}