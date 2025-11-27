<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Senha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>.login-container { max-width: 400px; margin-top: 10vh; }</style>
</head>
<body class="d-flex justify-content-center bg-light">
    <div class="container login-container bg-white p-4 rounded shadow">
        <h3 class="text-center mb-4">Recuperar Senha</h3>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
        <?php endif; ?>

        <?= form_open(route_to('forgot_password.send')) ?>
            <div class="mb-3">
                <label class="form-label">E-mail cadastrado:</label>
                <input type="email" name="email" class="form-control" required placeholder="seu@email.com">
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary" style="background-color: #DC4814; border-color: #DC4814;">Enviar Link</button>
                <a href="<?= route_to('login') ?>" class="btn btn-outline-secondary">Voltar</a>
            </div>
        <?= form_close() ?>
    </div>
</body>
</html>