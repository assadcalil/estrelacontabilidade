<?php
/**
 * Gerenciamento de sessões
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
 * Classe para gerenciar sessões e prevenção de logins simultâneos
 */
class SessionManager {
    /**
     * Inicializa a sessão com configurações seguras
     * 
     * @return bool True se a sessão foi iniciada com sucesso
     */
    public static function init() {
        // Verifica se a sessão já está ativa
        if (session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }
        
        // Configura os parâmetros da sessão para segurança
        session_name(SESSION_NAME);
        
        $cookieParams = [
            'lifetime' => SESSION_LIFETIME,
            'path' => SESSION_PATH,
            'domain' => SESSION_DOMAIN,
            'secure' => SESSION_SECURE,
            'httponly' => SESSION_HTTPONLY,
            'samesite' => 'Lax' // Protege contra ataques CSRF
        ];
        
        session_set_cookie_params($cookieParams);
        
        // Inicia a sessão
        if (session_start()) {
            // Regenera o ID da sessão periodicamente para melhorar a segurança
            if (!isset($_SESSION['last_regeneration']) || 
                (time() - $_SESSION['last_regeneration']) > 1800) {
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Destrói a sessão atual
     * 
     * @return bool True se a sessão foi destruída com sucesso
     */
    public static function destroy() {
        // Verifica se a sessão está ativa
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return true;
        }
        
        // Limpa todas as variáveis de sessão
        $_SESSION = [];
        
        // Destrói o cookie de sessão
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        // Destrói a sessão
        return session_destroy();
    }
    
    /**
     * Verifica se o usuário está logado
     * 
     * @return bool True se o usuário estiver logado
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Obtém o ID do usuário logado
     * 
     * @return int|null ID do usuário ou null se não estiver logado
     */
    public static function getUserId() {
        return self::isLoggedIn() ? $_SESSION['user_id'] : null;
    }
    
    /**
     * Obtém o tipo do usuário logado
     * 
     * @return int|null Tipo do usuário ou null se não estiver logado
     */
    public static function getUserType() {
        return isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;
    }
    
    /**
     * Verifica se o usuário tem um tipo específico
     * 
     * @param int|array $types Tipo(s) de usuário para verificar
     * @return bool True se o usuário tiver o tipo especificado
     */
    public static function hasUserType($types) {
        if (!self::isLoggedIn()) {
            return false;
        }
        
        if (!is_array($types)) {
            $types = [$types];
        }
        
        return in_array($_SESSION['user_type'], $types);
    }
    
    /**
     * Verifica e gerencia múltiplas sessões do mesmo usuário
     * 
     * @param int $userId ID do usuário
     * @return bool|string True se não houver conflito, ou token da sessão ativa
     */
    public static function checkMultipleSession($userId) {
        // Obtém uma conexão com o banco de dados
        $db = Database::getInstance();
        
        try {
            // Verifica se o usuário já tem uma sessão ativa
            $sql = "SELECT session_id, last_activity, ip_address FROM user_sessions 
                    WHERE user_id = ? AND is_active = 1 AND session_id != ?";
            $stmt = $db->query($sql, [$userId, session_id()]);
            
            if ($stmt->rowCount() > 0) {
                $session = $stmt->fetch();
                
                // Verifica se a sessão expirou
                $sessionTimeout = time() - SESSION_LIFETIME;
                if ($session['last_activity'] < $sessionTimeout) {
                    // A sessão expirou, marca como inativa
                    $updateSql = "UPDATE user_sessions SET is_active = 0 
                                WHERE session_id = ?";
                    $db->query($updateSql, [$session['session_id']]);
                    
                    return true;
                }
                
                // Retorna o token de sessão para identificação
                return $session['session_id'];
            }
            
            return true;
        } catch (PDOException $e) {
            ErrorHandler::logError('SESSION', "Erro ao verificar múltiplas sessões: " . $e->getMessage());
            return true; // Em caso de erro, permite o login para não travar o sistema
        }
    }
    
    /**
     * Registra uma nova sessão de usuário no banco de dados
     * 
     * @param int $userId ID do usuário
     * @param int $userType Tipo do usuário
     * @return bool True se a sessão foi registrada com sucesso
     */
    public static function registerSession($userId, $userType) {
        // Obtém uma conexão com o banco de dados
        $db = Database::getInstance();
        
        try {
            // Registra a nova sessão
            $sql = "INSERT INTO user_sessions (user_id, session_id, start_time, last_activity, 
                    ip_address, user_agent, is_active, user_type) 
                    VALUES (?, ?, ?, ?, ?, ?, 1, ?)";
            
            $params = [
                $userId,
                session_id(),
                time(),
                time(),
                $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                $userType
            ];
            
            $db->query($sql, $params);
            
            // Armazena o ID da sessão no banco para referência
            $_SESSION['db_session_id'] = $db->lastInsertId();
            
            return true;
        } catch (PDOException $e) {
            ErrorHandler::logError('SESSION', "Erro ao registrar sessão: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza a atividade da sessão atual
     * 
     * @return bool True se a sessão foi atualizada com sucesso
     */
    public static function updateActivity() {
        if (!self::isLoggedIn() || !isset($_SESSION['db_session_id'])) {
            return false;
        }
        
        // Obtém uma conexão com o banco de dados
        $db = Database::getInstance();
        
        try {
            $sql = "UPDATE user_sessions SET last_activity = ? WHERE id = ?";
            $db->query($sql, [time(), $_SESSION['db_session_id']]);
            
            return true;
        } catch (PDOException $e) {
            ErrorHandler::logError('SESSION', "Erro ao atualizar atividade da sessão: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Encerra uma sessão específica no banco de dados
     * 
     * @param string $sessionId ID da sessão a ser encerrada
     * @return bool True se a sessão foi encerrada com sucesso
     */
    public static function endSession($sessionId) {
        // Obtém uma conexão com o banco de dados
        $db = Database::getInstance();
        
        try {
            $sql = "UPDATE user_sessions SET is_active = 0, end_time = ? WHERE session_id = ?";
            $db->query($sql, [time(), $sessionId]);
            
            return true;
        } catch (PDOException $e) {
            ErrorHandler::logError('SESSION', "Erro ao encerrar sessão: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Encerra todas as outras sessões ativas do mesmo usuário
     * 
     * @param int $userId ID do usuário
     * @return bool True se as sessões foram encerradas com sucesso
     */
    public static function endOtherSessions($userId) {
        // Obtém uma conexão com o banco de dados
        $db = Database::getInstance();
        
        try {
            $sql = "UPDATE user_sessions SET is_active = 0, end_time = ? 
                    WHERE user_id = ? AND session_id != ? AND is_active = 1";
            $db->query($sql, [time(), $userId, session_id()]);
            
            return true;
        } catch (PDOException $e) {
            ErrorHandler::logError('SESSION', "Erro ao encerrar outras sessões: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Limpa sessões antigas e inativas do banco de dados
     * 
     * @param int $olderThan Limpar sessões mais antigas que X segundos (padrão: 30 dias)
     * @return bool True se as sessões foram limpas com sucesso
     */
    public static function cleanupSessions($olderThan = 2592000) {
        // Obtém uma conexão com o banco de dados
        $db = Database::getInstance();
        
        try {
            $cutoff = time() - $olderThan;
            
            // Limpa sessões antigas
            $sql = "DELETE FROM user_sessions WHERE 
                    (is_active = 0 AND end_time < ?) OR 
                    (is_active = 1 AND last_activity < ?)";
            
            $db->query($sql, [$cutoff, $cutoff]);
            
            return true;
        } catch (PDOException $e) {
            ErrorHandler::logError('SESSION', "Erro ao limpar sessões antigas: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém o tempo de bloqueio de login baseado no tipo de usuário
     * 
     * @param int $userType Tipo do usuário
     * @return int Tempo de bloqueio em segundos
     */
    public static function getLoginLockoutTime($userType) {
        // Admin e Editor têm tempo de bloqueio de 1 hora
        if (in_array($userType, [USER_ADMIN, USER_EDITOR])) {
            return LOGIN_LOCKOUT_TIME_ADMIN;
        }
        
        // Para os demais, 15 minutos
        return LOGIN_LOCKOUT_TIME_DEFAULT;
    }
    
    /**
     * Verifica se o usuário está bloqueado por excesso de tentativas de login
     * 
     * @param string $username Nome de usuário
     * @return bool|int False se não estiver bloqueado, ou tempo restante em segundos
     */
    public static function isUserLocked($username) {
        // Obtém uma conexão com o banco de dados
        $db = Database::getInstance();
        
        try {
            // Verifica se o usuário existe e seu tipo
            $sql = "SELECT id, user_type FROM users WHERE username = ? OR email = ?";
            $stmt = $db->query($sql, [$username, $username]);
            
            if ($stmt->rowCount() === 0) {
                return false; // Usuário não existe
            }
            
            $user = $stmt->fetch();
            $userId = $user['id'];
            $userType = $user['user_type'];
            
            // Verifica tentativas de login recentes
            $sql = "SELECT COUNT(*) as attempts, MAX(attempt_time) as last_attempt 
                    FROM login_attempts 
                    WHERE user_id = ? AND success = 0 
                    AND attempt_time > ?";
            
            $lockoutTime = self::getLoginLockoutTime($userType);
            $cutoff = time() - $lockoutTime;
            
            $stmt = $db->query($sql, [$userId, $cutoff]);
            $result = $stmt->fetch();
            
            if ($result['attempts'] >= MAX_LOGIN_ATTEMPTS) {
                // Calcula tempo restante de bloqueio
                $timeElapsed = time() - $result['last_attempt'];
                return $lockoutTime - $timeElapsed;
            }
            
            return false; // Não está bloqueado
        } catch (PDOException $e) {
            ErrorHandler::logError('SESSION', "Erro ao verificar bloqueio de usuário: " . $e->getMessage());
            return false; // Em caso de erro, permite o login para não travar o sistema
        }
    }
    
    /**
     * Registra uma tentativa de login
     * 
     * @param int $userId ID do usuário
     * @param bool $success Se a tentativa foi bem-sucedida
     * @return bool True se a tentativa foi registrada com sucesso
     */
    public static function recordLoginAttempt($userId, $success) {
        // Obtém uma conexão com o banco de dados
        $db = Database::getInstance();
        
        try {
            $sql = "INSERT INTO login_attempts (user_id, attempt_time, ip_address, user_agent, success) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $params = [
                $userId,
                time(),
                $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                $success ? 1 : 0
            ];
            
            $db->query($sql, $params);
            
            return true;
        } catch (PDOException $e) {
            ErrorHandler::logError('SESSION', "Erro ao registrar tentativa de login: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Limpa as tentativas de login malsucedidas de um usuário
     * 
     * @param int $userId ID do usuário
     * @return bool True se as tentativas foram limpas com sucesso
     */
    public static function clearLoginAttempts($userId) {
        // Obtém uma conexão com o banco de dados
        $db = Database::getInstance();
        
        try {
            $sql = "DELETE FROM login_attempts WHERE user_id = ? AND success = 0";
            $db->query($sql, [$userId]);
            
            return true;
        } catch (PDOException $e) {
            ErrorHandler::logError('SESSION', "Erro ao limpar tentativas de login: " . $e->getMessage());
            return false;
        }
    }
}