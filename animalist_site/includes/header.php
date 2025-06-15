<?php

require_once 'includes/fontes.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// img de perfil padrão
$default_avatar = "img/perfil_default.png"; 
$user_profile_pic = $default_avatar; 

// Verifica se o usuário está logado
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    // banco conectado?
    if (isset($conn) && $conn instanceof mysqli) {
        $sql = "SELECT foto_perfil_url FROM Usuarios WHERE id_usuario = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();

            // foto do usuario no banco
            if ($user_data && !empty($user_data['foto_perfil_url'])) {
                $user_profile_pic = $user_data['foto_perfil_url'];
            }
            
            $stmt->close();
        }
    } 
}


?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animalist - Seu Guia de Animes</title>
    <!-- Link para o CSS da navbar -->
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/style.css">
    <!-- Ícones Gerais -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
</head>

    <header>
        <nav class="barra-navegacao">
            <div class="container-navegacao">
                <a href="index.php">
                <img src="./img/logo_site.png" alt="Logo Animalist" height="60px">
                </a>
                <ul class="lista-links-navegacao">
                    <li><a href="index.php" class="link-nav">Home</a></li>
                    <li><a href="index.php#animes" class="link-nav">Animes</a></li> 
                    <li><a href="pesquisar.php" class="link-nav">Pesquisar</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="perfil.php#lista" class="link-nav">Sua Lista</a></li>
                    <?php endif; ?>
                </ul>
                <div class="acoes-usuario-navegacao">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- usuario logado -->
                        <a class="foto-perfil" href="perfil.php">
                            <img src="<?php echo $user_profile_pic; ?>" alt="Foto do Perfil" id="profileImage">
                        </a>
                        <a href="logout.php" class="botao-cadastrar-nav">Sair</a> 
                    <?php else: ?>
                        <!-- Usuário Não Logado -->
                        <span class="separador-vertical-nav">|</span>
                        <a href="login.php" class="link-nav link-entrar-nav">Entrar</a>
                        <a href="cadastro.php" class="botao-cadastrar-nav">Se Cadastrar</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        
    </header>
