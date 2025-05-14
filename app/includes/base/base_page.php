<?php
/**
 * Base para todas as páginas do sistema
 * 
 * @author Thiago Calil Assad
 * @created <?= date('Y-m-d') ?>
 */

// Define caminho base
if (!defined('BASE_PATH')) {
    define('BASE_PATH', realpath(dirname(__FILE__) . '/../../..'));
}

// Inclui o arquivo de inicialização
require_once BASE_PATH . '/app/init.php';

// Verifica autenticação
if (!SessionManager::isLoggedIn()) {
    // Salvar URL atual para redirecionamento após login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    // Redirecionar para página de login
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

// Verificar permissões se necessário
if (isset($requiredPermissions) && !Auth::checkPermission($requiredPermissions)) {
    // Redirecionar ou mostrar erro de permissão
    $_SESSION['error_message'] = 'Você não tem permissão para acessar este recurso.';
    header('Location: ' . BASE_URL . '/public/index.php');
    exit;
}

// Verificar acesso à empresa se necessário
if (isset($companyId) && !Auth::checkCompanyAccess($companyId)) {
    // Redirecionar ou mostrar erro de acesso
    $_SESSION['error_message'] = 'Você não tem acesso a esta empresa.';
    header('Location: ' . BASE_URL . '/public/index.php');
    exit;
}

// Incluir menu específico com base no tipo de usuário
switch ($_SESSION['user_type']) {
    case Auth::ADMIN:
        include_once BASE_PATH . '/app/menu/admin_menu.php';
        break;
        
    case Auth::EDITOR:
        include_once BASE_PATH . '/app/menu/editor_menu.php';
        break;
        
    case Auth::TAX:
        include_once BASE_PATH . '/app/menu/tax_menu.php';
        break;
        
    case Auth::EMPLOYEE:
        include_once BASE_PATH . '/app/menu/employee_menu.php';
        break;
        
    case Auth::FINANCIAL:
        include_once BASE_PATH . '/app/menu/financial_menu.php';
        break;
        
    case Auth::CLIENT:
        include_once BASE_PATH . '/app/menu/client_menu.php';
        break;
        
    default:
        include_once BASE_PATH . '/app/menu/default_menu.php';
}

// Carregar o cabeçalho da página
include_once BASE_PATH . '/app/includes/base/header.php';

// Em seguida, o conteúdo específico da página será incluído
// ...

// Ao final, deve-se incluir o rodapé
// include_once BASE_PATH . '/app/includes/base/footer.php';