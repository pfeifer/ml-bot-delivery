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
     * Lista todos os templates (redireciona)
     */
    public function index()
    {
        return redirect()->route('admin.mercadolivre.settings');
    }

    /**
     * Mostra o formulário para criar um novo template.
     */
    public function new()
    {
        $data = ['action' => 'create', 'template' => null];

        // MODIFICADO: Se for AJAX (modal), retorna a view parcial
        if ($this->request->isAJAX()) {
            return view('admin/message_templates/form_modal_content', $data);
        }
        
        return view('admin/message_templates/form', $data); // Fallback
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
            // MODIFICADO: Resposta AJAX em caso de sucesso
            if ($this->request->isAJAX()) {
                 session()->setFlashdata('success', 'Template de mensagem criado com sucesso!');
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Template de mensagem criado com sucesso!'
                ]);
            }
            return redirect()->route('admin.mercadolivre.settings')->with('success', 'Template de mensagem criado com sucesso!');
        } else {
             // MODIFICADO: Resposta AJAX em caso de falha de validação
             if ($this->request->isAJAX()) {
                 $data['action'] = 'create';
                 $data['template'] = null;
                 $data['validationErrors'] = $this->templateModel->errors(); // Passa os erros

                 return $this->response->setJSON([
                     'success' => false,
                     'message' => 'Erro de validação',
                     'form_html' => view('admin/message_templates/form_modal_content', $data)
                 ]);
             }
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
            // MODIFICADO: Resposta AJAX 404
             if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(404)->setBody('Template não encontrado.');
             }
            return redirect()->route('admin.mercadolivre.settings')->with('error', 'Template não encontrado.');
        }
        
        $data = ['action' => 'update', 'template' => $template];

        // MODIFICADO: Se for AJAX (modal), retorna a view parcial
        if ($this->request->isAJAX()) {
            return view('admin/message_templates/form_modal_content', $data);
        }
        
        return view('admin/message_templates/form', $data); // Fallback
    }

    /**
     * Processa a atualização de um template.
     */
    public function update($id = null)
    {
        $template = $this->templateModel->find($id);
        if (!$template) {
            // MODIFICADO: Resposta AJAX 404
             if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Template não encontrado.']);
             }
            return redirect()->route('admin.mercadolivre.settings')->with('error', 'Template não encontrado.');
        }

        $dataToUpdate = [
            'name'    => $this->request->getPost('name'),
            'content' => $this->request->getPost('content'),
        ];

        if ($this->templateModel->update($id, $dataToUpdate)) {
             // MODIFICADO: Resposta AJAX em caso de sucesso
             if ($this->request->isAJAX()) {
                 session()->setFlashdata('success', 'Template de mensagem atualizado com sucesso!');
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Template de mensagem atualizado com sucesso!'
                ]);
            }
             return redirect()->route('admin.mercadolivre.settings')->with('success', 'Template de mensagem atualizado com sucesso!');
        } else {
             // MODIFICADO: Resposta AJAX em caso de falha de validação
             if ($this->request->isAJAX()) {
                 $data['action'] = 'update';
                 $data['template'] = $template; // Passa o template original
                 $data['validationErrors'] = $this->templateModel->errors(); // Passa os erros

                 return $this->response->setJSON([
                     'success' => false,
                     'message' => 'Erro de validação',
                     'form_html' => view('admin/message_templates/form_modal_content', $data)
                 ]);
             }
             return redirect()->route('admin.message_templates.edit', $id)
                              ->withInput()
                              ->with('errors', $this->templateModel->errors());
        }
    }

    /**
     * Exclui um template. (Não precisa de modal)
     */
    public function delete($id = null)
    {
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
     * Exclui um template em massa. (Não precisa de modal)
     */
    public function deleteBatch()
    {
        $ids = $this->request->getPost('selected_ids');

        if (empty($ids) || !is_array($ids)) {
            return redirect()->route('admin.mercadolivre.settings')->with('error', 'Nenhum template selecionado para exclusão.');
        }

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
            
            if ($skippedDefault) {
                session()->setFlashdata('error', 'O "Template Padrão" (ID 1) não pode ser excluído e foi ignorado.');
            }
            
            return redirect()->route('admin.mercadolivre.settings')->with('success', $successMessage);
        
        } catch (\Exception $e) {
            log_message('error', 'Erro ao excluir templates em massa: ' . $e->getMessage());
            return redirect()->route('admin.mercadolivre.settings')->with('error', 'Ocorreu um erro ao tentar excluir os templates.');
        }
    }
}