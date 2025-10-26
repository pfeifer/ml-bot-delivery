<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
//$routes->get('/', 'Home::index');
$routes->get('/', 'AuthController::loginForm', ['as' => 'home']);
$routes->post('webhook/ml', 'WebhookController::handle');
// Rotas de Autenticação
$routes->get('login', 'AuthController::loginForm', ['as' => 'login']); // Rota nomeada 'login'
$routes->post('login', 'AuthController::attemptLogin');
$routes->get('logout', 'AuthController::logout');
// Grupo de Rotas do Admin (Protegido por Filtro - a criar)
$routes->group('admin', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Admin\DashboardController::index', ['as' => 'admin.dashboard']);
    // Produtos
    $routes->get('products', 'Admin\ProductsController::index', ['as' => 'admin.products']);
    $routes->get('products/new', 'Admin\ProductsController::new', ['as' => 'admin.products.new']);
    $routes->post('products/create', 'Admin\ProductsController::create', ['as' => 'admin.products.create']);
    $routes->get('products/edit/(:num)', 'Admin\ProductsController::edit/$1', ['as' => 'admin.products.edit']);
    $routes->post('products/update/(:num)', 'Admin\ProductsController::update/$1', ['as' => 'admin.products.update']);
    $routes->get('products/delete/(:num)', 'Admin\ProductsController::delete/$1', ['as' => 'admin.products.delete']); // Usar POST/DELETE seria melhor
    // Estoque
    $routes->get('stock', 'Admin\StockController::index', ['as' => 'admin.stock']);
    $routes->post('stock/add', 'Admin\StockController::add', ['as' => 'admin.stock.add']);
});
