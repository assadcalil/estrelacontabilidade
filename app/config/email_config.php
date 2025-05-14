<?php
/**
 * Configurações para envio de e-mails
 * 
 * @author Thiago Calil Assad
 * @updated <?= date('Y-m-d') ?>
 */

// Previne acesso direto ao arquivo
if (!defined('BASE_PATH')) {
    http_response_code(403);
    exit('Acesso proibido');
}

// Importa as classes do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Carrega o autoloader do Composer (ajuste o caminho conforme necessário)
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
} else {
    // Carregamento manual caso não use Composer
    $phpmailerPath = BASE_PATH . '/vendor/phpmailer/phpmailer/src/';
    if (file_exists($phpmailerPath . 'Exception.php')) {
        require_once $phpmailerPath . 'Exception.php';
        require_once $phpmailerPath . 'PHPMailer.php';
        require_once $phpmailerPath . 'SMTP.php';
    } else {
        // Tenta outra localização comum
        $phpmailerPath = BASE_PATH . '/phpmailer/phpmailer/src/';
        if (file_exists($phpmailerPath . 'Exception.php')) {
            require_once $phpmailerPath . 'Exception.php';
            require_once $phpmailerPath . 'PHPMailer.php';
            require_once $phpmailerPath . 'SMTP.php';
        }
    }
}

// Configurações SMTP - primeira definição mantém compatibilidade retroativa
if (!defined('SMTP_HOST')) define('SMTP_HOST', 'smtp.gmail.com');
if (!defined('SMTP_PORT')) define('SMTP_PORT', 587); // Alterado para 587 (TLS) que é mais compatível
if (!defined('SMTP_USER')) define('SMTP_USER', 'recuperacaoestrela@gmail.com');
if (!defined('SMTP_PASS')) define('SMTP_PASS', 'sgyrmsgdaxiqvupb');
if (!defined('SMTP_SECURE')) define('SMTP_SECURE', 'tls'); // Alterado para TLS para porta 587

// Definir constantes no novo formato para compatibilidade
if (!defined('MAIL_HOST')) define('MAIL_HOST', SMTP_HOST);
if (!defined('MAIL_PORT')) define('MAIL_PORT', SMTP_PORT);
if (!defined('MAIL_USERNAME')) define('MAIL_USERNAME', SMTP_USER);
if (!defined('MAIL_PASSWORD')) define('MAIL_PASSWORD', SMTP_PASS);
if (!defined('MAIL_ENCRYPTION')) define('MAIL_ENCRYPTION', SMTP_SECURE);

// Informações do remetente
if (!defined('EMAIL_FROM_ADDRESS')) define('EMAIL_FROM_ADDRESS', 'recuperacaoestrela@gmail.com');
if (!defined('EMAIL_FROM_NAME')) define('EMAIL_FROM_NAME', 'CONTABILIDADE ESTRELA');

// Manter compatibilidade com novo formato
if (!defined('MAIL_FROM_ADDRESS')) define('MAIL_FROM_ADDRESS', EMAIL_FROM_ADDRESS);
if (!defined('MAIL_FROM_NAME')) define('MAIL_FROM_NAME', EMAIL_FROM_NAME);

// Destinatários padrão
if (!defined('EMAIL_CC')) define('EMAIL_CC', 'cestrela.cancelar@terra.com.br');

// Templates de e-mail
define('EMAIL_TEMPLATES_DIR', BASE_PATH . '/app/templates/emails');

// Assuntos padrão para e-mails
define('EMAIL_SUBJECTS', [
    'password_recovery' => 'Recuperação de Senha - ' . EMAIL_FROM_NAME,
    'new_user' => 'Bem-vindo ao ' . SYSTEM_NAME,
    'new_company' => 'Nova Empresa Cadastrada - ' . SYSTEM_NAME,
    'new_certificate' => 'Novo Certificado Digital - ' . SYSTEM_NAME,
    'certificate_expiring' => 'Alerta: Certificado Digital Expirando - ' . SYSTEM_NAME,
    'login_alert' => 'Alerta de Segurança: Novo Login - ' . SYSTEM_NAME,
    'document_uploaded' => 'Novo Documento Disponível - ' . SYSTEM_NAME
]);

/**
 * Classe para gerenciar configurações e operações relacionadas a e-mails
 */
