<?php
/**
 * Processamento do formulário de login
 * 
 * @author Thiago Calil Assad
 * @created <?= date('Y-m-d') ?>
 */

// Defina o caminho base
define('BASE_PATH', realpath(dirname(__FILE__) . '/../../..'));

// Inclua os arquivos necessários
require_once BASE_PATH . '/app/config/constants.php';
require_once BASE_PATH . '/app/config/database.php';
require_once BASE_PATH . '/app/config/error_handler.php';
require_once BASE_PATH . '/app/helpers/SessionManager.php';

// Incluir explicitamente a classe Auth
require_once BASE_PATH . '/app/includes/auth/Auth.php';

// Inicializa o tratamento de erros
ErrorHandler::init(false); // false = modo de desenvolvimento

// Inicializa a sessão
SessionManager::init();

// Verifica se já está logado
if (SessionManager::isLoggedIn()) {
    header('Location: ' . BASE_URL . '/public/index.php');
    exit;
}

// Verificar autenticação por cookie "lembrar-me"
if (Auth::checkRememberToken()) {
    header('Location: ' . BASE_URL . '/public/index.php');
    exit;
}

// Verifica se é uma submissão de formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar campos
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) && $_POST['remember'] === 'on';
    
    if (empty($username) || empty($password)) {
        $_SESSION['error_message'] = 'Por favor, preencha todos os campos.';
        header('Location: ' . BASE_URL . '/public/login.php');
        exit;
    }
    
    // Tenta fazer login
    $result = Auth::login($username, $password, $remember);
    
    if ($result['success']) {
        // Verifica se há aviso de sessão múltipla
        if (isset($result['warning']) && $result['warning'] === 'multiple_session') {
            $_SESSION['multiple_session'] = true;
            $_SESSION['other_session_id'] = $result['session_id'];
        }
        
        // Redireciona para a página solicitada ou para a página inicial
        $redirect = $_SESSION['redirect_after_login'] ?? BASE_URL . '/public/index.php';
        unset($_SESSION['redirect_after_login']);
        
        header('Location: ' . $redirect);
        exit;
    } else {
        // Define mensagem de erro
        $_SESSION['error_message'] = $result['message'];
        header('Location: ' . BASE_URL . '/public/login.php');
        exit;
    }
}

// Se chegar aqui, redireciona para a página de login
header('Location: ' . BASE_URL . '/public/login.php');
exit;