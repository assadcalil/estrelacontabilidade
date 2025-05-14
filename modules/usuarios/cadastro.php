<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Cadastro de usuários
 * 
 * @author [Seu Nome]
 * @created <?= date('Y-m-d') ?>
 */

// Define caminho base
define('BASE_PATH', realpath(dirname(__FILE__) . '/../..'));

// Define título da página
$pageTitle = 'Novo Usuário - ' . 'SYSTEM_NAME';

// Include da base da página
require_once BASE_PATH . '/app/includes/base/base_page.php';

// Verificação de permissão - apenas admin pode criar usuários
if ($_SESSION['user_type'] != Auth::ADMIN && $_SESSION['user_type'] != Auth::EDITOR) {
    $_SESSION['error_message'] = "Acesso negado. Você não tem permissão para acessar esta página.";
    header("Location: " . BASE_URL . "/public/index.php");
    exit;
}

// Inicializar variáveis
$name = '';
$email = '';
$user_type = '';
$company_id = 0;
$errors = [];

// Instância do banco de dados
$db = Database::getInstance();

// Obter lista de empresas para o select
try {
    $sql = "SELECT id, emp_name FROM companies WHERE status = 1 ORDER BY emp_name ASC";
    $stmt = $db->query($sql);
    $companies = $stmt->fetchAll();
} catch (PDOException $e) {
    ErrorHandler::logError('USERS', "Erro ao listar empresas: " . $e->getMessage());
    $companies = [];
}

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Coletar dados do formulário
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $user_type = (int)($_POST['user_type'] ?? 0);
    $company_id = (int)($_POST['company_id'] ?? 0);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validação
    if (empty($name)) {
        $errors['name'] = "Nome é obrigatório";
    }
    
    if (empty($email)) {
        $errors['email'] = "E-mail é obrigatório";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "E-mail inválido";
    } else {
        // Verificar se o e-mail já existe
        try {
            $sql = "SELECT COUNT(*) FROM users WHERE email = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$email]);
            
            if ($stmt->fetchColumn() > 0) {
                $errors['email'] = "Este e-mail já está sendo utilizado";
            }
        } catch (PDOException $e) {
            ErrorHandler::logError('USERS', "Erro ao verificar e-mail: " . $e->getMessage());
            $errors['email'] = "Erro ao verificar e-mail. Tente novamente.";
        }
    }
    
    if ($user_type == 0) {
        $errors['user_type'] = "Selecione um tipo de usuário";
    }
    
    // Validar senha
    if (empty($password)) {
        $errors['password'] = "Senha é obrigatória";
    } elseif (strlen($password) < 8) {
        $errors['password'] = "A senha deve ter pelo menos 8 caracteres";
    }
    
    if ($password != $confirm_password) {
        $errors['confirm_password'] = "As senhas não conferem";
    }
    
    // Validações específicas por tipo de usuário
    if ($user_type == Auth::CLIENT && $company_id == 0) {
        $errors['company_id'] = "Cliente deve estar associado a uma empresa";
    }
    
    // Se não houver erros, inserir o novo usuário
    if (empty($errors)) {
        try {
            // Hash da senha
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Inserir o usuário
            $sql = "INSERT INTO users (name, email, password, user_type, company_id, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, 1, NOW())";
            
            $params = [
                $name,
                $email,
                $hashedPassword,
                $user_type,
                ($company_id > 0) ? $company_id : null
            ];
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                // Usuário criado com sucesso
                $_SESSION['success_message'] = "Usuário criado com sucesso!";
                header("Location: " . BASE_URL . "/modules/usuarios/index.php");
                exit;
            } else {
                $errors['general'] = "Erro ao criar usuário. Tente novamente.";
            }
        } catch (PDOException $e) {
            ErrorHandler::logError('USERS', "Erro ao criar usuário: " . $e->getMessage());
            $errors['general'] = "Erro ao criar usuário. Tente novamente.";
        }
    }
}

// CSS e JS adicionais se necessário
$extraCSS = '';
$extraJS = '
<script src="' . BASE_URL . '/assets/js/users.js"></script>
';
?>

