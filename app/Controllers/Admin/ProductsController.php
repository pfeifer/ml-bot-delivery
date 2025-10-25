<?php namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ProductModel; // Importa o Model

class ProductsController extends BaseController
{
    protected $productModel;

    public function __construct()
    {
        $this->productModel = new ProductModel(); // Instancia o Model
        helper(['form']); // Carrega o helper de formulário
    }

    /**
     * Lista todos os produtos.
     */
    public function index()
    {
        $data['products'] = $this->productModel->orderBy('id', 'DESC')->findAll(); // Busca todos os produtos

        return view('admin/products_list', $data); // Passa os produtos para a view
    }

    /**
     * Mostra o formulário para criar um novo produto.
     */
    public function new()
    {
        // Você precisará criar a view 'admin/products_form'
        return view('admin/products_form', ['action' => 'create', 'product' => null]); 
    }

    /**
     * Processa o formulário de criação de produto.
     */
    public function create()
    {
        // --- Lógica de Validação ---
        $rules = [
            'ml_item_id'   => 'required|is_unique[products.ml_item_id]|max_length[30]',
            'title'        => 'permit_empty|max_length[255]',
            'product_type' => 'required|in_list[unique_code,static_link]',
        ];

        if (! $this->validate($rules)) {
             return redirect()->route('admin.products.new')->withInput()->with('errors', $this->validator->getErrors());
        }

        // --- Salvar no Banco ---
        $dataToSave = [
            'ml_item_id'   => $this->request->getPost('ml_item_id'),
            'title'        => $this->request->getPost('title'),
            'product_type' => $this->request->getPost('product_type'),
            // 'delivery_data' será tratado separadamente ou no form de estoque
        ];

        if ($this->productModel->insert($dataToSave)) {
            return redirect()->route('admin.products')->with('success', 'Produto cadastrado com sucesso!');
        } else {
            return redirect()->route('admin.products.new')->withInput()->with('error', 'Erro ao salvar o produto.');
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
        // Você precisará criar a view 'admin/products_form'
        return view('admin/products_form', ['action' => 'update', 'product' => $product]);
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
        // Cuidado com a regra is_unique ao atualizar!
         $rules = [
            'ml_item_id'   => "required|is_unique[products.ml_item_id,id,{$id}]|max_length[30]", // Ignora o próprio ID
            'title'        => 'permit_empty|max_length[255]',
            'product_type' => 'required|in_list[unique_code,static_link]',
        ];

         if (! $this->validate($rules)) {
             return redirect()->route('admin.products.edit', $id)->withInput()->with('errors', $this->validator->getErrors());
        }

        // --- Atualizar no Banco ---
         $dataToUpdate = [
            'ml_item_id'   => $this->request->getPost('ml_item_id'),
            'title'        => $this->request->getPost('title'),
            'product_type' => $this->request->getPost('product_type'),
        ];

        if ($this->productModel->update($id, $dataToUpdate)) {
             return redirect()->route('admin.products')->with('success', 'Produto atualizado com sucesso!');
        } else {
             return redirect()->route('admin.products.edit', $id)->withInput()->with('error', 'Erro ao atualizar o produto.');
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