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

            <div class="mb-3">
                <a href="<?= route_to('admin.message_templates.new') ?>" class="btn btn-success">
                    <i class="fa-solid fa-plus fa-fw"></i>
                    Adicionar Novo Template
                </a>
            </div>

            <?php if (!empty($templates) && is_array($templates)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nome Identificador</th>
                                <th>Conteúdo (Início)</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($templates as $template): ?>
                                <tr>
                                    <td><?= esc($template->id) ?></td>
                                    <td>
                                        <?= esc($template->name) ?>
                                        
                                        <?php // *** CORREÇÃO AQUI: Adiciona badge "Padrão" *** ?>
                                        <?php if ($template->id == 1): ?>
                                            <span class="badge bg-primary ms-2">Padrão</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc(substr($template->content, 0, 100)) . (strlen($template->content) > 100 ? '...' : '') ?></td>
                                    <td>
                                        <a href="<?= route_to('admin.message_templates.edit', $template->id) ?>"
                                            class="btn btn-sm btn-outline-secondary" title="Editar">
                                            <i class="fa-solid fa-pencil fa-fw"></i> Editar
                                        </a>
                                        
                                        <?php // *** CORREÇÃO AQUI: Desabilita botão de excluir para o ID 1 *** ?>
                                        <?php if ($template->id != 1): ?>
                                            <a href="<?= route_to('admin.message_templates.delete', $template->id) ?>"
                                                class="btn btn-sm btn-outline-danger" title="Excluir"
                                                onclick="return confirm('Tem certeza que deseja excluir o template \'<?= esc($template->name, 'js') ?>\'?');">
                                                <i class="fa-solid fa-trash fa-fw"></i> Excluir
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-danger" disabled title="Não é possível excluir o template padrão">
                                                <i class="fa-solid fa-trash fa-fw"></i> Excluir
                                            </button>
                                        <?php endif; ?>
                                        <?php // *** FIM DA CORREÇÃO *** ?>
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

        </div>
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