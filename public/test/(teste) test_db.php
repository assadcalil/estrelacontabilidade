<?php
/**
 * Script para testar a conexão com o banco de dados
 * 
 * @author Thiago Calil Assad
 * @created <?= date('Y-m-d') ?>
 */

// Define caminho base
define('BASE_PATH', __DIR__);

// Configurações de exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclui arquivos de configuração essenciais
require_once BASE_PATH . '/app/config/constants.php';
require_once BASE_PATH . '/app/config/database.php';

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Teste de Conexão com o Banco de Dados</title>
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'>
</head>
<body class='bg-light'>
    <div class='container mt-5'>
        <div class='card shadow'>
            <div class='card-header bg-primary text-white'>
                <h4 class='mb-0'>Teste de Conexão com o Banco de Dados</h4>
            </div>
            <div class='card-body'>";

try {
    // Tenta obter uma instância do banco de dados
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "<div class='alert alert-success mb-4'>
            <h5 class='alert-heading'><i class='bi bi-check-circle-fill me-2'></i>Conexão estabelecida com sucesso!</h5>
            <hr>
            <p class='mb-0'>A conexão com o banco de dados <strong>" . DB_NAME . "</strong> foi estabelecida com sucesso.</p>
          </div>";
    
    // Tenta executar uma consulta simples
    try {
        // Verifica se a tabela users existe
        $stmt = $connection->query("SHOW TABLES LIKE 'users'");
        $userTableExists = $stmt->rowCount() > 0;
        
        if ($userTableExists) {
            // Conta quantos usuários existem
            $stmt = $connection->query("SELECT COUNT(*) FROM users");
            $userCount = $stmt->fetchColumn();
            
            echo "<div class='alert alert-info'>
                    <h5 class='alert-heading'><i class='bi bi-info-circle-fill me-2'></i>Tabela de usuários encontrada!</h5>
                    <p>Existe(m) <strong>$userCount</strong> usuário(s) no banco de dados.</p>
                  </div>";
            
            // Lista as tabelas existentes
            $stmt = $connection->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "<h5 class='mt-4'>Tabelas encontradas:</h5>
                  <ul class='list-group'>";
            
            foreach ($tables as $table) {
                echo "<li class='list-group-item'>$table</li>";
            }
            
            echo "</ul>";
        } else {
            echo "<div class='alert alert-warning'>
                    <h5 class='alert-heading'><i class='bi bi-exclamation-triangle-fill me-2'></i>Tabela de usuários não encontrada!</h5>
                    <p>A tabela 'users' não foi encontrada no banco de dados. Você precisa criar a estrutura do banco de dados.</p>
                  </div>";
        }
    } catch (PDOException $e) {
        echo "<div class='alert alert-warning'>
                <h5 class='alert-heading'><i class='bi bi-exclamation-triangle-fill me-2'></i>Consulta falhou!</h5>
                <p>A conexão foi estabelecida, mas ocorreu um erro ao executar consultas.</p>
                <hr>
                <p class='mb-0'>Erro: " . $e->getMessage() . "</p>
              </div>";
    }
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>
            <h5 class='alert-heading'><i class='bi bi-x-circle-fill me-2'></i>Falha na conexão!</h5>
            <p>Não foi possível conectar ao banco de dados.</p>
            <hr>
            <p class='mb-0'>Erro: " . $e->getMessage() . "</p>
          </div>";
}

echo "    </div>
            <div class='card-footer'>
                <a href='" . BASE_URL . "' class='btn btn-primary'>Voltar para a página inicial</a>
            </div>
        </div>
    </div>
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";