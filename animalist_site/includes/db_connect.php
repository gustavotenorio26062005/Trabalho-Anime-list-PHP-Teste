<?php
// Configurações do banco de dados
$servername = "localhost"; // Geralmente 'localhost' no XAMPP/WAMP
$username = "root";        // Usuário padrão do MySQL
$password = "";            // Senha padrão (geralmente vazia no XAMPP/WAMP)
$dbname = "animalist_db";  // Nome do banco de dados que você criou

// Cria a conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica se a conexão falhou
if ($conn->connect_error) {
    // Para depuração, exiba o erro. Em produção, você registraria o erro e mostraria uma mensagem amigável.
    die("Conexão falhou: " . $conn->connect_error);
}

// Define o conjunto de caracteres para evitar problemas com acentuação
$conn->set_charset("utf8mb4");

// Inicia a sessão PHP para gerenciar login do usuário
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>