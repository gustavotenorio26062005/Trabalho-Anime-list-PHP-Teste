<?php
// Configurações do banco de dados.
// As credenciais ficam em includes/config.php (não versionado).
// Copie includes/config.example.php para includes/config.php e ajuste os valores.
$config_file = __DIR__ . '/config.php';
if (!file_exists($config_file)) {
    $config_file = __DIR__ . '/config.example.php';
}
$db_config = require $config_file;

$servername = $db_config['host'];
$username   = $db_config['user'];
$password   = $db_config['password'];
$dbname     = $db_config['database'];

// Cria a conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica se a conexão falhou
if ($conn->connect_error) {
    // Para depuração, exiba o erro. Em produção, você registraria o erro e mostraria uma mensagem amigável.
    error_log("Conexão com o banco falhou: " . $conn->connect_error);
    die("Não foi possível conectar ao banco de dados. Verifique includes/config.php.");
}

// Define o conjunto de caracteres para evitar problemas com acentuação
$conn->set_charset("utf8mb4");

// Inicia a sessão PHP para gerenciar login do usuário
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Funções de proteção CSRF (csrf_token, csrf_field, csrf_validate)
require_once __DIR__ . '/csrf.php';
?>