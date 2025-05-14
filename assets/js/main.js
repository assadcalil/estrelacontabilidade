/**
 * Script principal do sistema
 * 
 * @author Thiago Calil Assad
 * @created 2023-08-10
 */

// Inicialização do sistema quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    initializeSidebar();
    initializeDataTables();
    initializeTooltips();
    initializeFormValidation();
    setupAjaxHandlers();
    setupSessionCheck();
});

/**
 * Inicializa a barra lateral
 */
function initializeSidebar() {
    // Evento de submenu
    const sidebarToggles = document.querySelectorAll('.sidebar-toggle');
    sidebarToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Se o sidebar está colapsado, expande-o antes de mostrar o submenu
            const sidebar = document.getElementById('sidebar');
            if (sidebar.classList.contains('collapsed')) {
                sidebar.classList.remove('collapsed');
                document.getElementById('content').classList.remove('expanded');
                
                // Salvar estado no localStorage
                localStorage.setItem('sidebar-collapsed', false);
                
                // Mudar ícone do botão de colapso
                const collapseBtn = document.getElementById('sidebarCollapseBtn');
                const icon = collapseBtn.querySelector('i');
                icon.classList.remove('bi-arrow-right-circle');
                icon.classList.add('bi-arrow-left-circle');
                
                // Pequeno delay para permitir a transição antes de abrir o submenu
                setTimeout(() => {
                    const parentItem = this.closest('.sidebar-item');
                    parentItem.classList.toggle('open');
                }, 300);
            } else {
                const parentItem = this.closest('.sidebar-item');
                parentItem.classList.toggle('open');
            }
        });
    });
    
    // Para dispositivos móveis
    document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('sidebar');
        const sidebarToggleTop = document.getElementById('sidebarToggleTop');
        
        // Se o sidebar está visível em dispositivos móveis e o clique é fora dele (e não no botão de toggle)
        if (window.innerWidth <= 768 && 
            sidebar.classList.contains('mobile-show') && 
            !sidebar.contains(e.target) && 
            !sidebarToggleTop.contains(e.target)) {
            sidebar.classList.remove('mobile-show');
        }
    });
    
    // Estado inicial dos itens do menu (abrir os que têm itens ativos)
    const activeSubItems = document.querySelectorAll('.sidebar-submenu .sidebar-item.active');
    activeSubItems.forEach(item => {
        const parentItem = item.closest('.sidebar-item');
        if (parentItem) {
            parentItem.classList.add('open');
        }
    });
}

/**
 * Inicializa as tabelas de dados
 */
function initializeDataTables() {
    const tables = document.querySelectorAll('.datatable');
    if (tables.length > 0) {
        tables.forEach(table => {
            const options = {
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.5/i18n/pt-BR.json'
                },
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]]
            };
            
            // Verificar se tem opções personalizadas via data attribute
            if (table.dataset.options) {
                try {
                    const customOptions = JSON.parse(table.dataset.options);
                    Object.assign(options, customOptions);
                } catch (e) {
                    console.error("Erro ao parsear opções de datatable:", e);
                }
            }
            
            // Inicializar o DataTable
            new DataTable(table, options);
        });
    }
}

/**
 * Inicializa tooltips do Bootstrap
 */
function initializeTooltips() {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));
}

/**
 * Inicializa validação de formulários
 */
function initializeFormValidation() {
    // Validação de formulários do Bootstrap
    const forms = document.querySelectorAll('.needs-validation');
    
    if (forms.length > 0) {
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        });
    }
    
    // Validação de confirmação de senha
    const passwordFields = document.querySelectorAll('input[type="password"][data-match]');
    passwordFields.forEach(field => {
        const matchSelector = field.getAttribute('data-match');
        const matchField = document.querySelector(matchSelector);
        
        if (matchField) {
            const validateMatch = () => {
                if (field.value !== matchField.value) {
                    field.setCustomValidity('As senhas não coincidem');
                } else {
                    field.setCustomValidity('');
                }
            };
            
            field.addEventListener('input', validateMatch);
            matchField.addEventListener('input', validateMatch);
        }
    });
}

/**
 * Configura handlers para requisições AJAX
 */
