<?= $this->extend('admin/template') ?>

<?= $this->section('title') ?>Gerenciar Templates de Mensagem<?= $this->endSection() ?>

<?= $this->section('page_title') ?>Templates de Mensagem Pós-Venda<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="mb-3">
    <a href="<?= route_to('admin.message_templates.new') ?>" class="btn btn-success">
        <i class="fa-solid fa-plus fa-fw"></i>
        Adicionar Template
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
                        <td><?= esc($template->name) ?></td>
                        <td><?= esc(substr($template->content, 0, 100)) . (strlen($template->content) > 100 ? '...' : '') ?></td>
                        <td>
                            <a href="<?= route_to('admin.message_templates.edit', $template->id) ?>"
                                class="btn btn-sm btn-outline-secondary" title="Editar">
                                <i class="fa-solid fa-pencil fa-fw"></i> Editar
                            </a>
                            <a href="<?= route_to('admin.message_templates.delete', $template->id) ?>"
                                class="btn btn-sm btn-outline-danger" title="Excluir"
                                onclick="return confirm('Tem certeza que deseja excluir o template \'<?= esc($template->name, 'js') ?>\'? Produtos que usam este template perderão a referência.');">
                                <i class="fa-solid fa-trash fa-fw"></i> Excluir
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="form-text">
        O "Template Padrão" (usado quando um produto não tem template específico) será o primeiro da lista acima (menor ID).
    </div>
<?php else: ?>
    <div class="alert alert-warning" role="alert">
        Nenhum template de mensagem cadastrado ainda. O sistema usará a mensagem padrão interna (hardcoded).
    </div>
<?php endif; ?>

<?= $this->endSection() ?>