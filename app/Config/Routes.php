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

// Grupo de Rotas do Admin (Protegido por Filtro 'auth')
$routes->group('admin', ['filter' => 'auth'], static function ($routes) {
    
    $routes->get('/', 'Admin\DashboardController::index', ['as' => 'admin.dashboard']);

    // Produtos
    $routes->get('products', 'Admin\ProductsController::index', ['as' => 'admin.products']);
    $routes->get('products/new', 'Admin\ProductsController::new', ['as' => 'admin.products.new']);
    $routes->post('products/create', 'Admin\ProductsController::create', ['as' => 'admin.products.create']);
    $routes->get('products/edit/(:num)', 'Admin\ProductsController::edit/$1', ['as' => 'admin.products.edit']);
    $routes->post('products/update/(:num)', 'Admin\ProductsController::update/$1', ['as' => 'admin.products.update']);
    $routes->get('products/delete/(:num)', 'Admin\ProductsController::delete/$1', ['as' => 'admin.products.delete']);
    $routes->post('products/delete-batch', 'Admin\ProductsController::deleteBatch', ['as' => 'admin.products.delete.batch']);

    // Estoque
    $routes->get('stock', 'Admin\StockController::index', ['as' => 'admin.stock']);
    $routes->get('stock/new', 'Admin\StockController::new', ['as' => 'admin.stock.new']);
    $routes->post('stock/add', 'Admin\StockController::add', ['as' => 'admin.stock.add']);
    $routes->post('stock/delete-batch', 'Admin\StockController::deleteBatch', ['as' => 'admin.stock.delete.batch']);

    // --- ROTA PRINCIPAL DO MERCADO LIVRE (COM TABS) ---
    $routes->get('mercadolivre', 'Admin\MercadoLivreController::index', ['as' => 'admin.mercadolivre.settings']);

    // --- ROTAS PARA TEMPLATES DE MENSAGEM (CRUD) ---
    $routes->group('message-templates', static function ($routes) {
        $routes->get('/', 'Admin\MercadoLivreController::index', ['as' => 'admin.message_templates']);
        $routes->get('new', 'Admin\MessageTemplatesController::new', ['as' => 'admin.message_templates.new']);
        $routes->post('create', 'Admin\MessageTemplatesController::create', ['as' => 'admin.message_templates.create']);
        $routes->get('edit/(:num)', 'Admin\MessageTemplatesController::edit/$1', ['as' => 'admin.message_templates.edit']);
        $routes->post('update/(:num)', 'Admin\MessageTemplatesController::update/$1', ['as' => 'admin.message_templates.update']);
        $routes->get('delete/(:num)', 'Admin\MessageTemplatesController::delete/$1', ['as' => 'admin.message_templates.delete']);
        $routes->post('delete-batch', 'Admin\MessageTemplatesController::deleteBatch', ['as' => 'admin.message_templates.delete.batch']);
    }); // Fim do grupo message-templates

}); // Fim do grupo admin


/*
 * --------------------------------------------------------------------
 * Rotas de Cron Job (NÃO DEVEM TER FILTRO DE AUTENTICAÇÃO)
 * --------------------------------------------------------------------
 */
// A rota (:any) é a sua chave secreta.
$routes->group('cron-tasks', static function ($routes) {
    $routes->get('migrate/(:any)', 'CronController::runMigrate/$1'); // Rota para Migrations
    $routes->get('seed/(:any)', 'CronController::runSeed/$1'); // Rota para Seeders
    $routes->get('refresh-token/(:any)', 'CronController::refreshToken/$1'); // Rota para o Refresh Token do ML
});