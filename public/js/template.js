// public/js/template.js
// --- Script para Toggle da Sidebar e Tooltips ---
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('adminSidebar');
    const toggleButton = document.getElementById('sidebarToggle');
    const body = document.body;
    const sidebarStateKey = 'sidebarCollapsedState';

    // --- MUDANÇA: Adicionado 'fa-lg' e 'fa-fw' aos ícones ---
    const iconExpanded = 'fa-solid fa-bars-staggered fa-flip-horizontal fa-lg fa-fw';
    const iconCollapsed = 'fa-solid fa-bars-staggered fa-lg fa-fw';

    // --- Nova função para atualizar o ícone ---
    function updateToggleIcon() {
        if (!toggleButton || !sidebar) return;
        const icon = toggleButton.querySelector('i');
        if (!icon) return;

        // Limpa classes de ícone antigas (incluindo as de ícones anteriores)
        icon.className = ''; 

        if (sidebar.classList.contains('collapsed')) {
            // Adiciona classes do ícone "recolhido"
            icon.classList.add(...iconCollapsed.split(' '));
        } else {
            // Adiciona classes do ícone "expandido"
            icon.classList.add(...iconExpanded.split(' '));
        }
    }
    // --- FIM DA MUDANÇA ---

    // Função para obter a duração da transição CSS (se definida)
    function getTransitionDuration(element) {
        const style = window.getComputedStyle(element);
        const durationString = style.getPropertyValue('--transition-speed') || style.transitionDuration || '0.3s';
        // Extrai o número e converte para milissegundos
        const duration = parseFloat(durationString) * (durationString.includes('ms') ? 1 : 1000);
        return duration;
    }
    
    // --- Função de toggle atualizada ---
    function toggleSidebar() {
        if (!sidebar || !body || !toggleButton) return; // Verifica se os elementos existem

        sidebar.classList.toggle('collapsed');
        body.classList.toggle('sidebar-collapsed');
        localStorage.setItem(sidebarStateKey, sidebar.classList.contains('collapsed'));
        
        // Atualiza o ícone
        updateToggleIcon();

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

    // --- Atualiza o ícone no carregamento da página ---
    updateToggleIcon();

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
        
        // MODIFICADO: Seleciona tooltips da sidebar E da navbar
        const tooltipTriggerListSidebar = sidebar ? sidebar.querySelectorAll('.nav-pills .nav-link, .logout-link, #dropdownUser') : [];
        const tooltipTriggerListNavbar = document.querySelectorAll('#dropdownUser, #themeSwitch'); // Adiciona os novos itens da navbar
        
        // Seleciona o botão de toggle separadamente
        const toggleButtonTooltip = document.getElementById('sidebarToggle');
        
        // Lógica para o botão de toggle (agora na navbar)
        if (toggleButtonTooltip) {
             // Destroi tooltip antigo se existir
            const oldTooltip = bootstrap.Tooltip.getInstance(toggleButtonTooltip);
            if (oldTooltip) {
                oldTooltip.dispose();
            }
            
            // Define o texto dinâmico
            const titleText = sidebar.classList.contains('collapsed') ? 'Expandir Menu' : 'Recolher Menu';
            toggleButtonTooltip.setAttribute('data-bs-toggle', 'tooltip');
            toggleButtonTooltip.setAttribute('data-bs-placement', 'bottom'); // Melhor 'bottom' na navbar
            toggleButtonTooltip.setAttribute('data-bs-trigger', 'hover');
            toggleButtonTooltip.setAttribute('data-bs-title', titleText);
            toggleButtonTooltip.removeAttribute('title');
            tooltipInstances.push(new bootstrap.Tooltip(toggleButtonTooltip));
        }

        // Tooltips da Navbar (Perfil e Tema) - Sempre ativos
        tooltipTriggerListNavbar.forEach(tooltipTriggerEl => {
            if (tooltipTriggerEl.hasAttribute('data-bs-title') || tooltipTriggerEl.hasAttribute('title')) {
                tooltipInstances.push(new bootstrap.Tooltip(tooltipTriggerEl));
            }
        });


        if (sidebar && sidebar.classList.contains('collapsed')) {
            // Sidebar Colapsada: Ativa tooltips (dos links da sidebar)
            tooltipTriggerListSidebar.forEach(tooltipTriggerEl => {
                let titleText = '';
                // Tenta pegar o texto de dentro do elemento (span)
                const textElement = tooltipTriggerEl.querySelector('span:not(.toggle-text)');
                if (textElement) {
                    titleText = textElement.textContent.trim();
                }
                
                // Casos especiais ou fallback para atributos
                if (!titleText && tooltipTriggerEl.hasAttribute('data-bs-title')) {
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
            // Sidebar Expandida: Desativa a maioria dos tooltips DA SIDEBAR
            tooltipTriggerListSidebar.forEach(tooltipTriggerEl => {
                // MANTÉM tooltip para o #dropdownUser se ele AINDA ESTIVER na sidebar (não está mais, mas seguro)
                const keepTooltip = (tooltipTriggerEl.id === 'dropdownUser');

                const tooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
                if (tooltip && !keepTooltip) {
                    tooltip.dispose(); // Destroi se não for para manter
                } else if (keepTooltip && tooltip) {
                    tooltipInstances.push(tooltip);
                } else if (keepTooltip && !tooltip && tooltipTriggerEl.hasAttribute('data-bs-title')) {
                    // Se for para manter E não existe, cria (para garantir)
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
    
    // MODIFICADO: Removido 'themeText'
    // const themeText = document.querySelector('.theme-switcher-label span.theme-switcher-text');
    
    // MODIFICADO: Seletor atualizado para navbar OU sidebar
    const themeContainer = document.querySelector('.theme-switcher-container-navbar') || document.querySelector('.theme-switcher-container');
    
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
        
        // MODIFICADO: Atualiza a interface do switch (ícone) - removido themeText
        if (themeSwitch && themeIcon) {
            // Remove sempre as classes de ícone anteriores para evitar conflito
            themeIcon.classList.remove('fa-sun', 'fa-moon');

            if (theme === 'dark') {
                themeSwitch.checked = true;
                // Adiciona Lua (FA)
                themeIcon.classList.add('fa-moon');
                // MODIFICADO: Removido 'themeText.textContent'
            } else { // theme === 'light'
                themeSwitch.checked = false;
                // Adiciona Sol (FA)
                themeIcon.classList.add('fa-sun');
                 // MODIFICADO: Removido 'themeText.textContent'
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
            // Previne o evento duplo se o clique for diretamente no input ou no label
            if (e.target !== themeSwitch && e.target.tagName.toLowerCase() !== 'label') {
                e.preventDefault();
                themeSwitch.click(); // Simula o clique no input
            }
        });
    }
})();

// --- Lógica de Navegação AJAX (Restaurada) ---
document.addEventListener('DOMContentLoaded', function () {
    // Verifica se o jQuery está carregado
    if (typeof jQuery === 'undefined') {
        console.error('jQuery não está carregado. A navegação AJAX foi desabilitada.');
        return;
    }

    (function($) { // Wrapper do jQuery
        
        // Interceptar cliques nos links da sidebar que marcamos
        $('#adminSidebar').on('click', 'a.ajax-link', function (e) {
            e.preventDefault(); // Impedir o carregamento da página inteira
            
            var $link = $(this);
            var url = $link.attr('href');

            // --- CORREÇÃO: Lógica que impedia o reload foi comentada ---
            // Ignorar se o link já estiver ativo
            // if ($link.hasClass('active') && !url.includes('#')) { // Permite abas (que usam #)
            //     return;
            // }
            // --- FIM DA CORREÇÃO ---
            
            // Selecionar os containers de conteúdo
            var $contentContainer = $('main.content');
            var $pageTitle = $('h1.mb-4'); // Seleciona o H1 do título da página

            // Mostrar um spinner de loading
            $pageTitle.text('Carregando...');
            $contentContainer.html('<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Carregando...</span></div></div>');

            // Fazer a requisição AJAX
            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'html', // Esperamos receber a página HTML completa
                cache: false, // Adicionado para garantir que os dados sejam novos
                success: function (responseHtml) {
                    try {
                        // Coloca a resposta (HTML completo) em um div temporário
                        var $newHtml = $('<div>').html(responseHtml);

                        // Encontrar o novo título, conteúdo e scripts da página de resposta
                        var newTitle = $newHtml.find('h1.mb-4').html();
                        var newContent = $newHtml.find('main.content').html();
                        var newScripts = $newHtml.find('#ajax-scripts').html(); // Pega o conteúdo do nosso container

                        if (newContent) {
                            // 1. Atualizar o título e o conteúdo da página atual
                            $pageTitle.html(newTitle);
                            $contentContainer.html(newContent);

                            // 2. Atualizar o estado 'active' na sidebar
                            $('#adminSidebar .nav-pills a.active').removeClass('active').addClass('link-body-emphasis');
                            $link.addClass('active').removeClass('link-body-emphasis');

                            // 3. Atualizar a URL na barra de endereços (para deep-linking e botão voltar)
                            history.pushState({ path: url }, '', url);

                            // 4. Atualizar o <title> da aba do navegador
                            var newPageTitle = $newHtml.find('title').text();
                            if (newPageTitle) {
                                document.title = newPageTitle;
                            }
                            
                            // 5. Remover scripts AJAX antigos (se existirem)
                            $('#ajax-scripts-container').remove();
                            
                            // 6. Adicionar e executar os novos scripts (MUITO IMPORTANTE para DataTables)
                            if (newScripts) {
                                // Criamos um novo container de script e o adicionamos
                                var $scriptContainer = $('<div id="ajax-scripts-container"></div>').html(newScripts);
                                $('body').append($scriptContainer);
                            }
                            
                            // 7. (Opcional) Rolar para o topo da área de conteúdo
                            $contentContainer.scrollTop(0);

                        } else {
                            // Fallback: Se não conseguir "parsear" o conteúdo (ex: erro de login/redirect)
                            window.location.href = url;
                        }
                    } catch(e) {
                         console.error("Erro ao processar resposta AJAX:", e);
                        window.location.href = url; // Fallback
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error('Erro no AJAX:', textStatus, errorThrown);
                    // Se falhar (ex: sessão expirou e foi redirecionado pro login),
                    // apenas recarrega a página inteira.
                    window.location.href = url;
                }
            });
        });

        // Lidar com o botão "Voltar" e "Avançar" do navegador
        $(window).on('popstate', function(e) {
            // Se o usuário clicar em "voltar", força um reload completo da URL de destino
            // (É mais simples do que recarregar o estado AJAX anterior)
            if (e.originalEvent.state && e.originalEvent.state.path) {
                 window.location.href = e.originalEvent.state.path;
            } else {
                 location.reload();
            }
        });

    })(jQuery);

});
// --- Fim da Lógica de Navegação AJAX ---