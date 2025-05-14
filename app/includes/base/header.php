<?php
/**
 * Cabeçalho padrão do sistema
 * 
 * @author Thiago Calil Assad
 * @created <?= date('Y-m-d') ?>
 */

// Previne acesso direto ao arquivo
if (!defined('BASE_PATH')) {
    http_response_code(403);
    exit('Acesso proibido');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? SYSTEM_NAME ?></title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?= BASE_URL ?>/assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- Fonte Nunito -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    
    <?php if (isset($extraCSS)): ?>
        <?= $extraCSS ?>
    <?php endif; ?>
    
    <!-- jQuery (necessário para Bootstrap e DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
</head>
<body>
<div class="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar" class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand-icon">
                <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Logo <?= SYSTEM_NAME ?>" class="logo-sidebar">
            </div>
            <button type="button" id="sidebarCollapseBtn" class="btn btn-link sidebar-collapse-btn">
                <i class="bi bi-arrow-left-circle"></i>
            </button>
        </div>

        <ul class="sidebar-nav">
            <?php foreach ($sidebarMenu as $menuItem): ?>
                <?php if (isset($menuItem['submenu'])): ?>
                    <li class="sidebar-item <?= $menuItem['active'] ? 'active open' : '' ?>">
                        <a href="#" class="sidebar-link sidebar-toggle" data-bs-toggle="collapse" data-bs-target="#submenu-<?= md5($menuItem['title']) ?>">
                            <i class="bi <?= $menuItem['icon'] ?>"></i>
                            <span><?= $menuItem['title'] ?></span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <ul id="submenu-<?= md5($menuItem['title']) ?>" class="sidebar-submenu collapse <?= $menuItem['active'] ? 'show' : '' ?>">
                            <?php foreach ($menuItem['submenu'] as $submenuItem): ?>
                                <li class="sidebar-item <?= $submenuItem['active'] ? 'active' : '' ?>">
                                    <a href="<?= $submenuItem['url'] ?>" class="sidebar-link">
                                        <i class="bi <?= $submenuItem['icon'] ?>"></i>
                                        <span><?= $submenuItem['title'] ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="sidebar-item <?= $menuItem['active'] ? 'active' : '' ?>">
                        <a href="<?= $menuItem['url'] ?>" class="sidebar-link">
                            <i class="bi <?= $menuItem['icon'] ?>"></i>
                            <span><?= $menuItem['title'] ?></span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </nav>

    <!-- Page Content -->
    <div id="content" class="content">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 shadow-sm">
            <!-- Sidebar Toggle (Mobile) -->
            <button id="sidebarToggleTop" class="btn btn-link d-lg-none rounded-circle mr-3">
                <i class="bi bi-list"></i>
            </button>

            <!-- Page title -->
            <div class="page-title d-none d-sm-inline-block">
                <h1 class="h4 mb-0 text-gray-800"><?= $pageTitle ?? SYSTEM_NAME ?></h1>
            </div>

            <!-- Topbar Navbar -->
            <ul class="navbar-nav ms-auto">
                <!-- Empresa Selector -->
                <?php if (isset($_SESSION['user_id']) && class_exists('Auth') && method_exists('Auth', 'getUserCompanies') && count(Auth::getUserCompanies()) > 1): ?>
                <li class="nav-item dropdown no-arrow mx-1">
                    <a class="nav-link dropdown-toggle" href="#" id="companyDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-building me-1"></i>
                        <span class="d-none d-lg-inline text-gray-600 small">
                            <?= htmlspecialchars($_SESSION['company_name'] ?? 'Selecionar Empresa') ?>
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end shadow animated--grow-in" aria-labelledby="companyDropdown">
                        <?php foreach (Auth::getUserCompanies() as $company): ?>
                        <a class="dropdown-item <?= ($_SESSION['company_id'] ?? 0) == $company['id'] ? 'active' : '' ?>" 
                           href="#" 
                           data-company-id="<?= $company['id'] ?>" 
                           onclick="switchCompany(<?= $company['id'] ?>)">
                            <i class="bi <?= $company['primary'] ? 'bi-building-fill' : 'bi-building' ?> me-2"></i>
                            <?= htmlspecialchars($company['name']) ?>
                            <small class="d-block text-muted"><?= htmlspecialchars($company['cnpj']) ?></small>
                            <?php if ($company['primary']): ?>
                            <span class="badge bg-primary">Principal</span>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </li>

                <div class="topbar-divider d-none d-sm-block"></div>
                <?php endif; ?>

                <!-- User Information -->
                <li class="nav-item dropdown no-arrow">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="d-none d-lg-inline text-gray-600 small"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Usuário') ?></span>
                        <i class="bi bi-person-circle ms-2 fs-5"></i>
                    </a>
                    <!-- Dropdown - User Information -->
                    <div class="dropdown-menu dropdown-menu-end shadow animated--grow-in" aria-labelledby="userDropdown">
                        <a class="dropdown-item" href="<?= BASE_URL ?>/modules/usuarios/profile.php">
                            <i class="bi bi-person me-2"></i>
                            Meu Perfil
                        </a>
                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <i class="bi bi-key me-2"></i>
                            Alterar Senha
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="<?= BASE_URL ?>/app/controllers/auth_controller.php?action=logout">
                            <i class="bi bi-box-arrow-right me-2"></i>
                            Sair
                        </a>
                    </div>
                </li>
            </ul>
        </nav>

        <!-- Modal de Alteração de Senha -->
        <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="changePasswordModalLabel">Alterar Senha</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <form id="changePasswordForm" action="<?= BASE_URL ?>/app/controllers/auth_controller.php?action=change_password" method="post">
                        <div class="modal-body">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['token'] ?>">
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Senha Atual</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="current_password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Nova Senha</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required 
                                           pattern=".{<?= PASSWORD_MIN_LENGTH ?>,<?= PASSWORD_MAX_LENGTH ?>}" 
                                           title="A senha deve ter entre <?= PASSWORD_MIN_LENGTH ?> e <?= PASSWORD_MAX_LENGTH ?> caracteres">
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">A senha deve ter entre <?= PASSWORD_MIN_LENGTH ?> e <?= PASSWORD_MAX_LENGTH ?> caracteres.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmar Nova Senha</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback" id="passwordMatchFeedback">
                                    As senhas não coincidem.
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary" id="changePasswordSubmit">Alterar Senha</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Conteúdo principal -->
        <div class="container-fluid">
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?= $_SESSION['error_message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?= $_SESSION['success_message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>