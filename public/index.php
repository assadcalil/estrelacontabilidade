<?php
/**
 * Página inicial do sistema (Dashboard)
 * 
 * @author Thiago Calil Assad
 * @created <?= date('Y-m-d') ?>
 */

// Define caminho base
define('BASE_PATH', realpath(dirname(__FILE__) . '/..'));

// Define título da página
$pageTitle = 'Dashboard - ' . SYSTEM_NAME;

// Include da base da página
require_once BASE_PATH . '/app/includes/base/base_page.php';

// Obter dados para o dashboard
$db = Database::getInstance();

// 1. Total de empresas ativas
try {
    $sql = "SELECT COUNT(*) FROM companies WHERE status = 1";
    $stmt = $db->query($sql);
    $totalCompanies = $stmt->fetchColumn();
} catch (PDOException $e) {
    ErrorHandler::logError('DASHBOARD', "Erro ao obter total de empresas: " . $e->getMessage());
    $totalCompanies = 0;
}

// 2. Total de documentos
try {
    $sql = "SELECT COUNT(*) FROM documents WHERE status = 1";
    $stmt = $db->query($sql);
    $totalDocuments = $stmt->fetchColumn();
} catch (PDOException $e) {
    ErrorHandler::logError('DASHBOARD', "Erro ao obter total de documentos: " . $e->getMessage());
    $totalDocuments = 0;
}

// 3. Certificados expirando nos próximos 30 dias
try {
    $sql = "SELECT COUNT(*) FROM certificates 
            WHERE status = 1 
            AND valid_until BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
    $stmt = $db->query($sql);
    $expiringCertificates = $stmt->fetchColumn();
} catch (PDOException $e) {
    ErrorHandler::logError('DASHBOARD', "Erro ao obter certificados expirando: " . $e->getMessage());
    $expiringCertificates = 0;
}

// 4. Impostos pendentes
try {
    $sql = "SELECT COUNT(*) FROM imposto WHERE status_boleto_2024 = 0 OR status_boleto_2025 = 0";
    $stmt = $db->query($sql);
    $pendingTaxes = $stmt->fetchColumn();
} catch (PDOException $e) {
    ErrorHandler::logError('DASHBOARD', "Erro ao obter impostos pendentes: " . $e->getMessage());
    $pendingTaxes = 0;
}

// 5. Últimos documentos adicionados
try {
    $sql = "SELECT d.id, d.title, d.created_at, d.category, c.emp_name 
            FROM documents d
            LEFT JOIN companies c ON d.company_id = c.id
            WHERE d.status = 1
            ORDER BY d.created_at DESC 
            LIMIT 5";
    $stmt = $db->query($sql);
    $recentDocuments = $stmt->fetchAll();
} catch (PDOException $e) {
    ErrorHandler::logError('DASHBOARD', "Erro ao obter documentos recentes: " . $e->getMessage());
    $recentDocuments = [];
}

// 6. Certificados próximos de expirar
try {
    $sql = "SELECT c.id, c.name, c.valid_until, co.emp_name 
            FROM certificates c
            LEFT JOIN companies co ON c.company_id = co.id
            WHERE c.status = 1 
            AND c.valid_until BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)
            ORDER BY c.valid_until ASC 
            LIMIT 5";
    $stmt = $db->query($sql);
    $expiringCertificatesList = $stmt->fetchAll();
} catch (PDOException $e) {
    ErrorHandler::logError('DASHBOARD', "Erro ao obter lista de certificados expirando: " . $e->getMessage());
    $expiringCertificatesList = [];
}

// Filtrar dados com base no tipo de usuário e empresa
if ($_SESSION['user_type'] == Auth::CLIENT) {
    // Restringir visibilidade para clientes apenas à sua empresa
    $companyId = $_SESSION['company_id'];
    
    // Recalcular as estatísticas
    try {
        // Documentos da empresa
        $sql = "SELECT COUNT(*) FROM documents WHERE status = 1 AND company_id = ?";
        $stmt = $db->query($sql, [$companyId]);
        $totalDocuments = $stmt->fetchColumn();
        
        // Certificados expirando
        $sql = "SELECT COUNT(*) FROM certificates 
                WHERE status = 1 AND company_id = ?
                AND valid_until BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
        $stmt = $db->query($sql, [$companyId]);
        $expiringCertificates = $stmt->fetchColumn();
        
        // Impostos pendentes
        $sql = "SELECT COUNT(*) FROM imposto WHERE company_id = ? AND (status_boleto_2024 = 0 OR status_boleto_2025 = 0)";
        $stmt = $db->query($sql, [$companyId]);
        $pendingTaxes = $stmt->fetchColumn();
        
        // Documentos recentes
        $sql = "SELECT d.id, d.title, d.created_at, d.category, c.emp_name 
                FROM documents d
                LEFT JOIN companies c ON d.company_id = c.id
                WHERE d.status = 1 AND d.company_id = ?
                ORDER BY d.created_at DESC 
                LIMIT 5";
        $stmt = $db->query($sql, [$companyId]);
        $recentDocuments = $stmt->fetchAll();
        
        // Certificados expirando
        $sql = "SELECT c.id, c.name, c.valid_until, co.emp_name 
                FROM certificates c
                LEFT JOIN companies co ON c.company_id = co.id
                WHERE c.status = 1 AND c.company_id = ?
                AND c.valid_until BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)
                ORDER BY c.valid_until ASC 
                LIMIT 5";
        $stmt = $db->query($sql, [$companyId]);
        $expiringCertificatesList = $stmt->fetchAll();
    } catch (PDOException $e) {
        ErrorHandler::logError('DASHBOARD', "Erro ao obter dados de cliente: " . $e->getMessage());
    }
}

