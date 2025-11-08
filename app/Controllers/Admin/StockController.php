<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use App\Models\StockCodeModel;
use Config\Services; // MELHORIA: Importa o Service

class StockController extends BaseController
{
    protected $productModel;
    protected $stockCodeModel;
    protected $encrypter;

    public function __construct()
    {
        $this->productModel = new ProductModel();
        $this->stockCodeModel = new StockCodeModel();
        // MELHORIA: Usa o Service para garantir a mesma instância
        $this->encrypter = Services::encrypter(); 
        helper(['form']);
    }

    /**
     * Lista todos os códigos de estoque.
     */
    public function index()
    {
        $data['codes'] = $this->stockCodeModel
            ->select('stock_codes.*, products.title, products.ml_item_id, products.id as product_id')
            ->join('products', 'products.id = stock_codes.product_id', 'left')
            ->orderBy('stock_codes.id', 'DESC')
            ->findAll();

        return view('admin/stock_list', $data);
    }


    /**
     * Mostra o formulário para adicionar estoque.
     * REMOVIDO: Lógica não-AJAX removida.
     */
    public function new()
    {
        // Apenas responde a requisições AJAX (Modais)
        if (! $this->request->isAJAX()) {
             return redirect()->route('admin.stock')->with('error', 'Acesso inválido.');
        }

        $data['productsForStock'] = $this->productModel->orderBy('title', 'ASC')->findAll();
        
        // Retorna a view parcial para o modal
        return view('admin/stock_form_modal_content', $data);
    }

    /**
     * Processa o formulário de adição de estoque.
     * REMOVIDO: Lógica não-AJAX removida.
     */
    public function add()
    {
        // Apenas responde a requisições AJAX
        if (! $this->request->isAJAX()) {
             return redirect()->route('admin.stock')->with('error', 'Acesso inválido.');
        }

        $rules = [
            'product_id' => 'required|is_natural_no_zero|is_not_unique[products.id]',
            'codes_or_link' => 'required',
            'expires_at' => 'permit_empty|valid_date[Y-m-d]',
        ];
        $product = $this->productModel->find($this->request->getPost('product_id'));

        // Resposta AJAX em caso de falha de validação
        if (!$product || !$this->validate($rules)) {
            $errors = $this->validator ? $this->validator->getErrors() : [];
            if (!$product)
                $errors['product_id'] = 'Produto selecionado é inválido.';

             $data['productsForStock'] = $this->productModel->orderBy('title', 'ASC')->findAll();
             $data['validationErrors'] = $errors; // Passa os erros

             return $this->response->setJSON([
                 'success' => false,
                 'message' => 'Erro de validação',
                 'form_html' => view('admin/stock_form_modal_content', $data)
             ]);
        }

        // Se a validação passar, processa
        $productId = $product->id;
        $inputType = $product->product_type;
        $inputData = trim($this->request->getPost('codes_or_link'));
        $expiresAt = $this->request->getPost('expires_at');
        $expiresAt = empty($expiresAt) ? null : $expiresAt;

        $insertedCount = 0;

        try {
            if ($inputType === 'link') {
                // Atualiza o link estático no próprio produto
                $encryptedLink = $this->encrypter->encrypt($inputData);
                if ($this->productModel->update($productId, ['delivery_data' => $encryptedLink])) {
                    $insertedCount = 1;
                } else {
                    throw new \Exception('Falha ao atualizar o link estático.');
                }

            } elseif ($inputType === 'code') {
                // Adiciona códigos em lote
                $codes = explode("\n", str_replace("\r", "", $inputData));
                $batchData = [];

                foreach ($codes as $code) {
                    $trimmedCode = trim($code);
                    if (!empty($trimmedCode)) {
                        $encryptedCode = $this->encrypter->encrypt($trimmedCode);

                        $batchData[] = [
                            'product_id' => $productId,
                            'code' => $encryptedCode,
                            'is_sold' => false,
                            'expires_at' => $expiresAt,
                            // created_at é definido pelo DB
                        ];
                    }
                }

                if (!empty($batchData)) {
                    if ($this->stockCodeModel->insertBatch($batchData)) {
                        $insertedCount = count($batchData);
                    } else {
                        $dbError = $this->stockCodeModel->db->error();
                        $errorMessage = $dbError['message'] ?? 'Falha ao inserir os códigos em lote.';
                        throw new \Exception($errorMessage);
                    }
                } else {
                    // Nenhum código válido foi fornecido
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'Nenhum código válido foi fornecido.'
                    ]);
                }
            }

            // Resposta AJAX de sucesso
            if ($insertedCount > 0) {
                session()->setFlashdata('success', $insertedCount . ' item(s) de estoque adicionado(s) com sucesso!');
                return $this->response->setJSON([
                    'success' => true,
                    'message' => $insertedCount . ' item(s) de estoque adicionado(s) com sucesso!'
                ]);
            }

            // Caso $insertedCount seja 0 (nenhum item adicionado)
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Nenhum item adicionado.'
            ]);

        } catch (\Throwable $e) {
            log_message('error', '[StockController::add] Erro: ' . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            
            // Resposta AJAX em caso de exceção
             return $this->response->setJSON([
                 'success' => false,
                 'message' => 'Ocorreu um erro interno ao adicionar o estoque: ' . $e->getMessage()
             ]);
        }
    }

    /**
     * Processa a exclusão em massa de códigos de estoque. (Ação direta)
     */
    public function deleteBatch()
    {
        $ids = $this->request->getPost('selected_ids');

        if (empty($ids) || !is_array($ids)) {
            return redirect()->route('admin.stock')->with('error', 'Nenhum código selecionado para exclusão.');
        }

        try {
            // Apenas códigos NÃO vendidos podem ser excluídos
            $deletableIds = $this->stockCodeModel
                ->whereIn('id', $ids)
                ->where('is_sold', false)
                ->findColumn('id');

            if (empty($deletableIds)) {
                return redirect()->route('admin.stock')->with('error', 'Nenhum código disponível foi selecionado. (Códigos vendidos não podem ser excluídos).');
            }

            $this->stockCodeModel->whereIn('id', $deletableIds)->delete();
            
            $count = count($deletableIds);
            $skippedCount = count($ids) - $count;
            $message = $count . ' código(s) disponível(is) excluído(s) com sucesso!';
            
            // MELHORIA: Adiciona um alerta de 'aviso' (info) se algum foi pulado
            if ($skippedCount > 0) {
                session()->setFlashdata('info', $skippedCount . ' código(s) (provavelmente já vendidos) foram ignorados e não podem ser excluídos.');
            }

            return redirect()->route('admin.stock')->with('success', $message);

        } catch (\Exception $e) {
            log_message('error', 'Erro ao excluir códigos de estoque em massa: ' . $e->getMessage());
            return redirect()->route('admin.stock')->with('error', 'Ocorreu um erro ao tentar excluir os códigos.');
        }
    }
}