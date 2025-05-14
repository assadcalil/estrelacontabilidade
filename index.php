<?php
/**
 * Redireciona para a página principal do sistema
 * 
 * @author Thiago Calil Assad
 * @created <?= date('Y-m-d') ?>
 */

// Define caminho base
define('BASE_PATH', __DIR__);

// Inclui configurações básicas
require_once BASE_PATH . '/app/config/constants.php';

// Redireciona para a página de login
header('Location: ' . BASE_URL . '/public/login.php');
exit;