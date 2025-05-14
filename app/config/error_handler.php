<?php
/**
 * Sistema de tratamento de erros
 * 
 * @author Thiago Calil Assad
 * @created <?= date('Y-m-d') ?>
 */

// Previne acesso direto ao arquivo
if (!defined('BASE_PATH')) {
    http_response_code(403);
    exit('Acesso proibido');
}

/**
 * Classe para gerenciar e tratar erros do sistema
 */
class ErrorHandler {
    // Níveis de erro
    const DEBUG = 1;
    const INFO = 2;
    const WARNING = 3;
    const ERROR = 4;
    const CRITICAL = 5;
    
    // Mapeamento de níveis para nomes legíveis
    private static $levelNames = [
        self::DEBUG => 'DEBUG',
        self::INFO => 'INFO',
        self::WARNING => 'WARNING',
        self::ERROR => 'ERROR',
        self::CRITICAL => 'CRITICAL'
    ];
    
    // Flag para indicar se estamos em modo de produção
    private static $productionMode = false;
    
    // Armazena erros para a sessão atual
    private static $errors = [];
    
    /**
     * Inicializa o tratamento de erros
     */
    public static function init($productionMode = false) {
        self::$productionMode = $productionMode;
        
        // Configura handlers para diferentes tipos de erros
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleFatalError']);
        
        // Configuração de exibição de erros baseada no modo
        if ($productionMode) {
            ini_set('display_errors', 0);
            ini_set('display_startup_errors', 0);
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        }
        
        // Cria diretório de logs se não existir
        if (!file_exists(LOG_PATH)) {
            mkdir(LOG_PATH, 0755, true);
        }
    }
    
    /**
     * Trata erros PHP
     */
    public static function handleError($errno, $errstr, $errfile, $errline) {
        // Não reportar se o erro estiver desativado pela configuração do error_reporting
        if (!(error_reporting() & $errno)) {
            return;
        }
        
        $level = self::mapErrorLevel($errno);
        $message = "$errstr in $errfile on line $errline";
        
        self::logToFile($level, 'PHP_ERROR', $message);
        self::addError($level, $message);
        
        if ($level >= self::ERROR) {
            self::displayError($level, $message, $errfile, $errline);
        }
        
        // Retorna true para impedir o tratamento padrão do PHP
        return true;
    }
    
    /**
     * Trata exceções não capturadas
     */
    public static function handleException($exception, $customMessage = null) {
        $message = $customMessage ?? $exception->getMessage();
        $file = $exception->getFile();
        $line = $exception->getLine();
        $trace = $exception->getTraceAsString();
        
        self::logToFile(self::ERROR, 'EXCEPTION', "$message in $file on line $line\nTrace: $trace");
        self::addError(self::ERROR, $message, $trace);
        
        self::displayError(self::ERROR, $message, $file, $line, $trace);
    }
    
