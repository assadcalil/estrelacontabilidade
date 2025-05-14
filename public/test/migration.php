<?php
/**
 * Passo 1: Criar estrutura de diretórios
 * Script simples para criar apenas os diretórios necessários
 */

// Desativar exibição de erros para evitar HTTP 500
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Define caminho base
$basePath = __DIR__;
$result = [];

// Lista de diretórios para criar
$directories = [
    // Principais diretórios
    $basePath . '/app/classes',
    $basePath . '/app/logs/email_logs',
    $basePath . '/app/logs/error_logs',
    $basePath . '/public/test',
    $basePath . '/install',
    $basePath . '/docs',

    // Diretórios de upload
    $basePath . '/uploads/documents',
    $basePath . '/uploads/certificates',
    $basePath . '/uploads/boletos',
    $basePath . '/uploads/profile_images',
];

echo "<!DOCTYPE html>
<html>
<head>
    <title>Criação de Diretórios - GED ESTRELA</title>
    <meta charset='utf-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .container { max-width: 800px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Criação de Diretórios - GED ESTRELA</h1>
        <p>Este script cria a nova estrutura de diretórios para o sistema.</p>
        
        <h2>Resultado</h2>
        <table>
            <tr>
                <th>Diretório</th>
                <th>Status</th>
            </tr>";

// Criar cada diretório
foreach ($directories as $dir) {
    // Verificar se já existe
    if (file_exists($dir)) {
        echo "<tr><td>{$dir}</td><td class='success'>Já existe</td></tr>";
        $result[$dir] = 'exists';
    } else {
        // Tentar criar o diretório
        if (@mkdir($dir, 0755, true)) {
            echo "<tr><td>{$dir}</td><td class='success'>Criado com sucesso</td></tr>";
            $result[$dir] = 'created';
        } else {
            echo "<tr><td>{$dir}</td><td class='error'>Falha ao criar</td></tr>";
            $result[$dir] = 'failed';
        }
    }
    // Atualizar o output
    flush();
}

echo "        </table>
        
        <h2>Próximos Passos</h2>
        <p>Execute o passo 2 para copiar os arquivos necessários.</p>
        <p><a href='step2-copy-files.php'>Ir para o Passo 2</a></p>
    </div>
</body>
</html>";