<?php
namespace App\Controllers\Admin;

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
     * Lista todos os códigos de estoque.
     */
    public function index()
    {
        // Busca os códigos e junta com os produtos para pegar o nome
        $data['codes'] = $this->stockCodeModel
            ->select('stock_codes.*, products.title, products.ml_item_id, products.id as product_id') // Garante product_id
            ->join('products', 'products.id = stock_codes.product_id', 'left')
            ->orderBy('stock_codes.id', 'DESC')
            ->findAll();

        return view('admin/stock_list', $data); // Carrega a nova view de listagem
    }


    /**
     * Mostra o formulário para adicionar estoque.
     * (Anteriormente era 'index')
     */
    public function new()
    {
        $data['productsForStock'] = $this->productModel->orderBy('title', 'ASC')->findAll();
        return view('admin/stock_form', $data); // Carrega a view do formulário
    }

    /**
     * Processa o formulário de adição de estoque.
     */
    public function add()
    {
        // --- Lógica de Validação ---
        $rules = [
            'product_id' => 'required|is_natural_no_zero|is_not_unique[products.id]',
            'codes_or_link' => 'required',
            'expires_at' => 'permit_empty|valid_date[Y-m-d]', // << Nova regra de validação
        ];
        $product = $this->productModel->find($this->request->getPost('product_id'));

        if (!$product || !$this->validate($rules)) {
            $errors = $this->validator ? $this->validator->getErrors() : [];
            if (!$product)
                $errors['product_id'] = 'Produto selecionado é inválido.';

            // Retorna para a rota nomeada 'admin.stock.new' (o formulário)
            return redirect()->route('admin.stock.new')->withInput()->with('errors', $errors);
        }

        $productId = $product->id;
        $inputType = $product->product_type;
        $inputData = trim($this->request->getPost('codes_or_link'));
        $expiresAt = $this->request->getPost('expires_at'); // << Pega a data de validade

        // Converte para null se estiver vazio, senão o DB pode reclamar
        $expiresAt = empty($expiresAt) ? null : $expiresAt;

        $insertedCount = 0;
        $errorMessages = [];


        // --- Salvar no Banco (com Criptografia) ---
        try {
            if ($inputType === 'static_link') {
                // Se for link estático, ignora expires_at
                $encryptedLink = $this->encrypter->encrypt($inputData);
                if ($this->productModel->update($productId, ['delivery_data' => $encryptedLink])) {
                    $insertedCount = 1;
                } else {
                    throw new \Exception('Falha ao atualizar o link estático.');
                }
            } elseif ($inputType === 'unique_code') {
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
                            'expires_at' => $expiresAt, // << Inclui a data de validade aqui
                        ];
                    }
                }

                if (!empty($batchData)) {
                    if ($this->stockCodeModel->insertBatch($batchData)) {
                        $insertedCount = count($batchData);
                    } else {
                        // Pega o erro do DB se disponível
                        $dbError = $this->stockCodeModel->db->error();
                        $errorMessage = $dbError['message'] ?? 'Falha ao inserir os códigos em lote.';
                        throw new \Exception($errorMessage);
                    }
                } else {
                    $errorMessages[] = 'Nenhum código válido foi fornecido.';
                }
            }

            if ($insertedCount > 0) {
                // Redireciona para a LISTA de estoque
                return redirect()->route('admin.stock')->with('success', $insertedCount . ' item(s) de estoque adicionado(s) com sucesso!');
            } else {
                // Usa with('error', ...) para mensagens de erro
                return redirect()->route('admin.stock.new')->withInput()->with('error', 'Nenhum item adicionado. ' . implode(' ', $errorMessages));
            }

        } catch (\Throwable $e) {
            log_message('error', '[StockController::add] Erro: ' . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            // Usa with('error', ...) para mensagens de erro
            return redirect()->route('admin.stock.new')->withInput()->with('error', 'Ocorreu um erro interno ao adicionar o estoque: ' . $e->getMessage());
        }
    }

    /**
     * Processa a exclusão em massa de códigos de estoque.
     */
    public function deleteBatch()
    {
        $ids = $this->request->getPost('selected_ids');

        if (empty($ids) || !is_array($ids)) {
            return redirect()->route('admin.stock')->with('error', 'Nenhum código selecionado para exclusão.');
        }

        try {
            // CAMADA DE SEGURANÇA:
            // Busca no DB APENAS os IDs que podem ser deletados (não vendidos)
            $deletableIds = $this->stockCodeModel
                ->whereIn('id', $ids)
                ->where('is_sold', false) // SÓ DELETA SE NÃO FOI VENDIDO
                ->findColumn('id');

            if (empty($deletableIds)) {
                return redirect()->route('admin.stock')->with('error', 'Nenhum código disponível foi selecionado. (Códigos vendidos não podem ser excluídos).');
            }

            // Deleta apenas os IDs seguros
            $this->stockCodeModel->whereIn('id', $deletableIds)->delete();
            
            $count = count($deletableIds);
            $skippedCount = count($ids) - $count;
            $message = $count . ' código(s) disponível(is) excluído(s) com sucesso!';
            
            // Se algum ID foi enviado mas não estava entre os "deletáveis" (ex: já vendido), avisa o usuário.
            if ($skippedCount > 0) {
                session()->setFlashdata('error', $skippedCount . ' código(s) (provavelmente já vendidos) foram ignorados e não podem ser excluídos.');
            }

            return redirect()->route('admin.stock')->with('success', $message);

        } catch (\Exception $e) {
            log_message('error', 'Erro ao excluir códigos de estoque em massa: ' . $e->getMessage());
            return redirect()->route('admin.stock')->with('error', 'Ocorreu um erro ao tentar excluir os códigos.');
        }
    }
}