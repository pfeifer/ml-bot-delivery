<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Admin ML Bot Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .login-container {
            max-width: 400px;
            margin-top: 10vh;
        }
    </style>
</head>

<body class="d-flex justify-content-center align-items-start">
    <div class="container login-container bg-white p-4 p-md-5 rounded shadow">
        <h2 class="text-center mb-4">Admin Login</h2>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger" role="alert">
                <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>
        <?= form_open(route_to('login')) ?>
        <?= csrf_field() ?>
        <div class="mb-3">
            <label for="email" class="form-label">Email:</label>
            <input type="email" name="email" id="email"
                class="form-control <?= (isset(validation_errors()['email'])) ? 'is-invalid' : '' ?>"
                value="<?= old('email') ?>" required>
            <?php if (isset(validation_errors()['email'])): ?>
                <div class="invalid-feedback"><?= validation_errors()['email'] ?></div>
            <?php endif; ?>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Senha:</label>
            <input type="password" name="password" id="password"
                class="form-control <?= (isset(validation_errors()['password'])) ? 'is-invalid' : '' ?>" required>
            <?php if (isset(validation_errors()['password'])): ?>
                <div class="invalid-feedback"><?= validation_errors()['password'] ?></div>
            <?php endif; ?>
        </div>
        <div class="d-grid">
            <button type="submit" class="btn btn-primary"
                style="background-color: #DC4814; border-color: #DC4814;">Entrar</button>
        </div>
        <?= form_close() ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
</body>

</html>