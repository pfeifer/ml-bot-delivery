<?= $this->extend('admin/template') ?>

<?= $this->section('title') ?>Configurações do Mercado Livre<?= $this->endSection() ?>

<?= $this->section('page_title') ?>Configurações do Mercado Livre<?= $this->endSection() ?>

<?= $this->section('content') ?>

<ul class="nav nav-tabs" id="mlSettingsTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#tab-profile" type="button" role="tab" aria-controls="tab-profile" aria-selected="false">
            <i class="fa-solid fa-user-gear fa-fw"></i>
            Perfil do Vendedor
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="credentials-tab" data-bs-toggle="tab" data-bs-target="#tab-credentials" type="button" role="tab" aria-controls="tab-credentials" aria-selected="true">
            <i class="fa-solid fa-key fa-fw"></i>
            Credenciais e Status
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="messages-tab" data-bs-toggle="tab" data-bs-target="#tab-messages" type="button" role="tab" aria-controls="tab-messages" aria-selected="false">
            <i class="fa-solid fa-envelope-open-text fa-fw"></i>
            Templates de Mensagem
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
    
    <div class="tab-pane fade" id="tab-messages" role="tabpanel" aria-labelledby="messages-tab" tabindex="0">
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
                    
                    <button type="submit" class="btn btn-outline-danger" id="deleteSelectedTemplateBtn">
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
            
            </form> 
        </div>
    </div>
    
    <div class="tab-pane fade" id="tab-profile" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">
        <div class="p-3 border border-top-0 rounded-bottom">
            <?php if (isset($seller_info) && $seller_info !== null): ?>
                
                <div class="alert alert-success" role="alert">
                    <i class="fa-solid fa-check-circle fa-fw"></i>
                    <strong>Conexão bem-sucedida!</strong> Os dados da sua conta de vendedor foram carregados via API.
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
                        Verifique o status das suas credenciais na aba "Credenciais e Status".
                    </p>
                </div>

            <?php endif; ?>
        </div>
    </div>
    
    <div class="tab-pane fade show active" id="tab-credentials" role="tabpanel" aria-labelledby="credentials-tab" tabindex="0">
        <div class="p-3 border border-top-0 rounded-bottom">
            
            <div id="refresh-alert-placeholder" class="mb-3"></div>
            
            <div>
                    <h5><i class="fa-solid fa-key fa-fw"></i> Status dos Tokens (Banco de Dados)</h5>
                    <?php if (isset($credentials) && $credentials !== null): ?>
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
                            title="Executa a atualização em segundo plano">
                            <i class="fa-solid fa-sync fa-fw"></i>
                            Forçar Atualização do Token (Refresh)
                        </button>
                        <div class="form-text">
                            Use isso se a conexão falhar. A atualização já é automática a cada 6 horas (pelo Cron Job) ou quando o token expira.
                        </div>
                    </div>
                    <?php else: ?>
                        <div class="alert alert-danger" role="alert">
                        <strong>Erro Crítico:</strong> Não foi possível carregar as credenciais do banco de dados (tabela `ml_credentials`).
                        </div>
                    <?php endif; ?>
            </div>
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
    // --- SCRIPT PARA "LEMBRAR" DA ABA ATIVA ---
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('#mlSettingsTabs button[data-bs-toggle="tab"]');
        const storageKey = 'mlSettingsActiveTab'; // Chave no localStorage

        // 1. Quando uma aba é mostrada, salva seu ID no localStorage
        tabs.forEach(tabEl => {
            tabEl.addEventListener('shown.bs.tab', function(event) {
                // event.target é o botão da aba que foi clicado
                const tabId = event.target.getAttribute('data-bs-target');
                localStorage.setItem(storageKey, tabId);
            });
        });

        // 2. No carregamento da página, verifica se há uma aba salva
        const activeTabId = localStorage.getItem(storageKey);
        if (activeTabId) {
            // Encontra o botão que corresponde ao ID salvo
            const tabToActivate = document.querySelector(`#mlSettingsTabs button[data-bs-target="${activeTabId}"]`);
            if (tabToActivate) {
                // Usa a API do Bootstrap para mostrar a aba salva, em vez da padrão
                // (O padrão 'active' é a aba de Credenciais, se nada for salvo)
                new bootstrap.Tab(tabToActivate).show();
            }
        }
    });
</script>

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
                // MODIFICADO: Usa a nova função showAlert
                showAlert('Por favor, selecione um template para editar.', 'Atenção');
                e.stopImmediatePropagation(); // Impede o 'click' (e o modal) de disparar
                return;
            }
            if (selected.length > 1) {
                // MODIFICADO: Usa a nova função showAlert
                showAlert('Você só pode editar um template por vez.', 'Atenção');
                e.stopImmediatePropagation(); // Impede o 'click' (e o modal) de disparar
                return;
            }
            
            const templateId = selected.val();
            const editUrl = '<?= rtrim(route_to('admin.message_templates.edit', 1), '1') ?>' + templateId;
            $(this).attr('data-url', editUrl); // Define o URL para o script global pegar
        });

        // 5. Lógica "Excluir Selecionados" para Templates (MODIFICADO)
        $('#batchDeleteTemplatesForm').on('submit', function(e) {
            e.preventDefault(); // Impede o envio imediato
            const form = $(this);
            const selected = templatesTable.rows().nodes().to$().find('.row-checkbox-template:checked');
            
            if (selected.length === 0) {
                showAlert('Por favor, selecione pelo menos um template para excluir.', 'Atenção');
                return false;
            }
            
            // Se a validação passar, mostra o modal de confirmação
            const msg = 'Tem certeza que deseja excluir os <strong>' + selected.length + '</strong> templates selecionados?<br><br><small>O Template Padrão (ID 1) não pode ser excluído e será ignorado.</small>';
            showConfirm(msg, 'Confirmar Exclusão', function() {
                form.get(0).submit(); // Envia o formulário de verdade
            });
        });

        // --- INÍCIO DO SCRIPT ATUALIZADO PARA O BOTÃO REFRESH (COM ALERTA BOOTSTRAP) ---
        $('#forceRefreshButton').on('click', function(e) {
            e.preventDefault();
            var $button = $(this);
            var originalHtml = $button.html();
            var url = $button.data('url');
            var $alertPlaceholder = $('#refresh-alert-placeholder'); // Seleciona o placeholder

            // Limpa alertas antigos
            $alertPlaceholder.html('');

            // Desabilita o botão e mostra "carregando"
            $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Atualizando...');

            // Função para criar o HTML do Alerta
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
                    // Sucesso!
                    var successMsg = '<strong>Sucesso!</strong> Token atualizado. A página será recarregada em 3 segundos...';
                    $alertPlaceholder.html(createAlert(successMsg, 'success'));
                    
                    // Recarrega a página após 3 segundos para o usuário ler a mensagem
                    setTimeout(function() {
                        location.reload();
                    }, 3000); // 3000ms = 3 segundos
                })
                .fail(function(jqXHR) {
                    // Erro!
                    console.error('Erro ao forçar refresh:', jqXHR.responseText);
                    var errorMsg = '<strong>Erro!</strong> Não foi possível atualizar o token. ' + (jqXHR.responseText || 'Verifique os logs.');
                    $alertPlaceholder.html(createAlert(errorMsg, 'danger'));

                    // Restaura o botão em caso de falha para que o usuário possa tentar novamente
                    $button.prop('disabled', false).html(originalHtml);
                });
        });
        // --- FIM DO SCRIPT ATUALIZADO ---

    });
</script>
<?= $this->endSection() ?>