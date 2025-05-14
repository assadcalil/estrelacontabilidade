<?php
/**
 * Script simples de teste de e-mail usando o caminho exato do PHPMailer
 * 
 * Este script está otimizado para o caminho específico do PHPMailer no sistema GED:
 * C:\inetpub\wwwroot\GED\vendor\phpmailer\phpmailer\src\PHPMailer.php
 */

// Define caminho base
define('BASE_PATH', realpath(dirname(__FILE__)));
echo "<p>Base Path: " . BASE_PATH . "</p>";

// Carregar diretamente os arquivos PHPMailer
$phpmailerPath = BASE_PATH . '/vendor/phpmailer/phpmailer/src/';

if (file_exists($phpmailerPath . 'PHPMailer.php')) {
    require_once $phpmailerPath . 'Exception.php';
    require_once $phpmailerPath . 'PHPMailer.php';
    require_once $phpmailerPath . 'SMTP.php';
    echo "<p style='color:green'>✓ PHPMailer encontrado no caminho: " . $phpmailerPath . "</p>";
} else {
    die("<p style='color:red'>✗ ERRO: PHPMailer não encontrado no caminho: " . $phpmailerPath . "</p>");
}

// Usar os namespaces do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Carregar constants.php caso exista para obter SYSTEM_NAME
if (file_exists(BASE_PATH . '/app/config/constants.php')) {
    require_once BASE_PATH . '/app/config/constants.php';
}

// Definir SYSTEM_NAME caso não esteja definido
if (!defined('SYSTEM_NAME')) {
    define('SYSTEM_NAME', 'GED ESTRELA');
}

// Configurações SMTP para Gmail com porta 587 (TLS)
define('TEST_SMTP_HOST', 'smtp.gmail.com');
define('TEST_SMTP_PORT', 587); // Porta 587 com TLS
define('TEST_SMTP_USER', 'recuperacaoestrela@gmail.com');
define('TEST_SMTP_PASS', 'sgyrmsgdaxiqvupb'); // Senha de app
define('TEST_SMTP_SECURE', 'tls'); // TLS para porta 587
define('TEST_FROM_EMAIL', 'recuperacaoestrela@gmail.com');
define('TEST_FROM_NAME', 'CONTABILIDADE ESTRELA');

