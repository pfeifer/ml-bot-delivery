<?php namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use App\Models\MessageTemplateModel; // << Import

class ProductsController extends BaseController
{
    protected $productModel;
    protected $templateModel; // << Propriedade

    public function __construct()
    {
        $this->productModel = new ProductModel();
        $this->templateModel = new MessageTemplateModel(); // << Instanciar
        helper(['form']);
    }

    /**
     * Lista todos os produtos.
     */
    public function index()
    {
        $data['products'] = $this->productModel->orderBy('id', 'DESC')->findAll(); 

        return view('admin/products_list', $data);
    }

    /**
     * Mostra o formulário para criar um novo produto.
     */
    public function new()
    {
        // Busca os templates disponíveis para o dropdown
        $data['templates'] = $this->templateModel->orderBy('name', 'ASC')->findAll();
        $data['action'] = 'create';
        $data['product'] = null;
        return view('admin/products_form', $data); // Passa os templates para a view
    }

    /**
     * Processa o formulário de criação de produto.
     */
    public function create()
    {
        // --- Lógica de Validação ---
        $rules = [
            'ml_item_id'       => 'required|is_unique[products.ml_item_id]|max_length[30]',
            'title'            => 'permit_empty|max_length[255]',
            'product_type'     => 'required|in_list[unique_code,static_link]',
            // Apenas checa se é um número > 0, se não for vazio
            'message_template_id' => 'permit_empty|is_natural_no_zero', 
        ];
        
        $templateId = $this->request->getPost('message_template_id');

        // *** CORREÇÃO AQUI ***
        // Adiciona a regra 'is_not_unique' APENAS se um ID foi enviado
        if (!empty($templateId)) {
            $rules['message_template_id'] .= '|is_not_unique[message_templates.id]';
        }

         $messages = [
            'message_template_id' => [
                'is_not_unique' => 'O template de mensagem selecionado não existe.'
            ]
        ];

        if (! $this->validate($rules, $messages)) { // Passa $messages
             return redirect()->route('admin.products.new')->withInput()->with('errors', $this->validator->getErrors());
        }

        // --- Salvar no Banco ---
        $dataToSave = [
            'ml_item_id'       => $this->request->getPost('ml_item_id'),
            'title'            => $this->request->getPost('title'),
            'product_type'     => $this->request->getPost('product_type'),
            // Salva NULL se o valor for vazio, senão salva o ID
            'message_template_id' => empty($templateId) ? null : (int)$templateId,
        ];

        if ($this->productModel->insert($dataToSave)) {
            return redirect()->route('admin.products')->with('success', 'Produto cadastrado com sucesso!');
        } else {
            $errors = $this->productModel->errors() ?: ['database' => 'Erro desconhecido ao salvar o produto.'];
            return redirect()->route('admin.products.new')->withInput()->with('errors', $errors);
        }
    }

    /**
     * Mostra o formulário para editar um produto existente.
     */
    public function edit($id = null)
    {
        $product = $this->productModel->find($id);
        if (!$product) {
             return redirect()->route('admin.products')->with('error', 'Produto não encontrado.');
        }
        // Busca os templates disponíveis para o dropdown
        $data['templates'] = $this->templateModel->orderBy('name', 'ASC')->findAll();
        $data['action'] = 'update';
        $data['product'] = $product;
        return view('admin/products_form', $data); // Passa templates e produto
    }

    /**
     * Processa o formulário de atualização de produto.
     */
    public function update($id = null)
    {
         $product = $this->productModel->find($id);
        if (!$product) {
             return redirect()->route('admin.products')->with('error', 'Produto não encontrado.');
        }

         // --- Lógica de Validação ---
         $rules = [
            'ml_item_id'       => "required|is_unique[products.ml_item_id,id,{$id}]|max_length[30]",
            'title'            => 'permit_empty|max_length[255]',
            'product_type'     => 'required|in_list[unique_code,static_link]',
             // Apenas checa se é um número > 0, se não for vazio
            'message_template_id' => 'permit_empty|is_natural_no_zero',
        ];
        
        $templateId = $this->request->getPost('message_template_id');

        // *** CORREÇÃO AQUI ***
        // Adiciona a regra 'is_not_unique' APENAS se um ID foi enviado
        if (!empty($templateId)) {
            $rules['message_template_id'] .= '|is_not_unique[message_templates.id]';
        }
        
         $messages = [
            'message_template_id' => [
                'is_not_unique' => 'O template de mensagem selecionado não existe.'
            ]
        ];

         if (! $this->validate($rules, $messages)) { // Passa $messages
             return redirect()->route('admin.products.edit', $id)->withInput()->with('errors', $this->validator->getErrors());
        }

        // --- Atualizar no Banco ---
         $dataToUpdate = [
            'ml_item_id'       => $this->request->getPost('ml_item_id'),
            'title'            => $this->request->getPost('title'),
            'product_type'     => $this->request->getPost('product_type'),
            'message_template_id' => empty($templateId) ? null : (int)$templateId, // Salva NULL se vazio
        ];

        if ($this->productModel->update($id, $dataToUpdate)) {
             return redirect()->route('admin.products')->with('success', 'Produto atualizado com sucesso!');
        } else {
             $errors = $this->productModel->errors() ?: ['database' => 'Erro desconhecido ao atualizar o produto.'];
             return redirect()->route('admin.products.edit', $id)->withInput()->with('errors', $errors);
        }
    }

    /**
     * Exclui um produto. (Use POST/DELETE para segurança em produção).
     */
    public function delete($id = null)
    {
         $product = $this->productModel->find($id);
        if (!$product) {
             return redirect()->route('admin.products')->with('error', 'Produto não encontrado.');
        }

        if ($this->productModel->delete($id)) {
             return redirect()->route('admin.products')->with('success', 'Produto excluído com sucesso!');
        } else {
             return redirect()->route('admin.products')->with('error', 'Erro ao excluir o produto.');
        }
    }
}