function setupAjaxHandlers() {
    // Preloader global para requisições AJAX
    let activeRequests = 0;
    const preloader = document.createElement('div');
    preloader.className = 'preloader';
    preloader.innerHTML = `
        <div class="preloader-content">
            <div class="preloader-spinner"></div>
            <p>Carregando...</p>
        </div>
    `;
    preloader.style.display = 'none';
    document.body.appendChild(preloader);
    
    // Intercepta todas as requisições AJAX
    const originalFetch = window.fetch;
    window.fetch = function() {
        activeRequests++;
        preloader.style.display = 'flex';
        
        return originalFetch.apply(this, arguments)
            .then(response => {
                activeRequests--;
                if (activeRequests === 0) {
                    preloader.style.display = 'none';
                }
                return response;
            })
            .catch(error => {
                activeRequests--;
                if (activeRequests === 0) {
                    preloader.style.display = 'none';
                }
                throw error;
            });
    };
    
    // Formulários com atributo data-ajax="true"
    const ajaxForms = document.querySelectorAll('form[data-ajax="true"]');
    ajaxForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            
            // Verificar validação Bootstrap
            if (form.classList.contains('needs-validation') && !form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }
            
            // Preparar dados do formulário
            const formData = new FormData(form);
            const submitBtn = form.querySelector('[type="submit"]');
            const loadingText = submitBtn.getAttribute('data-loading-text') || 'Processando...';
            const originalText = submitBtn.innerHTML;
            
            // Desabilitar o botão e mostrar texto de carregamento
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = loadingText;
            }
            
            // Enviar requisição
            fetch(form.action, {
                method: form.method || 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                // Reativar o botão
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
                
                // Se há um callback definido, chamá-lo
                const callback = form.getAttribute('data-callback');
                if (callback && typeof window[callback] === 'function') {
                    window[callback](data, form);
                } else {
                    // Comportamento padrão para respostas de sucesso/erro
                    if (data.success) {
                        // Mostrar mensagem de sucesso
                        showNotification(data.message || 'Operação realizada com sucesso!', 'success');
                        
                        // Redirecionar se necessário
                        if (data.redirect) {
                            setTimeout(() => {
                                window.location.href = data.redirect;
                            }, 1000);
                        } else if (form.getAttribute('data-reset-on-success') === 'true') {
                            form.reset();
                        }
                    } else {
                        // Mostrar mensagem de erro
                        showNotification(data.message || 'Ocorreu um erro ao processar a solicitação.', 'danger');
                    }
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                
                // Reativar o botão
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
                
                // Mostrar mensagem de erro
                showNotification('Ocorreu um erro ao processar a solicitação. Tente novamente.', 'danger');
            });
        });
    });
}

/**
 * Configura verificação periódica de sessão
 */
function setupSessionCheck() {
    // Verifica a sessão a cada 5 minutos
    const SESSION_CHECK_INTERVAL = 5 * 60 * 1000; // 5 minutos
    
    setInterval(() => {
        fetch(BASE_URL + '/app/controllers/session_controller.php?action=check_session', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'warning' && data.message === 'multiple_session') {
                // Mostrar modal de alerta de sessão múltipla
                const multipleSessionModal = new bootstrap.Modal(document.getElementById('multipleSessionModal'));
                if (multipleSessionModal) {
                    multipleSessionModal.show();
                } else {
                    // Se o modal não existir, mostrar uma notificação
                    showNotification('Sua conta está em uso em outro dispositivo.', 'warning');
                }
            }
        })
        .catch(error => {
            console.error('Erro ao verificar sessão:', error);
        });
    }, SESSION_CHECK_INTERVAL);
}

/**
 * Exibe uma notificação na tela
 * 
 * @param {string} message Mensagem a ser exibida
 * @param {string} type Tipo de notificação (success, danger, warning, info)
 * @param {number} duration Duração em ms (0 para não fechar automaticamente)
 */
function showNotification(message, type = 'info', duration = 5000) {
    // Criar o elemento de notificação
    const notification = document.createElement('div');
    notification.className = `toast align-items-center text-white bg-${type} border-0`;
    notification.setAttribute('role', 'alert');
    notification.setAttribute('aria-live', 'assertive');
    notification.setAttribute('aria-atomic', 'true');
    
    // Conteúdo da notificação
    notification.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fechar"></button>
        </div>
    `;
    
    // Adicionar ao container de notificações
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    toastContainer.appendChild(notification);
    
    // Inicializar e mostrar o toast
    const toast = new bootstrap.Toast(notification, {
        autohide: duration > 0,
        delay: duration
    });
    
    toast.show();
    
    // Remover o elemento após fechar
    notification.addEventListener('hidden.bs.toast', function() {
        notification.remove();
    });
}

/**
 * Funções auxiliares de formatação
 */
const formatters = {
    /**
     * Formata um valor como moeda brasileira
     * 
     * @param {number} value Valor a ser formatado
     * @param {string} currency Símbolo da moeda (padrão: R$)
     * @returns {string} Valor formatado
     */
    currency: function(value, currency = 'R$') {
        return currency + ' ' + value.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    },
    
    /**
     * Formata uma data no padrão brasileiro
     * 
     * @param {string|Date} date Data a ser formatada
     * @returns {string} Data formatada
     */
    date: function(date) {
        if (!date) return '';
        
        if (typeof date === 'string') {
            date = new Date(date);
        }
        
        return date.toLocaleDateString('pt-BR');
    },
    
    /**
     * Formata um número com separador de milhar
     * 
     * @param {number} value Valor a ser formatado
     * @param {number} decimals Número de casas decimais
     * @returns {string} Valor formatado
     */
    number: function(value, decimals = 0) {
        return value.toLocaleString('pt-BR', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }
};

// Expor funções úteis globalmente
window.showNotification = showNotification;
window.formatters = formatters;