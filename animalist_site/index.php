<?php
require_once 'includes/db_connect.php';
require_once 'includes/header.php';

// Função para buscar animes por gênero com limite
function buscarAnimePorGeneroComLimite($conn, $nomeGenero, $limite = 10) { // Parâmetros em português
    $animes = [];
    $sql = "SELECT A.id_anime, A.nome, A.capa_url
            FROM Animes A
            JOIN AnimeGeneros AG ON A.id_anime = AG.id_anime
            JOIN Generos G ON AG.id_genero = G.id_genero
            WHERE G.nome_genero = ?
            GROUP BY A.id_anime, A.nome, A.capa_url 
            ORDER BY RAND() 
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("si", $nomeGenero, $limite);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $animes[] = $row;
        }
        $stmt->close();
    } else {
        error_log("Erro ao preparar consulta para gênero $nomeGenero: " . $conn->error);
    }
    return $animes;
}
$secoesDeGeneros = [
    [
        'nome_db' => 'Ação', 
        'titulo_secao' => 'Animes de Ação', 
        'limite_animes' => 5 
    ],
    [
        'nome_db' => 'Aventura',
        'titulo_secao' => 'Animes de Aventura',
        'limite_animes' => 5
    ],
    [
        'nome_db' => 'Comédia',
        'titulo_secao' => 'Animes de Comédia',
        'limite_animes' => 4 
    ],
    [
        'nome_db' => 'Fantasia',
        'titulo_secao' => 'Animes de Fantasia',
        'limite_animes' => 5
    ]
];


?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animalist - Home</title>
    <link rel="stylesheet" href="css/index.css"> 
    <link rel="stylesheet" href="css/universal.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

</head>
<body class="index"> 
    
    <header class="cabecalho-principal-index" id="home-principal">
        <div class="logo-grande">
            <img src="img/logo_vertical.png" alt="">
        </div>
        <h1 class="titulo-cabecalho-index">DESCUBRA. CURTA. REGISTRE.</h1>
        <div class="textos-cabecalho-index">
            <div class="coluna-texto-index">
                <p>Aqui você acompanha facilmente os animes que está assistindo, sem se perder nos episódios.</p>
                <p class="texto-destaque-index"><strong>Descubra novas histórias</strong> e veja quais animes estão em alta entre os fãs.</p>
            </div>
            <div class="coluna-texto-index">
                <p>Avalie cada anime que assistir e compartilhe suas opiniões com a comunidade.</p>
                 <p class="texto-destaque-index">Monte sua própria <strong>lista personalizada</strong> e organize tudo do seu jeito, no seu ritmo.</p>
            </div>
        </div>
        <p class="lema-cabecalho-index">Simples, prático e do seu jeito!</p>
        <div class="container-acoes-cabecalho-index">
            <?php if (isset($_SESSION['user_id']) && isset($_SESSION['user_name'])): ?>
                <p class="saudacao-usuario-index">Olá, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
                <div class="botoes-usuario-logado-index">
                    <a href="perfil.php" class="botao-acao-principal-index">Meu Perfil</a>
                    <a href="pesquisar.php" class="botao-acao-principal-index">Explorar Animes</a>
                </div>
            <?php else: ?>
                <div class="botao-entrar-index">
                    <a href="login.php" class="botao-acao-principal-index">Entre Agora!</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- ======== ANIMES & GENEROS ========= -->

    <main class="conteudo-principal-index" id="animes">

        <?php 
            foreach ($secoesDeGeneros as $secao): 
                $animesDaSecao = buscarAnimePorGeneroComLimite($conn, $secao['nome_db'], $secao['limite_animes']);

                // Só exibe a seção se houver animes para ela
                if (!empty($animesDaSecao)): 
            ?>
                <section class="secao-anime-index">
                    <h2 class="titulo-secao-index"><?php echo htmlspecialchars($secao['titulo_secao']); ?></h2>
                    <div class="anime-grid grade-index"> 
                        <?php foreach ($animesDaSecao as $anime): ?>
                            <a href="anime_detalhes.php?id=<?php echo $anime['id_anime']; ?>" class="anime-link">
                                <div class="anime-card-poster">
                                    <?php if (!empty($anime['capa_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($anime['capa_url']); ?>" alt="Capa de <?php echo htmlspecialchars($anime['nome']); ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="anime-info">
                                    <h3><?php echo htmlspecialchars($anime['nome']); ?></h3>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="container-ver-todos-index">
                        <a href="pesquisar.php?genre=<?php echo urlencode($secao['nome_db']); ?>" class="link-ver-todos-index">
                            Ver todos de <?php echo htmlspecialchars($secao['nome_db']); ?> <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <hr class="separador-secao-index">
                </section>
            <?php 
                endif; 
            endforeach; 
            ?>

    </main>

    <?php require_once 'includes/footer.php'; ?>
</body>
</html>