<div class="container-fluid px-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Novo Usuário</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/public/index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/modules/usuarios/index.php">Usuários</a></li>
                <li class="breadcrumb-item active" aria-current="page">Novo</li>
            </ol>
        </nav>
    </div>
    
    <!-- Formulário -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header">
            <i class="bi bi-person-plus me-1"></i> Cadastrar Novo Usuário
        </div>
        <div class="card-body">
            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-danger">
                    <?= $errors['general'] ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>" class="needs-validation" novalidate>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Nome Completo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                               id="name" name="name" value="<?= htmlspecialchars($name) ?>" required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback">
                                <?= $errors['name'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="email" class="form-label">E-mail <span class="text-danger">*</span></label>
                        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                               id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback">
                                <?= $errors['email'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="user_type" class="form-label">Tipo de Usuário <span class="text-danger">*</span></label>
                        <select class="form-select <?= isset($errors['user_type']) ? 'is-invalid' : '' ?>" 
                                id="user_type" name="user_type" required onchange="toggleCompanyField()">
                            <option value="0" <?= $user_type === 0 ? 'selected' : '' ?>>Selecione</option>
                            <?php if ($_SESSION['user_type'] == Auth::ADMIN): ?>
                                <option value="<?= Auth::ADMIN ?>" <?= $user_type === Auth::ADMIN ? 'selected' : '' ?>>
                                    Administrador
                                </option>
                            <?php endif; ?>
                            <option value="<?= Auth::EDITOR ?>" <?= $user_type === Auth::EDITOR ? 'selected' : '' ?>>
                                Editor
                            </option>
                            <option value="<?= Auth::TAX ?>" <?= $user_type === Auth::TAX ? 'selected' : '' ?>>
                                Fiscal
                            </option>
                            <option value="<?= Auth::EMPLOYEE ?>" <?= $user_type === Auth::EMPLOYEE ? 'selected' : '' ?>>
                                Funcionário
                            </option>
                            <option value="<?= Auth::FINANCIAL ?>" <?= $user_type === Auth::FINANCIAL ? 'selected' : '' ?>>
                                Financeiro
                            </option>
                            <option value="<?= Auth::CLIENT ?>" <?= $user_type === Auth::CLIENT ? 'selected' : '' ?>>
                                Cliente
                            </option>
                        </select>
                        <?php if (isset($errors['user_type'])): ?>
                            <div class="invalid-feedback">
                                <?= $errors['user_type'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="company_id" class="form-label">Empresa</label>
                        <select class="form-select <?= isset($errors['company_id']) ? 'is-invalid' : '' ?>" 
                               id="company_id" name="company_id" <?= ($user_type == Auth::CLIENT) ? 'required' : '' ?>>
                            <option value="0">Nenhuma</option>
                            <?php foreach ($companies as $company): ?>
                                <option value="<?= $company['id'] ?>" <?= $company_id == $company['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($company['emp_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['company_id'])): ?>
                            <div class="invalid-feedback">
                                <?= $errors['company_id'] ?>
                            </div>
                        <?php endif; ?>
                        <div class="form-text" id="companyHelpText">
                            Obrigatório para usuários do tipo Cliente.
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="password" class="form-label">Senha <span class="text-danger">*</span></label>
                        <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                               id="password" name="password" required minlength="8">
                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback">
                                <?= $errors['password'] ?>
                            </div>
                        <?php endif; ?>
                        <div class="form-text">
                            A senha deve ter pelo menos 8 caracteres.
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="confirm_password" class="form-label">Confirmar Senha <span class="text-danger">*</span></label>
                        <input type="password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" 
                               id="confirm_password" name="confirm_password" required>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <div class="invalid-feedback">
                                <?= $errors['confirm_password'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <a href="<?= BASE_URL ?>/modules/usuarios/index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Voltar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Função para atualizar a obrigatoriedade do campo empresa com base no tipo de usuário
    function toggleCompanyField() {
        const userType = document.getElementById('user_type').value;
        const companyField = document.getElementById('company_id');
        
        if (userType == <?= Auth::CLIENT ?>) {
            companyField.setAttribute('required', 'required');
            document.getElementById('companyHelpText').classList.add('text-danger');
        } else {
            companyField.removeAttribute('required');
            document.getElementById('companyHelpText').classList.remove('text-danger');
        }
    }
    
    // Inicializar o estado do campo empresa ao carregar a página
    document.addEventListener('DOMContentLoaded', function() {
        toggleCompanyField();
        
        // Validação do formulário
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    });
</script>

<?php
// Inclusão do rodapé
include_once BASE_PATH . '/app/includes/base/footer.php';
?>