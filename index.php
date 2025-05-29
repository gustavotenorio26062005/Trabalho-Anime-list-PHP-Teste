<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css"> <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Animalist - Home</title>
    <link rel="stylesheet" href="/frontend/index.css">
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #202833;">
        <div class="container">
            <a class="navbar-brand" href="#">Animalist</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Gêneros</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Top Animes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Minha Lista</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cadastrar.html">Cadastrar-se</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section">
        <div class="container">
            <div class="hero-logo">
                <i class="fas fa-eye"></i> </div>
            <h1 class="text-center">DESCUBRA. CURTA. REGISTRE.</h1>
            <div class="row mt-4 align-items-center">
                <div class="col-md-6">
                    <p class="lead">Aqui você acompanha facilmente os animes que está assistindo, sem se perder nos episódios.</p>
                    <p class="lead">Descubra novas histórias e veja quais animes estão em alta entre os fãs.</p>
                </div>
                <div class="col-md-6">
                    <p class="lead">Avalie cada anime que assistir e compartilhe suas opiniões com a comunidade.</p>
                    <p class="lead">Monte sua própria <strong>lista personalizada</strong> e organize tudo do seu jeito, no seu ritmo.</p>
                </div>
            </div>
            <p class="lead-center">Simples, prático e do seu jeito!</p>
            <div class="text-center mt-4">
                 <a href="cadastrar.html" class="btn btn-custom-join">Entre Agora!</a>
            </div>
        </div>
    </section>

    <section class="search-filter-section">
        <div class="container">
            <form action="search_results.php" method="GET">
                <div class="row g-3 align-items-center">
                    <div class="col-md-5">
                        <input type="text" name="search_query" class="form-control form-control-lg" placeholder="Pesquisar anime...">
                    </div>
                    <div class="col-md-3">
                        <select name="genre" class="form-select form-select-lg">
                            <option value="" selected>Gênero</option>
                            <option value="acao">Ação</option>
                            <option value="aventura">Aventura</option>
                            <option value="comedia">Comédia</option>
                            <option value="drama">Drama</option>
                            <option value="fantasia">Fantasia</option>
                            </select>
                    </div>
                    <div class="col-md-3">
                        <select name="release_year" class="form-select form-select-lg">
                            <option value="" selected>Ano de Lançamento</option>
                            <option value="2025">2025</option>
                            <option value="2024">2024</option>
                            <option value="2023">2023</option>
                        </select>
                    </div>
                    <div class="col-md-1 text-center d-grid">
                        <button type="submit" class="btn btn-add-filter btn-lg">
                            <i class="fas fa-search"></i> </button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <main class="container mt-5">
        <section class="genre-section mb-5">
            <h2>Animes de Ação</h2>
            <div class="anime-card-row">
                <?php
                // PHP code to fetch and display Action Animes will go here.
                // Example:
                // $actionAnimes = fetchActionAnimesFromDatabase(); // Your function to get data
                // if (!empty($actionAnimes)) {
                //     foreach ($actionAnimes as $anime) {
                //         echo '<div class="card anime-card">';
                //         echo '    <a href="anime_details.php?id=' . htmlspecialchars($anime['id']) . '"><img src="' . htmlspecialchars($anime['image_url']) . '" class="card-img-top" alt="' . htmlspecialchars($anime['title']) . '"></a>';
                //         echo '    <div class="card-body">';
                //         echo '        <h5 class="card-title"><a href="anime_details.php?id=' . htmlspecialchars($anime['id']) . '" class="text-white text-decoration-none">' . htmlspecialchars($anime['title']) . '</a></h5>';
                //         echo '    </div>';
                //         echo '</div>';
                //     }
                // } else {
                //     echo '<p class="text-white-50">Nenhum anime de ação encontrado no momento.</p>';
                // }
                ?>

                <div class="card anime-card">
                    <a href="#"><img src="https://via.placeholder.com/200x280.png?text=Poster+Ação+1" class="card-img-top" alt="Nome do Anime de Ação"></a>
                    <div class="card-body">
                        <h5 class="card-title"><a href="#" class="text-white text-decoration-none">Aquela vez em que eu reencarnei como um site!</a></h5>
                    </div>
                </div>
                <div class="card anime-card">
                    <a href="#"><img src="https://via.placeholder.com/200x280.png?text=Poster+Ação+2" class="card-img-top" alt="Nome do Anime de Ação"></a>
                    <div class="card-body">
                        <h5 class="card-title"><a href="#" class="text-white text-decoration-none">Lutador da Web</a></h5>
                    </div>
                </div>
                <div class="card anime-card">
                    <a href="#"><img src="https://via.placeholder.com/200x280.png?text=Poster+Ação+3" class="card-img-top" alt="Nome do Anime de Ação"></a>
                    <div class="card-body">
                        <h5 class="card-title"><a href="#" class="text-white text-decoration-none">A Programadora Mágica</a></h5>
                    </div>
                </div>
                <div class="card anime-card">
                    <a href="#"><img src="https://via.placeholder.com/200x280.png?text=Poster+Ação+4" class="card-img-top" alt="Nome do Anime de Ação"></a>
                    <div class="card-body">
                        <h5 class="card-title"><a href="#" class="text-white text-decoration-none">Cyber Ninja Chronicles</a></h5>
                    </div>
                </div>
                 <div class="card anime-card">
                    <a href="#"><img src="https://via.placeholder.com/200x280.png?text=Poster+Ação+5" class="card-img-top" alt="Nome do Anime de Ação"></a>
                    <div class="card-body">
                        <h5 class="card-title"><a href="#" class="text-white text-decoration-none">Frontend Fantasia</a></h5>
                    </div>
                </div>
            </div>
        </section>

        <section class="genre-section mb-5">
            <h2>Animes de Comédia</h2>
            <div class="anime-card-row">
                <?php
                // PHP code to fetch and display Comedy Animes will go here.
                // Similar loop structure as the Action Animes section.
                // $comedyAnimes = fetchComedyAnimesFromDatabase();
                // if (!empty($comedyAnimes)) { ... }
                ?>
                <div class="card anime-card">
                    <a href="#"><img src="https://via.placeholder.com/200x280.png?text=Poster+Comédia+1" class="card-img-top" alt="Nome do Anime de Comédia"></a>
                    <div class="card-body">
                        <h5 class="card-title"><a href="#" class="text-white text-decoration-none">Meu Bug de Estimação</a></h5>
                    </div>
                </div>
                <div class="card anime-card">
                    <a href="#"><img src="https://via.placeholder.com/200x280.png?text=Poster+Comédia+2" class="card-img-top" alt="Nome do Anime de Comédia"></a>
                    <div class="card-body">
                        <h5 class="card-title"><a href="#" class="text-white text-decoration-none">Stack Overflow da Zueira</a></h5>
                    </div>
                </div>
                <div class="card anime-card">
                    <a href="#"><img src="https://via.placeholder.com/200x280.png?text=Poster+Comédia+3" class="card-img-top" alt="Nome do Anime de Comédia"></a>
                    <div class="card-body">
                        <h5 class="card-title"><a href="#" class="text-white text-decoration-none">404: Amor Não Encontrado</a></h5>
                    </div>
                </div>
                <div class="card anime-card">
                    <a href="#"><img src="https://via.placeholder.com/200x280.png?text=Poster+Comédia+4" class="card-img-top" alt="Nome do Anime de Comédia"></a>
                    <div class="card-body">
                        <h5 class="card-title"><a href="#" class="text-white text-decoration-none">A Vida é um Debug</a></h5>
                    </div>
                </div>
            </div>
        </section>

        </main>

    <footer class="footer mt-auto py-3">
        <div class="container">
            <span>&copy; <?php echo date("Y"); ?> Animalist. Todos os direitos reservados.</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>