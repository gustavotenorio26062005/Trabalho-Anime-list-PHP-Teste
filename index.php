<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animalist - Home</title>
    <!-- === CSS === -->
    <link rel="stylesheet" href="pages/css/index.css">
    <link rel="stylesheet" href="pages/css/navbar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- === FONTES === -->
     <?php include __DIR__ . '/pages/constante/fontes.php' ?>
</head>
<body>

    <?php include __DIR__ . '/pages/constante/navbar.php'; ?>
    

    <header class="cabecalho-principal">
        <div class="logo-container">
            <!-- Simulação do logo, idealmente seria uma imagem ou SVG -->
            <div class="logo-simulado">
                <i class="fas fa-eye logo-olho"></i>
                <i class="fas fa-check-circle logo-check"></i>
            </div>
        </div>
        <h1 class="titulo-cabecalho">DESCUBRA. CURTA. REGISTRE.</h1>
        <div class="textos-cabecalho">
            <div class="coluna-texto">
                <p>Aqui você acompanha facilmente os animes que está assistindo, sem se perder nos episódios.</p>
                <p class="texto-destaque"><strong>Descubra novas histórias</strong> e veja quais animes estão em alta entre os fãs.</p>
            </div>
            <div class="coluna-texto">
                <p>Avalie cada anime que assistir e compartilhe suas opiniões com a comunidade.</p>
                 <p class="texto-destaque">Monte sua própria <strong>lista personalizada</strong> e organize tudo do seu jeito, no seu ritmo.</p>
            </div>
        </div>
        <p class="lema-cabecalho">Simples, prático e do seu jeito!</p>
        <button class="botao-acao-principal">Entre Agora!</button>
    </header>

    <section class="secao-filtros">
        <div class="container-filtros">
            <div class="grupo-input">
                <label for="pesquisar">Pesquisar</label>
                <input type="text" id="pesquisar" name="pesquisar" class="campo-input">
            </div>
            <div class="grupo-input">
                <label for="genero">Gênero</label>
                <input type="text" id="genero" name="genero" class="campo-input">
            </div>
            <div class="grupo-input">
                <label for="ano">Ano de Lançamento</label>
                <input type="text" id="ano" name="ano" class="campo-input">
            </div>
            <button class="botao-icone botao-pesquisar" aria-label="Pesquisar"><i class="fas fa-search"></i></button>
            <button class="botao-icone botao-adicionar" aria-label="Adicionar"><i class="fas fa-plus"></i></button>
        </div>
    </section>

    <main class="conteudo-principal" id="pesquisar">
        <section class="secao-animes">
            <h2 class="titulo-secao">Animes de Ação</h2>
            <div class="grade-animes">
                <div class="card-anime">
                    <div class="imagem-placeholder-anime"></div>
                    <p class="titulo-card-anime">Aquela vez em que eu reencarnei como um site!</p>
                </div>
                <div class="card-anime">
                    <div class="imagem-placeholder-anime"></div>
                    <p class="titulo-card-anime">Aquela vez em que eu reencarnei como um site!</p>
                </div>
                <div class="card-anime">
                    <div class="imagem-placeholder-anime"></div>
                    <p class="titulo-card-anime">Aquela vez em que eu reencarnei como um site!</p>
                </div>
                <div class="card-anime">
                    <div class="imagem-placeholder-anime"></div>
                    <p class="titulo-card-anime">Aquela vez em que eu reencarnei como um site!</p>
                </div>
            </div>
        </section>

        <section class="secao-animes">
            <h2 class="titulo-secao">Animes de Comédia</h2>
            <div class="grade-animes">
                <div class="card-anime">
                    <div class="imagem-placeholder-anime"></div>
                    <p class="titulo-card-anime">Título do Anime de Comédia 1</p>
                </div>
                <div class="card-anime">
                    <div class="imagem-placeholder-anime"></div>
                    <p class="titulo-card-anime">Título do Anime de Comédia 2</p>
                </div>
                <div class="card-anime">
                    <div class="imagem-placeholder-anime"></div>
                    <p class="titulo-card-anime">Título do Anime de Comédia 3</p>
                </div>
                <div class="card-anime">
                    <div class="imagem-placeholder-anime"></div>
                    <p class="titulo-card-anime">Título do Anime de Comédia 4</p>
                </div>
            </div>
        </section>
    </main>


    <footer class="footer mt-auto py-3">
        <div class="container">
            <span>&copy; <?php echo date("Y"); ?> Animalist. Todos os direitos reservados.</span>
        </div>
    </footer>

    <!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script>
    $(document).ready(function(){
    // Add smooth scrolling to all links
    $("a").on('click', function(event) {

        // Make sure this.hash has a value before overriding default behavior
        if (this.hash !== "") {
        // Prevent default anchor click behavior
        event.preventDefault();

        // Store hash
        var hash = this.hash;

        // Using jQuery's animate() method to add smooth page scroll
        // The optional number (800) specifies the number of milliseconds it takes to scroll to the specified area
        $('html, body').animate({
            scrollTop: $(hash).offset().top
        }, 800, function(){

            // Add hash (#) to URL when done scrolling (default click behavior)
            window.location.hash = hash;
        });
        } // End if
    });
    });
    </script> -->
</body>
</html>

            <!-- // PHP code to fetch and display Comedy Animes will go here.
                // Similar loop structure as the Action Animes section.
                // $comedyAnimes = fetchComedyAnimesFromDatabase();
                // if (!empty($comedyAnimes)) { ... } -->