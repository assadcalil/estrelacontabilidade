<?php
/**
 * Página de login do sistema
 * 
 * @author Thiago Calil Assad
 * @created <?= date('Y-m-d') ?>
 */

// Define caminho base
define('BASE_PATH', realpath(dirname(__FILE__) . '/..'));

// Configurações de exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclua inicialização básica sem verificação de autenticação
require_once BASE_PATH . '/app/config/constants.php';
require_once BASE_PATH . '/app/config/database.php';
require_once BASE_PATH . '/app/config/error_handler.php';
require_once BASE_PATH . '/app/helpers/SessionManager.php';

// Inicializa o tratamento de erros
ErrorHandler::init(false); // false = modo de desenvolvimento

// Inicializa a sessão
SessionManager::init();

// Inclui a classe Auth se o arquivo existir
if (file_exists(BASE_PATH . '/app/includes/auth/Auth.php')) {
    require_once BASE_PATH . '/app/includes/auth/Auth.php';
    
    // Verifica se já está logado
    if (SessionManager::isLoggedIn()) {
        header('Location: ' . BASE_URL . '/public/index.php');
        exit;
    }

    // Verificar autenticação por cookie "lembrar-me"
    if (method_exists('Auth', 'checkRememberToken') && Auth::checkRememberToken()) {
        header('Location: ' . BASE_URL . '/public/index.php');
        exit;
    }
}

// Armazena URL para redirecionamento após login
if (isset($_GET['redirect'])) {
    $_SESSION['redirect_after_login'] = $_GET['redirect'];
}

// Gera token CSRF
if (!isset($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
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
    <title>Login - <?= SYSTEM_NAME ?></title>
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
                        <div class="col-md-6 card-left text-white">
                            <div class="p-4">
                                <img src="<?= $logoPath ?>" alt="Logo <?= SYSTEM_NAME ?>" class="system-logo mb-4">
                                <h1><?= SYSTEM_NAME ?></h1>
                                <p class="mb-4">Bem-vindo ao nosso sistema. Faça login para acessar todas as funcionalidades e gerenciar seus recursos.</p>
                                
                                <p class="mb-0">Se você não tem uma conta, entre em contato com o administrador do sistema.</p>
                            </div>
                        </div>
                        
                        <!-- Lado direito - Formulário de login -->
                        <div class="col-md-6 card-right">
                            <div class="login-form">

                                <div class="text-center mb-4">
                                    <h4 class="mb-3">Acesso ao Sistema</h4>
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
                                
                                <form action="<?= BASE_URL ?>/app/includes/auth/login.php" method="post">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['token'] ?>">
                                    
                                    <div class="mb-4">
                                        <label for="username" class="form-label">Usuário ou E-mail</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="username" name="username" placeholder="Digite seu usuário ou e-mail" required autofocus>
                                            <span class="input-group-text">
                                                <i class="bi bi-person-fill input-icon"></i>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="password" class="form-label">Senha</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="password" name="password" placeholder="Digite sua senha" required>
                                            <button class="input-group-text toggle-password" type="button" id="togglePassword" title="Mostrar senha">
                                                <i class="bi bi-eye-fill input-icon"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4 form-check">
                                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                        <label class="form-check-label" for="remember">Lembrar-me</label>
                                    </div>
                                    
                                    <div class="d-grid gap-2 mb-4">
                                        <button type="submit" class="btn btn-login btn-lg">
                                            <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
                                        </button>
                                    </div>
                                    
                                    <div class="text-center">
                                        <a href="<?= BASE_URL ?>/public/recovery.php" class="forgot-password">
                                            <i class="bi bi-question-circle-fill me-1"></i>Esqueceu sua senha?
                                        </a>
                                    </div>
                                </form>
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
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
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