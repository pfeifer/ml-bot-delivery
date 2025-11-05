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
        $data['templates'] = $this->templateModel->orderBy('name', 'ASC')->findAll();
        $data['action'] = 'create';
        $data['product'] = null;
        
        // MODIFICADO: Se for AJAX (modal), retorna a view parcial
        if ($this->request->isAJAX()) {
            // Usaremos um novo ficheiro de view SÓ para o conteúdo do modal
            return view('admin/products_form_modal_content', $data);
        }

        // Se não for AJAX, carrega a página completa (obsoleto, mas mantém a funcionalidade)
        return view('admin/products_form', $data);
    }

    /**
     * Processa o formulário de criação de produto.
     */
    public function create()
    {
        $rules = [
            'ml_item_id'       => 'required|is_unique[products.ml_item_id]|max_length[30]',
            'title'            => 'permit_empty|max_length[255]',
            'product_type'     => 'required|in_list[unique_code,static_link]',
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

        // MODIFICADO: Resposta AJAX em caso de falha de validação
        if (! $this->validate($rules, $messages)) { 
             if ($this->request->isAJAX()) {
                 $data['templates'] = $this->templateModel->orderBy('name', 'ASC')->findAll();
                 $data['action'] = 'create';
                 $data['product'] = null; 
                 // Passa os erros para a view parcial
                 $data['validationErrors'] = $this->validator->getErrors(); 

                 return $this->response->setJSON([
                     'success' => false,
                     'message' => 'Erro de validação',
                     // Retorna o HTML do formulário atualizado com os erros
                     'form_html' => view('admin/products_form_modal_content', $data)
                 ]);
             }
             // Fallback para não-AJAX
             return redirect()->route('admin.products.new')->withInput()->with('errors', $this->validator->getErrors());
        }

        $dataToSave = [
            'ml_item_id'       => $this->request->getPost('ml_item_id'),
            'title'            => $this->request->getPost('title'),
            'product_type'     => $this->request->getPost('product_type'),
            'message_template_id' => empty($templateId) ? null : (int)$templateId,
        ];

        if ($this->productModel->insert($dataToSave)) {
            // MODIFICADO: Resposta AJAX em caso de sucesso
            if ($this->request->isAJAX()) {
                 session()->setFlashdata('success', 'Produto cadastrado com sucesso!');
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Produto cadastrado com sucesso!'
                ]);
            }
            return redirect()->route('admin.products')->with('success', 'Produto cadastrado com sucesso!');
        } else {
             $errors = $this->productModel->errors() ?: ['database' => 'Erro desconhecido ao salvar o produto.'];
             // MODIFICADO: Resposta AJAX em caso de erro de DB
             if ($this->request->isAJAX()) {
                 return $this->response->setJSON([
                     'success' => false,
                     'message' => implode(' ', $errors)
                 ]);
             }
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
             // MODIFICADO: Resposta AJAX 404
             if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(404)->setBody('Produto não encontrado.');
             }
             return redirect()->route('admin.products')->with('error', 'Produto não encontrado.');
        }

        $data['templates'] = $this->templateModel->orderBy('name', 'ASC')->findAll();
        $data['action'] = 'update';
        $data['product'] = $product;
        
        // MODIFICADO: Se for AJAX (modal), retorna a view parcial
        if ($this->request->isAJAX()) {
            return view('admin/products_form_modal_content', $data);
        }

        return view('admin/products_form', $data);
    }

    /**
     * Processa o formulário de atualização de produto.
     */
    public function update($id = null)
    {
         $product = $this->productModel->find($id);
        if (!$product) {
             // MODIFICADO: Resposta AJAX 404
             if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Produto não encontrado.']);
             }
             return redirect()->route('admin.products')->with('error', 'Produto não encontrado.');
        }

         $rules = [
            'ml_item_id'       => "required|is_unique[products.ml_item_id,id,{$id}]|max_length[30]",
            'title'            => 'permit_empty|max_length[255]',
            'product_type'     => 'required|in_list[unique_code,static_link]',
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

         // MODIFICADO: Resposta AJAX em caso de falha de validação
         if (! $this->validate($rules, $messages)) {
             if ($this->request->isAJAX()) {
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
             return redirect()->route('admin.products.edit', $id)->withInput()->with('errors', $this->validator->getErrors());
        }

         $dataToUpdate = [
            'ml_item_id'       => $this->request->getPost('ml_item_id'),
            'title'            => $this->request->getPost('title'),
            'product_type'     => $this->request->getPost('product_type'),
            'message_template_id' => empty($templateId) ? null : (int)$templateId,
        ];

        if ($this->productModel->update($id, $dataToUpdate)) {
             // MODIFICADO: Resposta AJAX em caso de sucesso
             if ($this->request->isAJAX()) {
                 session()->setFlashdata('success', 'Produto atualizado com sucesso!');
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Produto atualizado com sucesso!'
                ]);
            }
             return redirect()->route('admin.products')->with('success', 'Produto atualizado com sucesso!');
        } else {
             $errors = $this->productModel->errors() ?: ['database' => 'Erro desconhecido ao atualizar o produto.'];
             // MODIFICADO: Resposta AJAX em caso de erro de DB
             if ($this->request->isAJAX()) {
                 return $this->response->setJSON([
                     'success' => false,
                     'message' => implode(' ', $errors)
                 ]);
             }
             return redirect()->route('admin.products.edit', $id)->withInput()->with('errors', $errors);
        }
    }

    /**
     * Exclui um produto. (Não precisa de modal)
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
     * Processa a exclusão em massa de produtos. (Não precisa de modal)
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