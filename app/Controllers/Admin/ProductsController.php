<?php namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use App\Models\MessageTemplateModel;

class ProductsController extends BaseController
{
    protected $productModel;
    protected $templateModel;

    public function __construct()
    {
        $this->productModel = new ProductModel();
        $this->templateModel = new MessageTemplateModel();
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
     * REMOVIDO: Lógica não-AJAX removida.
     */
    public function new()
    {
        // Apenas responde a requisições AJAX (Modais)
        if (! $this->request->isAJAX()) {
             return redirect()->route('admin.products')->with('error', 'Acesso inválido.');
        }

        $data['templates'] = $this->templateModel->orderBy('name', 'ASC')->findAll();
        $data['action'] = 'create';
        $data['product'] = null;
        
        // Retorna a view parcial para o modal
        return view('admin/products_form_modal_content', $data);
    }

    /**
     * Processa o formulário de criação de produto.
     * REMOVIDO: Lógica não-AJAX removida.
     */
    public function create()
    {
        // Apenas responde a requisições AJAX
        if (! $this->request->isAJAX()) {
             return redirect()->route('admin.products')->with('error', 'Acesso inválido.');
        }

        $rules = [
            'ml_item_id'       => 'required|is_unique[products.ml_item_id]|max_length[30]',
            'title'            => 'permit_empty|max_length[255]',
            'product_type'     => 'required|in_list[code,link]',
            'message_template_id' => 'permit_empty|is_natural_no_zero', 
        ];
        
        $templateId = $this->request->getPost('message_template_id');

        // Adiciona regra de validação condicional
        if (!empty($templateId)) {
            $rules['message_template_id'] .= '|is_not_unique[message_templates.id]';
        }

         $messages = [
            'message_template_id' => [
                'is_not_unique' => 'O template de mensagem selecionado não existe.'
            ]
        ];

        // Resposta AJAX em caso de falha de validação
        if (! $this->validate($rules, $messages)) { 
             $data['templates'] = $this->templateModel->orderBy('name', 'ASC')->findAll();
             $data['action'] = 'create';
             $data['product'] = null; 
             $data['validationErrors'] = $this->validator->getErrors(); 

             return $this->response->setJSON([
                 'success' => false,
                 'message' => 'Erro de validação',
                 'form_html' => view('admin/products_form_modal_content', $data)
             ]);
        }

        // Se a validação passar, salva os dados
        $dataToSave = [
            'ml_item_id'       => $this->request->getPost('ml_item_id'),
            'title'            => $this->request->getPost('title'),
            'product_type'     => $this->request->getPost('product_type'),
            'message_template_id' => empty($templateId) ? null : (int)$templateId,
        ];

        if ($this->productModel->insert($dataToSave)) {
            // Resposta AJAX de sucesso
            session()->setFlashdata('success', 'Produto cadastrado com sucesso!');
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Produto cadastrado com sucesso!'
            ]);
        } 
        
        // Resposta AJAX em caso de erro de DB
        $errors = $this->productModel->errors() ?: ['database' => 'Erro desconhecido ao salvar o produto.'];
        return $this->response->setJSON([
             'success' => false,
             'message' => implode(' ', $errors)
        ]);
    }

    /**
     * Mostra o formulário para editar um produto existente.
     * REMOVIDO: Lógica não-AJAX removida.
     */
    public function edit($id = null)
    {
        // Apenas responde a requisições AJAX
        if (! $this->request->isAJAX()) {
             return redirect()->route('admin.products')->with('error', 'Acesso inválido.');
        }

        $product = $this->productModel->find($id);
        if (!$product) {
             // Resposta AJAX 404
             return $this->response->setStatusCode(404)->setBody('Produto não encontrado.');
        }

        $data['templates'] = $this->templateModel->orderBy('name', 'ASC')->findAll();
        $data['action'] = 'update';
        $data['product'] = $product;
        
        // Retorna a view parcial para o modal
        return view('admin/products_form_modal_content', $data);
    }

    /**
     * Processa o formulário de atualização de produto.
     * REMOVIDO: Lógica não-AJAX removida.
     */
    public function update($id = null)
    {
         // Apenas responde a requisições AJAX
        if (! $this->request->isAJAX()) {
             return redirect()->route('admin.products')->with('error', 'Acesso inválido.');
        }

         $product = $this->productModel->find($id);
        if (!$product) {
             return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Produto não encontrado.']);
        }

         $rules = [
            'ml_item_id'       => "required|is_unique[products.ml_item_id,id,{$id}]|max_length[30]",
            'title'            => 'permit_empty|max_length[255]',
            'product_type'     => 'required|in_list[code,link]',
            'message_template_id' => 'permit_empty|is_natural_no_zero',
        ];
        
        $templateId = $this->request->getPost('message_template_id');

        if (!empty($templateId)) {
            $rules['message_template_id'] .= '|is_not_unique[message_templates.id]';
        }
        
         $messages = [
            'message_template_id' => [
                'is_not_unique' => 'O template de mensagem selecionado não existe.'
            ]
        ];

         // Resposta AJAX em caso de falha de validação
         if (! $this->validate($rules, $messages)) {
             $data['templates'] = $this->templateModel->orderBy('name', 'ASC')->findAll();
             $data['action'] = 'update';
             $data['product'] = $product;
             $data['validationErrors'] = $this->validator->getErrors(); // Passa os erros

             return $this->response->setJSON([
                 'success' => false,
                 'message' => 'Erro de validação',
                 'form_html' => view('admin/products_form_modal_content', $data)
             ]);
        }

         $dataToUpdate = [
            'ml_item_id'       => $this->request->getPost('ml_item_id'),
            'title'            => $this->request->getPost('title'),
            'product_type'     => $this->request->getPost('product_type'),
            'message_template_id' => empty($templateId) ? null : (int)$templateId,
        ];

        if ($this->productModel->update($id, $dataToUpdate)) {
             // Resposta AJAX de sucesso
             session()->setFlashdata('success', 'Produto atualizado com sucesso!');
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Produto atualizado com sucesso!'
            ]);
        } 

        // Resposta AJAX em caso de erro de DB
         $errors = $this->productModel->errors() ?: ['database' => 'Erro desconhecido ao atualizar o produto.'];
         return $this->response->setJSON([
             'success' => false,
             'message' => implode(' ', $errors)
         ]);
    }

    /**
     * Exclui um produto. (Ação direta, sem modal)
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

    /**
     * Processa a exclusão em massa de produtos. (Ação direta)
     */
    public function deleteBatch()
    {
        $ids = $this->request->getPost('selected_ids');

        if (empty($ids) || !is_array($ids)) {
            return redirect()->route('admin.products')->with('error', 'Nenhum produto selecionado para exclusão.');
        }

        try {
            $this->productModel->whereIn('id', $ids)->delete();
            $count = count($ids);
            return redirect()->route('admin.products')->with('success', $count . ' produto(s) excluído(s) com sucesso!');
        
        } catch (\Exception $e) {
            log_message('error', 'Erro ao excluir produtos em massa: ' . $e->getMessage());
            return redirect()->route('admin.products')->with('error', 'Ocorreu um erro ao tentar excluir os produtos.');
        }
    }
}