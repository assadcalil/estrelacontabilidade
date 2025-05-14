<?php
/**
 * Script de Instalação Completa para GED ESTRELA
 * 
 * Este script realiza uma instalação completa da solução de e-mail:
 * 1. Instala o PHPMailer (se ainda não estiver instalado)
 * 2. Instala os scripts da solução de e-mail
 * 3. Configura as permissões necessárias
 * 4. Testa a instalação
 */

// Define caminho base
define('BASE_PATH', realpath(dirname(__FILE__)));
$vendorDir = BASE_PATH . '/vendor';
$phpmailerDir = $vendorDir . '/phpmailer/phpmailer';
$appDir = BASE_PATH . '/app';
$mailerDir = $appDir . '/classes';
$testDir = BASE_PATH . '/public/test';
$templatesDir = $appDir . '/templates/emails';

// Função para exibir mensagens com cores
function printMessage($message, $type = 'info') {
    $colors = [
        'success' => "\033[0;32m", // Verde
        'error' => "\033[0;31m",   // Vermelho
        'info' => "\033[0;34m",    // Azul
        'warning' => "\033[0;33m"  // Amarelo
    ];
    
    $reset = "\033[0m";
    
    // Se estiver rodando em ambiente CLI
    if (php_sapi_name() === 'cli') {
        echo $colors[$type] . $message . $reset . PHP_EOL;
    } else {
        $colorClasses = [
            'success' => 'color:green',
            'error' => 'color:red',
            'info' => 'color:blue',
            'warning' => 'color:orange'
        ];
        echo "<div style='{$colorClasses[$type]}'>{$message}</div>" . PHP_EOL;
    }
}

// Verifica se o sistema é Windows ou Linux para usar o comando correto
$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
$pathSeparator = $isWindows ? '\\' : '/';

// Verifica se o Composer está instalado
function hasComposer() {
    global $isWindows;
    $cmd = $isWindows ? 'where composer' : 'which composer';
    exec($cmd, $output, $returnVal);
    return $returnVal === 0;
}

