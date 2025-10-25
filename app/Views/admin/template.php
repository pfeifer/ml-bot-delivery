<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->renderSection('title') ?> - Admin ML Bot</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <style>
        body { display: flex; min-height: 100vh; flex-direction: column; }
        .sidebar { width: 250px; background-color: #f8f9fa; border-right: 1px solid #dee2e6; }
        .main-content { flex: 1; display: flex; flex-direction: column; }
        .content { flex: 1; padding: 20px; }
        .navbar { border-bottom: 1px solid #dee2e6; }
    </style>
</head>
<body>
    <div class="d-flex" style="min-height: 100vh;">
        <nav class="sidebar d-flex flex-column p-3 bg-light">
            <h4 class="mb-4">Admin Menu</h4>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a href="<?= route_to('admin.dashboard') ?>" class="nav-link <?= (uri_string() === 'admin') ? 'active' : 'link-dark' ?>">
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="<?= route_to('admin.products') ?>" class="nav-link <?= (str_starts_with(uri_string(), 'admin/products')) ? 'active' : 'link-dark' ?>">
                        Produtos
                    </a>
                </li>
                <li>
                    <a href="<?= route_to('admin.stock') ?>" class="nav-link <?= (str_starts_with(uri_string(), 'admin/stock')) ? 'active' : 'link-dark' ?>">
                        Estoque
                    </a>
                </li>
                </ul>
            <hr>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center link-dark text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                    <strong>Admin</strong> </a>
                <ul class="dropdown-menu text-small shadow" aria-labelledby="dropdownUser">
                    <li><a class="dropdown-item" href="<?= route_to('logout') ?>">Sair</a></li>
                </ul>
            </div>
        </nav>

        <div class="main-content">
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <span class="navbar-brand mb-0 h1">ML Bot Delivery</span>
                     <span class="ms-auto">
                        <a href="<?= route_to('logout') ?>" class="btn btn-outline-danger btn-sm">Sair</a>
                    </span>
                </div>
            </nav>

            <main class="content">
                <h1 class="mb-4"><?= $this->renderSection('page_title') ?></h1>

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= session()->getFlashdata('success') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if (session()->getFlashdata('error')): ?>
                     <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= session()->getFlashdata('error') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?= $this->renderSection('content') // Conteúdo específico de cada página virá aqui ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    </body>
</html>