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
     * Lista todos os templates (redireciona para a página de settings)
     */
    public function index()
    {
        // Esta rota (admin/message-templates) agora é a mesma da página de settings
        return redirect()->route('admin.mercadolivre.settings');
    }

    /**
     * Mostra o formulário para criar um novo template.
     * REMOVIDO: Lógica não-AJAX removida.
     */
    public function new()
    {
        // Apenas responde a requisições AJAX (Modais)
        if (! $this->request->isAJAX()) {
             return redirect()->route('admin.mercadolivre.settings')->with('error', 'Acesso inválido.');
        }

        $data = ['action' => 'create', 'template' => null];
        return view('admin/message_templates/form_modal_content', $data);
    }

    /**
     * Processa a criação de um novo template.
     * REMOVIDO: Lógica não-AJAX removida.
     */
    public function create()
    {
        // Apenas responde a requisições AJAX
        if (! $this->request->isAJAX()) {
             return redirect()->route('admin.mercadolivre.settings')->with('error', 'Acesso inválido.');
        }

        $dataToSave = [
            'name'    => $this->request->getPost('name'),
            'content' => $this->request->getPost('content'),
        ];

        if ($this->templateModel->save($dataToSave)) {
            // Resposta AJAX de sucesso
            session()->setFlashdata('success', 'Template de mensagem criado com sucesso!');
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Template de mensagem criado com sucesso!'
            ]);
        } else {
             // Resposta AJAX em caso de falha de validação
             $data['action'] = 'create';
             $data['template'] = null;
             $data['validationErrors'] = $this->templateModel->errors(); // Passa os erros

             return $this->response->setJSON([
                 'success' => false,
                 'message' => 'Erro de validação',
                 'form_html' => view('admin/message_templates/form_modal_content', $data)
             ]);
        }
    }

    /**
     * Mostra o formulário para editar um template existente.
     * REMOVIDO: Lógica não-AJAX removida.
     */
    public function edit($id = null)
    {
        // Apenas responde a requisições AJAX
        if (! $this->request->isAJAX()) {
             return redirect()->route('admin.mercadolivre.settings')->with('error', 'Acesso inválido.');
        }

        $template = $this->templateModel->find($id);
        if (!$template) {
             return $this->response->setStatusCode(404)->setBody('Template não encontrado.');
        }
        
        $data = ['action' => 'update', 'template' => $template];
        return view('admin/message_templates/form_modal_content', $data);
    }

    /**
     * Processa a atualização de um template.
     * REMOVIDO: Lógica não-AJAX removida.
     */
    public function update($id = null)
    {
        // Apenas responde a requisições AJAX
        if (! $this->request->isAJAX()) {
             return redirect()->route('admin.mercadolivre.settings')->with('error', 'Acesso inválido.');
        }

        $template = $this->templateModel->find($id);
        if (!$template) {
             return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Template não encontrado.']);
        }

        $dataToUpdate = [
            'name'    => $this->request->getPost('name'),
            'content' => $this->request->getPost('content'),
        ];

        if ($this->templateModel->update($id, $dataToUpdate)) {
             // Resposta AJAX de sucesso
             session()->setFlashdata('success', 'Template de mensagem atualizado com sucesso!');
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Template de mensagem atualizado com sucesso!'
            ]);
        } else {
             // Resposta AJAX em caso de falha de validação
             $data['action'] = 'update';
             $data['template'] = $template; // Passa o template original
             $data['validationErrors'] = $this->templateModel->errors(); // Passa os erros

             return $this->response->setJSON([
                 'success' => false,
                 'message' => 'Erro de validação',
                 'form_html' => view('admin/message_templates/form_modal_content', $data)
             ]);
        }
    }

    /**
     * Exclui um template. (Ação direta)
     */
    public function delete($id = null)
    {
        // Protege o Template Padrão
        if ($id == 1) {
            return redirect()->route('admin.mercadolivre.settings')
                             ->with('error', 'Não é possível excluir o "Template Padrão" (ID 1), pois ele é usado pelo sistema.');
        }
            
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
     * Exclui um template em massa. (Ação direta)
     */
    public function deleteBatch()
    {
        $ids = $this->request->getPost('selected_ids');

        if (empty($ids) || !is_array($ids)) {
            return redirect()->route('admin.mercadolivre.settings')->with('error', 'Nenhum template selecionado para exclusão.');
        }

        // Garante que o ID 1 (Padrão) nunca seja excluído
        $filteredIds = [];
        $skippedDefault = false;
        foreach ($ids as $id) {
            if ((int)$id !== 1) {
                $filteredIds[] = (int)$id;
            } else {
                $skippedDefault = true;
            }
        }

        if (empty($filteredIds)) {
             $message = $skippedDefault ? 'Não é possível excluir o Template Padrão (ID 1).' : 'Nenhum template válido selecionado.';
            return redirect()->route('admin.mercadolivre.settings')->with('error', $message);
        }

        try {
            $this->templateModel->whereIn('id', $filteredIds)->delete();
            
            $count = count($filteredIds);
            $successMessage = $count . ' template(s) excluído(s) com sucesso!';
            
            // MELHORIA: Usa 'info' para o aviso
            if ($skippedDefault) {
                session()->setFlashdata('info', 'O "Template Padrão" (ID 1) não pode ser excluído e foi ignorado.');
            }
            
            return redirect()->route('admin.mercadolivre.settings')->with('success', $successMessage);
        
        } catch (\Exception $e) {
            log_message('error', 'Erro ao excluir templates em massa: ' . $e->getMessage());
            return redirect()->route('admin.mercadolivre.settings')->with('error', 'Ocorreu um erro ao tentar excluir os templates.');
        }
    }
}