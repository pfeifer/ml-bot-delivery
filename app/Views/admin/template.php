<!DOCTYPE html>
<html lang="pt-br" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->renderSection('title') ?> - Admin ML Bot</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --sidebar-width-expanded: 250px;
            /* Pode ajustar */
            --sidebar-width-collapsed: 80px;
            /* Pode ajustar */
            --transition-speed: 0.3s;
        }

        body {
            min-height: 100vh;
            overflow-x: hidden;
        }

        .sidebar {
            width: var(--sidebar-width-expanded);
            border-right: 1px solid var(--bs-border-color);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            transition: width var(--transition-speed) ease;
            overflow-x: hidden;
            z-index: 1030;
            display: flex;
            flex-direction: column;
        }

        .sidebar.collapsed {
            width: var(--sidebar-width-collapsed);
        }

        /* Esconde texto quando colapsado */
        .sidebar.collapsed .nav-link span,
        .sidebar.collapsed .sidebar-header span,
        .sidebar.collapsed .dropdown-toggle strong span,
        .sidebar.collapsed .theme-switcher-label span,
        /* Texto do label do tema */
        .sidebar.collapsed .logout-link span {
            /* Texto do link de logout */
            display: none;
        }

        /* Centraliza ícones e elementos quando colapsado */
        .sidebar.collapsed .nav-link,
        .sidebar.collapsed .dropdown-toggle,
        .sidebar.collapsed .logout-link,
        /* Link de logout */
        .sidebar.collapsed .sidebar-header a {
            /* Link do cabeçalho */
            justify-content: center;
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }

        /* Centraliza o switch quando colapsado */
        .sidebar.collapsed .theme-switcher-container {
            justify-content: center !important;
            padding: 0.5rem;
            /* Ajusta padding quando colapsado */
            background-color: transparent !important;
            /* Remove fundo quando colapsado (Opcional, mas melhora visual) */
            border: none;
            /* Remove borda quando colapsado (Opcional) */
        }

        .sidebar.collapsed .theme-switcher-container:hover {
            background-color: var(--bs-tertiary-bg) !important;
            /* Mantém um hover sutil */
        }


        .sidebar.collapsed .dropdown-menu {
            /* Ajusta posição do submenu do usuário */
            position: absolute !important;
            left: 100%;
            bottom: 5px;
            /* Alinha com a parte inferior do gatilho */
            margin-left: 0.5rem;
        }

        /* Estilos gerais para ícones */
        .sidebar .nav-link .bi,
        .sidebar .dropdown-toggle .bi,
        .sidebar .logout-link .bi,
        .sidebar .sidebar-header .bi {
            margin-right: 8px;
            vertical-align: text-bottom;
            width: 1.5em;
            /* Garante espaço para ícone */
            display: inline-block;
            text-align: center;
            transition: margin var(--transition-speed) ease;
        }

        /* Remove margem direita dos ícones quando colapsado */
        .sidebar.collapsed .nav-link .bi,
        .sidebar.collapsed .dropdown-toggle .bi,
        .sidebar.collapsed .logout-link .bi,
        .sidebar.collapsed .sidebar-header .bi {
            margin-right: 0;
        }

        .dropdown-item .bi {
            margin-right: 8px;
        }

        .btn .bi {
            vertical-align: text-bottom;
        }

        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width-expanded);
            transition: margin-left var(--transition-speed) ease;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        body.sidebar-collapsed .main-content {
            margin-left: var(--sidebar-width-collapsed);
        }

        .content {
            flex: 1;
            padding: 20px;
        }

        .navbar {
            border-bottom: 1px solid var(--bs-border-color);
        }

        #sidebarToggle {
            margin-right: 15px;
        }

        /* Estilos para o rodapé da sidebar */
        .sidebar-footer {
            margin-top: auto;
            /* Empurra para o final */
            padding-top: 1rem;
            border-top: 1px solid var(--bs-border-color);
        }

        /* Estilos Container Switch Tema */
        .theme-switcher-container {
            background-color: var(--bs-secondary-bg);
            /* Cor de fundo sutil que se adapta ao tema */
            border: 1px solid var(--bs-border-color-translucent);
            /* Borda sutil */
            cursor: pointer;
            transition: background-color var(--transition-speed) ease, border-color var(--transition-speed) ease;
            /* Transição suave */
        }

        .theme-switcher-container:hover {
            background-color: var(--bs-tertiary-bg);
            /* Cor de fundo no hover */
        }

        /* Ajustes Input e Label do Switch */
        .theme-switcher-container .form-check-input {
            cursor: pointer;
            font-size: 1.2em;
            margin-left: auto;
            /* Empurra o switch para a direita */
            /* Não precisa de margem extra aqui, justify-content-between cuida disso */
        }

        .theme-switcher-label {
            /* Garante que o label não impeça o clique no container */
            pointer-events: none;
            margin-right: auto;
            /* Garante que o label fique à esquerda */
        }

        .theme-switcher-label i.bi {
            vertical-align: text-bottom;
            /* me-2 no HTML cuida do espaçamento */
        }

        /* --- Comportamento Colapsado (Switch Tema) --- */
        /* Esconde texto e ícone do label quando colapsado */
        .sidebar.collapsed .theme-switcher-label span,
        .sidebar.collapsed .theme-switcher-label i.bi {
            display: none;
        }

        /* Garante que o label não ocupe espaço visível quando colapsado */
        .sidebar.collapsed .theme-switcher-label {
            width: 0;
            overflow: hidden;
            margin: 0;
            padding: 0;
            pointer-events: none;
            /* Desativa eventos no label escondido */
        }


        /* Estilo Link Logout */
        .logout-link {
            padding: 0.5rem 1rem;
            color: var(--bs-nav-link-color);
            display: flex;
            align-items: center;
            text-decoration: none;
            border-radius: var(--bs-nav-pills-border-radius);
        }

        .logout-link:hover {
            color: var(--bs-nav-link-hover-color);
            background-color: var(--bs-tertiary-bg);
        }

        .sidebar.collapsed .logout-link span {
            /* Esconde texto do logout */
            display: none;
        }

        /* Tooltip styles */
        body .tooltip .tooltip-inner {
            background-color: var(--bs-emphasis-color);
            color: var(--bs-body-bg);
        }

        body .tooltip.bs-tooltip-auto[data-popper-placement^=right] .tooltip-arrow::before,
        body .tooltip.bs-tooltip-end .tooltip-arrow::before {
            border-right-color: var(--bs-emphasis-color);
        }
    </style>
