<?php
/**
 * Menu padrão para usuários sem tipo específico
 * 
 * @author Thiago Calil Assad
 * @created <?= date('Y-m-d') ?>
 */

// Previne acesso direto ao arquivo
if (!defined('BASE_PATH')) {
    http_response_code(403);
    exit('Acesso proibido');
}

// Define o menu lateral padrão (mínimo)
$sidebarMenu = [
    [
        'title' => 'Dashboard',
        'icon' => 'bi-speedometer2',
        'url' => BASE_URL . '/public/index.php',
        'active' => false
    ],
    [
        'title' => 'Documentos',
        'icon' => 'bi-file-earmark-text',
        'url' => BASE_URL . '/modules/documentos/index.php',
        'active' => false
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