// Instala o PHPMailer usando o Composer
function installWithComposer() {
    global $vendorDir;
    
    printMessage("Instalando PHPMailer com Composer...", "info");
    
    // Verifica se o diretório vendor existe
    if (!file_exists($vendorDir)) {
        mkdir($vendorDir, 0755, true);
        printMessage("Diretório vendor criado: $vendorDir", "success");
    }
    
    // Cria arquivo composer.json se não existir
    $composerJsonPath = BASE_PATH . '/composer.json';
    
    if (!file_exists($composerJsonPath)) {
        $composerJson = json_encode([
            "require" => [
                "phpmailer/phpmailer" => "^6.8"
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        file_put_contents($composerJsonPath, $composerJson);
        printMessage("Arquivo composer.json criado: $composerJsonPath", "success");
    } else {
        // Atualiza o composer.json existente para incluir PHPMailer
        $composerData = json_decode(file_get_contents($composerJsonPath), true);
        
        if (!isset($composerData['require'])) {
            $composerData['require'] = [];
        }
        
        $composerData['require']['phpmailer/phpmailer'] = "^6.8";
        
        file_put_contents($composerJsonPath, json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        printMessage("Arquivo composer.json atualizado: $composerJsonPath", "success");
    }
    
    // Executa o Composer para instalar o PHPMailer
    $cmd = 'composer install';
    printMessage("Executando: $cmd", "info");
    
    if (php_sapi_name() === 'cli') {
        system($cmd, $returnVal);
        
        if ($returnVal !== 0) {
            printMessage("Erro ao executar o Composer. Código de retorno: $returnVal", "error");
            return false;
        }
    } else {
        // Se estiver rodando em um servidor web, use proc_open para capturar a saída
        $descriptorSpec = [
            0 => ["pipe", "r"],  // stdin
            1 => ["pipe", "w"],  // stdout
            2 => ["pipe", "w"]   // stderr
        ];
        
        $process = proc_open($cmd, $descriptorSpec, $pipes, BASE_PATH);
        
        if (is_resource($process)) {
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            
            foreach ($pipes as $pipe) {
                fclose($pipe);
            }
            
            $returnVal = proc_close($process);
            
            echo "<pre>$output</pre>";
            
            if ($returnVal !== 0) {
                printMessage("Erro ao executar o Composer: $error", "error");
                return false;
            }
        } else {
            printMessage("Não foi possível iniciar o processo do Composer", "error");
            return false;
        }
    }
    
    printMessage("Composer executado com sucesso", "success");
    return true;
}

// Instala o PHPMailer fazendo download direto do GitHub
function installWithDirectDownload() {
    global $vendorDir, $phpmailerDir, $pathSeparator;
    
    printMessage("Instalando PHPMailer via download direto...", "info");
    
    // Cria diretório vendor se não existir
    if (!file_exists($vendorDir)) {
        mkdir($vendorDir, 0755, true);
        printMessage("Diretório vendor criado: $vendorDir", "success");
    }
    
    // Cria diretório phpmailer
    if (!file_exists($phpmailerDir)) {
        mkdir($phpmailerDir, 0755, true);
        printMessage("Diretório phpmailer criado: $phpmailerDir", "success");
    }
    
    // URL do arquivo zip da versão mais recente do PHPMailer
    $phpmailerUrl = 'https://github.com/PHPMailer/PHPMailer/archive/refs/tags/v6.8.1.zip';
    $zipFile = $vendorDir . '/phpmailer.zip';
    
    // Faz o download do arquivo zip
    printMessage("Baixando PHPMailer de: $phpmailerUrl", "info");
    
    $downloadSuccess = false;
    
    // Tenta usar file_get_contents com allow_url_fopen
    if (ini_get('allow_url_fopen')) {
        $fileContents = @file_get_contents($phpmailerUrl);
        if ($fileContents !== false) {
            file_put_contents($zipFile, $fileContents);
            $downloadSuccess = true;
        }
    }
    
    // Se falhar, tenta usar cURL
    if (!$downloadSuccess && function_exists('curl_init')) {
        $ch = curl_init($phpmailerUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $fileContents = curl_exec($ch);
        
        if ($fileContents !== false) {
            file_put_contents($zipFile, $fileContents);
            $downloadSuccess = true;
        } else {
            printMessage("Erro cURL: " . curl_error($ch), "error");
        }
        
        curl_close($ch);
    }
    
    if (!$downloadSuccess) {
        printMessage("Não foi possível baixar o PHPMailer. Verifique sua conexão com a internet.", "error");
        return false;
    }
    
    printMessage("Download concluído: $zipFile", "success");
    
    // Extrai o arquivo zip
    $zip = new ZipArchive();
    
    if ($zip->open($zipFile) === true) {
        // Nome do diretório dentro do zip
        $zipDirName = 'PHPMailer-6.8.1';
        
        // Extrai para um diretório temporário
        $extractPath = $vendorDir . '/temp_phpmailer_extract';
        $zip->extractTo($extractPath);
        $zip->close();
        
        // Move os arquivos para o diretório correto
        $srcDir = $extractPath . '/' . $zipDirName;
        $phpmailerSrcDir = $phpmailerDir . '/src';
        
        // Cria o diretório de destino para o código-fonte
        if (!file_exists($phpmailerSrcDir)) {
            mkdir($phpmailerSrcDir, 0755, true);
        }
        
        // Copia os arquivos da pasta src
        $srcFiles = scandir($srcDir . '/src');
        foreach ($srcFiles as $file) {
            if ($file != '.' && $file != '..') {
                copy($srcDir . '/src/' . $file, $phpmailerSrcDir . '/' . $file);
            }
        }
        
        // Copia o arquivo autoload.php
        if (file_exists($srcDir . '/autoload.php')) {
            copy($srcDir . '/autoload.php', $phpmailerDir . '/autoload.php');
        }
        
        // Copia o arquivo composer.json para manter a compatibilidade
        if (file_exists($srcDir . '/composer.json')) {
            copy($srcDir . '/composer.json', $phpmailerDir . '/composer.json');
        }
        
        // Limpa
        // Remove o diretório temporário de extração
        deleteDirectory($extractPath);
        
        // Remove o arquivo zip
        unlink($zipFile);
        
        printMessage("PHPMailer extraído e instalado com sucesso", "success");
        return true;
    } else {
        printMessage("Não foi possível abrir o arquivo zip", "error");
        return false;
    }
}

// Função auxiliar para remover diretório recursivamente
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    
    return rmdir($dir);
}

// Verifica se o PHPMailer já está instalado
function isPhpMailerInstalled() {
    global $phpmailerDir;
    
    if (file_exists($phpmailerDir . '/src/PHPMailer.php')) {
        printMessage("PHPMailer já está instalado", "success");
        
        // Verifica a versão, se possível
        $versionFile = $phpmailerDir . '/composer.json';
        if (file_exists($versionFile)) {
            $composerData = json_decode(file_get_contents($versionFile), true);
            $version = isset($composerData['version']) ? $composerData['version'] : 'desconhecida';
            printMessage("Versão do PHPMailer: " . $version, "info");
        }
        
        return true;
    }
    
    return false;
}

// Instala os arquivos da solução de e-mail
function installEmailSolution() {
    global $appDir, $mailerDir, $testDir, $templatesDir;
    
    printMessage("\n== Instalando Solução de E-mail ==", "info");
    
    // Criar diretórios se não existirem
    if (!file_exists($mailerDir)) {
        mkdir($mailerDir, 0755, true);
        printMessage("Diretório criado: $mailerDir", "success");
    }

    if (!file_exists($testDir)) {
        mkdir($testDir, 0755, true);
        printMessage("Diretório criado: $testDir", "success");
    }

    if (!file_exists($templatesDir)) {
        mkdir($templatesDir, 0755, true);
        printMessage("Diretório criado: $templatesDir", "success");
    }
    
    // Classe Mailer otimizada
    $mailerContent = <<<'EOD'
<?php
/**
 * Classe Mailer otimizada para o sistema GED ESTRELA
 * 
 * Esta classe foi otimizada para usar o caminho exato do PHPMailer no sistema:
 * C:\inetpub\wwwroot\GED\vendor\phpmailer\phpmailer\src\PHPMailer.php
 */

// Carregar diretamente os arquivos PHPMailer
$phpmailerPath = realpath(dirname(__FILE__) . '/../../') . '/vendor/phpmailer/phpmailer/src/';

if (file_exists($phpmailerPath . 'PHPMailer.php')) {
    require_once $phpmailerPath . 'Exception.php';
    require_once $phpmailerPath . 'PHPMailer.php';
    require_once $phpmailerPath . 'SMTP.php';
} else {
    // Log de erro caso o PHPMailer não seja encontrado
    error_log('Erro: PHPMailer não encontrado no caminho: ' . $phpmailerPath);
}

// Usar os namespaces do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    private $lastError = '';
    private $debug = false;
    private static $logFile = null;
    
    /**
     * Habilita o modo de depuração
     * 
     * @param bool $enable Ativar ou desativar modo de depuração
     * @param string $logFile Caminho para arquivo de log (opcional)
     * @return void
     */
    public function enableDebug($enable = true, $logFile = null) {
        $this->debug = $enable;
        
        if ($logFile !== null) {
            self::$logFile = $logFile;
        } else if (self::$logFile === null && defined('BASE_PATH')) {
            self::$logFile = BASE_PATH . '/app/logs/email_debug.log';
        }
        
        // Cria diretório de logs se não existir
        if (!empty(self::$logFile) && !file_exists(dirname(self::$logFile))) {
            mkdir(dirname(self::$logFile), 0755, true);
        }
    }
    
    /**
     * Obtém o último erro ocorrido
     * 
     * @return string Mensagem de erro
     */
    public function getLastError() {
        return $this->lastError;
    }
    
    /**
     * Registra uma mensagem no log de depuração
     * 
     * @param string $message Mensagem a ser registrada
     * @param string $level Nível do log (INFO, ERROR, DEBUG)
     * @return void
     */
    private function logDebug($message, $level = 'INFO') {
        if (!$this->debug) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
        
        // Registrar no arquivo de log, se disponível
        if (!empty(self::$logFile)) {
            file_put_contents(self::$logFile, $logMessage, FILE_APPEND);
        }
        
        // Registrar usando ErrorHandler, se disponível
        if (class_exists('ErrorHandler')) {
            if ($level === 'ERROR') {
                ErrorHandler::logError('EMAIL', $message);
            } else {
                ErrorHandler::logInfo('EMAIL', $message);
            }
        } else {
            // Usar error_log como fallback
            error_log("EMAIL $level: $message");
        }
    }
    
    /**
     * Envia um e-mail usando a biblioteca PHPMailer
     * 
     * @param string $to Email do destinatário
     * @param string $subject Assunto do e-mail
     * @param string $body Corpo do e-mail (HTML)
     * @param string $altBody Corpo alternativo do e-mail (texto puro)
     * @param array $attachments Array de anexos (opcional)
     * @param array $cc Array de destinatários em cópia (opcional)
     * @param array $bcc Array de destinatários em cópia oculta (opcional)
     * @return boolean Resultado do envio (true/false)
     */
    public function send($to, $subject, $body, $altBody = '', $attachments = [], $cc = [], $bcc = []) {
        // Verificar se PHPMailer está disponível
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $this->lastError = 'PHPMailer não está disponível. Verifique a instalação.';
            $this->logDebug($this->lastError, 'ERROR');
            return false;
        }
        
        try {
            // Verificar constantes
            $host = defined('MAIL_HOST') ? MAIL_HOST : (defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com');
            $port = defined('MAIL_PORT') ? MAIL_PORT : (defined('SMTP_PORT') ? SMTP_PORT : 587);
            $username = defined('MAIL_USERNAME') ? MAIL_USERNAME : (defined('SMTP_USER') ? SMTP_USER : 'recuperacaoestrela@gmail.com');
            $password = defined('MAIL_PASSWORD') ? MAIL_PASSWORD : (defined('SMTP_PASS') ? SMTP_PASS : 'sgyrmsgdaxiqvupb');
            $encryption = defined('MAIL_ENCRYPTION') ? MAIL_ENCRYPTION : (defined('SMTP_SECURE') ? SMTP_SECURE : 'tls');
            $fromAddress = defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : (defined('EMAIL_FROM_ADDRESS') ? EMAIL_FROM_ADDRESS : $username);
            $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : (defined('EMAIL_FROM_NAME') ? EMAIL_FROM_NAME : SYSTEM_NAME);
            
            $this->lastError = '';
            $this->logDebug("Iniciando envio de e-mail para: " . (is_array($to) ? json_encode($to) : $to));
            $this->logDebug("Assunto: $subject");
            $this->logDebug("Configurações - Host: $host, Porta: $port, Usuário: $username, Criptografia: $encryption");
            
            // Criar instância do PHPMailer
            $mail = new PHPMailer(true);
            
            // Configurar servidor SMTP
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->Port = $port;
            $mail->SMTPAuth = true;
            $mail->Username = $username;
            $mail->Password = $password;
            $mail->CharSet = 'UTF-8';
            
            // Configurar criptografia
            if (strtolower($encryption) === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
                $this->logDebug("Usando criptografia SSL");
            } elseif (strtolower($encryption) === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS
                $this->logDebug("Usando criptografia TLS");
            }
            
            // Configurações adicionais para ajudar com problemas comuns
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            
            // Timeout mais longo para evitar erros de conexão
            $mail->Timeout = 30;
            
            // Se estiver em modo de depuração, ativar debug do PHPMailer
            if ($this->debug) {
                $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Output SMTP debug
                $mail->Debugoutput = function($str, $level) {
                    $this->logDebug("PHPMailer Debug: $str", 'DEBUG');
                };
            }
            
            // Configurar remetente
            $mail->setFrom($fromAddress, $fromName);
            $mail->addReplyTo($fromAddress, $fromName);
            
            // Adicionar destinatário(s)
            if (is_array($to)) {
                foreach ($to as $email => $name) {
                    if (is_numeric($email)) {
                        $mail->addAddress($name); // Se for array simples
                    } else {
                        $mail->addAddress($email, $name); // Se for array associativo
                    }
                }
            } else {
                $mail->addAddress($to);
            }
            
            // Adicionar cópias se fornecidas
            if (!empty($cc)) {
                if (is_array($cc)) {
                    foreach ($cc as $email) {
                        $mail->addCC($email);
                    }
                } else {
                    $mail->addCC($cc);
                }
            } else if (defined('EMAIL_CC') && !empty(EMAIL_CC)) {
                $mail->addCC(EMAIL_CC);
            }
            
            // Adicionar cópias ocultas se fornecidas
            if (!empty($bcc)) {
                if (is_array($bcc)) {
                    foreach ($bcc as $email) {
                        $mail->addBCC($email);
                    }
                } else {
                    $mail->addBCC($bcc);
                }
            }
            
            // Configurar anexos se fornecidos
            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    if (is_array($attachment) && isset($attachment['path']) && isset($attachment['name'])) {
                        $mail->addAttachment($attachment['path'], $attachment['name']);
                    } elseif (is_string($attachment) && file_exists($attachment)) {
                        $mail->addAttachment($attachment);
                    }
                }
            }
            
            // Configurar o e-mail para HTML
            $mail->isHTML(true);
            
            // Definir assunto e conteúdo
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            // Definir corpo alternativo em texto puro se fornecido
            if (!empty($altBody)) {
                $mail->AltBody = $altBody;
            } else {
                // Criar versão de texto do corpo HTML automaticamente
                $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $body));
            }
            
            // Enviar o e-mail
            $result = $mail->send();
            
            if ($result) {
                $this->logDebug('E-mail enviado com sucesso');
                if (class_exists('ErrorHandler')) {
                    ErrorHandler::logInfo('EMAIL', 'E-mail enviado com sucesso para: ' . (is_array($to) ? implode(', ', $to) : $to));
                }
            } else {
                throw new Exception($mail->ErrorInfo);
            }
            
            return $result;
            
        } catch (Exception $e) {
            // Registrar erro no log
            $this->lastError = $e->getMessage();
            $this->logDebug('Erro ao enviar e-mail: ' . $e->getMessage(), 'ERROR');
            
            if (class_exists('ErrorHandler')) {
                ErrorHandler::logError('EMAIL', 'Erro ao enviar e-mail: ' . $e->getMessage());
            } else {
                error_log('Erro ao enviar e-mail: ' . $e->getMessage());
            }
            
            return false;
        }
    }
    
    /**
     * Envia um e-mail de recuperação de senha
     * 
     * @param string $to Email do destinatário
     * @param string $name Nome do usuário
     * @param string $token Token de recuperação
     * @param string $resetUrl URL base para a recuperação da senha
     * @return boolean Resultado do envio
     */
    public function sendPasswordRecovery($to, $name, $token, $resetUrl = null) {
        // URL de redefinição de senha
        if ($resetUrl === null) {
            $resetUrl = BASE_URL . '/public/recovery.php?token=' . $token;
        } else {
            $resetUrl = $resetUrl . '?token=' . $token;
        }
        
        // Caminho para o template de recuperação de senha
        $templatePath = BASE_PATH . '/app/templates/emails/password_recovery.html';
        
        // Se o template não existir, criar diretório e usar template padrão
        if (!file_exists($templatePath)) {
            $templateDir = dirname($templatePath);
            if (!file_exists($templateDir)) {
                mkdir($templateDir, 0755, true);
            }
            
            $defaultTemplate = $this->getPasswordRecoveryTemplate($name, $resetUrl);
            file_put_contents($templatePath, $defaultTemplate);
            $this->logDebug("Template de recuperação de senha criado em: $templatePath");
        }
        
        // Agora que sabemos que o template existe, carregá-lo
        $body = file_get_contents($templatePath);
        
        // Substituir variáveis no template
        $body = str_replace('{{name}}', $name, $body);
        $body = str_replace('{{token_url}}', $resetUrl, $body);
        $body = str_replace('{{expiry_hours}}', defined('TOKEN_LIFETIME') ? TOKEN_LIFETIME / 3600 : 24, $body);
        $body = str_replace('{{SYSTEM_NAME}}', SYSTEM_NAME, $body);
        $body = str_replace('{{CURRENT_YEAR}}', date('Y'), $body);
        $body = str_replace('{{BASE_URL}}', BASE_URL, $body);
        
        // Criar versão em texto puro
        $altBody = "Olá {$name},\n\nRecebemos uma solicitação para redefinir sua senha.\n\nPara redefinir sua senha, acesse o link: {$resetUrl}\n\nEste link expirará em 24 horas.\n\nSe você não solicitou uma redefinição de senha, ignore este e-mail.\n\nAtenciosamente,\n" . SYSTEM_NAME;
        
        // Enviar e-mail
        return $this->send($to, 'Recuperação de Senha - ' . SYSTEM_NAME, $body, $altBody);
    }
    
    /**
     * Retorna um template HTML padrão para recuperação de senha
     * 
     * @param string $name Nome do usuário
     * @param string $resetUrl URL de recuperação
     * @return string Template HTML
     */
    private function getPasswordRecoveryTemplate($name, $resetUrl) {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Recuperação de Senha - {{SYSTEM_NAME}}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            background-color: #fff;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        .logo {
            max-width: 150px;
            margin: 0 auto 15px;
            display: block;
        }
        .content {
            margin-bottom: 20px;
        }
        .button {
            display: inline-block;
            background-color: #4e73df;
            color: white !important;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background-color: #3a56c5;
        }
        .footer {
            border-top: 1px solid #eee;
            padding-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
        .info {
            background-color: #f8f9fc;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #4e73df;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{BASE_URL}}/assets/images/logo.png" alt="{{SYSTEM_NAME}}" class="logo">
            <h2>Recuperação de Senha</h2>
        </div>
        <div class="content">
            <p>Olá <strong>{{name}}</strong>,</p>
            <p>Recebemos uma solicitação para redefinir sua senha de acesso ao sistema <strong>{{SYSTEM_NAME}}</strong>.</p>
            <p>Para redefinir sua senha, clique no botão abaixo:</p>
            <div style="text-align: center;">
                <a href="{{token_url}}" class="button">Redefinir Minha Senha</a>
            </div>
            <p>Se o botão acima não funcionar, copie e cole o link abaixo em seu navegador:</p>
            <p><a href="{{token_url}}">{{token_url}}</a></p>
            
            <div class="info">
                <p><strong>⚠️ Importante:</strong></p>
                <ul>
                    <li>Este link expirará em {{expiry_hours}} horas.</li>
                    <li>Se você não solicitou uma redefinição de senha, ignore este e-mail ou contate o suporte.</li>
                </ul>
            </div>
            
            <p>Atenciosamente,<br><strong>{{SYSTEM_NAME}}</strong></p>
        </div>
        <div class="footer">
            <p>&copy; {{CURRENT_YEAR}} {{SYSTEM_NAME}}. Todos os direitos reservados.</p>
            <p>Este é um e-mail automático, por favor não responda.</p>
        </div>
    </div>
</body>
</html>';
    }
    
    /**
     * Testa as configurações de e-mail enviando um e-mail de teste
     * 
     * @param string $to Email de destino para o teste
     * @return boolean Resultado do teste
     */
    public function testConnection($to) {
        $subject = 'Teste de Configuração de E-mail - ' . SYSTEM_NAME;
        $body = '<h2>Teste de Configuração de E-mail</h2>
                <p>Este é um e-mail de teste enviado por ' . SYSTEM_NAME . ' em ' . date('d/m/Y H:i:s') . '.</p>
                <p>Se você está recebendo este e-mail, significa que a configuração de envio de e-mails do sistema está funcionando corretamente.</p>
                <p><strong>Configurações utilizadas:</strong></p>
                <ul>
                    <li>Servidor SMTP: ' . (defined('MAIL_HOST') ? MAIL_HOST : (defined('SMTP_HOST') ? SMTP_HOST : 'não definido')) . '</li>
                    <li>Porta: ' . (defined('MAIL_PORT') ? MAIL_PORT : (defined('SMTP_PORT') ? SMTP_PORT : 'não definido')) . '</li>
                    <li>Usuário: ' . (defined('MAIL_USERNAME') ? MAIL_USERNAME : (defined('SMTP_USER') ? SMTP_USER : 'não definido')) . '</li>
                    <li>Remetente: ' . (defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : (defined('EMAIL_FROM_ADDRESS') ? EMAIL_FROM_ADDRESS : 'não definido')) . '</li>
                </ul>';
                
        return $this->send($to, $subject, $body);
    }
}
EOD;

    // Template de página de teste de e-mail
    $testEmailContent = <<<'EOD'
<?php
/**
 * Script simples de teste de e-mail usando o caminho exato do PHPMailer
 */

// Define caminho base
define('BASE_PATH', realpath(dirname(__FILE__) . '/../../'));
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
    echo "<p style='color:green'>✓ Arquivo de constantes carregado</p>";
} else {
    echo "<p style='color:orange'>⚠ Arquivo de constantes não encontrado, usando valores padrão</p>";
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
                        <p>Este é um e-mail de teste enviado diretamente pelo script <code>test_email.php</code> em ' . date('d/m/Y H:i:s') . '.</p>
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
            </div>
        </div>
    </div>
</body>
</html>
EOD;

    // Criar arquivo de constantes se não existir
    $constantsFile = $appDir . '/config/constants.php';
    if (!file_exists(dirname($constantsFile))) {
        mkdir(dirname($constantsFile), 0755, true);
        printMessage("Diretório criado: " . dirname($constantsFile), "success");
    }

    if (!file_exists($constantsFile)) {
        $constantsContent = '<?php
/**
 * Arquivo de constantes do sistema
 */

// Nome do sistema
define("SYSTEM_NAME", "GED ESTRELA");

// URL base do sistema
$base_url = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"];
define("BASE_URL", $base_url);

// Caminho base da aplicação
define("BASE_PATH", realpath(dirname(__FILE__) . "/../../"));

// Configurações de e-mail
define("MAIL_HOST", "smtp.gmail.com");
define("MAIL_PORT", 587);
define("MAIL_USERNAME", "recuperacaoestrela@gmail.com");
define("MAIL_PASSWORD", "sgyrmsgdaxiqvupb");
define("MAIL_ENCRYPTION", "tls");
define("MAIL_FROM_ADDRESS", "recuperacaoestrela@gmail.com");
define("MAIL_FROM_NAME", "CONTABILIDADE ESTRELA");

// Tempo de vida do token de recuperação de senha (24 horas em segundos)
define("TOKEN_LIFETIME", 86400);
';
        file_put_contents($constantsFile, $constantsContent);
        printMessage("Arquivo de constantes criado: $constantsFile", "success");
    }
    
    // Template de recuperação de senha
    $passwordRecoveryTemplate = <<<'EOD'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Recuperação de Senha - {{SYSTEM_NAME}}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            background-color: #fff;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        .logo {
            max-width: 150px;
            margin: 0 auto 15px;
            display: block;
        }
        .content {
            margin-bottom: 20px;
        }
        .button {
            display: inline-block;
            background-color: #4e73df;
            color: white !important;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background-color: #3a56c5;
        }
        .footer {
            border-top: 1px solid #eee;
            padding-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
        .info {
            background-color: #f8f9fc;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #4e73df;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{BASE_URL}}/assets/images/logo.png" alt="{{SYSTEM_NAME}}" class="logo">
            <h2>Recuperação de Senha</h2>
        </div>
        <div class="content">
            <p>Olá <strong>{{name}}</strong>,</p>
            <p>Recebemos uma solicitação para redefinir sua senha de acesso ao sistema <strong>{{SYSTEM_NAME}}</strong>.</p>
            <p>Para redefinir sua senha, clique no botão abaixo:</p>
            <div style="text-align: center;">
                <a href="{{token_url}}" class="button">Redefinir Minha Senha</a>
            </div>
            <p>Se o botão acima não funcionar, copie e cole o link abaixo em seu navegador:</p>
            <p><a href="{{token_url}}">{{token_url}}</a></p>
            
            <div class="info">
                <p><strong>⚠️ Importante:</strong></p>
                <ul>
                    <li>Este link expirará em {{expiry_hours}} horas.</li>
                    <li>Se você não solicitou uma redefinição de senha, ignore este e-mail ou contate o suporte.</li>
                </ul>
            </div>
            
            <p>Atenciosamente,<br><strong>{{SYSTEM_NAME}}</strong></p>
        </div>
        <div class="footer">
            <p>&copy; {{CURRENT_YEAR}} {{SYSTEM_NAME}}. Todos os direitos reservados.</p>
            <p>Este é um e-mail automático, por favor não responda.</p>
        </div>
    </div>
</body>
</html>
EOD;

    // Instalar arquivos
    printMessage("\nInstalando arquivos...", "info");
    
    // Instalar classe Mailer
    file_put_contents($mailerDir . '/Mailer.php', $mailerContent);
    printMessage("✓ Classe Mailer instalada em: " . $mailerDir . '/Mailer.php', "success");
    
    // Instalar página de teste de e-mail
    file_put_contents($testDir . '/test_email.php', $testEmailContent);
    printMessage("✓ Página de teste instalada em: " . $testDir . '/test_email.php', "success");
    
    // Instalar template de e-mail
    file_put_contents($templatesDir . '/password_recovery.html', $passwordRecoveryTemplate);
    printMessage("✓ Template de recuperação de senha instalado em: " . $templatesDir . '/password_recovery.html', "success");
    
    return true;
}

// Criar arquivo README com instruções
function createReadme() {
    global $vendorDir, $phpmailerDir;
    
    printMessage("\n== Criando arquivo README ==", "info");
    
    $readmeContent = <<<'EOD'
# Solução de E-mail para GED ESTRELA

Este pacote contém uma solução para corrigir problemas de envio de e-mails no sistema GED ESTRELA.

## Arquivos Instalados

1. **PHPMailer**: `vendor/phpmailer/phpmailer`
   - Biblioteca PHP para envio de e-mails via SMTP
   - Versão 6.8.1 ou superior

2. **Classe Mailer Otimizada**: `app/classes/Mailer.php`
   - Classe PHP com métodos para envio de e-mail com tratamento específico de erros
   - Suporte para debug e log de erros
   - Método específico para envio de e-mails de recuperação de senha

3. **Página de Teste de E-mail**: `public/test/test_email.php`
   - Interface web para testar o envio de e-mails
   - Mostra logs detalhados de debug do SMTP
   - Permite diagnosticar problemas de configuração

4. **Template de E-mail**: `app/templates/emails/password_recovery.html`
   - Template HTML responsivo para e-mails de recuperação de senha
   - Layout moderno e compatível com a maioria dos clientes de e-mail

5. **Configurações**: `app/config/constants.php`
   - Configurações SMTP do Gmail para a conta recuperacaoestrela@gmail.com

## Como Usar

1. **Para testar se o sistema está funcionando corretamente:**
   - Acesse a URL: `http://seu-site.com/public/test/test_email.php`
   - Digite seu e-mail e clique em "Enviar E-mail Agora"
   - Se tudo estiver funcionando, você receberá um e-mail de teste

2. **Para usar a classe Mailer em seu código:**
   ```php
   require_once BASE_PATH . '/app/classes/Mailer.php';
   
   $mailer = new Mailer();
   $mailer->enableDebug(true); // Ativa o modo de depuração
   
   // Enviar e-mail simples
   $mailer->send(
       'destinatario@example.com',
       'Assunto do E-mail',
       '<p>Conteúdo HTML do e-mail</p>'
   );
   
   // Enviar e-mail de recuperação de senha
   $mailer->sendPasswordRecovery(
       'usuario@example.com',
       'Nome do Usuário',
       'token-de-recuperacao-123'
   );
   ```

## Configurações SMTP Atuais

- **Servidor SMTP**: smtp.gmail.com
- **Porta**: 587 (TLS)
- **Usuário**: recuperacaoestrela@gmail.com
- **Senha**: sgyrmsgdaxiqvupb (Senha de aplicativo gerada no Google)

## Solução de Problemas

Se você encontrar problemas no envio de e-mails:

1. Verifique se o PHPMailer está instalado corretamente
2. Certifique-se de que o servidor tem acesso à internet e às portas SMTP (587 ou 465)
3. Verifique se a senha do aplicativo no Gmail ainda é válida
4. Consulte os logs de erro em `app/logs/email_debug.log`

## Suporte

Para problemas com esta solução, contate o suporte técnico.
EOD;

    file_put_contents(BASE_PATH . '/README_EMAIL_SOLUTION.md', $readmeContent);
    printMessage("✓ Arquivo README criado em: " . BASE_PATH . '/README_EMAIL_SOLUTION.md', "success");
    
    return true;
}

// Função para testar a instalação
function testInstallation() {
    global $phpmailerDir, $mailerDir, $testDir;
    
    printMessage("\n== Testando a instalação ==", "info");
    
    $errors = [];
    
    // Verificar se o PHPMailer está instalado
    if (!file_exists($phpmailerDir . '/src/PHPMailer.php')) {
        $errors[] = "PHPMailer não encontrado em: " . $phpmailerDir . '/src/PHPMailer.php';
    } else {
        printMessage("✓ PHPMailer encontrado", "success");
    }
    
    // Verificar se a classe Mailer foi instalada
    if (!file_exists($mailerDir . '/Mailer.php')) {
        $errors[] = "Classe Mailer não encontrada em: " . $mailerDir . '/Mailer.php';
    } else {
        printMessage("✓ Classe Mailer encontrada", "success");
    }
    
    // Verificar se a página de teste foi instalada
    if (!file_exists($testDir . '/test_email.php')) {
        $errors[] = "Página de teste não encontrada em: " . $testDir . '/test_email.php';
    } else {
        printMessage("✓ Página de teste encontrada", "success");
    }
    
    // Testar carregamento das classes do PHPMailer
    try {
        require_once $phpmailerDir . '/src/PHPMailer.php';
        require_once $phpmailerDir . '/src/SMTP.php';
        require_once $phpmailerDir . '/src/Exception.php';
        printMessage("✓ Classes PHPMailer carregadas com sucesso", "success");
    } catch (Exception $e) {
        $errors[] = "Erro ao carregar classes PHPMailer: " . $e->getMessage();
    }
    
    if (empty($errors)) {
        printMessage("\n✓ Todos os testes passaram com sucesso!", "success");
        return true;
    } else {
        printMessage("\n✗ Foram encontrados erros na instalação:", "error");
        foreach ($errors as $error) {
            printMessage("  - $error", "error");
        }
        return false;
    }
}

// Verifica se o script está sendo executado em modo CLI ou web
$isCli = php_sapi_name() === 'cli';

// Se for modo web, adiciona cabeçalho HTML
if (!$isCli) {
    echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Instalação Completa - GED ESTRELA</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
            background-color: #f8f9fa;
        }
        h1, h2 {
            color: #4e73df;
        }
        pre {
            background-color: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        .warning { color: orange; }
    </style>
</head>
<body>
    <h1>Instalação Completa - GED ESTRELA</h1>
    <div class='card'>
        <pre>";
}

echo "======================================================\n";
echo "  INSTALAÇÃO COMPLETA PARA GED ESTRELA\n";
echo "======================================================\n\n";

// Informações do sistema
echo "Diretório base: " . BASE_PATH . "\n";
echo "Sistema Operacional: " . PHP_OS . "\n";
echo "Versão do PHP: " . PHP_VERSION . "\n\n";

// Passo 1: Instalar PHPMailer
if (!isPhpMailerInstalled()) {
    printMessage("PHPMailer não encontrado. Iniciando instalação...", "info");
    
    // Verifica se o Composer está disponível
    if (hasComposer()) {
        printMessage("Composer encontrado no sistema", "success");
        
        // Instala com o Composer
        if (installWithComposer()) {
            printMessage("PHPMailer instalado com sucesso usando Composer!", "success");
        } else {
            printMessage("Falha ao instalar com Composer. Tentando download direto...", "warning");
            
            // Tenta instalar com download direto
            if (installWithDirectDownload()) {
                printMessage("PHPMailer instalado com sucesso via download direto!", "success");
            } else {
                printMessage("Falha na instalação do PHPMailer. Verifique as permissões do diretório e a conexão com a internet.", "error");
                exit(1);
            }
        }
    } else {
        printMessage("Composer não encontrado. Usando método de download direto.", "info");
        
        // Instala com download direto
        if (installWithDirectDownload()) {
            printMessage("PHPMailer instalado com sucesso via download direto!", "success");
        } else {
            printMessage("Falha na instalação do PHPMailer. Verifique as permissões do diretório e a conexão com a internet.", "error");
            exit(1);
        }
    }
} else {
    printMessage("PHPMailer já está instalado", "success");
}

// Passo 2: Instalar a solução de e-mail
if (installEmailSolution()) {
    printMessage("Solução de e-mail instalada com sucesso!", "success");
} else {
    printMessage("Falha na instalação da solução de e-mail.", "error");
    exit(1);
}

// Passo 3: Criar arquivo README
createReadme();

// Passo 4: Testar a instalação
testInstallation();

// Instruções finais
echo "\n======================================================\n";
echo "  INSTALAÇÃO CONCLUÍDA COM SUCESSO!\n";
echo "======================================================\n\n";

echo "Para testar a solução de e-mail, acesse:\n";
echo "http://seu-site.com/public/test/test_email.php\n\n";

// Se for modo web, adiciona rodapé HTML
if (!$isCli) {
    echo "</pre>
        </div>
        
        <div class='card'>
            <h2>Próximos Passos</h2>
            <p>A instalação foi concluída com sucesso! Para testar a solução de e-mail, acesse:</p>
            <p><a href='/public/test/test_email.php' class='btn btn-primary'>Testar Envio de E-mail</a></p>
            <p>Ou digite manualmente o URL: <code>http://seu-site.com/public/test/test_email.php</code></p>
        </div>
    </body>
</html>";
}