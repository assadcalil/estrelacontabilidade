<?php
/**
 * Controlador para ações de autenticação e autorização
 * 
 * @author Thiago Calil Assad
 * @created <?= date('Y-m-d') ?>
 */

// Define caminho base
define('BASE_PATH', realpath(dirname(__FILE__) . '/../..'));

// Inclui arquivos necessários
require_once BASE_PATH . '/app/init.php';

// Determinar a ação a ser executada
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin();
        break;
        
    case 'logout':
        handleLogout();
        break;
    
        case 'recovery':
            handleRecovery();
            break;
            
        case 'reset_password':
            handleResetPassword();
            break;
            
        case 'change_password':
            handleChangePassword();
            break;
            
        case 'verify_token':
            handleVerifyToken();
            break;
            
        case 'switch_company':
            handleSwitchCompany();
            break;
            
        default:
            // Se nenhuma ação válida foi especificada, redireciona para a página inicial
            header('Location: ' . BASE_URL . '/public/index.php');
            exit;
    }
    
    /**
     * Processa a tentativa de login
     */
    function handleLogin() {
        // Verifica se já está logado
        if (SessionManager::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/public/index.php');
            exit;
        }
        
        // Verifica se é uma submissão de formulário
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/public/login.php');
            exit;
        }
        
        // Validar campos
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']) && $_POST['remember'] === 'on';
        
        if (empty($username) || empty($password)) {
            $_SESSION['error_message'] = 'Por favor, preencha todos os campos.';
            header('Location: ' . BASE_URL . '/public/login.php');
            exit;
        }
        
        // Proteção CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['token']) {
            $_SESSION['error_message'] = 'Erro de validação do formulário. Por favor, tente novamente.';
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
    
    /**
     * Processa o logout do usuário
     */
    function handleLogout() {
        Auth::logout();
        
        // Redireciona para a página de login
        header('Location: ' . BASE_URL . '/public/login.php');
        exit;
    }
    
    /**
     * Atualização para função handleRecovery() em auth_controller.php
     * 
     * Esta atualização usa a classe Mailer otimizada para o sistema GED ESTRELA
     * para garantir o envio correto de e-mails de recuperação de senha.
     * 
     * Substitua a função handleRecovery() existente por esta versão.
     */

    function handleRecovery() {
        // Verifica se já está logado
        if (SessionManager::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/public/index.php');
            exit;
        }
        
        // Verifica se é uma submissão de formulário
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/public/recovery.php');
            exit;
        }
        
        // Proteção CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['token']) {
            $_SESSION['error_message'] = 'Erro de validação do formulário. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . '/public/recovery.php');
            exit;
        }
        
        // Validar e-mail
        $email = $_POST['email'] ?? '';
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error_message'] = 'Por favor, forneça um e-mail válido.';
            header('Location: ' . BASE_URL . '/public/recovery.php');
            exit;
        }
        
        // Criar log de depuração
        $logDir = BASE_PATH . '/app/logs';
        $logFile = $logDir . '/recovery_email.log';
        
        // Criar diretório de logs se não existir
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Iniciar registro de log
        $log = "[" . date('Y-m-d H:i:s') . "] Iniciando processo de recuperação para e-mail: $email\n";
        file_put_contents($logFile, $log, FILE_APPEND);
        
        // Gera o token de recuperação
        $result = Auth::generatePasswordResetToken($email);
        
        // Registrar resultado da geração do token
        if ($result['success']) {
            $log = "[" . date('Y-m-d H:i:s') . "] Token gerado com sucesso para o usuário ID: {$result['user']['id']}\n";
            file_put_contents($logFile, $log, FILE_APPEND);
        } else {
            $log = "[" . date('Y-m-d H:i:s') . "] Falha ao gerar token: {$result['message']}\n";
            file_put_contents($logFile, $log, FILE_APPEND);
        }
        
        // Se o token foi gerado com sucesso, enviar por e-mail
        if ($result['success']) {
            // Carregar a classe Mailer otimizada
            require_once BASE_PATH . '/app/classes/Mailer.php';
            
            // Inicializar Mailer com depuração ativada
            $mailer = new Mailer();
            $mailer->enableDebug(true, $logFile);
            
            // Enviar e-mail de recuperação
            $mailSent = $mailer->sendPasswordRecovery(
                $result['user']['email'],
                $result['user']['full_name'],
                $result['token']
            );
            
            // Registrar resultado do envio do e-mail
            if ($mailSent) {
                $log = "[" . date('Y-m-d H:i:s') . "] E-mail enviado com sucesso para: {$email}\n";
                file_put_contents($logFile, $log, FILE_APPEND);
                
                // Registrar sucesso no log do sistema
                if (class_exists('ErrorHandler')) {
                    ErrorHandler::logInfo('RECOVERY', "E-mail de recuperação enviado para: {$email}");
                }
                
                $_SESSION['success_message'] = 'Um link de recuperação foi enviado para o seu e-mail.';
                
                // Adicionar informação de debug ao setar um parâmetro na sessão
                $_SESSION['debug_info'] = "E-mail enviado com sucesso para {$email}. Verifique sua caixa de entrada ou pasta de spam.";
            } else {
                // Obter erro detalhado
                $errorMsg = $mailer->getLastError();
                $log = "[" . date('Y-m-d H:i:s') . "] Falha ao enviar e-mail: {$errorMsg}\n";
                file_put_contents($logFile, $log, FILE_APPEND);
                
                // Registrar erro no log do sistema
                if (class_exists('ErrorHandler')) {
                    ErrorHandler::logError('RECOVERY', "Falha ao enviar e-mail de recuperação para: {$email}. Erro: {$errorMsg}");
                }
                
                // Para segurança contra enumeração, mostramos mensagem genérica para usuários comuns
                $_SESSION['success_message'] = 'Se o e-mail existir em nossa base, um link de recuperação será enviado.';
                
                // Para administradores ou em modo de depuração, mostrar erro detalhado
                $_SESSION['debug_info'] = "Erro ao enviar e-mail: {$errorMsg}";
            }
        } else {
            // Para evitar enumeração de e-mails, sempre mostramos a mesma mensagem
            $_SESSION['success_message'] = 'Se o e-mail existir em nossa base, um link de recuperação será enviado.';
            $_SESSION['debug_info'] = "Usuário com e-mail {$email} não encontrado.";
        }
        
        header('Location: ' . BASE_URL . '/public/recovery.php');
        exit;
    }
    
    /**
     * Processa o reset de senha
     */
    function handleResetPassword() {
        // Verifica se já está logado
        if (SessionManager::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/public/index.php');
            exit;
        }
        
        // Verifica se é uma submissão de formulário
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/public/recovery.php');
            exit;
        }
        
        // Proteção CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['token']) {
            $_SESSION['error_message'] = 'Erro de validação do formulário. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . '/public/recovery.php');
            exit;
        }
        
        // Validar campos
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validar token
        if (empty($token)) {
            $_SESSION['error_message'] = 'Token inválido.';
            header('Location: ' . BASE_URL . '/public/recovery.php');
            exit;
        }
        
        // Validar senhas
        if (empty($password) || empty($confirmPassword)) {
            $_SESSION['error_message'] = 'Por favor, preencha todos os campos.';
            header('Location: ' . BASE_URL . '/public/recovery.php?token=' . $token);
            exit;
        }
        
        if ($password !== $confirmPassword) {
            $_SESSION['error_message'] = 'As senhas não coincidem.';
            header('Location: ' . BASE_URL . '/public/recovery.php?token=' . $token);
            exit;
        }
        
        if (strlen($password) < PASSWORD_MIN_LENGTH || strlen($password) > PASSWORD_MAX_LENGTH) {
            $_SESSION['error_message'] = 'A senha deve ter entre ' . PASSWORD_MIN_LENGTH . ' e ' . PASSWORD_MAX_LENGTH . ' caracteres.';
            header('Location: ' . BASE_URL . '/public/recovery.php?token=' . $token);
            exit;
        }
        
        // Resetar a senha
        $result = Auth::resetPassword($token, $password);
        
        if ($result['success']) {
            $_SESSION['success_message'] = 'Senha redefinida com sucesso. Você já pode fazer login.';
            header('Location: ' . BASE_URL . '/public/login.php');
            exit;
        } else {
            $_SESSION['error_message'] = $result['message'];
            header('Location: ' . BASE_URL . '/public/recovery.php?token=' . $token);
            exit;
        }
    }
    
    /**
     * Processa a alteração de senha do usuário logado
     */
    function handleChangePassword() {
        // Verifica se está logado
        if (!SessionManager::isLoggedIn()) {
            if ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '' === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Usuário não autenticado.'
                ]);
                exit;
            }
            
            header('Location: ' . BASE_URL . '/public/login.php');
            exit;
        }
        
        // Verifica se é uma submissão de formulário
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '' === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Método inválido.'
                ]);
                exit;
            }
            
            header('Location: ' . BASE_URL . '/public/index.php');
            exit;
        }
        
        // Proteção CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['token']) {
            if ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '' === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro de validação do formulário.'
                ]);
                exit;
            }
            
            $_SESSION['error_message'] = 'Erro de validação do formulário. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . '/public/index.php');
            exit;
        }
        
        // Validar campos
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            if ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '' === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Por favor, preencha todos os campos.'
                ]);
                exit;
            }
            
            $_SESSION['error_message'] = 'Por favor, preencha todos os campos.';
            header('Location: ' . $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/public/index.php');
            exit;
        }
        
        if ($newPassword !== $confirmPassword) {
            if ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '' === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'As senhas não coincidem.'
                ]);
                exit;
            }
            
            $_SESSION['error_message'] = 'As senhas não coincidem.';
            header('Location: ' . $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/public/index.php');
            exit;
        }
        
        // Alterar a senha
        $result = Auth::changePassword($currentPassword, $newPassword);
        
        if ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '' === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
        }
        
        if ($result['success']) {
            $_SESSION['success_message'] = 'Senha alterada com sucesso.';
        } else {
            $_SESSION['error_message'] = $result['message'];
        }
        
        header('Location: ' . $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/public/index.php');
        exit;
    }
    
    /**
     * Verifica a validade de um token
     */
    function handleVerifyToken() {
        // Verifica se já está logado
        if (SessionManager::isLoggedIn()) {
            if ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '' === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Usuário já autenticado.'
                ]);
                exit;
            }
            
            header('Location: ' . BASE_URL . '/public/index.php');
            exit;
        }
        
        // Verificar o token
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            if ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '' === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Token inválido.'
                ]);
                exit;
            }
            
            $_SESSION['error_message'] = 'Token inválido.';
            header('Location: ' . BASE_URL . '/public/recovery.php');
            exit;
        }
        
        // Verificar a validade do token
        $result = Auth::verifyPasswordResetToken($token);
        
        if ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '' === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
        }
        
        if (!$result['success']) {
            $_SESSION['error_message'] = $result['message'];
            header('Location: ' . BASE_URL . '/public/recovery.php');
            exit;
        }
        
        // Redireciona para a página de reset com o token
        header('Location: ' . BASE_URL . '/public/recovery.php?token=' . $token);
        exit;
    }
    
    /**
     * Troca a empresa ativa do usuário
     */
    function handleSwitchCompany() {
        // Verifica se está logado
        if (!SessionManager::isLoggedIn()) {
            if ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '' === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Usuário não autenticado.'
                ]);
                exit;
            }
            
            header('Location: ' . BASE_URL . '/public/login.php');
            exit;
        }
        
        // Verifica se é uma submissão de formulário
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '' === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Método inválido.'
                ]);
                exit;
            }
            
            header('Location: ' . BASE_URL . '/public/index.php');
            exit;
        }
        
        // Proteção CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['token']) {
            if ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '' === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro de validação do formulário.'
                ]);
                exit;
            }
            
            $_SESSION['error_message'] = 'Erro de validação do formulário. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . '/public/index.php');
            exit;
        }
        
        // Validar ID da empresa
        $companyId = $_POST['company_id'] ?? '';
        
        if (empty($companyId)) {
            if ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '' === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'ID da empresa inválido.'
                ]);
                exit;
            }
            
            $_SESSION['error_message'] = 'ID da empresa inválido.';
            header('Location: ' . $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/public/index.php');
            exit;
        }
        
        // Verificar acesso à empresa
        $access = Auth::checkCompanyAccess($companyId);
        
        if (!$access) {
            if ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '' === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Você não tem acesso a esta empresa.'
                ]);
                exit;
            }
            
            $_SESSION['error_message'] = 'Você não tem acesso a esta empresa.';
            header('Location: ' . $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/public/index.php');
            exit;
        }
        
        // Obter informações da empresa
        $db = Database::getInstance();
        try {
            $sql = "SELECT id, emp_name, emp_cnpj FROM companies WHERE id = ? AND status = 1";
            $stmt = $db->query($sql, [$companyId]);
            
            if ($stmt->rowCount() === 0) {
                if ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '' === 'XMLHttpRequest') {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Empresa não encontrada ou inativa.'
                    ]);
                    exit;
                }
                
                $_SESSION['error_message'] = 'Empresa não encontrada ou inativa.';
                header('Location: ' . $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/public/index.php');
                exit;
            }
            
            $company = $stmt->fetch();
            
            // Atualiza a empresa na sessão
            $_SESSION['company_id'] = $company['id'];
            $_SESSION['company_name'] = $company['emp_name'];
            $_SESSION['company_cnpj'] = $company['emp_cnpj'];
            
            if ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '' === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Empresa alternada com sucesso.',
                    'company' => [
                        'id' => $company['id'],
                        'name' => $company['emp_name'],
                        'cnpj' => $company['emp_cnpj']
                    ]
                ]);
                exit;
            }
            
            $_SESSION['success_message'] = 'Empresa alternada com sucesso.';
            header('Location: ' . $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/public/index.php');
            exit;
            
        } catch (PDOException $e) {
            ErrorHandler::logError('AUTH', "Erro ao alternar empresa: " . $e->getMessage());
            
            if ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '' === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao processar solicitação. Tente novamente.'
                ]);
                exit;
            }
            
            $_SESSION['error_message'] = 'Erro ao processar solicitação. Tente novamente.';
            header('Location: ' . $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/public/index.php');
            exit;
        }
    }