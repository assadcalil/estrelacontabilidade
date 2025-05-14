<?php
/**
 * Definições de constantes para o sistema GED
 * 
 * @author Thiago Calil Assad
 * @created <?= date('Y-m-d') ?>
 */

// Previne acesso direto ao arquivo
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(403);
    exit('Acesso proibido');
}

// Caminho base do sistema
if (!defined('BASE_PATH')) {
    define('BASE_PATH', realpath(dirname(__FILE__) . '/../..'));
}

// URLs da aplicação
// URLs da aplicação
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];
    
    // Ajuste aqui para o caminho correto
    $basePath = '/GED'; // Modifique conforme necessário
    
    define('BASE_URL', $protocol . $domainName . $basePath);
}

// Versão do sistema
define('SYSTEM_VERSION', '1.0.0');
define('SYSTEM_NAME', 'CONTABILIDADE ESTRELA');

// Configurações de sessão
define('SESSION_NAME', 'GED_ESTRELA_SESSION');
define('SESSION_LIFETIME', 86400); // 24 horas em segundos
define('SESSION_PATH', '/');
define('SESSION_DOMAIN', '');
define('SESSION_SECURE', false); // true para HTTPS
define('SESSION_HTTPONLY', true);

// Configurações de segurança
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_MAX_LENGTH', 32);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME_ADMIN', 3600); // 1 hora para admin e editores
define('LOGIN_LOCKOUT_TIME_DEFAULT', 900); // 15 minutos para os demais usuários
define('TOKEN_LIFETIME', 86400); // 24 horas para tokens de recuperação

// Tipos de usuários
define('USER_ADMIN', 1);
define('USER_EDITOR', 2);
define('USER_TAX', 3);
define('USER_EMPLOYEE', 4);
define('USER_FINANCIAL', 5);
define('USER_CLIENT', 6);

// Mapeamento de tipos de usuário para nomes legíveis
define('USER_TYPE_NAMES', [
    USER_ADMIN => 'Administrador',
    USER_EDITOR => 'Editor',
    USER_TAX => 'Fiscal',
    USER_EMPLOYEE => 'Funcionário',
    USER_FINANCIAL => 'Financeiro',
    USER_CLIENT => 'Cliente'
]);

// Configurações de upload
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('UPLOAD_ALLOWED_TYPES', [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'image/jpeg',
    'image/png'
]);
define('UPLOAD_PATH', BASE_PATH . '/uploads');

// Configurações de logs
define('LOG_PATH', BASE_PATH . '/app/logs');
define('LOG_LEVEL', 'DEBUG'); // DEBUG, INFO, WARNING, ERROR, CRITICAL
define('LOG_FILE_PREFIX', 'ged_');

// Status gerais do sistema
define('STATUS_ACTIVE', 1);
define('STATUS_INACTIVE', 0);
define('STATUS_DELETED', -1);
define('STATUS_PENDING', 2);

// Respostas JSON padronizadas
define('JSON_SUCCESS', ['status' => 'success']);
define('JSON_ERROR', ['status' => 'error']);

// Formato de data padrão para exibição e banco de dados
define('DATE_FORMAT', 'd/m/Y');
define('DATETIME_FORMAT', 'd/m/Y H:i:s');
define('DB_DATE_FORMAT', 'Y-m-d');
define('DB_DATETIME_FORMAT', 'Y-m-d H:i:s');

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Locale para formatação de números e datas
setlocale(LC_ALL, 'pt_BR.utf-8', 'pt_BR', 'Portuguese_Brazil');