// public/js/template.js
// --- Script para Toggle da Sidebar e Tooltips ---
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('adminSidebar');
    const toggleButton = document.getElementById('sidebarToggle');
    const body = document.body;
    const sidebarStateKey = 'sidebarCollapsedState';
    // Função para obter a duração da transição CSS (se definida)
    function getTransitionDuration(element) {
        const style = window.getComputedStyle(element);
        const durationString = style.getPropertyValue('--transition-speed') || style.transitionDuration || '0.3s';
        // Extrai o número e converte para milissegundos
        const duration = parseFloat(durationString) * (durationString.includes('ms') ? 1 : 1000);
        return duration;
    }
    // Função para alternar a sidebar
    function toggleSidebar() {
        if (!sidebar || !body || !toggleButton) return; // Verifica se os elementos existem

        sidebar.classList.toggle('collapsed');
        body.classList.toggle('sidebar-collapsed');
        localStorage.setItem(sidebarStateKey, sidebar.classList.contains('collapsed'));
        // Re-inicializa os tooltips após um pequeno delay para a animação CSS
        // Usa a duração da transição + um pequeno buffer
        const delay = getTransitionDuration(sidebar) + 50;
        setTimeout(initializeTooltips, delay);
    }
    // Verifica o estado inicial da sidebar no carregamento
    if (localStorage.getItem(sidebarStateKey) === 'true') {
        if (sidebar && body) {
            sidebar.classList.add('collapsed');
            body.classList.add('sidebar-collapsed');
        }
    }
    // Adiciona o evento de clique ao botão, se ele existir
    if (toggleButton) {
        toggleButton.addEventListener('click', toggleSidebar);
    }
    // --- Lógica dos Tooltips ---
    let tooltipInstances = [];
    function initializeTooltips() {
        // Destroi instâncias anteriores para evitar duplicação
        tooltipInstances.forEach(t => t.dispose());
        tooltipInstances = [];
        // Verifica se Bootstrap Tooltip está carregado antes de prosseguir
        if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) {
            console.warn('Bootstrap Tooltip component not found.');
            return;
        }
        // Seleciona todos os elementos elegíveis para tooltip na sidebar
        const tooltipTriggerList = sidebar ? sidebar.querySelectorAll('.nav-pills .nav-link, .logout-link, #dropdownUser, #sidebarToggle') : [];

        if (sidebar && sidebar.classList.contains('collapsed')) {
            // Sidebar Colapsada: Ativa tooltips
            tooltipTriggerList.forEach(tooltipTriggerEl => {
                let titleText = '';
                // Tenta pegar o texto de dentro do elemento (span)
                const textElement = tooltipTriggerEl.querySelector('span:not(.toggle-text)');
                if (textElement) {
                    titleText = textElement.textContent.trim();
                }
                // Casos especiais ou fallback para atributos
                if (tooltipTriggerEl.id === 'sidebarToggle') {
                    // Texto do botão toggle depende do estado atual
                    // ESTA LÓGICA AGORA FORNECE O TEXTO PARA O *TOOLTIP*
                    titleText = sidebar.classList.contains('collapsed') ? 'Expandir Menu' : 'Recolher Menu';
                } else if (tooltipTriggerEl.id === 'dropdownUser' && !titleText) {
                    // Fallback específico para o dropdown do usuário
                    titleText = tooltipTriggerEl.getAttribute('data-bs-title') || 'Opções do Usuário';
                } else if (!titleText && tooltipTriggerEl.hasAttribute('data-bs-title')) {
                    titleText = tooltipTriggerEl.getAttribute('data-bs-title');
                } else if (!titleText && tooltipTriggerEl.hasAttribute('title')) { // Fallback para title nativo
                    titleText = tooltipTriggerEl.getAttribute('title');
                }
                if (titleText) {
                    // Define os atributos necessários para o Bootstrap Tooltip
                    tooltipTriggerEl.setAttribute('data-bs-toggle', 'tooltip');
                    tooltipTriggerEl.setAttribute('data-bs-placement', 'right');
                    tooltipTriggerEl.setAttribute('data-bs-trigger', 'hover'); // Mostrar ao passar o mouse
                    // Usa data-bs-title para o Bootstrap 5
                    tooltipTriggerEl.setAttribute('data-bs-title', titleText);
                    // Remove o title nativo para não conflitar
                    tooltipTriggerEl.removeAttribute('title');

                    // Inicializa o tooltip do Bootstrap
                    tooltipInstances.push(new bootstrap.Tooltip(tooltipTriggerEl));
                }
            });
        } else {
            // Sidebar Expandida: Desativa a maioria dos tooltips
            tooltipTriggerList.forEach(tooltipTriggerEl => {
                // MANTÉM tooltip para o #sidebarToggle e #dropdownUser
                const keepTooltip = (tooltipTriggerEl.id === 'dropdownUser' || tooltipTriggerEl.id === 'sidebarToggle');

                const tooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
                if (tooltip && !keepTooltip) {
                    tooltip.dispose(); // Destroi se não for para manter
                } else if (keepTooltip && tooltip) {
                    tooltipInstances.push(tooltip);
                } else if (keepTooltip && !tooltip && (tooltipTriggerEl.hasAttribute('data-bs-title') || tooltipTriggerEl.id === 'sidebarToggle')) {
                    // Se for para manter E não existe, cria (para garantir)
                    // Lógica especial para o sidebarToggle que pega o texto dinamicamente
                    if (tooltipTriggerEl.id === 'sidebarToggle') {
                        tooltipTriggerEl.setAttribute('data-bs-title', sidebar.classList.contains('collapsed') ? 'Expandir Menu' : 'Recolher Menu');
                    }
                    tooltipInstances.push(new bootstrap.Tooltip(tooltipTriggerEl));
                }
                if (!keepTooltip) {
                    tooltipTriggerEl.removeAttribute('data-bs-toggle');
                    tooltipTriggerEl.removeAttribute('data-bs-placement');
                    tooltipTriggerEl.removeAttribute('data-bs-trigger');
                    tooltipTriggerEl.removeAttribute('data-bs-original-title');
                }
            });
        }
    }
    // Inicializa os tooltips no carregamento inicial da página
    setTimeout(initializeTooltips, 100);
    // --- Fim da Lógica dos Tooltips ---
});
// --- Script do Seletor de Tema (Switch) ---
(() => {
    'use strict'
    const storedTheme = localStorage.getItem('theme');
    const themeSwitch = document.getElementById('themeSwitch');
    // Seleciona o ícone pela classe .theme-icon
    const themeIcon = document.querySelector('.theme-switcher-label i.theme-icon');
    const themeText = document.querySelector('.theme-switcher-label span.theme-switcher-text');
    const themeContainer = document.querySelector('.theme-switcher-container');
    // Função para obter o tema preferido (armazenado ou do sistema)
    const getPreferredTheme = () => {
        if (storedTheme) {
            return storedTheme;
        }
        // Verifica a preferência do sistema operacional
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    // Função para definir o tema e atualizar a interface
    const setTheme = (theme) => {
        // Garante que o tema seja 'light' ou 'dark'
        if (theme !== 'light' && theme !== 'dark') {
            theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        // Define o atributo data-bs-theme no HTML root
        document.documentElement.setAttribute('data-bs-theme', theme);
        // Atualiza a interface do switch (ícone e texto) apenas se os elementos existirem
        if (themeSwitch && themeIcon && themeText) {
            // Remove sempre as classes de ícone anteriores para evitar conflito
            themeIcon.classList.remove('fa-sun', 'fa-moon');

            if (theme === 'dark') {
                themeSwitch.checked = true;
                // Adiciona Lua (FA)
                themeIcon.classList.add('fa-moon');
                themeText.textContent = 'Modo Escuro';
            } else { // theme === 'light'
                themeSwitch.checked = false;
                // Adiciona Sol (FA)
                themeIcon.classList.add('fa-sun');
                themeText.textContent = 'Modo Claro';
            }
            // Garante que 'fa-solid' esteja sempre presente (se não estiver fixo no HTML)
            if (!themeIcon.classList.contains('fa-solid')) {
                themeIcon.classList.add('fa-solid');
            }
        }
        // Salva o tema escolhido no localStorage
        localStorage.setItem('theme', theme);
    }
    // Define o tema inicial ao carregar a página
    setTheme(getPreferredTheme());
    // Ouve mudanças na preferência do sistema (caso nenhum tema tenha sido salvo manualmente)
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (!localStorage.getItem('theme')) { // Só muda se não houver preferência salva
            setTheme(getPreferredTheme());
        }
    });
    // Adiciona evento de mudança ao switch (checkbox)
    if (themeSwitch) {
        themeSwitch.addEventListener('change', () => {
            // Define o tema com base no estado 'checked' do switch
            setTheme(themeSwitch.checked ? 'dark' : 'light');
        });
    }
    // Adiciona evento de clique ao container do switch para ativar o input
    if (themeContainer && themeSwitch) {
        themeContainer.addEventListener('click', (e) => {
            // Previne o evento duplo se o clique for diretamente no input
            if (e.target !== themeSwitch) {
                e.preventDefault();
                themeSwitch.click(); // Simula o clique no input
            }
        });
    }
})();