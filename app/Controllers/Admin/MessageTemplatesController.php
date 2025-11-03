<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MessageTemplateModel;

class MessageTemplatesController extends BaseController
{
    protected $templateModel;

    public function __construct()
    {
        $this->templateModel = new MessageTemplateModel();
        helper(['form']);
    }

    /**
     * Lista todos os templates (agora redireciona para a página de settings)
     */
    public function index()
    {
        // A rota principal (/) agora aponta para MercadoLivreController::index
        // Este método não é mais chamado por essa rota, mas redirecionamos por segurança
        return redirect()->route('admin.mercadolivre.settings');
    }

    /**
     * Mostra o formulário para criar um novo template.
     */
    public function new()
    {
        return view('admin/message_templates/form', ['action' => 'create', 'template' => null]);
    }

    /**
     * Processa a criação de um novo template.
     */
    public function create()
    {
        $dataToSave = [
            'name'    => $this->request->getPost('name'),
            'content' => $this->request->getPost('content'),
        ];

        if ($this->templateModel->save($dataToSave)) {
            // Redireciona de volta para a lista (na página de settings)
            return redirect()->route('admin.mercadolivre.settings')->with('success', 'Template de mensagem criado com sucesso!');
        } else {
            return redirect()->route('admin.message_templates.new')
                             ->withInput()
                             ->with('errors', $this->templateModel->errors());
        }
    }

    /**
     * Mostra o formulário para editar um template existente.
     */
    public function edit($id = null)
    {
        $template = $this->templateModel->find($id);
        if (!$template) {
            return redirect()->route('admin.mercadolivre.settings')->with('error', 'Template não encontrado.');
        }
        return view('admin/message_templates/form', ['action' => 'update', 'template' => $template]);
    }

    /**
     * Processa a atualização de um template.
     */
    public function update($id = null)
    {
        $template = $this->templateModel->find($id);
        if (!$template) {
            return redirect()->route('admin.mercadolivre.settings')->with('error', 'Template não encontrado.');
        }

        $dataToUpdate = [
            'name'    => $this->request->getPost('name'),
            'content' => $this->request->getPost('content'),
        ];

        if ($this->templateModel->update($id, $dataToUpdate)) {
             return redirect()->route('admin.mercadolivre.settings')->with('success', 'Template de mensagem atualizado com sucesso!');
        } else {
             return redirect()->route('admin.message_templates.edit', $id)
                              ->withInput()
                              ->with('errors', $this->templateModel->errors());
        }
    }

    /**
     * Exclui um template.
     */
    public function delete($id = null)
    {
        // *** CORREÇÃO AQUI: Proíbe deletar o ID 1 ***
        if ($id == 1) {
            return redirect()->route('admin.mercadolivre.settings')
                             ->with('error', 'Não é possível excluir o "Template Padrão" (ID 1), pois ele é usado pelo sistema.');
        }
        // *** FIM DA CORREÇÃO ***
            
        $template = $this->templateModel->find($id);
        if (!$template) {
            return redirect()->route('admin.mercadolivre.settings')->with('error', 'Template não encontrado.');
        }

        if ($this->templateModel->delete($id)) {
            return redirect()->route('admin.mercadolivre.settings')->with('success', 'Template de mensagem excluído com sucesso!');
        } else {
            return redirect()->route('admin.mercadolivre.settings')->with('error', 'Erro ao excluir o template.');
        }
    }

    /**
     * Exclui um template em massa.
     */
    public function deleteBatch()
    {
        $ids = $this->request->getPost('selected_ids');

        if (empty($ids) || !is_array($ids)) {
            return redirect()->route('admin.mercadolivre.settings')->with('error', 'Nenhum template selecionado para exclusão.');
        }

        // --- PROTEÇÃO CRÍTICA: Impedir exclusão do ID 1 ---
        $filteredIds = [];
        $skippedDefault = false;
        foreach ($ids as $id) {
            if ((int)$id !== 1) {
                $filteredIds[] = (int)$id;
            } else {
                $skippedDefault = true;
            }
        }
        // ----------------------------------------------------

        if (empty($filteredIds)) {
             $message = $skippedDefault ? 'Não é possível excluir o Template Padrão (ID 1).' : 'Nenhum template válido selecionado.';
            return redirect()->route('admin.mercadolivre.settings')->with('error', $message);
        }

        try {
            $this->templateModel->whereIn('id', $filteredIds)->delete();
            
            $count = count($filteredIds);
            $successMessage = $count . ' template(s) excluído(s) com sucesso!';
            
            if ($skippedDefault) {
                // Se tentou excluir o 1, avisa que ele foi pulado
                session()->setFlashdata('error', 'O "Template Padrão" (ID 1) não pode ser excluído e foi ignorado.');
            }
            
            return redirect()->route('admin.mercadolivre.settings')->with('success', $successMessage);
        
        } catch (\Exception $e) {
            log_message('error', 'Erro ao excluir templates em massa: ' . $e->getMessage());
            return redirect()->route('admin.mercadolivre.settings')->with('error', 'Ocorreu um erro ao tentar excluir os templates.');
        }
    }
}