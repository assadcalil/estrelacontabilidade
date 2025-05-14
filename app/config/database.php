<?php
/**
 * Configuração de conexão com o banco de dados
 * 
 * @author Thiago Calil Assad
 * @created <?= date('Y-m-d') ?>
 */

// Previne acesso direto ao arquivo
if (!defined('BASE_PATH')) {
    http_response_code(403);
    exit('Acesso proibido');
}

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'GED_estrela');
define('DB_USER', 'root');
define('DB_PASS', '36798541');
define('DB_CHARSET', 'utf8mb4');

/**
 * Classe para gerenciar conexões com o banco de dados
 */
class Database {
    private static $instance = null;
    private $connection;
    private $queryCount = 0;
    private $queryLog = [];

    /**
     * Construtor privado para evitar criação direta do objeto
     */
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Registra conexão bem-sucedida no log
            $this->logActivity('CONNECTION', 'Conexão com o banco de dados estabelecida', true);
            
        } catch (PDOException $e) {
            // Registra erro de conexão
            $this->logActivity('CONNECTION_ERROR', $e->getMessage(), false);
            
            // Redireciona para uma página de erro ou retorna um erro
            ErrorHandler::handleException($e, 'Erro de conexão com o banco de dados');
            exit;
        }
    }

    /**
     * Implementação do padrão Singleton
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Obtém a conexão PDO
     * @return PDO
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Prepara e executa uma query SQL
     * @param string $sql Query SQL com placeholders
     * @param array $params Parâmetros para a query
     * @return PDOStatement
     */
    public function query($sql, $params = []) {
        $startTime = microtime(true);
        
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            
            // Registra a query no log
            $this->queryCount++;
            $this->queryLog[] = [
                'query' => $this->formatQuery($sql, $params),
                'time' => $executionTime,
                'rows' => $stmt->rowCount(),
                'success' => true
            ];
            
            return $stmt;
            
        } catch (PDOException $e) {
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            
            // Registra o erro no log
            $this->queryLog[] = [
                'query' => $this->formatQuery($sql, $params),
                'time' => $executionTime,
                'error' => $e->getMessage(),
                'success' => false
            ];
            
            // Lança a exceção novamente para ser tratada pelo ErrorHandler
            throw $e;
        }
    }

    /**
     * Formata uma query com seus parâmetros para o log
     * @param string $sql
     * @param array $params
     * @return string
     */
    private function formatQuery($sql, $params) {
        $formattedQuery = $sql;
        
        // Substitui placeholders por valores para melhor visualização no log
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $placeholder = is_numeric($key) ? '?' : ':' . $key;
                $formattedValue = is_string($value) ? "'" . $value . "'" : $value;
                $formattedQuery = preg_replace('/'. $placeholder . '/', $formattedValue, $formattedQuery, 1);
            }
        }
        
        return $formattedQuery;
    }

    /**
     * Registra atividade relacionada ao banco de dados
     * @param string $type Tipo de atividade
     * @param string $message Mensagem descritiva
     * @param bool $success Indica se foi uma operação bem-sucedida
     */
    public function logActivity($type, $message, $success = true) {
        if (!file_exists(BASE_PATH . '/app/logs')) {
            mkdir(BASE_PATH . '/app/logs', 0755, true);
        }
        
        $logFile = BASE_PATH . '/app/logs/database_' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp][$type][" . ($success ? 'SUCCESS' : 'ERROR') . "] $message" . PHP_EOL;
        
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    /**
     * Inicia uma transação
     */
    public function beginTransaction() {
        $this->connection->beginTransaction();
    }

    /**
     * Confirma uma transação
     */
    public function commit() {
        $this->connection->commit();
    }

    /**
     * Reverte uma transação
     */
    public function rollback() {
        $this->connection->rollBack();
    }

    /**
     * Verifica se uma tabela existe no banco de dados
     * @param string $tableName Nome da tabela
     * @return bool
     */
    public function tableExists($tableName) {
        $sql = "SHOW TABLES LIKE ?";
        $stmt = $this->query($sql, [$tableName]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Obtém o último ID inserido
     * @return string
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    /**
     * Obtém estatísticas de queries executadas
     * @return array
     */
    public function getQueryStats() {
        return [
            'count' => $this->queryCount,
            'log' => $this->queryLog
        ];
    }

    /**
     * Previne clonagem do objeto (padrão Singleton)
     */
    private function __clone() {}

    /**
     * Fecha a conexão com o banco quando o objeto é destruído
     */
    public function __destruct() {
        $this->connection = null;
    }
}