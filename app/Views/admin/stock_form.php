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
            <select name="product_id" id="product_id"
                class="form-select <?= (isset(validation_errors()['product_id'])) ? 'is-invalid' : '' ?>" required>
                <option value="">Selecione um Produto</option>
                <?php if (!empty($productsForStock) && is_array($productsForStock)): ?>
                    <?php foreach ($productsForStock as $product): ?>
                        <option value="<?= esc($product->id) ?>" data-type="<?= esc($product->product_type) ?>"
                            <?= old('product_id') == $product->id ? 'selected' : '' ?>>
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
            <textarea name="codes_or_link" id="codes_or_link" rows="10"
                class="form-control <?= (isset(validation_errors()['codes_or_link'])) ? 'is-invalid' : '' ?>"
                required><?= old('codes_or_link') ?></textarea>
            <div class="form-text">Para produtos 'Código Único', insira um código por linha. Para 'Link Estático',
                insira apenas o link (ele substituirá o existente, se houver).</div>
            <?php if (isset(validation_errors()['codes_or_link'])): ?>
                <div class="invalid-feedback"><?= validation_errors()['codes_or_link'] ?></div>
            <?php endif; ?>
        </div>

        <div class="mb-3" id="expires_at_field" style="display: none;"> <label for="expires_at" class="form-label">Data
                de Validade (Opcional):</label>
            <input type="date" name="expires_at" id="expires_at"
                class="form-control <?= (isset(validation_errors()['expires_at'])) ? 'is-invalid' : '' ?>"
                value="<?= old('expires_at') ?>">
            <div class="form-text">Deixe em branco se os códigos não tiverem data de validade. Aplica-se apenas a
                'Código Único'.</div>
            <?php if (isset(validation_errors()['expires_at'])): ?>
                <div class="invalid-feedback"><?= validation_errors()['expires_at'] ?></div>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary"
            style="background-color: #DC4814; border-color: #DC4814;">Adicionar ao Estoque</button>

        <?= form_close() ?>
    </div>
</div>

<hr class="my-4">

<h5 class="mt-4">Estoque Atual (Exemplo)</h5>
<p>...</p>


<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const productSelect = document.getElementById('product_id');
        const expiresAtField = document.getElementById('expires_at_field');
        const expiresAtInput = document.getElementById('expires_at'); // Input da data

        function checkProductType() {
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            const productType = selectedOption ? selectedOption.getAttribute('data-type') : null;

            if (productType === 'unique_code') {
                expiresAtField.style.display = 'block'; // Mostra o campo
            } else {
                expiresAtField.style.display = 'none'; // Esconde o campo
                expiresAtInput.value = ''; // Limpa o valor se não for código único
            }
        }
        // Verifica no carregamento da página (caso haja valor 'old')
        checkProductType();
        // Adiciona listener para mudanças no select
        productSelect.addEventListener('change', checkProductType);
    });
</script>
<?= $this->endSection() ?>