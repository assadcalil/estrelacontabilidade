<?php
/**
 * Processamento de recuperação de senha
 * 
 * @author Thiago Calil Assad
 * @created <?= date('Y-m-d') ?>
 */

// Defina o caminho base
define('BASE_PATH', realpath(dirname(__FILE__) . '/../../..'));

// Inclua os arquivos necessários
require_once BASE_PATH . '/app/init.php';

// Verifica se já está logado
if (SessionManager::isLoggedIn()) {
    header('Location: ' . BASE_URL . '/public/index.php');
    exit;
}

// Processar solicitação de recuperação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Solicitação de token de recuperação
    if ($action === 'request_token') {
        $email = $_POST['email'] ?? '';
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error_message'] = 'Por favor, forneça um e-mail válido.';
            header('Location: ' . BASE_URL . '/public/recovery.php');
            exit;
        }
        
        // Gera o token de recuperação
        $result = Auth::generatePasswordResetToken($email);
        
        // Se o token foi gerado com sucesso, enviar por e-mail
        if ($result['success']) {
            // Obter o e-mail formatado
            $emailData = [
                'name' => $result['user']['full_name'],
                'token_url' => BASE_URL . '/public/recovery.php?token=' . $result['token'],
                'expiry_hours' => TOKEN_LIFETIME / 3600 // Converter para horas
            ];
            
            $emailTemplate = EmailConfig::getTemplate('password_recovery', $emailData);
            $emailSubject = EmailConfig::getSubject('password_recovery');
            
            // Enviar e-mail usando a classe Mailer
            require_once BASE_PATH . '/app/includes/utils/Mailer.php';
            $mailer = new Mailer();
            $mailSent = $mailer->send($result['user']['email'], $emailSubject, $emailTemplate);
            
            if ($mailSent) {
                $_SESSION['success_message'] = 'Um link de recuperação foi enviado para o seu e-mail.';
            } else {
                // Mesmo que o e-mail falhe, não informamos ao usuário para evitar enumeração
                $_SESSION['success_message'] = 'Se o e-mail existir em nossa base, um link de recuperação será enviado.';
                ErrorHandler::logError('RECOVERY', "Falha ao enviar e-mail de recuperação para: {$email}");
            }
        } else {
            // Para evitar enumeração de e-mails, sempre mostramos a mesma mensagem
            $_SESSION['success_message'] = 'Se o e-mail existir em nossa base, um link de recuperação será enviado.';
        }
        
        header('Location: ' . BASE_URL . '/public/recovery.php');
        exit;
    }
    
    // Resetar senha com token
    if ($action === 'reset_password') {
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
}

// Verificar token válido
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    $result = Auth::verifyPasswordResetToken($token);
    
    if (!$result['success']) {
        $_SESSION['error_message'] = $result['message'];
        header('Location: ' . BASE_URL . '/public/recovery.php');
        exit;
    }
    
    // Continuar para a página de recuperação com o token (não redirecionar)
} else {
    // Exibir o formulário de solicitação de token (não redirecionar)
}

// Renderizar a página de recuperação
include BASE_PATH . '/public/recovery.php';