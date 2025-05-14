<?php
/**
 * Menu para usuários administradores
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
if (!Auth::checkPermission(Auth::ADMIN)) {
    return;
}

// Define o menu lateral para administradores
$sidebarMenu = [
    [
        'title' => 'Dashboard',
        'icon' => 'bi-speedometer2',
        'url' => BASE_URL . '/public/index.php',
        'active' => false
    ],
    [
        'title' => 'Usuários',
        'icon' => 'bi-gear',
        'submenu' => [
            [
                'title' => 'Cadastro de Usuários',
                'icon' => 'bi-people',
                'url' => BASE_URL . '/modules/usuarios/cadastro.php',
                'active' => false
            ],
            [
                'title' => 'Listagem de Usuarios',
                'icon' => 'bi-building',
                'url' => BASE_URL . '/modules/empresas/listagem.php',
                'active' => false
            ],
        ]
    ],
    [
        'title' => 'Administração',
        'icon' => 'bi-gear',
        'submenu' => [
            [
                'title' => 'Usuários',
                'icon' => 'bi-people',
                'url' => BASE_URL . '/modules/usuarios/index.php',
                'active' => false
            ],
            [
                'title' => 'Empresas',
                'icon' => 'bi-building',
                'url' => BASE_URL . '/modules/empresas/index.php',
                'active' => false
            ],
            [
                'title' => 'Logs do Sistema',
                'icon' => 'bi-journal-text',
                'url' => BASE_URL . '/modules/system/logs.php',
                'active' => false
            ]
        ]
    ],
    [
        'title' => 'Documentos',
        'icon' => 'bi-file-earmark-text',
        'url' => BASE_URL . '/modules/documentos/index.php',
        'active' => false
    ],
    [
        'title' => 'Certificados',
        'icon' => 'bi-patch-check',
        'url' => BASE_URL . '/modules/certificados/index.php',
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
        'title' => 'Relatórios',
        'icon' => 'bi-bar-chart',
        'submenu' => [
            [
                'title' => 'Documentos',
                'icon' => 'bi-file-text',
                'url' => BASE_URL . '/modules/relatorios/documentos.php',
                'active' => false
            ],
            [
                'title' => 'Certificados',
                'icon' => 'bi-patch-check',
                'url' => BASE_URL . '/modules/relatorios/certificados.php',
                'active' => false
            ],
            [
                'title' => 'Impostos',
                'icon' => 'bi-cash',
                'url' => BASE_URL . '/modules/relatorios/impostos.php',
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