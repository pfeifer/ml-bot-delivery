<?= $this->extend('admin/template') ?>

<?= $this->section('title') ?>Gerenciar Estoque<?= $this->endSection() ?>

<?= $this->section('page_title') ?>Adicionar ao Estoque<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">Adicionar Códigos ou Link</h5>
        <?= form_open(route_to('admin.stock.add')) ?>
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="product_id" class="form-label">Produto:</label>
                <select name="product_id" id="product_id" class="form-select <?= (isset(validation_errors()['product_id'])) ? 'is-invalid' : '' ?>" required>
                    <option value="">Selecione um Produto</option>
                    <?php if (!empty($productsForStock) && is_array($productsForStock)): ?>
                        <?php foreach ($productsForStock as $product): ?>
                            <option value="<?= esc($product->id) ?>" <?= old('product_id') == $product->id ? 'selected' : '' ?>>
                                #<?= esc($product->id) ?> - <?= esc($product->title ?: $product->ml_item_id) ?> 
                                (<?= esc($product->product_type === 'unique_code' ? 'Código Único' : 'Link Estático') ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <?php if (isset(validation_errors()['product_id'])): ?>
                    <div class="invalid-feedback"><?= validation_errors()['product_id'] ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="codes_or_link" class="form-label">Códigos (um por linha) ou Link Estático:</label>
                <textarea name="codes_or_link" id="codes_or_link" rows="10" class="form-control <?= (isset(validation_errors()['codes_or_link'])) ? 'is-invalid' : '' ?>" required><?= old('codes_or_link') ?></textarea>
                 <div class="form-text">Para produtos 'Código Único', insira um código por linha. Para 'Link Estático', insira apenas o link (ele substituirá o existente, se houver).</div>
                 <?php if (isset(validation_errors()['codes_or_link'])): ?>
                    <div class="invalid-feedback"><?= validation_errors()['codes_or_link'] ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary" style="background-color: #DC4814; border-color: #DC4814;">Adicionar ao Estoque</button>

        <?= form_close() ?>
    </div>
</div>

<hr class="my-4">

<h5 class="mt-4">Estoque Atual (Exemplo)</h5>
<p>...</p>


<?= $this->endSection() ?>