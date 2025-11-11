<?= $this->extend('admin/template') ?>

<?= $this->section('title') ?>Configurações do Mercado Livre<?= $this->endSection() ?>

<?= $this->section('page_title') ?>Configurações do Mercado Livre<?= $this->endSection() ?>

<?= $this->section('content') ?>

<ul class="nav nav-tabs" id="mlSettingsTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#tab-profile" type="button" role="tab" aria-controls="tab-profile" aria-selected="false">
            <i class="fa-solid fa-user-gear fa-fw"></i>
            Perfil (App Ativo)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="credentials-tab" data-bs-toggle="tab" data-bs-target="#tab-credentials" type="button" role="tab" aria-controls="tab-credentials" aria-selected="true">
            <i class="fa-solid fa-key fa-fw"></i>
            Aplicações (Apps) e Status
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="messages-tab" data-bs-toggle="tab" data-bs-target="#tab-messages" type="button" role="tab" aria-controls="tab-messages" aria-selected="false">
            <i class="fa-solid fa-envelope-open-text fa-fw"></i>
            Templates de Mensagem
        </button>
    </li>
</ul>

<div class="tab-content" id="mlSettingsTabsContent">
    
    <div class="tab-pane fade" id="tab-messages" role="tabpanel" aria-labelledby="messages-tab" tabindex="0">
        <div class="p-3 border border-top-0 rounded-bottom">
            
            <p>Gerencie os templates de mensagem. O "Template Padrão (ID #1)" será usado se nenhum for especificado no cadastro do produto.</p>

            <div class="mb-3" id="page-action-buttons-templates">
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
                
                <button type="submit" class="btn btn-outline-danger" id="deleteSelectedTemplateBtn" form="batchDeleteTemplatesForm">
                    <i class="fa-solid fa-trash fa-fw"></i>
                    Excluir Selecionados
                </button>
            </div>

            <form method="POST" action="<?= route_to('admin.message_templates.delete.batch') ?>" id="batchDeleteTemplatesForm">
                <?= csrf_field() ?>

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
            
            </form> 
        </div>
    </div>
    
    <div class="tab-pane fade" id="tab-profile" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">
        <div class="p-3 border border-top-0 rounded-bottom">
            <?php if (isset($seller_info) && $seller_info !== null): ?>
                
                <div class="alert alert-success" role="alert">
                    <i class="fa-solid fa-check-circle fa-fw"></i>
                    <strong>Conexão bem-sucedida!</strong> Os dados da sua conta de vendedor (vinculada à aplicação <strong>ativa</strong>) foram carregados via API.
                </div>

                <h5><i class="fa-solid fa-user-circle fa-fw"></i> Informações do Vendedor (Mercado Livre)</h5>
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        ID (Seller ID):
                        <strong><?= esc($seller_info->id ?? 'N/A') ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Nickname:
                        <strong><?= esc($seller_info->nickname ?? 'N/A') ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Nome:
                        <strong><?= esc($seller_info->first_name ?? '') ?> <?= esc($seller_info->last_name ?? '') ?></strong>
                    </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                        Email:
                        <strong><?= esc($seller_info->email ?? 'N/A') ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Site:
                        <strong><?= esc($seller_info->site_id ?? 'N/A') ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Status da Conta:
                        <span class="badge bg-success"><?= esc($seller_info->status->site_status ?? 'N/A') ?></span>
                    </li>
                </ul>

            <?php else: ?>
                
                <div class="alert alert-danger" role="alert">
                    <h4 class="alert-heading"><i class="fa-solid fa-triangle-exclamation fa-fw"></i> Conexão Falhou!</h4>
                    <p>Não foi possível carregar os dados da sua conta do Mercado Livre (API `/users/me`).</p>
                    <hr>
                    <p class="mb-0">
                        Verifique o status da sua <strong>aplicação ativa</strong> na aba "Aplicações (Apps) e Status".
                        Ela pode não ter sido autorizada ou o Access Token expirou e não pôde ser renovado.
                    </p>
                </div>

            <?php endif; ?>
        </div>
    </div>

    <div class="tab-pane fade show active" id="tab-credentials" role="tabpanel" aria-labelledby="credentials-tab" tabindex="0">
        <div class="p-3 border border-top-0 rounded-bottom">
            
            <div class="mb-4">
                <h5><i class="fa-solid fa-box-archive fa-fw"></i> Gerenciar Aplicações (Contas)</h5>
                <p>Liste, adicione ou edite as configurações das suas aplicações do Mercado Livre. Use o "switch" para definir qual aplicação está ativa no momento.</p>
                
                <a href="<?= route_to('admin.mercadolivre.credentials.new') ?>"
                   class="btn btn-success mb-3"
                   data-bs-toggle="ajax-modal"
                   data-title="Adicionar Nova Aplicação ML"
                   data-modal-size="modal-lg"> <i class="fa-solid fa-plus fa-fw"></i> Adicionar Nova Aplicação
                </a>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">Ativo</th>
                                <th>Nome da Aplicação</th>
                                <th>Client ID</th>
                                <th>Seller ID (Vendedor)</th>
                                <th style="width: 150px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($all_credentials)): foreach ($all_credentials as $cred): ?>
                                <tr <?= $cred->is_active ? 'class="table-primary"' : '' ?>>
                                    <td>
                                        <div class="form-check form-switch d-flex justify-content-center">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                   title="Ativar esta aplicação"
                                                   style="transform: scale(1.3);"
                                                   <?= $cred->is_active ? 'checked disabled' : '' ?>
                                                   onchange="if(confirm('Tem certeza que deseja ativar a aplicação \'<?= esc($cred->app_name, 'js') ?>\'? Isso desativará qualquer outra aplicação.')) { window.location.href = '<?= route_to('admin.mercadolivre.credentials.activate', $cred->id) ?>'; } else { this.checked = false; }">
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?= esc($cred->app_name) ?></strong>
                                        <?= $cred->is_active ? '<span class="badge bg-primary ms-2">Ativo</span>' : '' ?>
                                    </td>
                                    <td>
                                        <code><?= '...' . esc(substr($cred->client_id, -6)) ?></code>
                                    </td>
                                    <td>
                                        <code><?= esc($cred->seller_id ?: 'Não vinculado') ?></code>
                                    </td>
                                    <td>
                                        <a href="<?= route_to('admin.mercadolivre.credentials.edit', $cred->id) ?>"
                                           class="btn btn-sm btn-outline-primary"
                                           data-bs-toggle="ajax-modal"
                                           data-title="Editar: <?= esc($cred->app_name, 'js') ?>"
                                           data-modal-size="modal-lg" title="Editar">
                                            <i class="fa-solid fa-pencil fa-fw"></i>
                                        </a>
                                        
                                        <?php if (!$cred->is_active): // Não deixa excluir app ativa ?>
                                        <a href="<?= route_to('admin.mercadolivre.credentials.delete', $cred->id) ?>"
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Tem certeza que deseja excluir a aplicação \'<?= esc($cred->app_name, 'js') ?>\'?')"
                                           title="Excluir">
                                            <i class="fa-solid fa-trash fa-fw"></i>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">Nenhuma aplicação cadastrada. Clique em "Adicionar Nova Aplicação".</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <hr class="my-4">

            <?php if (isset($credentials) && $credentials !== null): // $credentials agora é a ATIVA ?>
                
                <h4 class="mb-3">Status da Aplicação Ativa (<?= esc($credentials->app_name) ?>)</h4>

                <div class_alias="mb-4 p-3 border rounded bg-body-tertiary">
                    <?php if (!empty($credentials->refresh_token)): ?>
                        <h5 class="text-success"><i class="fa-solid fa-check-circle fa-fw"></i> Aplicação Ativa Autorizada</h5>
                        <p>
                            Seu Refresh Token está salvo. A aplicação renovará o Access Token automaticamente.
                            Se você revogou as permissões no Mercado Livre, use o botão abaixo para reautorizar.
                        </p>
                        <a href="<?= route_to('admin.mercadolivre.authorize') ?>" class="btn btn-outline-warning">
                            <i class="fa-solid fa-key fa-fw"></i>
                            Re-autorizar Aplicação Ativa
                        </a>
                    <?php else: ?>
                        <h5 class="text-danger"><i class="fa-solid fa-triangle-exclamation fa-fw"></i> Autorização Pendente</h5>
                        <p>
                            A aplicação <strong>ativa</strong> ainda não foi autorizada a acessar sua conta do Mercado Livre.
                            Clique no botão abaixo para conceder as permissões.
                        </p>
                        <a href="<?= route_to('admin.mercadolivre.authorize') ?>" class="btn btn-primary btn-lg" style="background-color: #009EE3; border-color: #009EE3;">
                            <i class="fa-solid fa-handshake-simple fa-fw"></i>
                            Autorizar Aplicação Ativa
                        </a>
                    <?php endif; ?>
                </div>

                <div id="refresh-alert-placeholder" class="mb-3 mt-3"></div>
                
                <div class="mt-3">
                        <h5><i class="fa-solid fa-key fa-fw"></i> Status dos Tokens (Aplicação Ativa)</h5>
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Access Token:
                                <?php if (!empty($credentials->access_token)): ?>
                                    <span class="badge bg-success">Presente</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Ausente</span>
                                <?php endif; ?>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Refresh Token:
                                <?php if (!empty($credentials->refresh_token)): ?>
                                    <span class="badge bg-success">Presente</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Ausente</span>
                                <?php endif; ?>
                            </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                Token Atualizado em:
                                <strong><?= esc($credentials->token_updated_at ? date('d/m/Y H:i', strtotime($credentials->token_updated_at)) : 'Nunca') ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Expira em (segundos):
                                <strong><?= esc($credentials->expires_in ?? 'N/A') ?></strong>
                            </li>
                        </ul>
                        <div class="d-grid gap-2 mt-3">
                            <button type="button" 
                                id="forceRefreshButton" 
                                class="btn btn-outline-primary" 
                                data-url="<?= route_to('cron.refresh-token', 'mR7HAhCdw9JPqylpbSLxWeyC879SoLNw') // USA A CHAVE DO SEU CRONCONTROLLER ?>"
                                title="Executa a atualização em segundo plano"
                                <?= (empty($credentials->refresh_token)) ? 'disabled' : '' ?>>
                                <i class="fa-solid fa-sync fa-fw"></i>
                                Forçar Atualização do Token (Refresh)
                            </button>
                            <div class="form-text">
                                Usa o Refresh Token da aplicação ativa para obter um novo Access Token.
                            </div>
                        </div>
                </div>

            <?php else: ?>
                <div class="alert alert-warning" role="alert">
                    <h4 class="alert-heading"><i class="fa-solid fa-toggle-off fa-fw"></i> Nenhuma Aplicação Ativa</h4>
                    <p>Não há nenhuma aplicação marcada como "ativa". Por favor, adicione uma nova aplicação ou ative uma existente na lista acima para que o robô possa funcionar.</p>
                </div>
            <?php endif; ?>

        </div>
    </div>
    </div> <?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // =================================================================
    // SCRIPT PARA "LEMBRAR" DA ABA ATIVA (IIFE para rodar imediatamente)
    // =================================================================
    (function() {
        const tabs = document.querySelectorAll('#mlSettingsTabs button[data-bs-toggle="tab"]');
        const storageKey = 'mlSettingsActiveTab'; // Chave no localStorage

        // 1. Quando uma aba é mostrada, salva seu ID no localStorage
        tabs.forEach(tabEl => {
            tabEl.addEventListener('shown.bs.tab', function(event) {
                const tabId = event.target.getAttribute('data-bs-target');
                localStorage.setItem(storageKey, tabId);
            });
        });

        // 2. No carregamento (ou recarregamento AJAX), verifica se há uma aba salva
        const activeTabId = localStorage.getItem(storageKey);
        
        // Adia a execução para o próximo "tick" do navegador.
        setTimeout(function() {
            if (activeTabId) {
                const tabToActivate = document.querySelector(`#mlSettingsTabs button[data-bs-target="${activeTabId}"]`);
                if (tabToActivate) {
                    const tabInstance = bootstrap.Tab.getInstance(tabToActivate) || new bootstrap.Tab(tabToActivate);
                    tabInstance.show();
                }
            }
        }, 10); // 10ms é o suficiente.
    })();

    // =================================================================
    // SCRIPT DO DATATABLE E BOTÕES (IIFE com jQuery)
    // =================================================================
    (function($) {
        
        let templatesTable = null; // Guarda a instância do DataTable
        const messagesTabEl = document.getElementById('messages-tab');
        
        // Define o objeto de linguagem PT-BR uma vez
        const dataTableLangPtBr = {
            "emptyTable": "Nenhum registro encontrado",
            "info": "Mostrando de _START_ até _END_ de _TOTAL_ registros",
            "infoEmpty": "Mostrando 0 até 0 de 0 registros",
            "infoFiltered": "(Filtrados de _MAX_ registros)",
            "infoThousands": ".",
            "loadingRecords": "Carregando...",
            "processing": "Processando...",
            "zeroRecords": "Nenhum registro encontrado",
            "search": "Pesquisar:",
            "paginate": { "first": "Primeiro", "last": "Último", "next": "Próximo", "previous": "Anterior" },
            "aria": { "sortAscending": ": Ativar para ordenar a coluna de forma ascendente", "sortDescending": ": Ativar para ordenar a coluna de forma descendente" },
            "autoFill": { "cancel": "Cancelar", "fill": "Preencher todos os campos com <i>%d</i>", "fillHorizontal": "Preencher campos horizontalmente", "fillVertical": "Preencher campos verticalmente" },
            "buttons": { "copy": "Copiar", "copyTitle": "Copiar para a Área de Transferência", "copySuccess": { "_": "%d linhas copiadas", "1": "1 linha copiada" }, "csv": "CSV", "excel": "Excel", "pageLength": { "-1": "Mostrar todos os registros", "_": "Mostrar %d registros" }, "pdf": "PDF", "print": "Imprimir", "colvis": "Visibilidade da Coluna" },
            "select": { "rows": { "_": "Selecionado %d linhas", "0": "Nenhuma linha selecionada", "1": "Selecionado 1 linha" } }
        };

        // Função para inicializar a tabela
        function initTemplatesTable() {
            // Verifica se a tabela já existe (pode ser recarregamento AJAX)
            if ($.fn.DataTable.isDataTable('#templatesTable')) {
                templatesTable = $('#templatesTable').DataTable();
                templatesTable.columns.adjust().draw();
            } else if (!templatesTable && $('#templatesTable').length > 0) { // Só inicializa UMA VEZ
                templatesTable = new DataTable('#templatesTable', {
                    "columnDefs": [
                        {
                            "targets": [0, 4], // Coluna do checkbox e Ações
                            "orderable": false,
                            "searchable": false
                        }
                    ],
                    "language": dataTableLangPtBr, 
                    "order": [[ 1, "asc" ]] // Ordenar por ID (coluna 1) ascendente
                });
            }
        }

        if (messagesTabEl) {
            // 1. Ouve o evento 'shown.bs.tab' (dispara DEPOIS que a aba fica visível)
            messagesTabEl.addEventListener('shown.bs.tab', function (event) {
                initTemplatesTable();
            });
        }
        
        // 2. Verifica se a aba já está ativa no carregamento (devido ao localStorage)
        const activeTabButton = document.querySelector('#mlSettingsTabs button.active');
        if (activeTabButton && activeTabButton.id === 'messages-tab') {
            initTemplatesTable();
        }

        // --- Lógica dos Botões (Templates) ---
        
        $(document).off('click', '#selectAllTemplates').on('click', '#selectAllTemplates', function () {
            if (!templatesTable) return; 
            const isChecked = $(this).prop('checked');
            templatesTable.rows().nodes().to$().find('.row-checkbox-template:not(:disabled)').prop('checked', isChecked);
        });

        $('#templatesTable tbody').on('change', '.row-checkbox-template', function () {
            if (!$(this).prop('checked')) {
                $('#selectAllTemplates').prop('checked', false);
            }
        });

        $(document).off('mousedown', '#editSelectedTemplateBtnModal').on('mousedown', '#editSelectedTemplateBtnModal', function(e) {
            if (!templatesTable) { 
                showAlert('Por favor, clique na aba "Templates de Mensagem" primeiro para carregar a tabela.', 'Atenção');
                e.stopImmediatePropagation(); 
                return;
            }
            
            const selected = templatesTable.rows().nodes().to$().find('.row-checkbox-template:checked');
            
            if (selected.length === 0) {
                showAlert('Por favor, selecione um template para editar.', 'Atenção');
                e.stopImmediatePropagation(); 
                return;
            }
            if (selected.length > 1) {
                showAlert('Você só pode editar um template por vez.', 'Atenção');
                e.stopImmediatePropagation();
                return;
            }
            
            const templateId = selected.val();
            const editUrl = '<?= rtrim(route_to('admin.message_templates.edit', 1), '1') ?>' + templateId;
            $(this).attr('data-url', editUrl);
        });

        $(document).off('submit', '#batchDeleteTemplatesForm').on('submit', '#batchDeleteTemplatesForm', function(e) {
            e.preventDefault(); 
            
            if (!templatesTable) { 
                showAlert('Por favor, clique na aba "Templates de Mensagem" primeiro para carregar a tabela.', 'Atenção');
                return false;
            }

            const form = $(this);
            const selected = templatesTable.rows().nodes().to$().find('.row-checkbox-template:checked');
            
            if (selected.length === 0) {
                showAlert('Por favor, selecione pelo menos um template para excluir.', 'Atenção');
                return false;
            }
            
            const msg = 'Tem certeza que deseja excluir os <strong>' + selected.length + '</strong> templates selecionados?<br><br><small>O Template Padrão (ID 1) não pode ser excluído e será ignorado.</small>';
            
            showConfirm(msg, 'Confirmar Exclusão', function() {
                // Mostra spinner no local
                $('#page-content-container').html('<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Carregando...</span></div></div>');
                $('#alert-container').empty(); // Limpa alertas antigos

                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: form.serialize(),
                    dataType: 'html', 
                    
                    success: function(responseHtml) {
                        try {
                            var $newHtml = $('<div>').html(responseHtml);
                            
                            var newAlerts = $newHtml.find('#alert-container').html();
                            var newPageContent = $newHtml.find('#page-content-container').html();
                            var newScripts = $newHtml.find('#ajax-scripts').html();
                            var newPageTitle = $newHtml.find('title').text();
                            var newH1Title = $newHtml.find('#page-title-h1').html();
                            
                            if (newPageContent !== undefined && newAlerts !== undefined) {
                                $('#alert-container').html(newAlerts);
                                $('#page-content-container').html(newPageContent);
                                
                                if (newH1Title) $('#page-title-h1').html(newH1Title);
                                if (newPageTitle) document.title = newPageTitle;
                                
                                $('#ajax-scripts-container').empty().html(newScripts ? '<div id="ajax-scripts">' + newScripts + '</div>' : '');
                                
                                $('#main-content-wrapper').scrollTop(0);
                            } else {
                                location.reload(); // Fallback
                            }
                        } catch(e) {
                            console.error("Erro ao processar recarga AJAX (delete tpl):", e);
                            location.reload(); // Fallback
                        }
                    },
                    error: function() {
                        location.reload(); // Fallback em caso de erro
                    }
                });
            });
        });

        // --- SCRIPT DO BOTÃO REFRESH (Aplicações) ---
        $(document).off('click', '#forceRefreshButton').on('click', '#forceRefreshButton', function(e) {
            e.preventDefault();
            var $button = $(this);
            var originalHtml = $button.html();
            var url = $button.data('url');
            var $alertPlaceholder = $('#refresh-alert-placeholder'); 

            $alertPlaceholder.html('');
            $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Atualizando...');

            function createAlert(message, type) {
                return `
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
            }

            $.get(url)
                .done(function(response) {
                    var successMsg = '<strong>Sucesso!</strong> Token da aplicação ativa foi atualizado. A página será recarregada em 3 segundos...';
                    $alertPlaceholder.html(createAlert(successMsg, 'success'));
                    
                    setTimeout(function() {
                        // Faz um reload parcial da página atual (via AJAX)
                        let $activeLink = $('#adminSidebar a.nav-link.active');
                        if ($activeLink.length > 0) {
                            $activeLink.click();
                        } else {
                            location.reload();
                        }
                    }, 3000);
                })
                .fail(function(jqXHR) {
                    console.error('Erro ao forçar refresh:', jqXHR.responseText);
                    var errorMsg = '<strong>Erro!</strong> Não foi possível atualizar o token. ' + (jqXHR.responseText || 'Verifique os logs.');
                    $alertPlaceholder.html(createAlert(errorMsg, 'danger'));
                    $button.prop('disabled', false).html(originalHtml);
                });
        });

    })(jQuery); // Fim do IIFE do jQuery

</script>
<?= $this->endSection() ?>