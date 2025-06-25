<?php

// 1. Includes e inicializações
require_once 'includes/db_connect.php'; // Conexão e session_start() (se db_connect.php fizer isso)
require_once 'includes/header.php';     // Nosso header integrado (que também chama session_start())


// 2. Lógica de busca, filtro e ordenação
$search_query = '';
$filter_year = '';
$filter_genre = '';
$sort_by = 'nome';  
$sort_order = 'ASC'; 
$animes = []; 

// Obter valores do GET para filtros e ordenação
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $search_query = trim($_GET['search'] ?? '');
    $filter_year = trim($_GET['year'] ?? '');
    $filter_genre = trim($_GET['genre'] ?? '');
    
    // Validar e definir o campo de ordenação
    $allowed_sort_by = ['nome', 'ano_lancamento']; // Campos permitidos para ordenação
    if (isset($_GET['sort_by']) && in_array($_GET['sort_by'], $allowed_sort_by)) {
        $sort_by = $_GET['sort_by'];
    }

    // Validar e definir a ordem (ASC/DESC)
    $allowed_sort_order = ['ASC', 'DESC'];
    if (isset($_GET['sort_order']) && in_array(strtoupper($_GET['sort_order']), $allowed_sort_order)) {
        $sort_order = strtoupper($_GET['sort_order']);
    }
}

// Construção da query SQL base
$sql = "SELECT A.id_anime, A.nome, A.ano_lancamento, A.sinopse, A.capa_url, 
               GROUP_CONCAT(DISTINCT G.nome_genero SEPARATOR ', ') AS generos
        FROM Animes A
        LEFT JOIN AnimeGeneros AG ON A.id_anime = AG.id_anime
        LEFT JOIN Generos G ON AG.id_genero = G.id_genero
        WHERE 1=1 "; // Começa com 1=1 para facilitar a concatenação de cláusulas AND

$params = []; // Array para os parâmetros da query preparada
$types = '';  // String para os tipos dos parâmetros

// Adicionar condição para busca por nome
if (!empty($search_query)) {
    $sql .= " AND A.nome LIKE ? ";
    $params[] = '%' . $search_query . '%'; // Busca parcial
    $types .= 's';
}

// Adicionar condição para filtro por ano
if (!empty($filter_year)) {
    $sql .= " AND A.ano_lancamento = ? ";
    $params[] = (int)$filter_year; // Converte para inteiro
    $types .= 'i';
}

// Adicionar condição para filtro por gênero
// Esta abordagem filtra animes que TENHAM o gênero especificado.
if (!empty($filter_genre)) {
    $sql .= " AND A.id_anime IN (
                SELECT AG_filter.id_anime 
                FROM AnimeGeneros AG_filter 
                JOIN Generos G_filter ON AG_filter.id_genero = G_filter.id_genero 
                WHERE G_filter.nome_genero = ?
              ) ";
    $params[] = $filter_genre;
    $types .= 's';
}

// Agrupar resultados por anime para corrigir a agregação de gêneros
$sql .= " GROUP BY A.id_anime";

// Adicionar ordenação (validada para evitar injeção de SQL)
$orderByColumn = 'A.nome'; // Coluna padrão para ordenação
if ($sort_by === 'ano_lancamento') {
    $orderByColumn = 'A.ano_lancamento';
}
$sql .= " ORDER BY " . $orderByColumn . " " . $sort_order; // $sort_order já está validado (ASC/DESC)

// Preparar e executar a query
$stmt = $conn->prepare($sql);

if (!$stmt) {
    // Em um ambiente de produção, logar o erro em vez de exibi-lo diretamente
    echo "Erro ao preparar a consulta: " . htmlspecialchars($conn->error);
    require_once 'includes/footer.php';
    if (isset($conn)) { $conn->close(); }
    exit;
}

if (!empty($params)) {
    // Usar o operador splat (...) para passar os parâmetros para bind_param
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $animes[] = $row;
    }
}
$stmt->close();

// Obter todos os gêneros distintos para as opções do filtro
$generos_options = [];
$result_generos = $conn->query("SELECT DISTINCT nome_genero FROM Generos ORDER BY nome_genero ASC");
if ($result_generos && $result_generos->num_rows > 0) {
    while ($row = $result_generos->fetch_assoc()) {
        $generos_options[] = $row['nome_genero'];
    }
}
if($result_generos) $result_generos->close();

