<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MlOrderModel; // <-- USA O NOVO MODEL

class OrdersController extends BaseController
{
    protected $orderModel;

    public function __construct()
    {
        $this->orderModel = new MlOrderModel(); // <-- USA O NOVO MODEL
    }

    /**
     * Lista todos os pedidos que recebemos notificação.
     */
    public function index()
    {
        $data['orders'] = $this->orderModel
            ->select('ml_orders.*, products.title')
            ->join('products', 'products.id = ml_orders.product_id', 'left')
            ->orderBy('ml_orders.date_created', 'DESC') // Ordena pelos mais recentes
            ->findAll();

        return view('admin/orders_list', $data);
    }
}