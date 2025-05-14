<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Página de recuperação de senha
 * 
 * @author Thiago Calil Assad
 * @created <?= date('Y-m-d') ?>
 */

// Define caminho base
define('BASE_PATH', realpath(dirname(__FILE__) . '/..'));

// Inclua inicialização básica sem verificação de autenticação
require_once BASE_PATH . '/app/config/constants.php';
require_once BASE_PATH . '/app/config/database.php';
require_once BASE_PATH . '/app/config/error_handler.php';
require_once BASE_PATH . '/app/helpers/SessionManager.php';
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

// Gera token CSRF
if (!isset($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

// Verifica se há um token de recuperação
$token = $_GET['token'] ?? '';
$validToken = false;
$tokenInfo = [];

if (!empty($token)) {
    $result = Auth::verifyPasswordResetToken($token);
    $validToken = $result['success'];
    if ($validToken) {
        $tokenInfo = $result['user'];
    }
}

// Verifica se o logo existe, senão usa um padrão
$logoPath = file_exists(BASE_PATH . '/assets/images/logo.png') ? 
    BASE_URL . '/assets/images/logo.png' : 
    'https://via.placeholder.com/150x50?text=Logo';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperação de Senha - <?= SYSTEM_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/login.css">
    <?php if (file_exists(BASE_PATH . '/assets/images/favicon.ico')): ?>
    <link rel="shortcut icon" href="<?= BASE_URL ?>/assets/images/favicon.ico" type="image/x-icon">
    <?php endif; ?>
</head>
<body>
    <div class="container login-container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-lg">
                    <div class="row g-0">
                        <!-- Lado esquerdo - Branding e mensagem -->
                        <div class="col-md-5 card-left text-white">
                            <div class="p-4">
                                <img src="<?= $logoPath ?>" alt="Logo <?= SYSTEM_NAME ?>" class="system-logo mb-4">
                                <h1><?= SYSTEM_NAME ?></h1>
                                <p class="mb-4"><?= $validToken ? 'Defina uma nova senha segura para sua conta.' : 'Esqueceu sua senha? Nós ajudamos você a recuperar o acesso à sua conta.' ?></p>
                                
                                <p class="mb-0">A recuperação de senha é simples e segura. Basta seguir as instruções ao lado.</p>
                            </div>
                        </div>
                        
                        <!-- Lado direito - Formulário de recuperação -->
                        <div class="col-md-7 card-right">
                            <div class="login-form">
                                <div class="text-center mb-4">
                                    <h4 class="mb-3"><?= $validToken ? 'Redefinir Senha' : 'Recuperação de Senha' ?></h4>
                                </div>
                                
                                <?php if (isset($_SESSION['error_message'])): ?>
                                    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                        <?= $_SESSION['error_message'] ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                                    </div>
                                    <?php unset($_SESSION['error_message']); ?>
                                <?php endif; ?>
                                
                                <?php if (isset($_SESSION['success_message'])): ?>
                                    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                                        <i class="bi bi-check-circle-fill me-2"></i>
                                        <?= $_SESSION['success_message'] ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                                    </div>
                                    <?php unset($_SESSION['success_message']); ?>
                                <?php endif; ?>

                                <!-- Adicione este código para mensagens de depuração -->
                                <?php if (isset($_SESSION['debug_info']) && (isset($_GET['debug']) || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'))): ?>
                                    <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
                                        <i class="bi bi-info-circle-fill me-2"></i>
                                        <strong>Informação de Depuração:</strong> <?= $_SESSION['debug_info'] ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                                    </div>
                                    <?php unset($_SESSION['debug_info']); ?>
                                <?php endif; ?>

                                <!-- Adicione este código para exibir o status do arquivo de log, visível apenas para admin ou com parâmetro debug -->
                                <?php if (isset($_GET['debug']) || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')): 
                                    $logFile = BASE_PATH . '/app/logs/recovery_email.log';
                                    $logStatus = file_exists($logFile) ? 'Arquivo de log encontrado: ' . $logFile : 'Arquivo de log não encontrado';
                                    $logContent = file_exists($logFile) ? nl2br(htmlspecialchars(file_get_contents($logFile))) : 'Nenhum log disponível';
                                ?>
                                    <div class="card mb-4">
                                        <div class="card-header bg-light">
                                            <h5 class="mb-0">Informações de Depuração</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="alert alert-info">
                                                <p><strong>Status do Log:</strong> <?= $logStatus ?></p>
                                                <p><strong>Último acesso:</strong> <?= date('d/m/Y H:i:s') ?></p>
                                            </div>
                                            
                                            <h6>Conteúdo do Log:</h6>
                                            <pre style="max-height: 200px; overflow-y: auto; background-color: #f8f9fa; padding: 10px; border-radius: 5px;"><?= $logContent ?></pre>
                                            
                                            <h6 class="mt-3">Configurações de E-mail:</h6>
                                            <ul>
                                                <li><strong>MAIL_HOST/SMTP_HOST:</strong> 
                                                    <?= defined('MAIL_HOST') ? MAIL_HOST : (defined('SMTP_HOST') ? SMTP_HOST : 'Não definido') ?>
                                                </li>
                                                <li><strong>MAIL_PORT/SMTP_PORT:</strong> 
                                                    <?= defined('MAIL_PORT') ? MAIL_PORT : (defined('SMTP_PORT') ? SMTP_PORT : 'Não definido') ?>
                                                </li>
                                                <li><strong>MAIL_ENCRYPTION/SMTP_SECURE:</strong> 
                                                    <?= defined('MAIL_ENCRYPTION') ? MAIL_ENCRYPTION : (defined('SMTP_SECURE') ? SMTP_SECURE : 'Não definido') ?>
                                                </li>
                                                <li><strong>MAIL_FROM_ADDRESS/EMAIL_FROM_ADDRESS:</strong> 
                                                    <?= defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : (defined('EMAIL_FROM_ADDRESS') ? EMAIL_FROM_ADDRESS : 'Não definido') ?>
                                                </li>
                                            </ul>
                                            
                                            <div class="d-flex gap-2 mt-3">
                                                <a href="<?= BASE_URL ?>/public/recovery.php" class="btn btn-sm btn-secondary">Esconder Depuração</a>
                                                <a href="<?= BASE_URL ?>/test_email.php" class="btn btn-sm btn-primary">Testar Envio de E-mail</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($validToken): ?>
                                    <!-- Formulário de redefinição de senha -->
                                    <form action="<?= BASE_URL ?>/app/controllers/auth_controller.php?action=reset_password" method="post" id="resetPasswordForm">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['token'] ?>">
                                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                                        
                                        <div class="mb-4">
                                            <p class="text-muted">Por favor, digite uma nova senha para sua conta associada ao e-mail <strong><?= htmlspecialchars($tokenInfo['email']) ?></strong>.</p>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="password" class="form-label">Nova Senha</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="password" name="password" 
                                                       required pattern=".{<?= PASSWORD_MIN_LENGTH ?>,<?= PASSWORD_MAX_LENGTH ?>}" 
                                                       placeholder="Digite sua nova senha"
                                                       title="A senha deve ter entre <?= PASSWORD_MIN_LENGTH ?> e <?= PASSWORD_MAX_LENGTH ?> caracteres">
                                                <button class="input-group-text toggle-password" type="button" id="togglePassword" title="Mostrar senha">
                                                    <i class="bi bi-eye-fill input-icon"></i>
                                                </button>
                                            </div>
                                            <div class="form-text">A senha deve ter entre <?= PASSWORD_MIN_LENGTH ?> e <?= PASSWORD_MAX_LENGTH ?> caracteres.</div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="confirm_password" class="form-label">Confirmar Senha</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                                       required placeholder="Confirme sua nova senha">
                                                <button class="input-group-text toggle-password" type="button" id="toggleConfirmPassword" title="Mostrar senha">
                                                    <i class="bi bi-eye-fill input-icon"></i>
                                                </button>
                                            </div>
                                            <div class="invalid-feedback" id="passwordMatchFeedback">
                                                As senhas não coincidem.
                                            </div>
                                        </div>
                                        
                                        <div class="d-grid gap-2 mb-4">
                                            <button type="submit" class="btn btn-login btn-lg" id="submitButton">
                                                <i class="bi bi-check-circle me-2"></i>Redefinir Senha
                                            </button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <!-- Formulário de solicitação de recuperação -->
                                    <form action="<?= BASE_URL ?>/app/controllers/auth_controller.php?action=recovery" method="post">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['token'] ?>">
                                        
                                        <div class="mb-4">
                                            <p class="text-muted">Digite seu e-mail cadastrado para receber um link de redefinição de senha.</p>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="email" class="form-label">E-mail</label>
                                            <div class="input-group">
                                                <input type="email" class="form-control" id="email" name="email" 
                                                       required placeholder="Digite seu e-mail">
                                                <span class="input-group-text">
                                                    <i class="bi bi-envelope-fill input-icon"></i>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="d-grid gap-2 mb-4">
                                            <button type="submit" class="btn btn-login btn-lg">
                                                <i class="bi bi-send me-2"></i>Enviar Link de Recuperação
                                            </button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                                
                                <div class="text-center">
                                    <a href="<?= BASE_URL ?>/public/login.php" class="forgot-password">
                                        <i class="bi bi-arrow-left-circle-fill me-1"></i>Voltar para o Login
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-center">
                        <small>&copy; <?= date('Y') ?> - <?= SYSTEM_NAME ?> v<?= SYSTEM_VERSION ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle de visibilidade da senha
        const togglePasswordVisibility = (inputId, buttonId) => {
            document.getElementById(buttonId).addEventListener('click', function() {
                const passwordInput = document.getElementById(inputId);
                const icon = this.querySelector('i');
                
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('bi-eye-fill');
                    icon.classList.add('bi-eye-slash-fill');
                    this.setAttribute('title', 'Esconder senha');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('bi-eye-slash-fill');
                    icon.classList.add('bi-eye-fill');
                    this.setAttribute('title', 'Mostrar senha');
                }
            });
        };
        
        <?php if ($validToken): ?>
        // Validação de senhas para formulário de reset
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('resetPasswordForm');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            const feedback = document.getElementById('passwordMatchFeedback');
            const submitButton = document.getElementById('submitButton');
            
            // Verificar correspondência das senhas
            const checkPasswordMatch = () => {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.classList.add('is-invalid');
                    feedback.style.display = 'block';
                    submitButton.disabled = true;
                    return false;
                } else {
                    confirmPassword.classList.remove('is-invalid');
                    feedback.style.display = 'none';
                    submitButton.disabled = false;
                    return true;
                }
            };
            
            // Adicionar validação nos eventos de input
            password.addEventListener('input', checkPasswordMatch);
            confirmPassword.addEventListener('input', checkPasswordMatch);
            
            // Validar no envio do formulário
            form.addEventListener('submit', function(event) {
                if (!checkPasswordMatch()) {
                    event.preventDefault();
                }
            });
            
            // Inicializar toggles de senha
            togglePasswordVisibility('password', 'togglePassword');
            togglePasswordVisibility('confirm_password', 'toggleConfirmPassword');
        });
        <?php endif; ?>
        
        // Auto-dismiss de alertas após 5 segundos
        window.addEventListener('DOMContentLoaded', () => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
        
        // Adicionar efeito aos campos de formulário
        const formControls = document.querySelectorAll('.form-control');
        formControls.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
    </script>
</body>
</html>