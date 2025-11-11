<?php
// Este ficheiro é carregado dentro do modal AJAX
$cred = $cred ?? (object)['id' => null, 'app_name' => '', 'client_id' => '', 'client_secret' => '', 'redirect_uri' => ''];
$formAction = route_to('admin.mercadolivre.credentials.save');
$validationErrors = $validationErrors ?? session()->getFlashdata('errors');

// Se a redirect_uri estiver vazia, sugere a padrão
if (empty($cred->redirect_uri)) {
    $cred->redirect_uri = site_url(route_to('admin.mercadolivre.callback'));
}
?>

<?php if (!empty($validationErrors) && is_array($validationErrors)): ?>
    <div class="alert alert-danger" role="alert">
         <h5 class="alert-heading">Erro de Validação</h5>
        <ul>
            <?php foreach ($validationErrors as $error): ?>
                <li><?= esc($error) ?></li>
            <?php endforeach ?>
        </ul>
    </div>
<?php endif; ?>


<?= form_open($formAction) ?>
<?= csrf_field() ?>
<input type="hidden" name="id" value="<?= esc($cred->id) ?>">

<div class="mb-3">
    <label for="app_name" class="form-label">Nome da Aplicação</label>
    <input type="text" class="form-control <?= (isset($validationErrors['app_name'])) ? 'is-invalid' : '' ?>"
        id="app_name" name="app_name" value="<?= old('app_name', $cred->app_name) ?>"
        placeholder="Ex: Minha Loja Principal" required>
     <div class="form-text">Um nome amigável para identificar esta conta/app.</div>
</div>

<div class="mb-3">
    <label for="client_id" class="form-label">Client ID (APP_ID)</label>
    <input type="text" class="form-control <?= (isset($validationErrors['client_id'])) ? 'is-invalid' : '' ?>"
        id="client_id" name="client_id" value="<?= old('client_id', $cred->client_id) ?>"
        placeholder="Ex: 1234567890123456" required>
</div>

<div class="mb-3">
    <label for="client_secret" class="form-label">Client Secret</label>
    <input type="password" class="form-control <?= (isset($validationErrors['client_secret'])) ? 'is-invalid' : '' ?>"
        id="client_secret" name="client_secret" value="<?= old('client_secret', $cred->client_secret) ?>"
        placeholder="<?= $action === 'update' ? 'Deixe em branco para não alterar' : 'Insira o Client Secret' ?>"
        <?= ($action === 'create') ? 'required' : '' ?>>
     <div class="form-text text-warning">
         <?= ($action === 'update') ? 'Preencha apenas se desejar alterar o Client Secret salvo.' : 'O Client Secret será encriptado no banco de dados.' ?>
     </div>
</div>

<div class="mb-3">
    <label for="redirect_uri" class="form-label">Redirect URI</label>
    <input type="text" class="form-control <?= (isset($validationErrors['redirect_uri'])) ? 'is-invalid' : '' ?>"
        id="redirect_uri" name="redirect_uri" value="<?= old('redirect_uri', $cred->redirect_uri) ?>"
        placeholder="Ex: https://seusite.com/admin/mercadolivre/callback" required>
     <div class="form-text">
         Deve ser <strong>exatamente</strong> igual à URL cadastrada na sua aplicação no Mercado Livre.
     </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
    <button type="submit" class="btn btn-primary" style="background-color: #DC4814; border-color: #DC4814;">
        Salvar Aplicação
    </button>
</div>

<?= form_close() ?>