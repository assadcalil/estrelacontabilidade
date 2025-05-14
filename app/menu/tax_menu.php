<?php
/**
 * Menu para usuários do setor fiscal
 * 
 * @author Thiago Calil Assad
 * @created <?= date('Y-m-d') ?>
 */

// Previne acesso direto ao arquivo
if (!defined('BASE_PATH')) {
    http_response_code(403);
    exit('Acesso proibido');
}

// Verifica se o usuário tem permissão para ver este menu
if (!Auth::checkPermission(Auth::TAX)) {
    return;
}

// Define o menu lateral para usuários do setor fiscal
$sidebarMenu = [
    [
        'title' => 'Dashboard',
        'icon' => 'bi-speedometer2',
        'url' => BASE_URL . '/public/index.php',
        'active' => false
    ],
    [
        'title' => 'Empresas',
        'icon' => 'bi-building',
        'url' => BASE_URL . '/modules/empresas/index.php',
        'active' => false
    ],
    [
        'title' => 'Impostos',
        'icon' => 'bi-cash-stack',
        'submenu' => [
            [
                'title' => 'Todos os Impostos',
                'icon' => 'bi-list-check',
                'url' => BASE_URL . '/modules/impostos/index.php',
                'active' => false
            ],
            [
                'title' => 'Boletos',
                'icon' => 'bi-receipt',
                'url' => BASE_URL . '/modules/impostos/boletos/index.php',
                'active' => false
            ]
        ]
    ],
    [
        'title' => 'Certificados',
        'icon' => 'bi-patch-check',
        'url' => BASE_URL . '/modules/certificados/index.php',
        'active' => false
    ],
    [
        'title' => 'Documentos',
        'icon' => 'bi-file-earmark-text',
        'url' => BASE_URL . '/modules/documentos/index.php',
        'active' => false
    ],
    [
        'title' => 'Relatórios',
        'icon' => 'bi-bar-chart',
        'submenu' => [
            [
                'title' => 'Impostos',
                'icon' => 'bi-cash',
                'url' => BASE_URL . '/modules/relatorios/impostos.php',
                'active' => false
            ],
            [
                'title' => 'Certificados',
                'icon' => 'bi-patch-check',
                'url' => BASE_URL . '/modules/relatorios/certificados.php',
                'active' => false
            ]
        ]
    ]
];

// Marca o item ativo com base na URL atual
$currentUrl = $_SERVER['PHP_SELF'];
foreach ($sidebarMenu as &$item) {
    if (isset($item['url']) && strpos($currentUrl, basename($item['url'])) !== false) {
        $item['active'] = true;
    } elseif (isset($item['submenu'])) {
        foreach ($item['submenu'] as &$subitem) {
            if (strpos($currentUrl, basename($subitem['url'])) !== false) {
                $subitem['active'] = true;
                $item['active'] = true;
            }
        }
    }
}