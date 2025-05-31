<?php
// Certifica-se de que a sessão está iniciada para verificar o login
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animalist - Seu Guia de Animes</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <a href="index.php" class="logo">Animalist</a>
            <ul class="nav-links">
                <li><a href="animes.php">Animes</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="perfil.php">Perfil</a></li>
                    <li><a href="logout.php">Sair</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="cadastro.php">Cadastro</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main>