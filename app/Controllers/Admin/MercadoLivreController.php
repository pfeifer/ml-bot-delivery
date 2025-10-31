<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MessageTemplateModel; // Importar o model

class MercadoLivreController extends BaseController
{
    protected $templateModel;

    public function __construct()
    {
        $this->templateModel = new MessageTemplateModel();
        helper(['form']);
    }

    /**
     * Exibe a página principal de configurações do Mercado Livre com TABS.
     * A primeira TAB é a lista de templates de mensagem.
     */
    public function index()
    {
        // Carrega os dados para a primeira tab (Mensagens)
        $data['templates'] = $this->templateModel->orderBy('name', 'ASC')->findAll();
        
        // Carrega a view principal que contém as TABS
        return view('admin/mercadolivre/settings', $data);
    }
}