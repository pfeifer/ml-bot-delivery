<?= $this->extend('admin/template') ?>

<?= $this->section('title') ?>Configurações do Mercado Livre<?= $this->endSection() ?>

<?= $this->section('page_title') ?>Configurações do Mercado Livre<?= $this->endSection() ?>

<?= $this->section('content') ?>

<ul class="nav nav-tabs" id="mlSettingsTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="messages-tab" data-bs-toggle="tab" data-bs-target="#tab-messages" type="button" role="tab" aria-controls="tab-messages" aria-selected="true">
            <i class="fa-solid fa-envelope-open-text fa-fw"></i>
            Templates de Mensagem
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="credentials-tab" data-bs-toggle="tab" data-bs-target="#tab-credentials" type="button" role="tab" aria-controls="tab-credentials" aria-selected="false">
            <i class="fa-solid fa-key fa-fw"></i>
            Credenciais (em breve)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="other-tab" data-bs-toggle="tab" data-bs-target="#tab-other" type="button" role="tab" aria-controls="tab-other" aria-selected="false">
            <i class="fa-solid fa-sliders fa-fw"></i>
            Outras Configurações (em breve)
        </button>
    </li>
</ul>

<div class="tab-content" id="mlSettingsTabsContent">
    
    <div class="tab-pane fade show active" id="tab-messages" role="tabpanel" aria-labelledby="messages-tab" tabindex="0">
        <div class="p-3 border border-top-0 rounded-bottom">
            
            <p>Gerencie os templates de mensagem. O "Template Padrão (ID #1)" será usado se nenhum for especificado no cadastro do produto.</p>

            <form method="POST" action="<?= route_to('admin.message_templates.delete.batch') ?>" id="batchDeleteTemplatesForm">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <a href="<?= route_to('admin.message_templates.new') ?>" 
                       class="btn btn-success"
                       data-bs-toggle="ajax-modal"
                       data-title="Adicionar Novo Template"
                       data-modal-size="modal-lg">
                        <i class="fa-solid fa-plus fa-fw"></i>
                        Adicionar Novo Template
                    </a>
                    
                    <button type="button" class="btn btn-outline-primary" 
                            id="editSelectedTemplateBtnModal"
                            data-bs-toggle="ajax-modal"
                            data-title="Editar Template"
                            data-modal-size="modal-lg">
                        <i class="fa-solid fa-pencil fa-fw"></i>
                        Editar Selecionado
                    </button>
                    
                    <button type="submit" class="btn btn-outline-danger" id="deleteSelectedTemplateBtn" 
                            onclick="return confirm('Tem certeza que deseja excluir os templates selecionados?\n\nO Template Padrão (ID 1) não pode ser excluído.');">
                        <i class="fa-solid fa-trash fa-fw"></i>
                        Excluir Selecionados
                    </button>
                </div>

                <?php if (!empty($templates) && is_array($templates)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="templatesTable">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 20px;">
                                        <input class="form-check-input" type="checkbox" id="selectAllTemplates">
                                    </th>
                                    <th>ID</th>
                                    <th>Nome Identificador</th>
                                    <th>Conteúdo (Início)</th>
                                    <th style="width: 50px;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($templates as $template): ?>
                                    <tr>
                                        <td>
                                            <input class="form-check-input row-checkbox-template" type="checkbox" 
                                                   name="selected_ids[]" value="<?= esc($template->id) ?>" 
                                                   <?= ($template->id == 1) ? 'disabled' : '' ?>>
                                        </td>
                                        <td><?= esc($template->id) ?></td>
                                        <td>
                                            <?= esc($template->name) ?>
                                            
                                            <?php if ($template->id == 1): ?>
                                                <span class="badge bg-primary ms-2">Padrão</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= esc(substr($template->content, 0, 100)) . (strlen($template->content) > 100 ? '...' : '') ?></td>
                                        <td>
                                            <a href="<?= route_to('admin.message_templates.edit', $template->id) ?>"
                                               class="btn btn-sm btn-outline-primary"
                                               data-bs-toggle="ajax-modal"
                                               data-title="Editar Template #<?= esc($template->id) ?>"
                                               data-modal-size="modal-lg"
                                               title="Editar">
                                                <i class="fa-solid fa-pencil fa-fw"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning" role="alert">
                        Nenhum template de mensagem cadastrado (nem mesmo o Padrão).
                        <strong>Execute o seeder:</strong> `php spark db:seed DefaultMessageTemplateSeeder`
                    </div>
                <?php endif; ?>
            
            </form> </div>
    </div>
    <div class="tab-pane fade" id="tab-credentials" role="tabpanel" aria-labelledby="credentials-tab" tabindex="0">
        <div class="p-3 border border-top-0 rounded-bottom">
            <h4>Gerenciamento de Credenciais</h4>
            <p>Em breve: Área para gerenciar o `client_id`, `client_secret` e o fluxo de re-autenticação (refresh token) do Mercado Livre.</p>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-other" role="tabpanel" aria-labelledby="other-tab" tabindex="0">
        <div class="p-3 border border-top-0 rounded-bottom">
            <h4>Outras Configurações</h4>
            <p>Em breve: Outras configurações relacionadas à API do Mercado Livre.</p>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $(document).ready(function () {
        // 1. Inicializa o DataTables para Templates
        const templatesTable = new DataTable('#templatesTable', {
            "columnDefs": [
                {
                    "targets": [0, 4], // Coluna do checkbox e Ações
                    "orderable": false,
                    "searchable": false
                }
            ],
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/2.0.8/i18n/pt-BR.json"
            },
            "order": [[ 1, "asc" ]] // Ordenar por ID (coluna 1) ascendente
        });

        // 2. Lógica "Selecionar Todos" para Templates
        $('#selectAllTemplates').on('click', function () {
            const isChecked = $(this).prop('checked');
            // Seleciona todos, exceto os desabilitados (ID 1)
            templatesTable.rows().nodes().to$().find('.row-checkbox-template:not(:disabled)').prop('checked', isChecked);
        });

        // 3. Desmarcar "Selecionar Todos"
        $('#templatesTable tbody').on('change', '.row-checkbox-template', function () {
            if (!$(this).prop('checked')) {
                $('#selectAllTemplates').prop('checked', false);
            }
        });

        // 4. Lógica "Editar Selecionado" para Templates (para o modal)
        $('#editSelectedTemplateBtnModal').on('mousedown', function(e) {
            const selected = templatesTable.rows().nodes().to$().find('.row-checkbox-template:checked');
            
            if (selected.length === 0) {
                alert('Por favor, selecione um template para editar.');
                e.stopImmediatePropagation(); // Impede o 'click' (e o modal) de disparar
                return;
            }
            if (selected.length > 1) {
                alert('Você só pode editar um template por vez.');
                e.stopImmediatePropagation(); // Impede o 'click' (e o modal) de disparar
                return;
            }
            
            const templateId = selected.val();
            const editUrl = '<?= rtrim(route_to('admin.message_templates.edit', 1), '1') ?>' + templateId;
            $(this).attr('data-url', editUrl); // Define o URL para o script global pegar
        });

        // 5. Lógica "Excluir Selecionados" para Templates
        $('#batchDeleteTemplatesForm').on('submit', function(e) {
            const selected = templatesTable.rows().nodes().to$().find('.row-checkbox-template:checked');
            if (selected.length === 0) {
                alert('Por favor, selecione pelo menos um template para excluir.');
                e.preventDefault();
                return false;
            }
        });
    });
</script>
<?= $this->endSection() ?>