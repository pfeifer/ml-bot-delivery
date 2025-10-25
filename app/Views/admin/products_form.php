<?= $this->extend('admin/template') ?>

<?= $this->section('title') ?><?= ($action === 'create') ? 'Adicionar Produto' : 'Editar Produto' ?><?= $this->endSection() ?>

<?= $this->section('page_title') ?><?= ($action === 'create') ? 'Adicionar Novo Produto' : 'Editar Produto #' . esc($product->id) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php 
    $formAction = ($action === 'create') ? route_to('admin.products.create') : route_to('admin.products.update', $product->id);
?>

<?= form_open($formAction) ?>
    <?= csrf_field() ?>

    <div class="mb-3">
        <label for="ml_item_id" class="form-label">ML Item ID</label>
        <input type="text" class="form-control <?= (isset(validation_errors()['ml_item_id'])) ? 'is-invalid' : '' ?>" 
               id="ml_item_id" name="ml_item_id" 
               value="<?= old('ml_item_id', $product->ml_item_id ?? '') ?>" 
               placeholder="Ex: MLB1234567890" 
               required>
        <?php if (isset(validation_errors()['ml_item_id'])): ?>
            <div class="invalid-feedback"><?= validation_errors()['ml_item_id'] ?></div>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <label for="title" class="form-label">Título do Anúncio (Opcional)</label>
        <input type="text" class="form-control <?= (isset(validation_errors()['title'])) ? 'is-invalid' : '' ?>" 
               id="title" name="title" 
               value="<?= old('title', $product->title ?? '') ?>"
               placeholder="Ex: Item de Teste – Por favor, NÃO OFERTAR!">
         <?php if (isset(validation_errors()['title'])): ?>
            <div class="invalid-feedback"><?= validation_errors()['title'] ?></div>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <label for="product_type" class="form-label">Tipo de Entrega</label>
        <select class="form-select <?= (isset(validation_errors()['product_type'])) ? 'is-invalid' : '' ?>" 
                id="product_type" name="product_type" required>
            <option value="">Selecione o tipo...</option>
            <option value="unique_code" <?= old('product_type', $product->product_type ?? '') === 'unique_code' ? 'selected' : '' ?>>
                Código Único (Gift Card, Voucher, Chave)
            </option>
            <option value="static_link" <?= old('product_type', $product->product_type ?? '') === 'static_link' ? 'selected' : '' ?>>
                Link Estático (Ebook, Curso, Download)
            </option>
        </select>
        <?php if (isset(validation_errors()['product_type'])): ?>
            <div class="invalid-feedback"><?= validation_errors()['product_type'] ?></div>
        <?php endif; ?>
         <div class="form-text">Define como o robô fará a entrega.</div>
    </div>
    
    <div class="mt-4">
        <button type="submit" class="btn btn-primary" style="background-color: #DC4814; border-color: #DC4814;">Salvar Produto</button>
        <a href="<?= route_to('admin.products') ?>" class="btn btn-secondary">Cancelar</a>
    </div>

<?= form_close() ?>

<?= $this->endSection() ?>