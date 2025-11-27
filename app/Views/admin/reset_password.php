<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Nova Senha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>.login-container { max-width: 400px; margin-top: 10vh; }</style>
</head>
<body class="d-flex justify-content-center bg-light">
    <div class="container login-container bg-white p-4 rounded shadow">
        <h3 class="text-center mb-4">Definir Nova Senha</h3>

        <?php if (session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                <?php foreach (session()->getFlashdata('errors') as $error): ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach ?>
                </ul>
            </div>
        <?php endif; ?>

        <?= form_open(route_to('reset_password.update')) ?>
            <input type="hidden" name="token" value="<?= esc($token) ?>">
            
            <div class="mb-3">
                <label class="form-label">Nova Senha:</label>
                <input type="password" name="password" class="form-control" required minlength="8">
            </div>
            <div class="mb-3">
                <label class="form-label">Confirmar Senha:</label>
                <input type="password" name="password_confirm" class="form-control" required>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary" style="background-color: #DC4814; border-color: #DC4814;">Salvar Senha</button>
            </div>
        <?= form_close() ?>
    </div>
</body>
</html>