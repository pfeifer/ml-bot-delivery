<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Admin ML Bot Delivery</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <script>
        (function () {
            const getPreferredTheme = () => {
                // Verifica a preferência do sistema operacional
                return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            // Define o atributo data-bs-theme no <html> antes da página carregar
            document.documentElement.setAttribute('data-bs-theme', getPreferredTheme());
        })();
    </script>

    <style>
        .login-container {
            max-width: 400px;
            margin-top: 10vh;
        }

        /* ADICIONADO: Garante que a mensagem de erro da validação apareça corretamente com o input-group */
        .input-group .invalid-feedback {
            width: 100%;
        }
    </style>
</head>

<body class="d-flex justify-content-center align-items-start">

    <div class="container login-container bg-body p-4 p-md-5 rounded shadow">
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
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fa-solid fa-user fa-fw"></i>
                </span>
                <input type="email" name="email" id="email"
                    class="form-control <?= (isset(validation_errors()['email'])) ? 'is-invalid' : '' ?>"
                    value="<?= old('email') ?>" required placeholder="seu.email@exemplo.com">
                <?php if (isset(validation_errors()['email'])): ?>
                    <div class="invalid-feedback"><?= validation_errors()['email'] ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Senha:</label>
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fa-solid fa-lock fa-fw"></i>
                </span>
                <input type="password" name="password" id="password"
                    class="form-control <?= (isset(validation_errors()['password'])) ? 'is-invalid' : '' ?>" required
                    placeholder="Sua senha"> <?php if (isset(validation_errors()['password'])): ?>
                    <div class="invalid-feedback"><?= validation_errors()['password'] ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="d-grid">
            <div class="mb-3 text-end">
                <a href="<?= route_to('forgot_password') ?>" class="text-decoration-none small">Esqueceu sua senha?</a>
            </div>
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