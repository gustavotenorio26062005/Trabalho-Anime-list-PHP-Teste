<?php
require_once 'includes/db_connect.php';
require_once 'includes/header.php';

$search_query = '';
$filter_year = '';
$filter_genre = '';
$animes = [];

// Lógica de busca e filtro
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $search_query = trim($_GET['search'] ?? '');
    $filter_year = trim($_GET['year'] ?? '');
    $filter_genre = trim($_GET['genre'] ?? '');
}

$sql = "SELECT A.id_anime, A.nome, A.ano_lancamento, A.sinopse, A.capa_url, 
               GROUP_CONCAT(G.nome_genero SEPARATOR ', ') AS generos
        FROM Animes A
        LEFT JOIN AnimeGeneros AG ON A.id_anime = AG.id_anime
        LEFT JOIN Generos G ON AG.id_genero = G.id_genero
        WHERE 1=1 "; // Inicia a condição WHERE com 1=1 para facilitar a adição de outras condições

$params = [];
$types = '';

if (!empty($search_query)) {
    // Usar % para busca parcial
    $sql .= " AND A.nome LIKE ? ";
    $params[] = '%' . $search_query . '%';
    $types .= 's';
}

if (!empty($filter_year)) {
    $sql .= " AND A.ano_lancamento = ? ";
    $params[] = (int)$filter_year; // Converte para int para segurança
    $types .= 'i';
}

if (!empty($filter_genre)) {
    $sql .= " AND G.nome_genero = ? ";
    $params[] = $filter_genre;
    $types .= 's';
}

$sql .= " GROUP BY A.id_anime ORDER BY A.nome ASC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    // A função call_user_func_array é usada para bind_param com array de parâmetros
    call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $params));
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $animes[] = $row;
    }
}
$stmt->close();

// Obter todos os gêneros para o filtro
$generos_options = [];
$result_generos = $conn->query("SELECT nome_genero FROM Generos ORDER BY nome_genero ASC");
if ($result_generos->num_rows > 0) {
    while ($row = $result_generos->fetch_assoc()) {
        $generos_options[] = $row['nome_genero'];
    }
}
$result_generos->close();

// Obter todos os anos de lançamento para o filtro
$anos_options = [];
$result_anos = $conn->query("SELECT DISTINCT ano_lancamento FROM Animes ORDER BY ano_lancamento DESC");
if ($result_anos->num_rows > 0) {
    while ($row = $result_anos->fetch_assoc()) {
        $anos_options[] = $row['ano_lancamento'];
    }
}
$result_anos->close();

?>

<h2>Lista de Animes</h2>

<div class="form-container">
    <form action="animes.php" method="GET" class="search-filter-form">
        <div class="form-group">
            <label for="search">Buscar por Nome:</label>
            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Ex: Attack on Titan">
        </div>
        <div class="form-group">
            <label for="year">Filtrar por Ano:</label>
            <select id="year" name="year">
                <option value="">Todos os Anos</option>
                <?php foreach ($anos_options as $ano): ?>
                    <option value="<?php echo $ano; ?>" <?php echo ($filter_year == $ano) ? 'selected' : ''; ?>>
                        <?php echo $ano; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="genre">Filtrar por Gênero:</label>
            <select id="genre" name="genre">
                <option value="">Todos os Gêneros</option>
                <?php foreach ($generos_options as $genero): ?>
                    <option value="<?php echo htmlspecialchars($genero); ?>" <?php echo ($filter_genre == $genero) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($genero); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <button type="submit">Pesquisar/Filtrar</button>
        </div>
    </form>
</div>

<div class="anime-grid">
    <?php if (!empty($animes)): ?>
        <?php foreach ($animes as $anime): ?>
            <div class="anime-card">
                <img src="<?php echo htmlspecialchars($anime['capa_url'] ?? 'https://via.placeholder.com/200x250?text=Sem+Capa'); ?>" alt="<?php echo htmlspecialchars($anime['nome']); ?>">
                <h3><?php echo htmlspecialchars($anime['nome']); ?></h3>
                <p>Ano: <?php echo htmlspecialchars($anime['ano_lancamento']); ?></p>
                <p>Gêneros: <?php echo htmlspecialchars($anime['generos'] ?? 'N/A'); ?></p>
                <a href="#" class="details-button">Ver Detalhes</a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Nenhum anime encontrado com os filtros aplicados.</p>
    <?php endif; ?>
</div>

<?php
require_once 'includes/footer.php';
$conn->close();
?>