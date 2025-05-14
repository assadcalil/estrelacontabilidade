<?php
/**
 * Script para verificar a estrutura de pastas e arquivos do sistema
 * 
 * Este script verifica se os arquivos importantes existem e mostra informações
 * sobre suas datas de modificação e permissões.
 */

// Define caminho base
define('BASE_PATH', realpath(dirname(__FILE__)));
echo "Base Path: " . BASE_PATH . "\n\n";

// Função para verificar arquivo
function checkFile($path, $description) {
    $fullPath = BASE_PATH . $path;
    $status = file_exists($fullPath) ? "✓ EXISTE" : "✗ NÃO EXISTE";
    $modified = file_exists($fullPath) ? date("Y-m-d H:i:s", filemtime($fullPath)) : "N/A";
    $size = file_exists($fullPath) ? filesize($fullPath) . " bytes" : "N/A";
    $perms = file_exists($fullPath) ? substr(sprintf('%o', fileperms($fullPath)), -4) : "N/A";
    
    $result = [
        'path' => $path,
        'fullPath' => $fullPath,
        'description' => $description,
        'status' => $status,
        'modified' => $modified,
        'size' => $size,
        'perms' => $perms
    ];
    
    return $result;
}

// Função para criar diretório e arquivo se não existir
function createDirectoryAndFile($path, $content) {
    $fullPath = BASE_PATH . $path;
    $directory = dirname($fullPath);
    
    if (!file_exists($directory)) {
        if (mkdir($directory, 0755, true)) {
            echo "Diretório criado: $directory\n";
        } else {
            echo "Erro ao criar diretório: $directory\n";
            return false;
        }
    }
    
    if (!file_exists($fullPath)) {
        if (file_put_contents($fullPath, $content)) {
            echo "Arquivo criado: $fullPath\n";
            return true;
        } else {
            echo "Erro ao criar arquivo: $fullPath\n";
            return false;
        }
    }
    
    return true;
}

// Lista de arquivos para verificar
$filesToCheck = [
    '/app/config/email_config.php' => 'Configurações de e-mail',
    '/app/includes/utils/Mailer.php' => 'Classe de envio de e-mail',
    '/app/templates/emails/password_recovery.html' => 'Template de recuperação de senha',
    '/app/logs/email_debug.log' => 'Log de depuração de e-mail',
    '/app/logs/recovery_email.log' => 'Log de recuperação de senha',
    '/app/controllers/auth_controller.php' => 'Controlador de autenticação',
    '/public/recovery.php' => 'Página de recuperação de senha',
    '/test_email.php' => 'Script de teste de e-mail',
    '/email_test.log' => 'Log de teste de e-mail'
];

// Verificar cada arquivo
$results = [];
foreach ($filesToCheck as $path => $description) {
    $results[] = checkFile($path, $description);
}

// Verificar se o diretório de templates existe
$templatesDir = BASE_PATH . '/app/templates/emails';
$templatesDirExists = is_dir($templatesDir);
$templatesDirStatus = $templatesDirExists ? "✓ EXISTE" : "✗ NÃO EXISTE";

// Verificar se o diretório de logs existe
$logsDir = BASE_PATH . '/app/logs';
$logsDirExists = is_dir($logsDir);
$logsDirStatus = $logsDirExists ? "✓ EXISTE" : "✗ NÃO EXISTE";

// Modo HTML se executado no navegador
$isWeb = isset($_SERVER['HTTP_HOST']);

