<?php
/**
 * Classe de autenticação para o sistema GED
 * 
 * @author Thiago Calil Assad
 * @created <?= date('Y-m-d') ?>
 */

// Previne acesso direto ao arquivo
if (!defined('BASE_PATH')) {
    http_response_code(403);
    exit('Acesso proibido');
}

class Auth {
    // Constantes para tipos de usuários
    const ADMIN = 1;
    const EDITOR = 2;
    const TAX = 3;
    const EMPLOYEE = 4;
    const FINANCIAL = 5;
    const CLIENT = 6;
    
    /**
     * Tenta autenticar um usuário
     * 
     * @param string $username Nome de usuário ou e-mail
     * @param string $password Senha
     * @param bool $remember Manter conectado
     * @return array Status da autenticação e mensagem
     */
    public static function login($username, $password, $remember = false) {
        // Obtém conexão com o banco
        $db = Database::getInstance();
        
        try {
            // Verifica se o usuário está bloqueado por excesso de tentativas
            $lockTime = SessionManager::isUserLocked($username);
            if ($lockTime !== false) {
                $minutes = ceil($lockTime / 60);
                return [
                    'success' => false,
                    'message' => "Conta temporariamente bloqueada. Tente novamente em $minutes minutos."
                ];
            }
            
            // Busca o usuário por nome de usuário ou e-mail
            $sql = "SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 1";
            $stmt = $db->query($sql, [$username, $username]);
            
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Usuário não encontrado ou inativo.'
                ];
            }
            
            $user = $stmt->fetch();
            
            // Verifica a senha
            if (!password_verify($password, $user['password'])) {
                // Registra tentativa falha
                SessionManager::recordLoginAttempt($user['id'], false);
                
                // Verifica se excedeu o número de tentativas
                $sql = "SELECT COUNT(*) FROM login_attempts 
                        WHERE user_id = ? AND success = 0 
                        AND attempt_time > ?";
                
                $cutoff = time() - SessionManager::getLoginLockoutTime($user['user_type']);
                $stmt = $db->query($sql, [$user['id'], $cutoff]);
                $attempts = $stmt->fetchColumn();
                
                $remaining = MAX_LOGIN_ATTEMPTS - $attempts;
                
                if ($remaining <= 0) {
                    return [
                        'success' => false,
                        'message' => 'Conta temporariamente bloqueada por excesso de tentativas. Tente novamente mais tarde.'
                    ];
                }
                
                return [
                    'success' => false,
                    'message' => "Senha incorreta. Você tem mais $remaining tentativa(s)."
                ];
            }
            
            // Verifica se há sessões simultâneas
            $sessionCheck = SessionManager::checkMultipleSession($user['id']);
            
            // Inicia a sessão
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['company_id'] = $user['company_id'];
            $_SESSION['token'] = bin2hex(random_bytes(32));
            
            // Se o usuário optou por "lembrar", define um cookie de longa duração
            if ($remember) {
                $rememberToken = bin2hex(random_bytes(32));
                $expiry = time() + (30 * 24 * 60 * 60); // 30 dias
                
                // Salva o token no banco de dados
                $sql = "UPDATE users SET remember_token = ?, token_expiry = ? WHERE id = ?";
                $db->query($sql, [$rememberToken, date('Y-m-d H:i:s', $expiry), $user['id']]);
                
                // Define o cookie
                setcookie('remember_token', $rememberToken, $expiry, '/', '', true, true);
            }
            
            // Registra sessão no banco de dados
            SessionManager::registerSession($user['id'], $user['user_type']);
            
            // Registra login bem-sucedido
            SessionManager::recordLoginAttempt($user['id'], true);
            
            // Atualiza último login
            $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
            $db->query($sql, [$user['id']]);
            
            // Limpa tentativas anteriores
            SessionManager::clearLoginAttempts($user['id']);
            