// Inicializar variáveis
$emailSent = false;
$errorMessage = '';
$result = null;
$recipientEmail = '';
$debugOutput = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $recipientEmail = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    
    if (!$recipientEmail) {
        $errorMessage = 'E-mail inválido. Por favor, forneça um endereço de e-mail válido.';
    } else {
        try {
            // Criar nova instância do PHPMailer com debug
            $mail = new PHPMailer(true);
            
            // Habilitar debug
            $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Saída detalhada
            ob_start(); // Capturar saída de debug

            // Configurações do servidor
            $mail->isSMTP();
            $mail->Host = TEST_SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = TEST_SMTP_USER;
            $mail->Password = TEST_SMTP_PASS;
            $mail->Port = TEST_SMTP_PORT;
            $mail->CharSet = 'UTF-8';
            
            // Configuração de segurança (TLS para porta 587)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            
            // Timeout mais longo para evitar erros
            $mail->Timeout = 30;
            
            // Desabilitar verificação SSL para evitar problemas com certificados
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            
            // Remetente e destinatário
            $mail->setFrom(TEST_FROM_EMAIL, TEST_FROM_NAME);
            $mail->addAddress($recipientEmail);
            
            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = 'Teste de E-mail Direto - ' . SYSTEM_NAME;
            $mail->Body = '<h2>Teste de Configuração de E-mail</h2>
                        <p>Este é um e-mail de teste enviado diretamente pelo script <code>test_email_simple.php</code> em ' . date('d/m/Y H:i:s') . '.</p>
                        <p>Se você está recebendo este e-mail, significa que a configuração de envio de e-mails do sistema está funcionando corretamente.</p>
                        <p><strong>Configurações utilizadas:</strong></p>
                        <ul>
                            <li>Servidor SMTP: ' . TEST_SMTP_HOST . '</li>
                            <li>Porta: ' . TEST_SMTP_PORT . '</li>
                            <li>Criptografia: ' . TEST_SMTP_SECURE . '</li>
                            <li>Remetente: ' . TEST_FROM_EMAIL . '</li>
                        </ul>';
            
            $mail->AltBody = 'Este é um e-mail de teste. Se você está vendo esta mensagem, seu cliente de e-mail não suporta HTML.';
            
            // Enviar
            $result = $mail->send();
            $emailSent = true;
            
            // Obter saída de debug
            $debugOutput = ob_get_clean();
            
        } catch (Exception $e) {
            $debugOutput = ob_get_clean();
            $errorMessage = $mail->ErrorInfo;
            $emailSent = false;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Simples de E-mail - <?= SYSTEM_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .container {
            max-width: 800px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            overflow: hidden;
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #4e73df;
            color: white;
            font-weight: bold;
        }
        pre {
            background-color: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
            max-height: 300px;
            overflow-y: auto;
        }
        .config-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .config-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1 class="mb-4 text-center">Teste Simples de E-mail - <?= SYSTEM_NAME ?></h1>
        
        <div class="card mb-4">
            <div class="card-header py-3">
                <h5 class="m-0">Enviar E-mail de Teste</h5>
            </div>
            <div class="card-body">
                <p>Este script testa o envio de e-mail usando o PHPMailer encontrado em:</p>
                <code><?= $phpmailerPath ?></code>
                
                <?php if ($emailSent): ?>
                    <div class="alert alert-success mt-3">
                        <h5><i class="bi bi-check-circle-fill me-2"></i>E-mail enviado com sucesso!</h5>
                        <p>Um e-mail de teste foi enviado para <strong><?= htmlspecialchars($recipientEmail) ?></strong>.</p>
                        <p>Verifique sua caixa de entrada (ou pasta de spam) para confirmar o recebimento.</p>
                    </div>
                <?php elseif ($errorMessage): ?>
                    <div class="alert alert-danger mt-3">
                        <h5><i class="bi bi-exclamation-triangle-fill me-2"></i>Falha ao enviar e-mail!</h5>
                        <p>Ocorreu um erro ao tentar enviar e-mail para <strong><?= htmlspecialchars($recipientEmail) ?></strong>.</p>
                        <p><strong>Erro:</strong> <?= htmlspecialchars($errorMessage) ?></p>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="" class="mt-3">
                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail para teste:</label>
                        <input type="email" class="form-control" id="email" name="email" required 
                               placeholder="Digite seu e-mail" value="<?= htmlspecialchars($recipientEmail) ?>">
                        <div class="form-text">Um e-mail de teste será enviado para este endereço.</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Enviar E-mail Agora</button>
                </form>
            </div>
        </div>
        
        <?php if (!empty($debugOutput)): ?>
        <div class="card mb-4">
            <div class="card-header py-3">
                <h5 class="m-0">Log de Depuração SMTP</h5>
            </div>
            <div class="card-body">
                <pre><?= htmlspecialchars($debugOutput) ?></pre>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header py-3">
                <h5 class="m-0">Configurações SMTP Utilizadas</h5>
            </div>
            <div class="card-body">
                <div class="config-item">
                    <strong>Servidor SMTP:</strong>
                    <span><?= TEST_SMTP_HOST ?></span>
                </div>
                <div class="config-item">
                    <strong>Porta:</strong>
                    <span><?= TEST_SMTP_PORT ?></span>
                </div>
                <div class="config-item">
                    <strong>Usuário:</strong>
                    <span><?= TEST_SMTP_USER ?></span>
                </div>
                <div class="config-item">
                    <strong>Senha:</strong>
                    <span>********</span>
                </div>
                <div class="config-item">
                    <strong>Segurança:</strong>
                    <span><?= TEST_SMTP_SECURE ?></span>
                </div>
                <div class="config-item">
                    <strong>Remetente:</strong>
                    <span><?= TEST_FROM_EMAIL ?></span>
                </div>
                <div class="config-item">
                    <strong>Nome do Remetente:</strong>
                    <span><?= TEST_FROM_NAME ?></span>
                </div>
                <div class="config-item">
                    <strong>PHP Version:</strong>
                    <span><?= phpversion() ?></span>
                </div>
                <div class="config-item">
                    <strong>OpenSSL:</strong>
                    <span><?= extension_loaded('openssl') ? 'Disponível ✓' : 'Não disponível ✗' ?></span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>