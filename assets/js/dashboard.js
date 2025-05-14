/**
 * dashboard.js - Scripts para os gráficos e funcionalidades do dashboard
 */

document.addEventListener('DOMContentLoaded', function() {
    // Configuração de cores
    const colors = {
        primary: '#2c3e50',
        secondary: '#3498db',
        success: '#2ecc71',
        warning: '#f39c12',
        danger: '#e74c3c',
        info: '#3498db',
        light: '#ecf0f1',
        dark: '#2c3e50',
        gray: '#95a5a6',
        
        // Cores adicionais para gráficos
        chart: [
            'rgba(52, 152, 219, 0.8)',
            'rgba(46, 204, 113, 0.8)',
            'rgba(155, 89, 182, 0.8)',
            'rgba(52, 73, 94, 0.8)',
            'rgba(243, 156, 18, 0.8)',
            'rgba(231, 76, 60, 0.8)',
            'rgba(26, 188, 156, 0.8)',
            'rgba(241, 196, 15, 0.8)',
            'rgba(230, 126, 34, 0.8)',
            'rgba(149, 165, 166, 0.8)'
        ]
    };
    
    // Configurações globais do Chart.js
    Chart.defaults.font.family = "'Nunito', sans-serif";
    Chart.defaults.color = '#6c757d';
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0, 0, 0, 0.7)';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    
    // Gráfico 1: Documentos por Categoria
    initDocumentsByCategoryChart();
    
    // Gráfico 2: Próximos Vencimentos
    initUpcomingExpirationsChart();
    
    // Animação de números nos cards
    animateCounters();
    
    /**
     * Inicializa o gráfico de documentos por categoria
     */
    function initDocumentsByCategoryChart() {
        const ctx = document.getElementById('documentsByCategory');
        
        if (!ctx) return;
        
        // Simulação de dados - Substitua por dados reais da sua API
        const data = {
            labels: ['Contratos', 'Certificados', 'Contábil', 'Fiscal', 'RH', 'Outros'],
            datasets: [{
                label: 'Documentos por Categoria',
                data: [65, 59, 80, 81, 56, 40],
                backgroundColor: colors.chart,
                borderWidth: 1
            }]
        };
        
        new Chart(ctx, {
            type: 'bar',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false,
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
    
    /**
     * Inicializa o gráfico de próximos vencimentos
     */
    function initUpcomingExpirationsChart() {
        const ctx = document.getElementById('upcomingExpirations');
        
        if (!ctx) return;
        
        // Simulação de dados - Substitua por dados reais da sua API
        const data = {
            labels: ['30 dias', '60 dias', '90 dias'],
            datasets: [{
                label: 'Vencimentos',
                data: [12, 19, 8],
                backgroundColor: [
                    colors.danger,
                    colors.warning,
                    colors.success
                ],
                borderWidth: 0,
                borderRadius: 5
            }]
        };
        
        new Chart(ctx, {
            type: 'doughnut',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    /**
     * Animação de contadores
     */
    function animateCounters() {
        const counters = document.querySelectorAll('.dashboard-card h2');
        
        counters.forEach(counter => {
            const target = parseInt(counter.innerText.replace(/,/g, ''), 10);
            const increment = target / 20;
            let current = 0;
            
            const updateCounter = () => {
                if (current < target) {
                    current += increment;
                    if (current > target) current = target;
                    counter.innerText = Math.ceil(current).toLocaleString();
                    setTimeout(updateCounter, 50);
                } else {
                    counter.innerText = target.toLocaleString();
                }
            };
            
            updateCounter();
        });
    }
    
    // Atualização automática de dados a cada 5 minutos
    setInterval(function() {
        // Você poderia adicionar aqui uma chamada AJAX para atualizar os dados
        console.log('Atualização automática de dados');
    }, 300000); // 5 minutos
});

/**
 * Função para mostrar loading durante requisições AJAX
 */
function showLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.add('chart-loading');
    }
}

/**
 * Função para esconder loading após requisições AJAX
 */
function hideLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.remove('chart-loading');
    }
}

/**
 * Utilidade para formatação de data
 */
function formatDate(dateString) {
    const options = { year: 'numeric', month: '2-digit', day: '2-digit' };
    return new Date(dateString).toLocaleDateString('pt-BR', options);
}

/**
 * Exemplo de como fazer uma requisição AJAX para atualizar os dados
 */
function fetchDashboardData() {
    // Mostrar loading
    showLoading('documentsByCategory');
    showLoading('upcomingExpirations');
    
    // Exemplo de requisição usando fetch API
    fetch('/api/dashboard/stats')
        .then(response => response.json())
        .then(data => {
            // Atualizar os números nos cards
            document.querySelector('.card:nth-child(1) h2').innerText = data.totalCompanies.toLocaleString();
            document.querySelector('.card:nth-child(2) h2').innerText = data.totalDocuments.toLocaleString();
            document.querySelector('.card:nth-child(3) h2').innerText = data.expiringCertificates.toLocaleString();
            document.querySelector('.card:nth-child(4) h2').innerText = data.pendingTaxes.toLocaleString();
            
            // Atualizar gráficos com os novos dados
            // ...
            
            // Esconder loading
            hideLoading('documentsByCategory');
            hideLoading('upcomingExpirations');
        })
        .catch(error => {
            console.error('Erro ao buscar dados do dashboard:', error);
            hideLoading('documentsByCategory');
            hideLoading('upcomingExpirations');
        });
}