<?php
// Este ficheiro é carregado dentro do modal AJAX
// Não usa extend() ou section()

$product = $product ?? (object)['id' => null, 'ml_item_id' => '', 'title' => '', 'product_type' => '', 'message_template_id' => null];
$formAction = ($action === 'create') ? route_to('admin.products.create') : route_to('admin.products.update', $product->id);
$validationErrors = $validationErrors ?? session()->getFlashdata('errors'); // Pega erros da variável ou da sessão
$selectedTemplateId = old('message_template_id', $product->message_template_id ?? null);
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


<?= form_open($formAction) ?>
<?= csrf_field() ?>

<div class="mb-3">
    <label for="ml_item_id" class="form-label">ML Item ID</label>
    <input type="text" class="form-control <?= (isset($validationErrors['ml_item_id'])) ? 'is-invalid' : '' ?>"
        id="ml_item_id" name="ml_item_id" value="<?= old('ml_item_id', $product->ml_item_id ?? '') ?>"
        placeholder="Ex: MLB1234567890" required>
</div>

<div class="mb-3">
    <label for="title" class="form-label">Título do Anúncio (Opcional)</label>
    <input type="text" class="form-control <?= (isset($validationErrors['title'])) ? 'is-invalid' : '' ?>" id="title"
        name="title" value="<?= old('title', $product->title ?? '') ?>"
        placeholder="Ex: Item de Teste – Por favor, NÃO OFERTAR!">
</div>

<div class="mb-3">
    <label for="product_type" class="form-label">Tipo de Entrega</label>
    <select class="form-select <?= (isset($validationErrors['product_type'])) ? 'is-invalid' : '' ?>"
        id="product_type" name="product_type" required>
        <option value="">Selecione o tipo...</option>
        <option value="unique_code" <?= old('product_type', $product->product_type ?? '') === 'unique_code' ? 'selected' : '' ?>>
            Código Único (Gift Card, Voucher, Chave)
        </option>
        <option value="static_link" <?= old('product_type', $product->product_type ?? '') === 'static_link' ? 'selected' : '' ?>>
            Link Estático (Ebook, Curso, Download)
        </option>
    </select>
    <div class="form-text">Define como o robô fará a entrega.</div>
</div>

<div class="mb-3">
    <label for="message_template_id" class="form-label">Template de Mensagem Pós-Venda</label>
    <select class="form-select <?= (isset($validationErrors['message_template_id'])) ? 'is-invalid' : '' ?>"
        id="message_template_id" name="message_template_id">
        
        <option value="" <?= ($selectedTemplateId === null || $selectedTemplateId == '') ? 'selected' : '' ?>>
            -- Usar Template Padrão (ID #1) --
        </option>
        
        <?php if (!empty($templates) && is_array($templates)): ?>
            <?php foreach ($templates as $template): ?>
                <option value="<?= esc($template->id) ?>"
                    <?= (string)$selectedTemplateId === (string)$template->id ? 'selected' : '' ?>>
                    <?= esc($template->name) ?> <?= ($template->id == 1) ? '(Padrão)' : '' ?>
                </option>
            <?php endforeach; ?>
        <?php endif; ?>
    </select>
     
     <div class="form-text">Selecione um template ou deixe em branco para usar o Padrão (ID #1).</div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
    <button type="submit" class="btn btn-primary" style="background-color: #DC4814; border-color: #DC4814;">
        Salvar Produto
    </button>
</div>

<?= form_close() ?>