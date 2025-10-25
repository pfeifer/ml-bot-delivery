<!DOCTYPE html>
<html lang="pt-br" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->renderSection('title') ?> - Admin ML Bot</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --sidebar-width-expanded: 250px;
            --sidebar-width-collapsed: 80px;
            --transition-speed: 0.3s;
        }
        body { min-height: 100vh; overflow-x: hidden; }
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
        }
        .sidebar.collapsed { width: var(--sidebar-width-collapsed); }
        .sidebar.collapsed .nav-link span,
        .sidebar.collapsed .sidebar-header span,
        .sidebar.collapsed .dropdown-toggle strong span { display: none; }
        .sidebar.collapsed .nav-link,
        .sidebar.collapsed .dropdown-toggle {
            justify-content: center;
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }
         .sidebar.collapsed .dropdown-menu {
             position: absolute !important;
             left: 100%;
             top: -10px;
             margin-left: 0.5rem;
         }
        .sidebar .nav-link .bi {
            margin-right: 8px;
            vertical-align: text-bottom;
            width: 1.5em;
            display: inline-block;
            text-align: center;
            transition: margin var(--transition-speed) ease;
        }
         .sidebar.collapsed .nav-link .bi { margin-right: 0; }
        .dropdown-item .bi { margin-right: 8px; }
        .btn .bi { vertical-align: text-bottom; }
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width-expanded);
            transition: margin-left var(--transition-speed) ease;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        body.sidebar-collapsed .main-content { margin-left: var(--sidebar-width-collapsed); }
        .content { flex: 1; padding: 20px; }
        .navbar { border-bottom: 1px solid var(--bs-border-color); }
         #sidebarToggle { margin-right: 15px; }
         .dropdown-item.active .bi-check { display: inline-block !important; }
         .dropdown-item .bi-check { display: none; margin-left: auto; }
    </style>
</head>

