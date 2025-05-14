<?php
/**
 * Controlador para gerenciamento de sessões
 * 
 * @author Thiago Calil Assad
 * @created <?= date('Y-m-d') ?>
 */

// Define caminho base
define('BASE_PATH', realpath(dirname(__FILE__) . '/../..'));

// Inclui arquivos necessários
require_once BASE_PATH . '/app/config/constants.php';
require_once BASE_PATH . '/app/config/database.php';
require_once BASE_PATH . '/app/config/error_handler.php';
require_once BASE_PATH . '/app/helpers/SessionManager.php';

// Inicializa o tratamento de erros
ErrorHandler::init(false); // false = modo de desenvolvimento

// Inicializa a sessão
SessionManager::init();

// Verifica se o usuário está autenticado
if (!SessionManager::isLoggedIn() && $_REQUEST['action'] !== 'cleanup') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Usuário não autenticado']);
    exit;
}

// Processa as ações solicitadas
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'check_session':
        checkSession();
        break;
        
    case 'update_activity':
        updateActivity();
        break;
        
    case 'force_single_session':
        forceSingleSession();
        break;
        
    case 'end_session':
        endSession();
        break;
        
    case 'cleanup':
        cleanupSessions();
        break;
        
    default:
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Ação inválida']);
        break;
}

/**
 * Verifica o status da sessão atual
 */
function checkSession() {
    $userId = SessionManager::getUserId();
    
    // Verifica se há múltiplas sessões para o mesmo usuário
    $result = SessionManager::checkMultipleSession($userId);
    
    // Se o resultado for uma string, é o ID de uma sessão existente
    if (is_string($result)) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'warning',
            'message' => 'multiple_session',
            'session_id' => $result
        ]);
        exit;
    }
    
    // Atualiza a atividade da sessão atual
    SessionManager::updateActivity();
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Sessão válida']);
}

/**
 * Atualiza o timestamp de atividade da sessão atual
 */
function updateActivity() {
    $result = SessionManager::updateActivity();
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $result ? 'success' : 'error',
        'message' => $result ? 'Atividade atualizada' : 'Erro ao atualizar atividade'
    ]);
}

/**
 * Força uma única sessão para o usuário, terminando todas as outras
 */
function forceSingleSession() {
    // Verifica o token CSRF
    if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Token inválido']);
        exit;
    }
    
    $userId = SessionManager::getUserId();
    $result = SessionManager::endOtherSessions($userId);
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $result ? 'success' : 'error',
        'message' => $result ? 'Outras sessões encerradas' : 'Erro ao encerrar outras sessões'
    ]);
}

/**
 * Encerra uma sessão específica
 */
function endSession() {
    // Verifica se o usuário é admin ou está encerrando sua própria sessão
    if (!SessionManager::hasUserType(USER_ADMIN) && 
        (!isset($_POST['session_id']) || $_POST['session_id'] === session_id())) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Permissão negada']);
        exit;
    }
    
    // Verifica o token CSRF
    if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Token inválido']);
        exit;
    }
    
    $sessionId = $_POST['session_id'] ?? session_id();
    $result = SessionManager::endSession($sessionId);
    
    // Se a sessão encerrada for a atual, destrói a sessão
    if ($sessionId === session_id()) {
        SessionManager::destroy();
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $result ? 'success' : 'error',
        'message' => $result ? 'Sessão encerrada' : 'Erro ao encerrar sessão'
    ]);
}

/**
 * Limpa sessões antigas e inativas do banco de dados
 * Esta função pode ser chamada via CRON para manutenção
 */
function cleanupSessions() {
    // Verifica token de acesso para CRON
    $cronToken = $_GET['cron_token'] ?? '';
    
    // Se não for um usuário administrador, verifica o token CRON
    if (!SessionManager::hasUserType(USER_ADMIN) && $cronToken !== CRON_TOKEN) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado']);
        exit;
    }
    
    $olderThan = isset($_GET['days']) ? (int)$_GET['days'] * 86400 : 2592000; // Padrão: 30 dias
    $result = SessionManager::cleanupSessions($olderThan);
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $result ? 'success' : 'error',
        'message' => $result ? 'Sessões antigas removidas' : 'Erro ao limpar sessões'
    ]);
}