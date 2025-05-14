<?php
/**
 * Arquivo de inicialização do sistema GED
 * 
 * @author Thiago Calil Assad
 * @created <?= date('Y-m-d') ?>
 */

// Define caminho base
if (!defined('BASE_PATH')) {
    define('BASE_PATH', realpath(dirname(__FILE__) . '/..'));
}

// Configurações de exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configura o fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Inclui arquivos de configuração essenciais
require_once BASE_PATH . '/app/config/constants.php';
require_once BASE_PATH . '/app/config/database.php';
require_once BASE_PATH . '/app/config/error_handler.php';

// Inclui utilitários e classes auxiliares
require_once BASE_PATH . '/app/helpers/SessionManager.php';

// Inicia o tratamento de erros
$productionMode = false; // Alterar para true em produção
ErrorHandler::init($productionMode);

// Inicia o gerenciamento de sessão
SessionManager::init();

// Inclui a classe Auth se o arquivo existir
if (file_exists(BASE_PATH . '/app/includes/auth/Auth.php')) {
    require_once BASE_PATH . '/app/includes/auth/Auth.php';
}

// Inclui modais se existirem
if (file_exists(BASE_PATH . '/app/modals/error_modal.php')) {
    require_once BASE_PATH . '/app/modals/error_modal.php';
}
if (file_exists(BASE_PATH . '/app/modals/success_modal.php')) {
    require_once BASE_PATH . '/app/modals/success_modal.php';
}
if (file_exists(BASE_PATH . '/app/modals/confirmation_modal.php')) {
    require_once BASE_PATH . '/app/modals/confirmation_modal.php';
}

// Gera um token CSRF para formulários
if (!isset($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

// Verifica autenticação através de cookie "lembrar-me"
if (!SessionManager::isLoggedIn() && 
    class_exists('Auth') && 
    method_exists('Auth', 'checkRememberToken')) {
    Auth::checkRememberToken();
}

// Função auxiliar para verificar permissões
function checkPermission($requiredTypes) {
    if (!class_exists('Auth') || !method_exists('Auth', 'checkPermission')) {
        return false;
    }
    
    return Auth::checkPermission($requiredTypes);
}

// Função auxiliar para redirecionar com mensagem
function redirectWithMessage($url, $message, $type = 'error') {
    if ($type === 'error') {
        $_SESSION['error_message'] = $message;
    } else {
        $_SESSION['success_message'] = $message;
    }
    
    header('Location: ' . $url);
    exit;
}