</head>

<body>

    <nav class="sidebar p-3 bg-body-tertiary" id="adminSidebar">
        <div class="sidebar-header d-flex align-items-center mb-4">
            <a href="<?= route_to('admin.dashboard') ?>"
                class="link-body-emphasis text-decoration-none d-flex align-items-center w-100">
                <i class="bi bi-robot fs-4"></i>
                <span class="fs-5 ms-2">ML Bot Admin</span>
            </a>
        </div>

        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="<?= route_to('admin.dashboard') ?>"
                    class="nav-link d-flex align-items-center <?= (uri_string() === 'admin') ? 'active' : 'link-body-emphasis' ?>"
                    data-bs-toggle="tooltip">
                    <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="<?= route_to('admin.products') ?>"
                    class="nav-link d-flex align-items-center <?= (str_starts_with(uri_string(), 'admin/products')) ? 'active' : 'link-body-emphasis' ?>"
                    data-bs-toggle="tooltip">
                    <i class="bi bi-box-seam"></i> <span>Produtos</span>
                </a>
            </li>
            <li>
                <a href="<?= route_to('admin.stock') ?>"
                    class="nav-link d-flex align-items-center <?= (str_starts_with(uri_string(), 'admin/stock')) ? 'active' : 'link-body-emphasis' ?>"
                    data-bs-toggle="tooltip">
                    <i class="bi bi-stack"></i> <span>Estoque</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">

            <a href="<?= route_to('logout') ?>" class="logout-link mb-2" data-bs-toggle="tooltip">
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
                <button class="btn btn-outline-secondary" type="button" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
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

    <script>
        // --- Script para Toggle da Sidebar e Tooltips ---
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('adminSidebar');
            const toggleButton = document.getElementById('sidebarToggle');
            const body = document.body;
            const sidebarStateKey = 'sidebarCollapsedState';

            function getTransitionDuration(element) {
                const duration = getComputedStyle(element).getPropertyValue('--transition-speed') || '0.3s';
                return parseFloat(duration) * 1000;
            }

            function toggleSidebar() {
                sidebar.classList.toggle('collapsed');
                body.classList.toggle('sidebar-collapsed');
                localStorage.setItem(sidebarStateKey, sidebar.classList.contains('collapsed'));
                setTimeout(initializeTooltips, getTransitionDuration(sidebar));
            }

            if (localStorage.getItem(sidebarStateKey) === 'true') {
                sidebar.classList.add('collapsed');
                body.classList.add('sidebar-collapsed');
            }

            if (toggleButton) {
                toggleButton.addEventListener('click', toggleSidebar);
            }

            // --- Tooltip Logic ---
            let tooltipInstances = [];
            function initializeTooltips() {
                tooltipInstances.forEach(t => t.dispose());
                tooltipInstances = [];
                const tooltipTriggerList = sidebar.querySelectorAll('.nav-pills .nav-link, .logout-link, #dropdownUser');

                if (sidebar.classList.contains('collapsed')) {
                    tooltipTriggerList.forEach(tooltipTriggerEl => {
                        let titleText = '';
                        const textSpan = tooltipTriggerEl.querySelector('span');
                        if (textSpan && textSpan.textContent.trim()) { titleText = textSpan.textContent.trim(); }
                        const strongSpan = tooltipTriggerEl.querySelector('strong > span');
                        if (strongSpan && strongSpan.textContent.trim()) { titleText = strongSpan.textContent.trim(); }
                        if (!titleText && tooltipTriggerEl.hasAttribute('data-bs-title')) { titleText = tooltipTriggerEl.getAttribute('data-bs-title'); }

                        if (titleText) {
                            tooltipTriggerEl.setAttribute('data-bs-toggle', 'tooltip');
                            tooltipTriggerEl.setAttribute('data-bs-title', titleText);
                            tooltipTriggerEl.setAttribute('data-bs-placement', 'right');
                            tooltipTriggerEl.setAttribute('data-bs-trigger', 'hover');
                            tooltipInstances.push(new bootstrap.Tooltip(tooltipTriggerEl));
                        }
                    });
                } else {
                    tooltipTriggerList.forEach(tooltipTriggerEl => {
                        if (tooltipTriggerEl.id !== 'dropdownUser') { tooltipTriggerEl.removeAttribute('data-bs-title'); }
                        tooltipTriggerEl.removeAttribute('data-bs-toggle');
                        tooltipTriggerEl.removeAttribute('data-bs-placement');
                        tooltipTriggerEl.removeAttribute('data-bs-trigger');
                        const tooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
                        if (tooltip) tooltip.dispose();
                    });
                }
            }
            initializeTooltips();
            // --- End Tooltip Logic ---
        });


        // --- Script do Seletor de Tema (Switch) ---
        (() => {
            'use strict'
            const storedTheme = localStorage.getItem('theme');
            const themeSwitch = document.getElementById('themeSwitch');
            const themeIcon = document.querySelector('.theme-switcher-label i.bi');
            const themeText = document.querySelector('.theme-switcher-label span.theme-switcher-text');
            const themeLabel = document.querySelector('.theme-switcher-label');
            const themeContainer = document.querySelector('.theme-switcher-container'); // Container adicionado

            const getPreferredTheme = () => {
                if (storedTheme) { return storedTheme; }
                return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }

            const setTheme = (theme) => {
                if (theme !== 'light' && theme !== 'dark') {
                    theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                }
                document.documentElement.setAttribute('data-bs-theme', theme);
                if (themeSwitch && themeIcon && themeText) {
                    if (theme === 'dark') {
                        themeSwitch.checked = true;
                        themeIcon.classList.remove('bi-sun-fill');
                        themeIcon.classList.add('bi-moon-stars-fill');
                        themeText.textContent = 'Modo Escuro';
                    } else {
                        themeSwitch.checked = false;
                        themeIcon.classList.remove('bi-moon-stars-fill');
                        themeIcon.classList.add('bi-sun-fill');
                        themeText.textContent = 'Modo Claro';
                    }
                }
                localStorage.setItem('theme', theme);
            }

            setTheme(getPreferredTheme());

            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                if (!localStorage.getItem('theme')) {
                    setTheme(getPreferredTheme());
                }
            });

            if (themeSwitch) {
                themeSwitch.addEventListener('change', () => {
                    setTheme(themeSwitch.checked ? 'dark' : 'light');
                });
            }

            // Listener para clique no CONTAINER inteiro (inclui label e área vazia)
            if (themeContainer && themeSwitch) {
                themeContainer.addEventListener('click', (e) => {
                    // Só alterna se o clique NÃO foi diretamente no input (para não disparar duas vezes)
                    if (e.target !== themeSwitch) {
                        e.preventDefault(); // Previne o comportamento padrão do label se clicado
                        themeSwitch.click(); // Simula o clique no input
                    }
                });
            }

        })();
    </script>
</body>

</html>