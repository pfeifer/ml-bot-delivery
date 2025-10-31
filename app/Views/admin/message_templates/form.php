<?= $this->extend('admin/template') ?>

<?= $this->section('title') ?><?= ($action === 'create') ? 'Adicionar Template' : 'Editar Template' ?><?= $this->endSection() ?>

<?= $this->section('page_title') ?><?= ($action === 'create') ? 'Adicionar Novo Template de Mensagem' : 'Editar Template: ' . esc($template->name ?? '') ?><?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
// Garantir que $template não é nulo em modo 'create'
$template = $template ?? (object)['id' => null, 'name' => '', 'content' => ''];
$formAction = ($action === 'create') ? route_to('admin.message_templates.create') : route_to('admin.message_templates.update', $template->id);
?>

<?= form_open($formAction) ?>
<?= csrf_field() ?>

<?php $validationErrors = session()->getFlashdata('errors'); ?>

<div class="mb-3">
    <label for="name" class="form-label">Nome do Template</label>
    <input type="text" class="form-control <?= (isset($validationErrors['name'])) ? 'is-invalid' : '' ?>"
        id="name" name="name" value="<?= old('name', $template->name ?? '') ?>"
        placeholder="Ex: Entrega Código Padrão" required>
     <div class="form-text">Usado apenas para identificar o template na administração.</div>
    <?php if (isset($validationErrors['name'])): ?>
        <div class="invalid-feedback"><?= $validationErrors['name'] ?></div>
    <?php endif; ?>
</div>

<div class="mb-3">
    <label for="content" class="form-label">Conteúdo da Mensagem</label>
    <textarea class="form-control <?= (isset($validationErrors['content'])) ? 'is-invalid' : '' ?>"
        id="content" name="content" rows="10" required><?= old('content', $template->content ?? 'Olá! Agradecemos por sua compra.

Segue seu {delivery_content}

Qualquer dúvida, estamos à disposição.') ?></textarea>
    <div class="form-text">
        Esta é a mensagem que será enviada ao comprador. Use <strong>{delivery_content}</strong> onde o código ou link deve ser inserido.
    </div>
    <?php if (isset($validationErrors['content'])): ?>
        <div class="invalid-feedback"><?= $validationErrors['content'] ?></div>
    <?php endif; ?>
</div>

<div class="mt-4">
    <button type="submit" class="btn btn-primary" style="background-color: #DC4814; border-color: #DC4814;">Salvar Template</button>
    <a href="<?= route_to('admin.message_templates') ?>" class="btn btn-secondary">Cancelar</a>
</div>

<?= form_close() ?>

<?= $this->endSection() ?>