    /**
     * Trata erros fatais que ocorrem durante o desligamento do PHP
     */
    public static function handleFatalError() {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $level = self::mapErrorLevel($error['type']);
            $message = $error['message'];
            $file = $error['file'];
            $line = $error['line'];
            
            self::logToFile($level, 'FATAL_ERROR', "$message in $file on line $line");
            self::displayError($level, $message, $file, $line);
        }
    }
    
    /**
     * Registra erro ou mensagem no log
     */
    public static function logError($category, $message, $level = self::ERROR, $context = []) {
        self::logToFile($level, $category, $message, $context);
        self::addError($level, $message);
        
        return true;
    }

    public static function logInfo($module, $message) {
        self::logToFile($module, $message, 'INFO');
        self::addError($level, $message);
    }
    
    /**
     * Adiciona um erro à lista de erros da sessão atual
     */
    private static function addError($level, $message, $trace = null) {
        self::$errors[] = [
            'level' => $level,
            'levelName' => self::$levelNames[$level] ?? 'UNKNOWN',
            'message' => $message,
            'trace' => $trace,
            'timestamp' => time()
        ];
        
        // Armazena na sessão para acesso posterior
        if (isset($_SESSION) && session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['system_errors'] = self::$errors;
        }
    }
    
    /**
     * Escreve log em arquivo
     */
    private static function logToFile($level, $category, $message, $context = []) {
        $levelName = self::$levelNames[$level] ?? 'UNKNOWN';
        $timestamp = date('Y-m-d H:i:s');
        $logFile = LOG_PATH . '/' . LOG_FILE_PREFIX . date('Y-m-d') . '.log';
        
        // Formata contexto se disponível
        $contextStr = '';
        if (!empty($context)) {
            $contextStr = ' Context: ' . json_encode($context);
        }
        
        // IP do usuário e URL atual
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
        $url = $_SERVER['REQUEST_URI'] ?? 'Unknown URL';
        $userId = $_SESSION['user_id'] ?? 'Not logged in';
        
        $logEntry = "[$timestamp][$levelName][$category][$ip][$url][User:$userId] $message$contextStr" . PHP_EOL;
        
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * Exibe erro para o usuário
     */
    private static function displayError($level, $message, $file = null, $line = null, $trace = null) {
        // Em produção, mostrar mensagem genérica para o usuário
        if (self::$productionMode) {
            $errorMessage = "Ocorreu um erro no sistema. Por favor, tente novamente mais tarde ou entre em contato com o suporte.";
            $errorDetails = "Código de referência: " . uniqid('ERR-');
            
            // Registrar o erro com ID para rastreamento
            self::logToFile(self::ERROR, 'DISPLAY_ERROR', "Error ID: $errorDetails - $message");
        } else {
            // Em desenvolvimento, mostrar detalhes completos
            $errorMessage = htmlspecialchars($message);
            $errorDetails = '';
            
            if ($file && $line) {
                $errorDetails .= "Arquivo: " . htmlspecialchars($file) . " (linha " . $line . ")";
            }
            
            if ($trace) {
                $errorDetails .= "<br><br>Stack Trace:<br>" . nl2br(htmlspecialchars($trace));
            }
        }
        
        // Se for uma solicitação AJAX, retornar JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => $errorMessage,
                'details' => self::$productionMode ? $errorDetails : $errorDetails
            ]);
            exit;
        }
        
        // Se o buffer de saída estiver ativo, limpar
        if (ob_get_length()) {
            ob_clean();
        }
        
        // Exibir página de erro
        if (!headers_sent()) {
            if ($level >= self::ERROR) {
                http_response_code(500);
            }
            header('Content-Type: text/html; charset=utf-8');
        }
        
        // Exibe página de erro usando modal Bootstrap
        echo '<!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Erro - ' . SYSTEM_NAME . '</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body>
            <div class="modal d-block" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">Erro no Sistema</h5>
                        </div>
                        <div class="modal-body">
                            <p class="mb-3">' . $errorMessage . '</p>';
                            
        if (!empty($errorDetails)) {
            echo '<div class="alert alert-secondary small">
                    <strong>Detalhes técnicos:</strong><br>
                    ' . $errorDetails . '
                  </div>';
        }
                            
        echo '      </div>
                        <div class="modal-footer">
                            <a href="' . BASE_URL . '" class="btn btn-secondary">Voltar para a página inicial</a>
                            <button type="button" class="btn btn-primary" onclick="window.history.back();">Voltar</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-backdrop fade show"></div>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        </body>
        </html>';
        
        exit;
    }
    
    /**
     * Converte níveis de erro do PHP para níveis da nossa classe
     */
    private static function mapErrorLevel($phpErrorLevel) {
        switch ($phpErrorLevel) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
                return self::ERROR;
                
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                return self::WARNING;
                
            case E_NOTICE:
            case E_USER_NOTICE:
                return self::INFO;
                
            case E_STRICT:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                return self::DEBUG;
                
            default:
                return self::INFO;
        }
    }
    
    /**
     * Obtém todos os erros registrados
     */
    public static function getErrors() {
        return self::$errors;
    }
    
    /**
     * Limpa todos os erros
     */
    public static function clearErrors() {
        self::$errors = [];
        
        if (isset($_SESSION) && session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['system_errors'] = [];
        }
    }
    
    /**
     * Verifica se existem erros de um nível específico
     */
    public static function hasErrors($level = null) {
        if ($level === null) {
            return !empty(self::$errors);
        }
        
        foreach (self::$errors as $error) {
            if ($error['level'] >= $level) {
                return true;
            }
        }
        
        return false;
    }
}