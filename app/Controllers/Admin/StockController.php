<?php namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use App\Models\StockCodeModel; // Importa os Models

class StockController extends BaseController
{
    protected $productModel;
    protected $stockCodeModel;
    protected $encrypter;

    public function __construct()
    {
        $this->productModel = new ProductModel();
        $this->stockCodeModel = new StockCodeModel();
        $this->encrypter = \Config\Services::encrypter(); // Carrega o serviço de criptografia
        helper(['form']); // Carrega o helper de formulário
    }

    /**
     * Mostra o formulário para adicionar estoque.
     */
    public function index()
    {
        // Busca apenas produtos que podem ter estoque (ou link)
        $data['productsForStock'] = $this->productModel->orderBy('title', 'ASC')->findAll(); 
        // Você pode querer buscar também o estoque atual aqui para exibir

        return view('admin/stock_form', $data); // Passa a lista de produtos para o select
    }

    /**
     * Processa o formulário de adição de estoque.
     */
    public function add()
    {
         // --- Lógica de Validação ---
         $rules = [
            'product_id'    => 'required|is_natural_no_zero|is_not_unique[products.id]', // Garante que o ID existe
            'codes_or_link' => 'required',
        ];
         $product = $this->productModel->find($this->request->getPost('product_id'));

         if (!$product || ! $this->validate($rules)) {
             $errors = $this->validator ? $this->validator->getErrors() : [];
             if (!$product) $errors['product_id'] = 'Produto selecionado é inválido.';

             return redirect()->route('admin.stock')->withInput()->with('errors', $errors);
        }

        $productId = $product->id;
        $inputType = $product->product_type;
        $inputData = trim($this->request->getPost('codes_or_link'));
        $insertedCount = 0;
        $errorMessages = [];


        // --- Salvar no Banco (com Criptografia) ---
        try {
            if ($inputType === 'static_link') {
                // Criptografa o link antes de salvar/atualizar
                $encryptedLink = $this->encrypter->encrypt($inputData);

                if ($this->productModel->update($productId, ['delivery_data' => $encryptedLink])) {
                    $insertedCount = 1;
                } else {
                    throw new \Exception('Falha ao atualizar o link estático.');
                }
            } 
            elseif ($inputType === 'unique_code') {
                $codes = explode("\n", str_replace("\r", "", $inputData)); // Divide por linha
                $batchData = [];

                foreach ($codes as $code) {
                    $trimmedCode = trim($code);
                    if (!empty($trimmedCode)) {
                        // Criptografa cada código antes de preparar para inserção
                         $encryptedCode = $this->encrypter->encrypt($trimmedCode);

                         $batchData[] = [
                            'product_id' => $productId,
                            'code' => $encryptedCode, // Salva criptografado
                            'is_sold' => false,
                        ];
                    }
                }

                if (!empty($batchData)) {
                    // Insere todos os códigos de uma vez (mais eficiente)
                    if ($this->stockCodeModel->insertBatch($batchData)) {
                        $insertedCount = count($batchData);
                    } else {
                         throw new \Exception('Falha ao inserir os códigos em lote.');
                    }
                } else {
                    $errorMessages[] = 'Nenhum código válido foi fornecido.';
                }
            }

            if ($insertedCount > 0) {
                 return redirect()->route('admin.stock')->with('success', $insertedCount . ' item(s) de estoque adicionado(s) com sucesso!');
            } else {
                 return redirect()->route('admin.stock')->withInput()->with('error', 'Nenhum item adicionado. ' . implode(' ', $errorMessages));
            }

        } catch (\Throwable $e) {
             log_message('error', '[StockController::add] Erro: ' . $e->getMessage());
             return redirect()->route('admin.stock')->withInput()->with('error', 'Ocorreu um erro interno ao adicionar o estoque.');
        }
    }
}