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

#funcao para saber em que pag/secao estamos
function get_nav_active_class($target_page_name_from_link, $target_fragment_from_link = null) {
    $current_script_name = basename($_SERVER['PHP_SELF']); 
    $current_url_fragment = parse_url($_SERVER['REQUEST_URI'], PHP_URL_FRAGMENT); 

    $target_script_name_parts = explode('#', $target_page_name_from_link);
    $target_script_name = basename($target_script_name_parts[0]);

    if ($current_script_name == $target_script_name) {
        if ($target_fragment_from_link === null && $current_url_fragment === null) {
            // Link para a página base (ex: Home em index.php, Pesquisar em pesquisar.php) E URL está na base
            return 'ativo';
        }
        if ($target_fragment_from_link !== null && $current_url_fragment == $target_fragment_from_link) {
            // Link para uma seção (ex: Animes em index.php#animes) E URL tem o mesmo fragmento
            return 'ativo';
        }
    }
    return '';
}

$user_profile_pic = isset($user_profile_pic) ? $user_profile_pic : "img/perfil_default.png"; 

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animalist - Seu Guia de Animes</title>
    <!-- Link para o CSS da navbar -->
    <link rel="stylesheet" href="css/navbar.css">
    <!-- Ícones Gerais -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
</head>

<body>
    <header>
        <nav class="barra-navegacao">
            <div class="container-navegacao">
                <a href="index.php">
                <img src="./img/logo_site.png" alt="Logo Animalist" height="60px">
                </a>
                <ul class="lista-links-navegacao">
                    <li><a href="index.php" data-scroll-target="home-principal" class="link-nav <?php echo get_nav_active_class('index.php'); ?>">Home</a></li>
                    <li><a href="index.php#animes" class="link-nav <?php echo get_nav_active_class('index.php', 'animes'); ?>">Animes</a></li> 
                    <li><a href="pesquisar.php" class="link-nav <?php echo get_nav_active_class('pesquisar.php'); ?>">Pesquisar</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="perfil.php#lista" class="link-nav <?php echo get_nav_active_class('perfil.php', 'lista'); ?>">Sua Lista</a></li>
                    <?php endif; ?>
                </ul>
                <div class="acoes-usuario-navegacao">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a class="foto-perfil" href="perfil.php">
                            <img src="<?php echo htmlspecialchars($user_profile_pic); ?>" alt="Foto do Perfil" id="profileImage">
                        </a>
                        <a href="logout.php" class="botao-cadastrar-nav">Sair</a> 
                    <?php else: ?>
                        <span class="separador-vertical-nav">|</span>
                        <a href="login.php" class="link-nav link-entrar-nav">Entrar</a>
                        <a href="cadastro.php" class="botao-cadastrar-nav">Se Cadastrar</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>

</body>

<script src="js/navbar.js"> </script>
</html>