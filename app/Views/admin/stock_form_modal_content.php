<?php
// Este ficheiro é carregado dentro do modal AJAX
$validationErrors = $validationErrors ?? session()->getFlashdata('errors');
?>

<?php if (!empty($validationErrors) && is_array($validationErrors)): ?>
    <div class="alert alert-danger" role="alert">
         <h5 class="alert-heading">Erro de Validação</h5>
         <p>Por favor, corrija os problemas abaixo:</p>
        <ul>
            <?php foreach ($validationErrors as $error): ?>
                <li><?= esc($error) ?></li>
            <?php endforeach ?>
        </ul>
    </div>
<?php endif; ?>

<?= form_open(route_to('admin.stock.add')) ?>
<?= csrf_field() ?>

<div class="mb-3">
    <label for="product_id" class="form-label">Produto:</label>
    <select name="product_id" id="product_id_modal"
        class="form-select <?= (isset($validationErrors['product_id'])) ? 'is-invalid' : '' ?>" required>
        <option value="">Selecione um Produto</option>
        <?php if (!empty($productsForStock) && is_array($productsForStock)): ?>
            <?php foreach ($productsForStock as $product): ?>
                <option value="<?= esc($product->id) ?>" data-type="<?= esc($product->product_type) ?>"
                    <?= old('product_id') == $product->id ? 'selected' : '' ?>>
                    #<?= esc($product->id) ?> - <?= esc($product->title ?: $product->ml_item_id) ?>
                    (<?= esc($product->product_type === 'code' ? 'Código Único' : 'Link Estático') ?>)
                </option>
            <?php endforeach; ?>
        <?php endif; ?>
    </select>
</div>

<div class="mb-3">
    <label for="codes_or_link" class="form-label">Códigos (um por linha) ou Link Estático:</label>
    <textarea name="codes_or_link" id="codes_or_link_modal" rows="10"
        class="form-control <?= (isset($validationErrors['codes_or_link'])) ? 'is-invalid' : '' ?>"
        required><?= old('codes_or_link') ?></textarea>
    <div class="form-text">Para produtos 'Código Único', insira um código por linha. Para 'Link Estático',
        insira apenas o link (ele substituirá o existente, se houver).</div>
</div>

<div class="mb-3" id="expires_at_field_modal" style="display: none;"> <label for="expires_at" class="form-label">Data
        de Validade (Opcional):</label>
    <input type="date" name="expires_at" id="expires_at_modal"
        class="form-control <?= (isset($validationErrors['expires_at'])) ? 'is-invalid' : '' ?>"
        value="<?= old('expires_at') ?>">
    <div class="form-text">Deixe em branco se os códigos não tiverem data de validade. Aplica-se apenas a
        'Código Único'.</div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
    <button type="submit" class="btn btn-primary" style="background-color: #DC4814; border-color: #DC4814;">
        Adicionar ao Estoque
    </button>
</div>

<?= form_close() ?>