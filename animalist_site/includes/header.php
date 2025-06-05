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
    <!-- Link para o CSS da navbar (ajuste o caminho se necessário) -->
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/style.css">
    <!-- Adicionando Font Awesome para os ícones do logo (se não estiver globalmente incluído) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Se você tiver um style.css global que ainda é necessário, mantenha-o ou adicione aqui -->
    <!-- <link rel="stylesheet" href="css/style.css"> -->
</head>
<body>
    <header>
        <nav class="barra-navegacao">
            <div class="container-navegacao">
                <img src="./img/logo_site.jpg" alt="" width="100px" height="100px">
                </a>
                <ul class="lista-links-navegacao">
                    <li><a href="index.php" class="link-nav">Home</a></li>
                    <li><a href="#animes" class="link-nav">Animes</a></li> 
                    <li><a href="pesquisar.php" class="link-nav">Pesquisar</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="perfil.php" class="link-nav">Perfil</a></li>
                        <!-- Se você tiver uma página "Sua Lista" separada, adicione aqui: -->
                        <!-- <li><a href="sua_lista.php" class="link-nav">Sua Lista</a></li> -->
                    <?php endif; ?>
                </ul>
                <div class="acoes-usuario-navegacao">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Usuário Logado -->
                        <!-- Pode-se adicionar o nome do usuário aqui se desejar, ex:
                        <span style="color: #c9d1d9; margin-right: 15px;">Olá, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuário'); ?>!</span>
                        -->
                        <a href="logout.php" class="botao-cadastrar-nav">Sair</a> <!-- "Sair" estilizado como botão -->
                    <?php else: ?>
                        <!-- Usuário Não Logado -->
                        <span class="separador-vertical-nav">|</span>
                        <a href="login.php" class="link-nav link-entrar-nav">Entrar</a>
                        <a href="cadastro.php" class="botao-cadastrar-nav">Se Cadastrar</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
        <script>
        // Script de scroll suave para âncoras # na mesma página.
        // Este script só afeta links que começam com '#' e têm um ID de destino.
        // Links para outras páginas (ex: login.php, animes.php) funcionarão normalmente.
        document.addEventListener('DOMContentLoaded', () => {
            // Seleciona links dentro da barra de navegação que começam com '#'
            const anchorLinks = document.querySelectorAll('.barra-navegacao a[href^="#"]');

            anchorLinks.forEach(link => {
                link.addEventListener('click', function(event) {
                    const href = this.getAttribute('href');

                    // Verifica se é um link de âncora válido (ex: "#secao" e não apenas "#")
                    if (href.startsWith('#') && href.length > 1) {
                        const targetId = href.substring(1);
                        const targetElement = document.getElementById(targetId);

                        if (targetElement) {
                            event.preventDefault(); // Previne o salto padrão da âncora
                            targetElement.scrollIntoView({
                                behavior: 'smooth'
                            });

                            // Opcional: Atualiza a URL com o hash
                            if (history.pushState) {
                                history.pushState(null, null, href);
                            } else {
                                // Fallback para navegadores mais antigos
                                window.location.hash = href;
                            }
                        } else {
                            // Se o elemento não for encontrado na página atual, permite o comportamento padrão.
                            // Isso pode ser útil se o link for para uma âncora em outra página,
                            // embora este script seja primariamente para âncoras na mesma página.
                            console.warn(`Element with ID '${targetId}' not found on this page for smooth scroll.`);
                        }
                    }
                    // Se não for um link de âncora que se qualifica, o comportamento padrão do link prossegue.
                });
            });
        });
        </script>
    </header>
    <main> <!-- A tag <main> é aberta aqui e deve ser fechada no seu footer.php -->