class EmailConfig {
    /**
     * Obtém o template HTML para um tipo específico de e-mail
     * 
     * @param string $templateName Nome do template
     * @param array $data Dados para substituir no template
     * @return string HTML do template processado
     */
    public static function getTemplate($templateName, $data = []) {
        $templateFile = EMAIL_TEMPLATES_DIR . '/' . $templateName . '.html';
        
        if (!file_exists($templateFile)) {
            // Tenta criar um template padrão se não existir
            self::createDefaultTemplate($templateName);
            
            if (!file_exists($templateFile)) {
                if (class_exists('ErrorHandler')) {
                    ErrorHandler::logError('EMAIL', "Template de e-mail não encontrado: $templateName");
                }
                return self::getDefaultTemplateContent($templateName, $data);
            }
        }
        
        $template = file_get_contents($templateFile);
        
        // Substitui placeholders por valores reais
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $template = str_replace('{{' . $key . '}}', $value, $template);
            }
        }
        
        // Substitui placeholders padrão
        $template = str_replace('{{SYSTEM_NAME}}', SYSTEM_NAME, $template);
        $template = str_replace('{{CURRENT_YEAR}}', date('Y'), $template);
        $template = str_replace('{{BASE_URL}}', BASE_URL, $template);
        
        return $template;
    }
    
    /**
     * Obtém o assunto para um tipo específico de e-mail
     * 
     * @param string $type Tipo de e-mail
     * @return string Assunto do e-mail
     */
    public static function getSubject($type) {
        $subjects = EMAIL_SUBJECTS;
        
        if (array_key_exists($type, $subjects)) {
            return $subjects[$type];
        }
        
        // Retorna um assunto genérico se o tipo não estiver definido
        return SYSTEM_NAME . ' - Notificação';
    }
    
    /**
     * Verifica se um endereço de e-mail é válido
     * 
     * @param string $email Endereço de e-mail
     * @return bool
     */
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Cria o diretório de templates se não existir
     * 
     * @return bool
     */
    public static function ensureTemplateDirectory() {
        if (!file_exists(EMAIL_TEMPLATES_DIR)) {
            return mkdir(EMAIL_TEMPLATES_DIR, 0755, true);
        }
        return true;
    }
    
    /**
     * Cria um template padrão para um tipo de e-mail
     * 
     * @param string $templateName Nome do template
     * @return bool
     */
    public static function createDefaultTemplate($templateName) {
        self::ensureTemplateDirectory();
        
        $templateFile = EMAIL_TEMPLATES_DIR . '/' . $templateName . '.html';
        $content = self::getDefaultTemplateContent($templateName);
        
        if (empty($content)) {
            return false;
        }
        
        return file_put_contents($templateFile, $content) !== false;
    }
    
    /**
     * Obtém o conteúdo padrão para um template específico
     * 
     * @param string $templateName Nome do template
     * @param array $data Dados para substituir no template
     * @return string Conteúdo do template
     */
    public static function getDefaultTemplateContent($templateName, $data = []) {
        $content = '';
        
        switch ($templateName) {
            case 'password_recovery':
                $resetUrl = isset($data['token_url']) ? $data['token_url'] : '{{token_url}}';
                $name = isset($data['name']) ? $data['name'] : '{{name}}';
                
                $content = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Recuperação de Senha</title>
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
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        .content {
            margin-bottom: 20px;
        }
        .button {
            display: inline-block;
            background-color: #4e73df;
            color: white !important;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            border-top: 1px solid #eee;
            padding-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Recuperação de Senha</h2>
        </div>
        <div class="content">
            <p>Olá ' . $name . ',</p>
            <p>Recebemos uma solicitação para redefinir sua senha.</p>
            <p>Para redefinir sua senha, clique no botão abaixo:</p>
            <div style="text-align: center;">
                <a href="' . $resetUrl . '" class="button">Redefinir Senha</a>
            </div>
            <p>Se o botão acima não funcionar, copie e cole o link abaixo em seu navegador:</p>
            <p><a href="' . $resetUrl . '">' . $resetUrl . '</a></p>
            <p>Este link expirará em 24 horas.</p>
            <p>Se você não solicitou uma redefinição de senha, ignore este e-mail.</p>
            <p>Atenciosamente,<br>{{SYSTEM_NAME}}</p>
        </div>
        <div class="footer">
            <p>&copy; {{CURRENT_YEAR}} {{SYSTEM_NAME}}. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>';
                break;
                
            // Adicione mais templates padrão conforme necessário
            
            default:
                $content = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{SYSTEM_NAME}}</title>
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
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        .content {
            margin-bottom: 20px;
        }
        .footer {
            border-top: 1px solid #eee;
            padding-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{SYSTEM_NAME}}</h2>
        </div>
        <div class="content">
            <p>Olá,</p>
            <p>Este é um e-mail automático do sistema {{SYSTEM_NAME}}.</p>
            <p>Atenciosamente,<br>{{SYSTEM_NAME}}</p>
        </div>
        <div class="footer">
            <p>&copy; {{CURRENT_YEAR}} {{SYSTEM_NAME}}. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>';
                break;
        }
        
        // Substitui placeholders padrão
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $content = str_replace('{{' . $key . '}}', $value, $content);
            }
        }
        
        return $content;
    }
}

/**
 * Classe para envio de e-mails usando PHPMailer
 */
