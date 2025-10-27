// public/js/template.js

// --- Script para Toggle da Sidebar e Tooltips ---
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('adminSidebar');
    const toggleButton = document.getElementById('sidebarToggle');
    const body = document.body;
    const sidebarStateKey = 'sidebarCollapsedState';

    // Função para obter a duração da transição CSS (se definida)
    function getTransitionDuration(element) {
        const duration = getComputedStyle(element).getPropertyValue('--transition-speed') || '0.3s';
        return parseFloat(duration) * 1000;
    }

    // Função para alternar a sidebar
    function toggleSidebar() {
        if (!sidebar || !body || !toggleButton) return; // Verifica se os elementos existem

        sidebar.classList.toggle('collapsed');
        body.classList.toggle('sidebar-collapsed');
        localStorage.setItem(sidebarStateKey, sidebar.classList.contains('collapsed'));

        // Atualiza o texto do botão toggle, se existir o span
        const toggleTextSpan = toggleButton.querySelector('.toggle-text');
        if (toggleTextSpan) {
            toggleTextSpan.textContent = sidebar.classList.contains('collapsed') ? 'Expandir Menu' : 'Recolher Menu';
        }
        // Re-inicializa os tooltips após um pequeno delay
        setTimeout(initializeTooltips, 50);
    }

    // Verifica o estado inicial da sidebar no carregamento
    if (localStorage.getItem(sidebarStateKey) === 'true') {
        if (sidebar && body) {
            sidebar.classList.add('collapsed');
            body.classList.add('sidebar-collapsed');
        }
        // Ajusta texto inicial do botão se colapsado e o botão existe
        const toggleTextSpan = toggleButton ? toggleButton.querySelector('.toggle-text') : null;
        if (toggleTextSpan) {
            toggleTextSpan.textContent = 'Expandir Menu';
        }
    }

    // Adiciona o evento de clique ao botão, se ele existir
    if (toggleButton) {
        toggleButton.addEventListener('click', toggleSidebar);
    }

    // --- Lógica dos Tooltips ---
    let tooltipInstances = [];
    function initializeTooltips() {
        // Destroi instâncias anteriores
        tooltipInstances.forEach(t => t.dispose());
        tooltipInstances = [];

        // Seleciona todos os elementos que podem ter tooltip na sidebar
        const tooltipTriggerList = sidebar ? sidebar.querySelectorAll('.nav-pills .nav-link, .logout-link, #dropdownUser, #sidebarToggle') : [];

        if (sidebar && sidebar.classList.contains('collapsed')) {
            // Sidebar Colapsada: Ativa tooltips
            tooltipTriggerList.forEach(tooltipTriggerEl => {
                let titleText = '';
                if (tooltipTriggerEl.id === 'sidebarToggle') {
                    titleText = tooltipTriggerEl.getAttribute('title') || 'Expandir Menu';
                } else {
                    const textSpan = tooltipTriggerEl.querySelector('span');
                    if (textSpan && textSpan.textContent.trim()) { titleText = textSpan.textContent.trim(); }
                    const strongSpan = tooltipTriggerEl.querySelector('strong > span');
                    if (strongSpan && strongSpan.textContent.trim()) { titleText = strongSpan.textContent.trim(); }
                    if (!titleText && tooltipTriggerEl.hasAttribute('data-bs-title')) { titleText = tooltipTriggerEl.getAttribute('data-bs-title'); }
                }
                if (titleText) {
                    tooltipTriggerEl.setAttribute('data-bs-toggle', 'tooltip');
                    tooltipTriggerEl.setAttribute('data-bs-placement', 'right');
                    tooltipTriggerEl.setAttribute('data-bs-trigger', 'hover');
                    tooltipTriggerEl.setAttribute('data-bs-original-title', titleText);
                    // Verifica se Bootstrap Tooltip está carregado
                    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                        tooltipInstances.push(new bootstrap.Tooltip(tooltipTriggerEl));
                    }
                }
            });
        } else {
            // Sidebar Expandida: Desativa tooltips (exceto dropdownUser)
            tooltipTriggerList.forEach(tooltipTriggerEl => {
                if (tooltipTriggerEl.id !== 'dropdownUser') {
                    // Verifica se Bootstrap Tooltip está carregado antes de usar getInstance
                    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                        const tooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
                        if (tooltip) { tooltip.dispose(); }
                    }
                    tooltipTriggerEl.removeAttribute('data-bs-toggle');
                    tooltipTriggerEl.removeAttribute('data-bs-placement');
                    tooltipTriggerEl.removeAttribute('data-bs-trigger');
                    tooltipTriggerEl.removeAttribute('data-bs-original-title');
                } else {
                    // Garante tooltip do usuário (se Bootstrap estiver carregado)
                    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                        const tooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl) || new bootstrap.Tooltip(tooltipTriggerEl);
                        tooltipInstances.push(tooltip);
                    }
                }
            });
            // Garante que o botão toggle também não tenha tooltip quando expandido
             if (toggleButton && typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                 const tooltip = bootstrap.Tooltip.getInstance(toggleButton);
                 if (tooltip) { tooltip.dispose(); }
                 toggleButton.removeAttribute('data-bs-toggle');
                 toggleButton.removeAttribute('data-bs-placement');
                 toggleButton.removeAttribute('data-bs-trigger');
                 toggleButton.removeAttribute('data-bs-original-title');
             }
        }
    }
    // Inicializa os tooltips no carregamento da página
    initializeTooltips();
    // --- Fim da Lógica dos Tooltips ---
});

// --- Script do Seletor de Tema (Switch) ---
(() => {
    'use strict'
    const storedTheme = localStorage.getItem('theme');
    const themeSwitch = document.getElementById('themeSwitch');
    const themeIcon = document.querySelector('.theme-switcher-label i.bi');
    const themeText = document.querySelector('.theme-switcher-label span.theme-switcher-text');
    const themeContainer = document.querySelector('.theme-switcher-container');

    const getPreferredTheme = () => {
        if (storedTheme) { return storedTheme; }
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    const setTheme = (theme) => {
        if (theme !== 'light' && theme !== 'dark') {
            theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        document.documentElement.setAttribute('data-bs-theme', theme);
        // Atualiza a interface do switch apenas se os elementos existirem
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

    // Define o tema inicial
    setTheme(getPreferredTheme());

    // Ouve mudanças na preferência do sistema (se nenhum tema foi salvo)
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (!localStorage.getItem('theme')) {
            setTheme(getPreferredTheme());
        }
    });

    // Adiciona evento ao switch, se ele existir
    if (themeSwitch) {
        themeSwitch.addEventListener('change', () => {
            setTheme(themeSwitch.checked ? 'dark' : 'light');
        });
    }

    // Adiciona evento ao container do switch, se ele existir
    if (themeContainer && themeSwitch) {
        themeContainer.addEventListener('click', (e) => {
            // Previne duplo disparo se clicar diretamente no input
            if (e.target !== themeSwitch) {
                e.preventDefault();
                themeSwitch.click(); // Simula o clique no input
            }
        });
    }
})();
// --- Fim do Script do Seletor de Tema ---