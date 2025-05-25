<?php
// Inicia a sessão apenas se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuração do banco de dados
$localhost = "localhost";
$usuario = "root";
$senha = "";
$banco = "animalist";

global $pdo; // Declaração da variável global

try {
    $pdo = new PDO("mysql:dbname=".$banco.";host=".$localhost, $usuario, $senha);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
    exit;
}
?>