<body class="d-flex flex-column">

    <nav class="sidebar d-flex flex-column p-3 bg-body-tertiary" id="adminSidebar">
        <div class="sidebar-header d-flex justify-content-between align-items-center mb-4">
             <a href="<?= route_to('admin.dashboard') ?>" class="link-body-emphasis text-decoration-none">
                <span class="fs-5">Admin Menu</span>
             </a>
        </div>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="<?= route_to('admin.dashboard') ?>" class="nav-link d-flex align-items-center <?= (uri_string() === 'admin') ? 'active' : 'link-body-emphasis' ?>">
                    <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="<?= route_to('admin.products') ?>" class="nav-link d-flex align-items-center <?= (str_starts_with(uri_string(), 'admin/products')) ? 'active' : 'link-body-emphasis' ?>">
                    <i class="bi bi-box-seam"></i> <span>Produtos</span>
                </a>
            </li>
            <li>
                <a href="<?= route_to('admin.stock') ?>" class="nav-link d-flex align-items-center <?= (str_starts_with(uri_string(), 'admin/stock')) ? 'active' : 'link-body-emphasis' ?>">
                    <i class="bi bi-stack"></i> <span>Estoque</span>
                </a>
            </li>
        </ul>
        <hr>
        <div class="dropdown">
             <a href="#" class="d-flex align-items-center link-body-emphasis text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                 <i class="bi bi-person-circle me-2"></i> <strong><span>Admin</span></strong>
            </a>
            <ul class="dropdown-menu text-small shadow" aria-labelledby="dropdownUser">
                <li><a class="dropdown-item" href="<?= route_to('logout') ?>"><i class="bi bi-box-arrow-right"></i> Sair</a></li>
            </ul>
        </div>
    </nav>

    <div class="main-content">
        <nav class="navbar navbar-expand navbar-light bg-body-tertiary">
            <div class="container-fluid">
                <button class="btn btn-outline-secondary" type="button" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <span class="navbar-brand mb-0 h1 ms-2">ML Bot Delivery</span>
                <div class="ms-auto d-flex align-items-center">
                     <div class="dropdown me-2">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle d-flex align-items-center" type="button" id="bd-theme" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-circle-half me-2 theme-icon-active"></i>
                            <span class="d-lg-inline">Tema</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="bd-theme">
                            <li><button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="light"><i class="bi bi-sun-fill me-2"></i> Light <i class="bi bi-check ms-auto"></i></button></li>
                            <li><button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="dark"><i class="bi bi-moon-stars-fill me-2"></i> Dark <i class="bi bi-check ms-auto"></i></button></li>
                            <li><button type="button" class="dropdown-item d-flex align-items-center active" data-bs-theme-value="auto"><i class="bi bi-circle-half me-2"></i> Auto <i class="bi bi-check ms-auto"></i></button></li>
                        </ul>
                    </div>
                    <a href="<?= route_to('logout') ?>" class="btn btn-outline-danger btn-sm">
                       <i class="bi bi-box-arrow-right"></i> Sair
                    </a>
                </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('adminSidebar');
            const toggleButton = document.getElementById('sidebarToggle');
            const body = document.body;
            const sidebarStateKey = 'sidebarCollapsedState';

            function toggleSidebar() {
                sidebar.classList.toggle('collapsed');
                body.classList.toggle('sidebar-collapsed');
                localStorage.setItem(sidebarStateKey, sidebar.classList.contains('collapsed'));
                // Trigger tooltip reinitialization after transition ends (optional but smoother)
                // sidebar.addEventListener('transitionend', initializeTooltips, { once: true });
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
                 const collapsedSidebarLinks = sidebar.querySelectorAll('.nav-link'); // Get links inside this function scope

                if (sidebar.classList.contains('collapsed') && bootstrap && bootstrap.Tooltip) {
                    collapsedSidebarLinks.forEach(link => {
                        const text = link.querySelector('span')?.textContent?.trim();
                        if (text) {
                            link.setAttribute('data-bs-toggle', 'tooltip');
                            link.setAttribute('data-bs-placement', 'right');
                            link.setAttribute('data-bs-title', text); // Use data-bs-title for BS5.3+
                            tooltipInstances.push(new bootstrap.Tooltip(link, { trigger: 'hover' })); // Trigger on hover
                        }
                    });
                } else {
                     collapsedSidebarLinks.forEach(link => {
                        // Remove tooltip attributes if they exist
                        if (link.hasAttribute('data-bs-toggle')) link.removeAttribute('data-bs-toggle');
                        if (link.hasAttribute('data-bs-placement')) link.removeAttribute('data-bs-placement');
                        if (link.hasAttribute('data-bs-title')) link.removeAttribute('data-bs-title');
                        const tooltip = bootstrap.Tooltip.getInstance(link); // Get instance if exists
                        if (tooltip) tooltip.dispose(); // Dispose instance
                     });
                }
            }

            initializeTooltips();
            const observer = new MutationObserver(mutations => {
                 mutations.forEach(mutation => {
                    if (mutation.attributeName === 'class') {
                         // Small delay might help ensure styles are applied before initializing tooltips
                         setTimeout(initializeTooltips, 50); 
                    }
                });
            });
            observer.observe(sidebar, { attributes: true });
             // --- End Tooltip Logic ---
        });
    </script>

    <script>
      (() => {
        'use strict'
        const storedTheme = localStorage.getItem('theme')
        const getPreferredTheme = () => {
          if (storedTheme) { return storedTheme }
          return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light' // Default to OS preference if no storage
        }
        const setTheme = theme => {
          let themeToSet = theme
          if (theme === 'auto') {
              themeToSet = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
          }
          document.documentElement.setAttribute('data-bs-theme', themeToSet)
        }
        setTheme(getPreferredTheme() === 'auto' ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : getPreferredTheme()) // Apply initial theme immediately

        const showActiveTheme = (theme, focus = false) => {
          const themeSwitcher = document.querySelector('#bd-theme')
          if (!themeSwitcher) return; // Exit if element not found
          const activeThemeIcon = document.querySelector('.theme-icon-active')
          const btnToActive = document.querySelector(`[data-bs-theme-value="${theme}"]`)
          const iconOfActiveBtn = btnToActive?.querySelector('i')?.classList?.item(1)

          document.querySelectorAll('[data-bs-theme-value]').forEach(element => {
            element.classList.remove('active')
            element.setAttribute('aria-pressed', 'false')
          })

          if (btnToActive) {
              btnToActive.classList.add('active')
              btnToActive.setAttribute('aria-pressed', 'true')
          }
          if (activeThemeIcon && iconOfActiveBtn) {
              activeThemeIcon.classList.remove('bi-sun-fill', 'bi-moon-stars-fill', 'bi-circle-half')
              activeThemeIcon.classList.add(iconOfActiveBtn)
          }
          if (focus) { themeSwitcher.focus() }
        }

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
          const currentStoredTheme = localStorage.getItem('theme'); // Get fresh value
          if (currentStoredTheme === 'auto' || !currentStoredTheme) { // React if auto or no preference set
            const preferred = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            setTheme(preferred);
            showActiveTheme('auto'); // Keep showing 'auto' as selected in dropdown
          }
        })

        window.addEventListener('DOMContentLoaded', () => {
          const currentTheme = localStorage.getItem('theme') || 'auto'; // Default to auto
          showActiveTheme(currentTheme)
          document.querySelectorAll('[data-bs-theme-value]')
            .forEach(toggle => {
              toggle.addEventListener('click', () => {
                const theme = toggle.getAttribute('data-bs-theme-value')
                localStorage.setItem('theme', theme)
                setTheme(theme)
                showActiveTheme(theme, true)
              })
            })
        })
      })()
    </script>
</body>
</html>