class EmailSender {
    /**
     * Envia um e-mail usando as configurações definidas
     * 
     * @param string|array $to Destinatário(s)
     * @param string $subject Assunto do e-mail
     * @param string $body Corpo do e-mail (HTML)
     * @param string $altBody Corpo alternativo (texto plano)
     * @param array $attachments Anexos (opcional)
     * @param array $cc Destinatários em cópia (opcional)
     * @param array $bcc Destinatários em cópia oculta (opcional)
     * @return bool Sucesso ou falha no envio
     */
    public static function send($to, $subject, $body, $altBody = '', $attachments = [], $cc = [], $bcc = []) {
        try {
            $mail = new PHPMailer(true);
            
            // Configurações do servidor
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->Port = SMTP_PORT;
            $mail->CharSet = 'UTF-8';
            
            // Configurar criptografia
            if (SMTP_SECURE === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
            } elseif (SMTP_SECURE === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS
            }
            
            // Timeout mais longo para evitar erros de conexão
            $mail->Timeout = 30;
            
            // Opções adicionais para resolver problemas comuns
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            
            // Configurações de debug (descomente para depuração)
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            
            // Remetente
            $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
            
            // Destinatários
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
            
            // Cópias (CC)
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
            
            // Cópias ocultas (BCC)
            if (!empty($bcc)) {
                if (is_array($bcc)) {
                    foreach ($bcc as $email) {
                        $mail->addBCC($email);
                    }
                } else {
                    $mail->addBCC($bcc);
                }
            }
            
            // Anexos
            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    if (is_array($attachment) && isset($attachment['path'])) {
                        $filename = isset($attachment['name']) ? $attachment['name'] : '';
                        $mail->addAttachment($attachment['path'], $filename);
                    } else {
                        $mail->addAttachment($attachment);
                    }
                }
            }
            
            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            if (!empty($altBody)) {
                $mail->AltBody = $altBody;
            } else {
                // Gera automaticamente a versão em texto plano
                $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $body));
            }
            
            // Enviar o e-mail
            $result = $mail->send();
            
            if ($result) {
                if (class_exists('ErrorHandler')) {
                    ErrorHandler::logInfo('EMAIL', "E-mail enviado com sucesso para: " . (is_array($to) ? json_encode($to) : $to));
                }
            } else {
                throw new Exception($mail->ErrorInfo);
            }
            
            return $result;
            
        } catch (Exception $e) {
            if (class_exists('ErrorHandler')) {
                ErrorHandler::logError('EMAIL', 'Erro ao enviar e-mail: ' . $e->getMessage());
            } else {
                error_log('Erro ao enviar e-mail: ' . $e->getMessage());
            }
            
            return false;
        }
    }
    
    /**
     * Envia um e-mail usando um template
     * 
     * @param string $to Destinatário
     * @param string $templateName Nome do template
     * @param array $data Dados para o template
     * @param array $attachments Anexos (opcional)
     * @param array $cc Destinatários em cópia (opcional)
     * @param array $bcc Destinatários em cópia oculta (opcional)
     * @return bool Sucesso ou falha no envio
     */
    public static function sendTemplate($to, $templateName, $data = [], $attachments = [], $cc = [], $bcc = []) {
        $subject = EmailConfig::getSubject($templateName);
        $body = EmailConfig::getTemplate($templateName, $data);
        
        if (!$body) {
            if (class_exists('ErrorHandler')) {
                ErrorHandler::logError('EMAIL', "Template de e-mail não encontrado: $templateName");
            } else {
                error_log("Template de e-mail não encontrado: $templateName");
            }
            return false;
        }
        
        return self::send($to, $subject, $body, '', $attachments, $cc, $bcc);
    }
    
    /**
     * Testa as configurações de e-mail
     * 
     * @param string $to Destinatário para teste
     * @return bool Sucesso ou falha no envio
     */
    public static function testConnection($to) {
        $subject = 'Teste de Conexão de E-mail - ' . SYSTEM_NAME;
        $body = '<h1>Teste de Conexão de E-mail</h1>';
        $body .= '<p>Este é um e-mail de teste enviado por ' . SYSTEM_NAME . ' em ' . date('d/m/Y H:i:s') . '.</p>';
        $body .= '<p>Se você está recebendo este e-mail, significa que a configuração de envio de e-mails do sistema está funcionando corretamente.</p>';
        $body .= '<p><strong>Configurações utilizadas:</strong></p>';
        $body .= '<ul>';
        $body .= '<li>Servidor SMTP: ' . SMTP_HOST . '</li>';
        $body .= '<li>Porta: ' . SMTP_PORT . '</li>';
        $body .= '<li>Criptografia: ' . SMTP_SECURE . '</li>';
        $body .= '<li>Remetente: ' . EMAIL_FROM_ADDRESS . '</li>';
        $body .= '</ul>';
        
        return self::send($to, $subject, $body);
    }
}