// Obter todos os anos de lançamento distintos para as opções do filtro
$anos_options = [];
$result_anos = $conn->query("SELECT DISTINCT ano_lancamento FROM Animes WHERE ano_lancamento IS NOT NULL ORDER BY ano_lancamento DESC");
if ($result_anos && $result_anos->num_rows > 0) {
    while ($row = $result_anos->fetch_assoc()) {
        $anos_options[] = $row['ano_lancamento'];
    }
}
if($result_anos) $result_anos->close();

?>

<!-- Início do HTML da página -->

<!-- Formulário de Filtros e Pesquisa -->
 <!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animalist - Pesquisar Animes</title>
    <!-- === CSS === -->
    <link rel="stylesheet" href="css/pesquisar.css">
    <link rel="stylesheet" href="css/universal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- === FONTES === -->
     <!--<?php include __DIR__ . '/pages/constante/fontes.php' ?> -->

</head>
<body>
    

<form action="pesquisar.php" method="GET" class="form-filtros-principal">
    <section class="secao-filtros">
        <div class="container-filtros">
            
            <div class="grupo-input">
                <label for="search-input">Pesquisar</label>
                <input type="text" id="search-input" name="search" class="campo-input" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Nome do anime...">
            </div>

            <div class="grupo-input">
                <label for="genre-select">Gênero</label>
                <select id="genre-select" name="genre" class="campo-input">
                    <option value="">Todos os Gêneros</option>
                    <?php foreach ($generos_options as $genero_opt): ?>
                        <option value="<?php echo htmlspecialchars($genero_opt); ?>" <?php echo ($filter_genre == $genero_opt) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($genero_opt); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grupo-input">
                <label for="year-select">Ano de Lançamento</label>
                <select id="year-select" name="year" class="campo-input">
                    <option value="">Todos os Anos</option>
                    <?php foreach ($anos_options as $ano_opt): ?>
                        <option value="<?php echo $ano_opt; ?>" <?php echo ($filter_year == $ano_opt) ? 'selected' : ''; ?>>
                            <?php echo $ano_opt; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="grupo-input">
                <label for="sort_by-select">Ordenar por</label>
                <select id="sort_by-select" name="sort_by" class="campo-input">
                    <option value="nome" <?php echo ($sort_by == 'nome') ? 'selected' : ''; ?>>Nome</option>
                    <option value="ano_lancamento" <?php echo ($sort_by == 'ano_lancamento') ? 'selected' : ''; ?>>Ano</option>
                </select>
            </div>
            
            <div class="grupo-input">
                <label for="sort_order-select">Ordem</label>
                <select id="sort_order-select" name="sort_order" class="campo-input">
                    <option value="ASC" <?php echo ($sort_order == 'ASC') ? 'selected' : ''; ?>>Ascendente</option>
                    <option value="DESC" <?php echo ($sort_order == 'DESC') ? 'selected' : ''; ?>>Descendente</option>
                </select>
            </div>

            <button type="submit" class="botao-icone botao-pesquisar" aria-label="Pesquisar"><i class="fas fa-search"></i></button>

            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 1): // Verifica se o tipo de usuário é 1 (admin) ?>
     <a href="admin.php" class="botao-icone botao-adicionar" aria-label="Painel de Admin" title="Painel de Admin">
         <i class="fa fa-plus-square" aria-hidden="true"></i>

     </a>
<?php endif; ?>
            
            
        </div>
    </section>
</form>

<!-- Grade de Animes -->
<div class="anime-grid">
    <?php if (!empty($animes)): ?>
        <?php foreach ($animes as $anime): ?>
            <a href="anime_detalhes.php?id=<?php echo $anime['id_anime']; ?>" class="anime-link">

                    <div class="anime-card-poster">
                        <img src="<?php echo htmlspecialchars(!empty($anime['capa_url']) ? $anime['capa_url'] : 'img/logo_site.jpg'); ?>" 
                            alt="Capa de <?php echo htmlspecialchars($anime['nome']); ?>">

                        <?php if (!empty($anime['ano_lancamento'])): ?>
                        <span class="ano-anime"><?php echo htmlspecialchars($anime['ano_lancamento']); ?></span>
                        <?php endif; ?>

                    </div>

                    <div class="anime-info">
                        <h3><?php echo htmlspecialchars($anime['nome']); ?></h3>
                        <!-- Removido ano e gênero daqui para um visual mais limpo -->
                    </div>


            </a>
            
        <?php endforeach; ?>
    <?php else: ?>
        <p class="mensagem-vazio">
            Nenhum anime encontrado com os filtros e termos de pesquisa aplicados. Tente ajustar sua busca.
        </p>
    <?php endif; ?>
</div>
</body>


<?php

// Footer e fechar conexão
require_once 'includes/footer.php';
if (isset($conn)) {
    $conn->close();
}
?>