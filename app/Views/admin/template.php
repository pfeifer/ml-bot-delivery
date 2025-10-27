<!DOCTYPE html>
<html lang="pt-br" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->renderSection('title') ?> - Admin ML Bot</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="<?= base_url('css/template.css') ?>"> <?= $this->renderSection('styles') ?>

</head>

<body>

    <nav class="sidebar p-3 bg-body-tertiary" id="adminSidebar">
        <div class="sidebar-header d-flex align-items-center mb-3">
            <a href="<?= route_to('admin.dashboard') ?>"
                class="link-body-emphasis text-decoration-none d-flex align-items-center me-2">
                <i class="bi bi-robot fs-4"></i>
                <span class="fs-5 ms-2">ML Bot Admin</span>
            </a>
        </div>

        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item sidebar-toggle-item">
                <button class="nav-link link-body-emphasis d-flex align-items-center" type="button" id="sidebarToggle"
                    title="Recolher/Expandir Menu">
                    <i class="bi bi-list fs-5"></i>
                    <span class="toggle-text ms-2">Recolher Menu</span>
                </button>
            </li>
            <li class="nav-item">
                <a href="<?= route_to('admin.dashboard') ?>"
                    class="nav-link d-flex align-items-center <?= (uri_string() === 'admin') ? 'active' : 'link-body-emphasis' ?>">
                    <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="<?= route_to('admin.products') ?>"
                    class="nav-link d-flex align-items-center <?= (str_starts_with(uri_string(), 'admin/products')) ? 'active' : 'link-body-emphasis' ?>">
                    <i class="bi bi-box-seam"></i> <span>Produtos</span>
                </a>
            </li>
            <li>
                <a href="<?= route_to('admin.stock') ?>"
                    class="nav-link d-flex align-items-center <?= (str_starts_with(uri_string(), 'admin/stock')) ? 'active' : 'link-body-emphasis' ?>">
                    <i class="bi bi-stack"></i> <span>Estoque</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <a href="<?= route_to('logout') ?>" class="logout-link mb-2">
                <i class="bi bi-box-arrow-right"></i>
                <span>Sair</span>
            </a>
            <div
                class="form-check form-switch mb-3 d-flex align-items-center justify-content-between theme-switcher-container p-2 rounded">
                <label class="form-check-label theme-switcher-label d-flex align-items-center" for="themeSwitch">
                    <i class="bi bi-moon-stars-fill theme-icon me-2"></i>
                    <span class="theme-switcher-text">Modo Escuro</span>
                </label>
                <input class="form-check-input" type="checkbox" role="switch" id="themeSwitch">
            </div>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center link-body-emphasis text-decoration-none dropdown-toggle"
                    id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false" data-bs-toggle="tooltip"
                    data-bs-title="Opções do Usuário">
                    <i class="bi bi-person-circle"></i>
                    <strong><span><?= esc(session()->get('admin_first_name') ?: 'Admin') ?></span></strong>
                </a>
                <ul class="dropdown-menu text-small shadow" aria-labelledby="dropdownUser">
                    <li><a class="dropdown-item disabled" href="#">Perfil (em breve)</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="main-content">
        <nav class="navbar navbar-expand navbar-light bg-body-tertiary">
            <div class="container-fluid">
                <span class="navbar-brand mb-0 h1 ms-2 d-none d-md-inline">ML Bot Delivery</span>
                <div class="ms-auto"> </div>
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
            <?= $this->renderSection('content') ?>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    <script src="<?= base_url('js/template.js') ?>"></script> <?= $this->renderSection('scripts') ?>
</body>

</html>