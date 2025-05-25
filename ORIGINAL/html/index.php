<?php
require '../verifica.php'; // Verifique a sessão (supondo que verifica.php valide a sessão)

// Redireciona para a página de login se a sessão não estiver ativa
if (!isset($_SESSION['iduser']) || empty($_SESSION['iduser'])) {
    echo "Você precisa estar logado para acessar esta página.";
    header("Refresh: 5; url=login.php");
    exit;
}

// Rota com switch para definir a página com base no parâmetro "page"
$page = isset($_GET['page']) ? $_GET['page'] : 'home'; // Página padrão: home

switch ($page) {
    case 'home':
        $titulo = "Bem-vindo ao Anime-list, " . $_SESSION['nome'] . "!";
        $conteudo = "<p class='lead'>Explore os melhores animes, notícias, e muito mais!</p>";
        break;

    case 'animes':
        $titulo = "Lista de Animes";
        $conteudo = "<p class='lead'>Descubra os animes mais populares do momento!</p>";
        break;

    case 'sobre':
        $titulo = "Sobre Nós";
        $conteudo = "<p class='lead'>Saiba mais sobre o Anime-list e como surgiu.</p>";
        break;

    case 'contato':
        $titulo = "Contato";
        $conteudo = "<p class='lead'>Entre em contato conosco para sugestões ou dúvidas.</p>";
        break;

    default:
        $titulo = "Página Não Encontrada";
        $conteudo = "<p class='lead'>Desculpe, mas essa página não existe.</p>";
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <title><?php echo $titulo; ?></title>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Anime-list</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="?page=home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="?page=animes">Animes</a></li>
                    <li class="nav-item"><a class="nav-link" href="?page=sobre">Sobre</a></li>
                    <li class="nav-item"><a class="nav-link" href="?page=contato">Contato</a></li>
                </ul>
                <!-- Barra de Pesquisa -->
                <form class="d-flex me-3" role="search">
                    <input class="form-control me-2" type="search" placeholder="Pesquisar" aria-label="Pesquisar">
                    <button class="btn btn-outline-light" type="submit">Buscar</button>
                </form>
                <!-- Imagem de Perfil e Botão de Sair -->
                <div class="d-flex align-items-center">
                    <img src="images/perfil.jpg" alt="Perfil" class="rounded-circle me-3" style="width: 40px; height: 40px;">
                    <a href="logout.php" class="btn btn-danger">Sair</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Conteúdo da Página -->
    <main>
        <section class="text-center bg-dark text-light py-5">
            <div class="container">
                <h1 class="display-4"><?php echo $titulo; ?></h1>
                <?php echo $conteudo; ?>
            </div>
        </section>
    </main>
</body>
</html>