            // Verifica se deve alertar sobre sessão múltipla
            if (is_string($sessionCheck)) {
                return [
                    'success' => true,
                    'message' => 'Login realizado com sucesso. Você já possui outra sessão ativa.',
                    'warning' => 'multiple_session',
                    'session_id' => $sessionCheck
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Login realizado com sucesso.'
            ];
            
        } catch (PDOException $e) {
            ErrorHandler::logError('AUTH', "Erro ao autenticar usuário: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao processar login. Tente novamente.'
            ];
        }
    }
    
    /**
     * Verifica autenticação através de cookie "lembrar-me"
     * 
     * @return bool Sucesso ou falha
     */
    public static function checkRememberToken() {
        if (!isset($_COOKIE['remember_token']) || empty($_COOKIE['remember_token'])) {
            return false;
        }
        
        $token = $_COOKIE['remember_token'];
        $db = Database::getInstance();
        
        try {
            $sql = "SELECT * FROM users WHERE remember_token = ? AND token_expiry > NOW() AND status = 1";
            $stmt = $db->query($sql, [$token]);
            
            if ($stmt->rowCount() === 0) {
                // Remove o cookie inválido
                setcookie('remember_token', '', time() - 3600, '/', '', true, true);
                return false;
            }
            
            $user = $stmt->fetch();
            
            // Inicia a sessão
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['company_id'] = $user['company_id'];
            $_SESSION['token'] = bin2hex(random_bytes(32));
            
            // Registra sessão no banco de dados
            SessionManager::registerSession($user['id'], $user['user_type']);
            
            // Atualiza último login
            $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
            $db->query($sql, [$user['id']]);
            
            return true;
            
        } catch (PDOException $e) {
            ErrorHandler::logError('AUTH', "Erro ao verificar remember token: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Encerra a sessão do usuário
     * 
     * @return bool Sucesso ou falha
     */
    public static function logout() {
        // Verifica se existe uma sessão ativa
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Registra o encerramento da sessão no banco
        if (session_id()) {
            SessionManager::endSession(session_id());
        }
        
        // Remove cookie de "lembrar-me" se existir
        if (isset($_COOKIE['remember_token'])) {
            // Remove do banco
            $db = Database::getInstance();
            try {
                $sql = "UPDATE users SET remember_token = NULL, token_expiry = NULL WHERE id = ?";
                $db->query($sql, [$_SESSION['user_id']]);
            } catch (PDOException $e) {
                ErrorHandler::logError('AUTH', "Erro ao limpar remember token: " . $e->getMessage());
            }
            
            // Remove cookie
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
        
        // Destrói a sessão
        return SessionManager::destroy();
    }
    
    /**
     * Gera um token para recuperação de senha
     * 
     * @param string $email Email do usuário
     * @return array Status e mensagem
     */
    public static function generatePasswordResetToken($email) {
        $db = Database::getInstance();
        
        try {
            // Verifica se o email existe
            $sql = "SELECT * FROM users WHERE email = ? AND status = 1";
            $stmt = $db->query($sql, [$email]);
            
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'E-mail não encontrado em nossa base.'
                ];
            }
            
            $user = $stmt->fetch();
            
            // Gera um token de recuperação
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', time() + TOKEN_LIFETIME); // 24 horas
            
            // Salva o token no banco
            $sql = "UPDATE users SET password_reset_token = ?, token_expiry = ? WHERE id = ?";
            $db->query($sql, [$token, $expiry, $user['id']]);
            
            return [
                'success' => true,
                'message' => 'Token gerado com sucesso.',
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name']
                ]
            ];
            
        } catch (PDOException $e) {
            ErrorHandler::logError('AUTH', "Erro ao gerar token de recuperação: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao processar solicitação. Tente novamente.'
            ];
        }
    }
    
    /**
     * Verifica se um token de recuperação de senha é válido
     * 
     * @param string $token Token de recuperação
     * @return array Status e dados do usuário
     */
    public static function verifyPasswordResetToken($token) {
        $db = Database::getInstance();
        
        try {
            // Verifica se o token existe e não expirou
            $sql = "SELECT * FROM users WHERE password_reset_token = ? AND token_expiry > NOW() AND status = 1";
            $stmt = $db->query($sql, [$token]);
            
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Token inválido ou expirado.'
                ];
            }
            
            $user = $stmt->fetch();
            
            return [
                'success' => true,
                'message' => 'Token válido.',
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name']
                ]
            ];
            
        } catch (PDOException $e) {
            ErrorHandler::logError('AUTH', "Erro ao verificar token de recuperação: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao processar solicitação. Tente novamente.'
            ];
        }
    }
    
    /**
     * Redefine a senha do usuário usando um token de recuperação
     * 
     * @param string $token Token de recuperação
     * @param string $password Nova senha
     * @return array Status e mensagem
     */
    public static function resetPassword($token, $password) {
        $db = Database::getInstance();
        
        try {
            // Verifica o token
            $verify = self::verifyPasswordResetToken($token);
            
            if (!$verify['success']) {
                return $verify;
            }
            
            // Valida a senha
            if (strlen($password) < PASSWORD_MIN_LENGTH || strlen($password) > PASSWORD_MAX_LENGTH) {
                return [
                    'success' => false,
                    'message' => 'A senha deve ter entre ' . PASSWORD_MIN_LENGTH . ' e ' . PASSWORD_MAX_LENGTH . ' caracteres.'
                ];
            }
            
            // Atualiza a senha e limpa o token
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = ?, password_reset_token = NULL, token_expiry = NULL, updated_at = NOW() WHERE password_reset_token = ?";
            $db->query($sql, [$hashedPassword, $token]);
            
            return [
                'success' => true,
                'message' => 'Senha redefinida com sucesso.'
            ];
            
        } catch (PDOException $e) {
            ErrorHandler::logError('AUTH', "Erro ao redefinir senha: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao processar solicitação. Tente novamente.'
            ];
        }
    }
    
    /**
     * Verifica se o usuário atual tem permissão para acessar um recurso
     * 
     * @param int|array $requiredTypes Tipos de usuário permitidos
     * @return bool Se o usuário tem permissão
     */
    public static function checkPermission($requiredTypes) {
        if (!SessionManager::isLoggedIn()) {
            return false;
        }
        
        if (!is_array($requiredTypes)) {
            $requiredTypes = [$requiredTypes];
        }
        
        $userType = $_SESSION['user_type'] ?? null;
        
        // Administradores sempre têm acesso a tudo
        if ($userType === self::ADMIN) {
            return true;
        }
        
        return in_array($userType, $requiredTypes);
    }
    
    /**
     * Verifica se o usuário atual tem acesso a uma empresa específica
     * 
     * @param int $companyId ID da empresa
     * @return bool|array False se não tem acesso, ou array com detalhes do acesso
     */
    public static function checkCompanyAccess($companyId) {
        if (!SessionManager::isLoggedIn()) {
            return false;
        }
        
        $userId = $_SESSION['user_id'];
        $userType = $_SESSION['user_type'];
        
        // Administradores e Editores têm acesso a todas as empresas
        if (in_array($userType, [self::ADMIN, self::EDITOR])) {
            return [
                'access' => true,
                'level' => 3, // Nível administrativo
                'message' => 'Acesso administrativo concedido.'
            ];
        }
        
        // Para os outros usuários, verificar a empresa principal ou acessos específicos
        $db = Database::getInstance();
        
        try {
            // Verifica se é a empresa principal do usuário
            if ($_SESSION['company_id'] == $companyId) {
                return [
                    'access' => true,
                    'level' => 2, // Nível de edição
                    'message' => 'Acesso como empresa principal.'
                ];
            }
            
            // Verifica acessos adicionais
            $sql = "SELECT access_level FROM user_company_access 
                    WHERE user_id = ? AND company_id = ?";
            $stmt = $db->query($sql, [$userId, $companyId]);
            
            if ($stmt->rowCount() === 0) {
                return false;
            }
            
            $access = $stmt->fetch();
            
            return [
                'access' => true,
                'level' => $access['access_level'],
                'message' => 'Acesso concedido via permissões adicionais.'
            ];
            
        } catch (PDOException $e) {
            ErrorHandler::logError('AUTH', "Erro ao verificar acesso a empresa: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém todas as empresas que o usuário atual tem acesso
     * 
     * @return array Lista de empresas
     */
    public static function getUserCompanies() {
        if (!SessionManager::isLoggedIn()) {
            return [];
        }
        
        $userId = $_SESSION['user_id'];
        $userType = $_SESSION['user_type'];
        $companyId = $_SESSION['company_id'];
        
        $db = Database::getInstance();
        
        try {
            $companies = [];
            
            // Administradores e Editores têm acesso a todas as empresas
            if (in_array($userType, [self::ADMIN, self::EDITOR])) {
                $sql = "SELECT id, emp_name, emp_cnpj FROM companies WHERE status = 1 ORDER BY emp_name";
                $stmt = $db->query($sql);
                
                while ($row = $stmt->fetch()) {
                    $companies[] = [
                        'id' => $row['id'],
                        'name' => $row['emp_name'],
                        'cnpj' => $row['emp_cnpj'],
                        'primary' => false,
                        'access_level' => 3
                    ];
                }
                
                return $companies;
            }
            
            // Para outros usuários, obter empresa principal
            if ($companyId) {
                $sql = "SELECT id, emp_name, emp_cnpj FROM companies WHERE id = ? AND status = 1";
                $stmt = $db->query($sql, [$companyId]);
                
                if ($stmt->rowCount() > 0) {
                    $row = $stmt->fetch();
                    $companies[] = [
                        'id' => $row['id'],
                        'name' => $row['emp_name'],
                        'cnpj' => $row['emp_cnpj'],
                        'primary' => true,
                        'access_level' => 2
                    ];
                }
            }
            
            // Obter empresas adicionais
            $sql = "SELECT c.id, c.emp_name, c.emp_cnpj, uca.access_level 
                    FROM companies c
                    JOIN user_company_access uca ON c.id = uca.company_id
                    WHERE uca.user_id = ? AND c.status = 1 AND c.id != ?
                    ORDER BY c.emp_name";
            $stmt = $db->query($sql, [$userId, $companyId ?: 0]);
            
            while ($row = $stmt->fetch()) {
                $companies[] = [
                    'id' => $row['id'],
                    'name' => $row['emp_name'],
                    'cnpj' => $row['emp_cnpj'],
                    'primary' => false,
                    'access_level' => $row['access_level']
                ];
            }
            
            return $companies;
            
        } catch (PDOException $e) {
            ErrorHandler::logError('AUTH', "Erro ao obter empresas do usuário: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Altera a senha do usuário atual
     * 
     * @param string $currentPassword Senha atual
     * @param string $newPassword Nova senha
     * @return array Status e mensagem
     */
    public static function changePassword($currentPassword, $newPassword) {
        if (!SessionManager::isLoggedIn()) {
            return [
                'success' => false,
                'message' => 'Usuário não autenticado.'
            ];
        }
        
        $userId = $_SESSION['user_id'];
        $db = Database::getInstance();
        
        try {
            // Verificar senha atual
            $sql = "SELECT password FROM users WHERE id = ?";
            $stmt = $db->query($sql, [$userId]);
            
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Usuário não encontrado.'
                ];
            }
            
            $user = $stmt->fetch();
            
            if (!password_verify($currentPassword, $user['password'])) {
                return [
                    'success' => false,
                    'message' => 'Senha atual incorreta.'
                ];
            }
            
            // Validar nova senha
            if (strlen($newPassword) < PASSWORD_MIN_LENGTH || strlen($newPassword) > PASSWORD_MAX_LENGTH) {
                return [
                    'success' => false,
                    'message' => 'A nova senha deve ter entre ' . PASSWORD_MIN_LENGTH . ' e ' . PASSWORD_MAX_LENGTH . ' caracteres.'
                ];
            }
            
            // Atualizar senha
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
            $db->query($sql, [$hashedPassword, $userId]);
            
            return [
                'success' => true,
                'message' => 'Senha alterada com sucesso.'
            ];
            
        } catch (PDOException $e) {
            ErrorHandler::logError('AUTH', "Erro ao alterar senha: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao processar solicitação. Tente novamente.'
            ];
        }
    }
}