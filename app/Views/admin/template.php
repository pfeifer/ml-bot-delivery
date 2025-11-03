<!DOCTYPE html>
<html lang="pt-br" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->renderSection('title') ?> - Admin ML Bot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.min.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="<?= base_url('css/template.css') ?>">
    <?= $this->renderSection('styles') ?>
</head>

<body>
    <nav class="sidebar p-3 bg-body-tertiary" id="adminSidebar">
        <div class="sidebar-top-wrapper">
            <div class="sidebar-header d-flex align-items-center">
                <a href="<?= route_to('admin.dashboard') ?>"
                    class="link-body-emphasis text-decoration-none d-flex align-items-center">
                    <i class="fa-solid fa-robot fa-lg fa-fw"></i>
                    <span class="fs-5 ms-2">ML Bot Admin</span>
                </a>
            </div>
        </div>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="<?= route_to('admin.dashboard') ?>"
                    class="nav-link ajax-link d-flex align-items-center <?= (uri_string() === 'admin') ? 'active' : 'link-body-emphasis' ?>">
                    <i class="fa-solid fa-gauge fa-lg fa-fw"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= route_to('admin.products') ?>"
                    class="nav-link ajax-link d-flex align-items-center <?= (str_starts_with(uri_string(), 'admin/products')) ? 'active' : 'link-body-emphasis' ?>">
                    <i class="fa-solid fa-box fa-lg fa-fw"></i>
                    <span>Produtos</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= route_to('admin.stock') ?>"
                    class="nav-link ajax-link d-flex align-items-center <?= (str_starts_with(uri_string(), 'admin/stock')) ? 'active' : 'link-body-emphasis' ?>">
                    <i class="fa-solid fa-boxes-stacked fa-lg fa-fw"></i>
                    <span>Estoque</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="<?= route_to('admin.mercadolivre.settings') ?>" 
                   class="nav-link ajax-link d-flex align-items-center 
                        <?php // Ativa o link se estiver na página de settings OU nas páginas de CRUD de templates
                        if (str_starts_with(uri_string(), 'admin/mercadolivre') || str_starts_with(uri_string(), 'admin/message-templates')) {
                            echo 'active';
                        } else {
                            echo 'link-body-emphasis';
                        } ?>
                   ">
                    <i class="fa-solid fa-handshake-simple fa-lg fa-fw"></i>
                    <span>Mercado Livre</span>
                </a>
                </li>
            </ul>

        <div class="sidebar-footer">
            <a href="<?= route_to('logout') ?>" class="logout-link mb-2">
                <i class="fa-solid fa-right-from-bracket fa-lg fa-fw"></i>
                <span>Sair</span>
            </a>
            <div
                class="form-check form-switch mb-3 d-flex align-items-center justify-content-between theme-switcher-container p-2 rounded">
                <label class="form-check-label theme-switcher-label d-flex align-items-center" for="themeSwitch">
                    
                    <i class="fa-solid fa-moon theme-icon fa-lg fa-fw"></i> 
                    
                    <span class="theme-switcher-text">Modo Escuro</span>
                </label>
                <input class="form-check-input" type="checkbox" role="switch" id="themeSwitch">
            </div>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center link-body-emphasis text-decoration-none dropdown-toggle"
                    id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false" data-bs-toggle="tooltip" data-bs-placement="right"
                    data-bs-title="Opções do Usuário">
                    <i class="fa-solid fa-user fa-lg fa-fw"></i>
                    <span class="fw-bold"><?= esc(session()->get('admin_first_name') ?: 'Admin') ?></span>
                </a>
                <ul class="dropdown-menu text-small shadow" aria-labelledby="dropdownUser">
                    <li><a class="dropdown-item disabled" href="#">Perfil (em breve)</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="main-content">
        <nav class="navbar navbar-expand navbar-light bg-body-tertiary">
            <div class="container-fluid align-items-center">
            
                <button class="nav-link link-body-emphasis d-flex align-items-center me-3" type="button"
                    id="sidebarToggle">
                    <i class="fa-solid"></i>
                </button>

                <span class="navbar-brand mb-0 h1 ms-2 d-none d-md-inline">ML Bot Delivery</span>
                <div class="ms-auto"> </div>
            </div>
        </nav>
        <main class="content p-4"> 
            <h1 class="mb-4"><?= $this->renderSection('page_title') ?></h1>
            
            <?php // Flashdata (Sucesso, Erro) ?>
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= session()->getFlashdata('success') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= session()->getFlashdata('error') ?>
                    <button typebutton" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

             <?php // Erros de Validação (vindos de redirect com ->with('errors'))
                $validationErrors = session()->getFlashdata('errors');
                if (!empty($validationErrors) && is_array($validationErrors)): ?>
                 <div class="alert alert-danger alert-dismissible fade show" role="alert">
                      <h5 class="alert-heading">Erro de Validação</h5>
                      <p>Por favor, corrija os problemas abaixo:</p>
                     <ul>
                         <?php foreach ($validationErrors as $error): ?>
                             <li><?= esc($error) ?></li>
                         <?php endforeach ?>
                     </ul>
                     <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                 </div>
             <?php endif; ?>

            <?= $this->renderSection('content') ?>
        </main>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.min.js"></script>

    <script src="<?= base_url('js/template.js') ?>"></script>
    
    <div id="ajax-scripts">
        <?= $this->renderSection('scripts') ?>
    </div>
</body>

</html>