// CSS extra para o dashboard
$extraCSS = '
<link rel="stylesheet" href="' . BASE_URL . '/assets/css/dashboard.css">
';

// JS extra para gráficos
$extraJS = '
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="' . BASE_URL . '/assets/js/dashboard.js"></script>
';

?>

<div class="container-fluid px-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/public/index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
            </ol>
        </nav>
    </div>
    
    <!-- Cards de Estatísticas -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card dashboard-card bg-primary text-white mb-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Empresas</h5>
                            <h2 class="mt-2 mb-0"><?= number_format($totalCompanies) ?></h2>
                        </div>
                        <div class="icon-box">
                            <i class="bi bi-building"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="<?= BASE_URL ?>/modules/empresas/index.php">Ver Detalhes</a>
                    <div class="small text-white"><i class="bi bi-arrow-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card dashboard-card bg-success text-white mb-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Documentos</h5>
                            <h2 class="mt-2 mb-0"><?= number_format($totalDocuments) ?></h2>
                        </div>
                        <div class="icon-box">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="<?= BASE_URL ?>/modules/documentos/index.php">Ver Detalhes</a>
                    <div class="small text-white"><i class="bi bi-arrow-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card dashboard-card bg-warning text-white mb-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Certificados Expirando</h5>
                            <h2 class="mt-2 mb-0"><?= number_format($expiringCertificates) ?></h2>
                        </div>
                        <div class="icon-box">
                            <i class="bi bi-patch-exclamation"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="<?= BASE_URL ?>/modules/certificados/index.php?expiring=1">Ver Detalhes</a>
                    <div class="small text-white"><i class="bi bi-arrow-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card dashboard-card bg-danger text-white mb-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Impostos Pendentes</h5>
                            <h2 class="mt-2 mb-0"><?= number_format($pendingTaxes) ?></h2>
                        </div>
                        <div class="icon-box">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="<?= BASE_URL ?>/modules/impostos/index.php?pending=1">Ver Detalhes</a>
                    <div class="small text-white"><i class="bi bi-arrow-right"></i></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gráficos e Dados Adicionais -->
    <div class="row">
        <div class="col-xl-6">
            <div class="card mb-4 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-file-text me-1"></i>
                        Documentos Recentes
                    </div>
                    <div class="card-actions">
                        <button class="btn btn-sm btn-primary" onclick="window.location.href='<?= BASE_URL ?>/modules/documentos/index.php'">
                            <i class="bi bi-file-plus"></i> Novo
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($recentDocuments)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Documento</th>
                                        <th>Empresa</th>
                                        <th>Categoria</th>
                                        <th>Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentDocuments as $doc): ?>
                                        <tr>
                                            <td>
                                                <a href="<?= BASE_URL ?>/modules/documentos/view.php?id=<?= $doc['id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($doc['title']) ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($doc['emp_name'] ?? 'N/A') ?></td>
                                            <td>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($doc['category'] ?? 'Geral') ?></span>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($doc['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            Não há documentos recentes para exibir.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card mb-4 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-patch-check me-1"></i>
                        Certificados Próximos de Expirar
                    </div>
                    <div class="card-actions">
                        <button class="btn btn-sm btn-primary" onclick="window.location.href='<?= BASE_URL ?>/modules/certificados/index.php'">
                            <i class="bi bi-plus-circle"></i> Gerenciar
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($expiringCertificatesList)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Certificado</th>
                                        <th>Empresa</th>
                                        <th>Validade</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($expiringCertificatesList as $cert): ?>
                                        <?php 
                                            $daysLeft = floor((strtotime($cert['valid_until']) - time()) / (60 * 60 * 24));
                                            $badgeClass = 'bg-success';
                                            $badgeText = 'OK';
                                            
                                            if ($daysLeft <= 30) {
                                                $badgeClass = 'bg-danger';
                                                $badgeText = 'Crítico';
                                            } elseif ($daysLeft <= 60) {
                                                $badgeClass = 'bg-warning';
                                                $badgeText = 'Atenção';
                                            }
                                        ?>
                                        <tr>
                                            <td>
                                                <a href="<?= BASE_URL ?>/modules/certificados/view.php?id=<?= $cert['id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($cert['name']) ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($cert['emp_name'] ?? 'N/A') ?></td>
                                            <td><?= date('d/m/Y', strtotime($cert['valid_until'])) ?></td>
                                            <td>
                                                <span class="badge <?= $badgeClass ?>">
                                                    <?= $badgeText ?> (<?= $daysLeft ?> dias)
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            Não há certificados expirando em breve.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gráficos Adicionais - Apenas um exemplo, você precisará ajustar o JS correspondente -->
    <div class="row">
        <div class="col-xl-8">
            <div class="card mb-4 shadow-sm">
                <div class="card-header">
                    <i class="bi bi-graph-up me-1"></i>
                    Documentos por Categoria
                </div>
                <div class="card-body">
                    <canvas id="documentsByCategory" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card mb-4 shadow-sm">
                <div class="card-header">
                    <i class="bi bi-calendar-check me-1"></i>
                    Próximos Vencimentos
                </div>
                <div class="card-body">
                    <canvas id="upcomingExpirations" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Inclusão do rodapé
include_once BASE_PATH . '/app/includes/base/footer.php';
?>