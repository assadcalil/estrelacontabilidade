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
            } else if (method_exists('ErrorHandler', 'logInfo')) {
                ErrorHandler::logInfo('EMAIL', $message);
            } else {
                // Fallback: usar logError para mensagens informativas também
                ErrorHandler::logError('EMAIL', "INFO: $message");
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