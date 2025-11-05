<?php
// Este ficheiro é carregado dentro do modal AJAX
$template = $template ?? (object)['id' => null, 'name' => '', 'content' => ''];
$formAction = ($action === 'create') ? route_to('admin.message_templates.create') : route_to('admin.message_templates.update', $template->id);
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


<?= form_open($formAction) ?>
<?= csrf_field() ?>

<div class="mb-3">
    <label for="name" class="form-label">Nome do Template</label>
    <input type="text" class="form-control <?= (isset($validationErrors['name'])) ? 'is-invalid' : '' ?>"
        id="name" name="name" value="<?= old('name', $template->name ?? '') ?>"
        placeholder="Ex: Entrega Código Padrão" required>
     <div class="form-text">Usado apenas para identificar o template na administração.</div>
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
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
    <button type="submit" class="btn btn-primary" style="background-color: #DC4814; border-color: #DC4814;">
        Salvar Template
    </button>
</div>

<?= form_close() ?>