if ($isWeb) {
    // Não mostrar o caminho completo por segurança em ambiente web
    header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de Estrutura do Sistema</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .table-responsive {
            margin-top: 20px;
        }
        .exists {
            color: green;
            font-weight: bold;
        }
        .not-exists {
            color: red;
            font-weight: bold;
        }
        .card-header {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>Verificação de Estrutura do Sistema</h1>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Diretórios Importantes</div>
                    <div class="card-body">
                        <p><strong>Diretório de Templates:</strong> <span class="<?= $templatesDirExists ? 'exists' : 'not-exists' ?>"><?= $templatesDirStatus ?></span></p>
                        <p><strong>Diretório de Logs:</strong> <span class="<?= $logsDirExists ? 'exists' : 'not-exists' ?>"><?= $logsDirStatus ?></span></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Ações</div>
                    <div class="card-body">
                        <form action="" method="post">
                            <input type="hidden" name="create_dirs" value="1">
                            <button type="submit" class="btn btn-primary" <?= ($templatesDirExists && $logsDirExists) ? 'disabled' : '' ?>>
                                Criar Diretórios Faltantes
                            </button>
                        </form>
                        <hr>
                        <a href="test_email.php" class="btn btn-success">Ir para Teste de E-mail</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Arquivo</th>
                        <th>Descrição</th>
                        <th>Status</th>
                        <th>Última Modificação</th>
                        <th>Tamanho</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result): ?>
                    <tr>
                        <td><?= htmlspecialchars($result['path']) ?></td>
                        <td><?= htmlspecialchars($result['description']) ?></td>
                        <td class="<?= strpos($result['status'], '✓') !== false ? 'exists' : 'not-exists' ?>">
                            <?= htmlspecialchars($result['status']) ?>
                        </td>
                        <td><?= htmlspecialchars($result['modified']) ?></td>
                        <td><?= htmlspecialchars($result['size']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            <h3>Próximos Passos</h3>
            <ul>
                <li>Certifique-se de que a classe <code>Mailer.php</code> esteja corretamente instalada</li>
                <li>Verifique se o arquivo <code>email_config.php</code> tem as configurações corretas</li>
                <li>Crie o diretório de templates se não existir</li>
                <li>Use o script <code>test_email.php</code> para testar o envio de e-mails</li>
            </ul>
        </div>
    </div>
</body>
</html>
<?php
} else {
    // Saída para linha de comando
    echo "=== VERIFICAÇÃO DE ESTRUTURA DO SISTEMA ===\n\n";
    
    echo "DIRETÓRIOS IMPORTANTES:\n";
    echo "- Diretório de Templates: $templatesDirStatus ($templatesDir)\n";
    echo "- Diretório de Logs: $logsDirStatus ($logsDir)\n\n";
    
    echo "ARQUIVOS DO SISTEMA:\n";
    echo str_repeat('-', 100) . "\n";
    echo sprintf("%-40s %-25s %-15s %-20s %-10s\n", "CAMINHO", "DESCRIÇÃO", "STATUS", "MODIFICADO", "TAMANHO");
    echo str_repeat('-', 100) . "\n";
    
    foreach ($results as $result) {
        echo sprintf("%-40s %-25s %-15s %-20s %-10s\n",
            substr($result['path'], 0, 39),
            substr($result['description'], 0, 24),
            $result['status'],
            $result['modified'],
            $result['size']
        );
    }
    
    echo str_repeat('-', 100) . "\n";
}

// Criar diretórios e arquivos faltantes se solicitado
if (isset($_POST['create_dirs'])) {
    // Criar diretório de templates
    if (!$templatesDirExists) {
        if (mkdir($templatesDir, 0755, true)) {
            echo '<div class="alert alert-success">Diretório de templates criado com sucesso!</div>';
        } else {
            echo '<div class="alert alert-danger">Erro ao criar diretório de templates!</div>';
        }
    }
    
    // Criar diretório de logs
    if (!$logsDirExists) {
        if (mkdir($logsDir, 0755, true)) {
            echo '<div class="alert alert-success">Diretório de logs criado com sucesso!</div>';
        } else {
            echo '<div class="alert alert-danger">Erro ao criar diretório de logs!</div>';
        }
    }
    
    // Redirecionar para atualizar a página
    if ($isWeb) {
        echo "<script>window.location.href = window.location.pathname;</script>";
    }
}
?>