<?php
require_once 'includes/db_connect.php';
require_once 'includes/header.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$initial_items_to_show = 5; 

// --- DADOS MOCK PARA PREENCHIMENTO (PARA VISUALIZAÇÃO) ---
function get_mock_anime_data($count, $base_name, $base_id_start, $status_prefix = '') {
    $mock_animes = [];
    for ($i = 0; $i < $count; $i++) {
        $id = $base_id_start + $i;
        $capa_num = ($i % 5) + 1; // Para variar um pouco a URL do placeholder
        $mock_animes[] = [
            "id_anime" => "mock_" . $id,
            "nome" => $status_prefix . " " . $base_name . " " . ($i + 1),
            "capa_url" => "https://upload.wikimedia.org/wikipedia/commons/d/d4/American-Automobile-Association-Logo.svg" . $capa_num . " " . substr($base_name,0,1) . ($i+1),
        ];
    }
    return $mock_animes;
}

// --- BUSCAR DADOS DO USUÁRIO ---
$user_data = [];
$user_data['nome'] = $_SESSION['user_name'] ?? 'Usuário Exemplo';
$user_data['email'] = $_SESSION['user_email'] ?? 'usuario@exemplo.com';
$user_data['foto_perfil_url'] = 'img/perfil_default.png';
$user_data['descricao'] = 'Grande fã de animes de todos os gêneros. Sempre procurando a próxima série para maratonar!';

$stmt_user = $conn->prepare("SELECT nome, email, data_nascimento, foto_perfil_url, fundo_perfil_url, descricao FROM Usuarios WHERE id_usuario = ?");
if ($stmt_user) {
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($result_user->num_rows > 0) {
        $db_user_data = $result_user->fetch_assoc();
        $user_data['nome'] = !empty($db_user_data['nome']) ? $db_user_data['nome'] : $user_data['nome'];
        $user_data['email'] = !empty($db_user_data['email']) ? $db_user_data['email'] : $user_data['email'];
        $user_data['data_nascimento'] = $db_user_data['data_nascimento'] ?? '';
        $user_data['foto_perfil_url'] = !empty($db_user_data['foto_perfil_url']) ? $db_user_data['foto_perfil_url'] : $user_data['foto_perfil_url'];
        $user_data['descricao'] = !empty($db_user_data['descricao']) ? $db_user_data['descricao'] : $user_data['descricao'];
    }
    $stmt_user->close();
}
$user_type = $_SESSION['user_type'] ?? 'normal';

// --- BUSCAR LISTA PESSOAL DE ANIMES (E APLICAR MOCK SE VAZIO) ---
$animes_pessoais_raw = [];
$stmt_list = $conn->prepare("
    SELECT LA.status_anime, A.id_anime, A.nome, A.ano_lancamento, A.capa_url
    FROM ListaPessoalAnimes LA
    JOIN Animes A ON LA.id_anime = A.id_anime
    WHERE LA.id_usuario = ?
    ORDER BY A.nome ASC
");
if ($stmt_list) {
    $stmt_list->bind_param("i", $user_id);
    $stmt_list->execute();
    $result_list = $stmt_list->get_result();
    while ($row = $result_list->fetch_assoc()) {
        $animes_pessoais_raw[] = $row;
    }
    $stmt_list->close();
}

$animes_por_status = [
    "Favoritos" => [],
    "Assistindo" => [],
    "Completado" => [],
    "Parou/Droppado" => [],
    "Planejado" => []
];

if (empty($animes_pessoais_raw)) { // Se não houver dados do banco, usa mock
    $animes_por_status["Favoritos"] = get_mock_anime_data(8, "Favorito", 100, "Mock");
    $animes_por_status["Assistindo"] = get_mock_anime_data(10, "Assistindo", 200, "Mock");
    $animes_por_status["Completado"] = get_mock_anime_data(12, "Completado", 300, "Mock");
    $animes_por_status["Parou/Droppado"] = get_mock_anime_data(7, "Droppado", 400, "Mock");
    $animes_por_status["Planejado"] = get_mock_anime_data(5, "Planejado", 500, "Mock"); // Menos de 6, não mostra "Ver Mais"
} else {
    // Mapeia dados reais do banco para as categorias
    foreach ($animes_pessoais_raw as $anime_item) {
        $status = strtolower(trim($anime_item['status_anime'] ?? ''));
        $anime_data_for_list = [
            "id_anime" => $anime_item['id_anime'],
            "nome" => $anime_item['nome'],
            "capa_url" => $anime_item['capa_url'] ?? 'images/poster_placeholder.png'
        ];
        if ($status == 'assistindo') $animes_por_status["Assistindo"][] = $anime_data_for_list;
        elseif ($status == 'completo' || $status == 'completado') $animes_por_status["Completado"][] = $anime_data_for_list;
        elseif ($status == 'dropado' || $status == 'parado' || $status == 'pausado') $animes_por_status["Parou/Droppado"][] = $anime_data_for_list;
        elseif ($status == 'planejado' || $status == 'planejo assistir') $animes_por_status["Planejado"][] = $anime_data_for_list;
        // Adicione sua lógica para "Favoritos" aqui se tiver um campo específico no banco.
        // Por enquanto, se "Favoritos" do banco estiver vazio, adicionamos mock para visualização.
    }
    if (empty($animes_por_status["Favoritos"])) { // Adiciona mock para favoritos se a lista do DB estiver vazia
         $animes_por_status["Favoritos"] = get_mock_anime_data(8, "Favorito", 100, "DB Mock");
    }
}

$anime_lists_display = [];
$categories_order = ["Favoritos", "Assistindo", "Completado", "Parou/Droppado", "Planejado"];
foreach ($categories_order as $cat) {
    if (isset($animes_por_status[$cat])) { // Garante que a chave exista
        $anime_lists_display[$cat] = $animes_por_status[$cat];
    }
}


// --- BUSCAR AVALIAÇÕES (E APLICAR MOCK SE VAZIO) ---
$avaliacoes = [];
$stmt_reviews = $conn->prepare("
    SELECT AV.nota, AV.comentario, A.nome AS nome_anime, A.id_anime
    FROM Avaliacoes AV JOIN Animes A ON AV.id_anime = A.id_anime WHERE AV.id_usuario = ? ORDER BY AV.data_avaliacao DESC
");
if ($stmt_reviews) {
    $stmt_reviews->bind_param("i", $user_id);
    $stmt_reviews->execute();
    $result_reviews = $stmt_reviews->get_result();
    while ($row = $result_reviews->fetch_assoc()) {
        $avaliacoes[] = $row;
    }
    $stmt_reviews->close();
}

if (empty($avaliacoes)) { // Mock para avaliações
    for ($i = 0; $i < 4; $i++) {
        $avaliacoes[] = [
            'id_anime' => 'mock_eval_' . $i,
            'nome_anime' => 'Anime Mock Avaliado ' . ($i + 1),
            'nota' => rand(7, 10),
            'comentario' => 'Este é um comentário de exemplo para o anime mock ' . ($i + 1) . '. Uma obra interessante com altos e baixos, mas que vale a pena conferir pela sua originalidade.'
        ];
    }
}

// --- GERAR HTML DAS AVALIAÇÕES PARA A SIDEBAR ---
$avaliacoes_html_sidebar = '';
if (!empty($avaliacoes)) {
    $avaliacoes_html_sidebar .= '<div class="user-reviews-sidebar-content">';
    $avaliacoes_html_sidebar .= '<h3 style="color: #65ebba; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #253750;">Minhas Avaliações</h3>';
    $avaliacoes_html_sidebar .= '<div class="reviews-list-sidebar" style="display: flex; flex-direction: column; gap: 15px;">';
    $count_reviews = 0;
    foreach ($avaliacoes as $avaliacao) {
        if ($count_reviews >= 5) $avaliacoes_html_sidebar .= '<div class="review-item-sidebar initially-hidden-review" style="background-color: #101c2e; padding: 10px; border-radius: 4px; border: 1px solid #253750;">'; // Esconde avaliações extras
        else $avaliacoes_html_sidebar .= '<div class="review-item-sidebar" style="background-color: #101c2e; padding: 10px; border-radius: 4px; border: 1px solid #253750;">';
        
        $avaliacoes_html_sidebar .= '<h4 style="color: #65ebba; margin-bottom: 5px; font-size: 1em;">';
        $avaliacoes_html_sidebar .= '<a href="anime_detalhes.php?id=' . ($anime_item['id_anime'] ?? $avaliacao['id_anime']) . '" style="color: #65ebba; text-decoration: none;">' . htmlspecialchars($avaliacao['nome_anime']) . '</a>';
        $avaliacoes_html_sidebar .= '</h4>';
        $avaliacoes_html_sidebar .= '<p style="margin-bottom: 5px; font-size: 0.9em;"><strong>Nota:</strong> ';
        for($s = 0; $s < 5; $s++) {
            $avaliacoes_html_sidebar .= '<i class="fas fa-star" style="color:' . ($s < round($avaliacao['nota'] / 2) ? '#ffc107' : '#3a4e68') . ';"></i>';
        }
        $avaliacoes_html_sidebar .= ' (' . htmlspecialchars($avaliacao['nota']) . '/10)</p>';
        if (!empty($avaliacao['comentario'])) {
            $avaliacoes_html_sidebar .= '<p style="font-size: 0.85em; color: #aebac3;"><em>"' . nl2br(htmlspecialchars($avaliacao['comentario'])) . '"</em></p>';
        }
        $avaliacoes_html_sidebar .= '</div>';
        $count_reviews++;
    }
    $avaliacoes_html_sidebar .= '</div>';
    if ($count_reviews > 5) {
        $avaliacoes_html_sidebar .= '<a href="#" id="viewAllReviewsSidebar" class="view-all" style="display: block; text-align: center; margin-top: 15px; color: #60758b;">Ver Todas Avaliações</a>';
    }
    $avaliacoes_html_sidebar .= '</div>';
} else {
    $avaliacoes_html_sidebar = '<div class="user-reviews-sidebar-content"><h3 style="color: #65ebba; margin-bottom: 15px;">Minhas Avaliações</h3><p style="color: #60758b;">Você ainda não fez nenhuma avaliação.</p></div>';
}

?>


<!-- ================= PÁGINA COMEÇA AQUI ================== -->

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animalist - Home</title>
    <!-- === CSS === -->
    <link rel="stylesheet" href="css/perfil.css">
    <link rel="stylesheet" href="css/universal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

<div class="container">
    <header class="profile-header">
        <div class="profile-pic-container">
            <img src="<?php echo htmlspecialchars($user_data['foto_perfil_url']); ?>" alt="Foto do Perfil" id="profileImage">
        </div>
        <div class="profile-info">
            <input type="text" id="userName" value="<?php echo htmlspecialchars($user_data['nome']); ?>" placeholder="Seu Nome">
            <textarea id="userDescription" placeholder="Conte um pouco sobre você..."><?php echo htmlspecialchars($user_data['descricao']); ?></textarea>
        </div>
        <div class="profile-actions">
            <button id="addContentBtn" aria-label="Adicionar novo item" title="Adicionar Anime à Lista (Em Breve)"><i class="fas fa-plus"></i></button>
            <a href="editar_perfil.php" class="profile-action-link" aria-label="Editar Perfil Completo" title="Editar Perfil Completo"
               style="background-color: #2f81f7; color: white; border: none; margin-bottom: 10px; border-radius: 50%; width: 40px; height: 40px; display: flex; justify-content: center; align-items: center; text-decoration: none; font-size: 1.2em;">
                <i class="fas fa-edit"></i>
            </a>
            <button id="deleteProfileBtn" aria-label="Deletar perfil" title="Deletar Conta"><i class="fas fa-trash"></i></button>
        </div>
    </header>

    <main class="content-area">
        <section class="anime-lists-section" id="lista">
            <?php if (!empty($anime_lists_display)): ?>
                <?php foreach ($anime_lists_display as $list_title => $animes_in_list): ?>
                    <?php if (empty($animes_in_list) && !in_array($list_title, ["Favoritos", "Assistindo", "Completado", "Parou/Droppado", "Planejado"])) { continue; } // Não mostra categorias não fixas se vazias ?>
                    <?php $grid_id = 'grid-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($list_title)); ?>
                    <div class="anime-list-category">
                        <div class="list-header">
                            <h2><?php echo htmlspecialchars($list_title); ?> (<?php echo count($animes_in_list); ?>)</h2>
                            <?php if (count($animes_in_list) > $initial_items_to_show): ?>
                                <a href="#" class="view-all" data-target-grid="<?php echo $grid_id; ?>">Ver Tudo</a>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($animes_in_list)): ?>
                            <div class="anime-grid" id="<?php echo $grid_id; ?>">
                                <?php foreach ($animes_in_list as $index => $anime): ?>
                                <div class="anime-poster <?php echo $index >= $initial_items_to_show ? 'initially-hidden' : ''; ?>" 
                                     data-anime-name="<?php echo htmlspecialchars($anime['nome']); ?>"
                                     data-anime-id="<?php echo htmlspecialchars($anime['id_anime']); ?>">
                                    <div class="poster-placeholder">
                                        <img src="<?php echo htmlspecialchars(!empty($anime['capa_url']) && $anime['capa_url'] !== 'images/poster_placeholder.png' ? $anime['capa_url'] : 'https://upload.wikimedia.org/wikipedia/commons/d/d4/American-Automobile-Association-Logo.svg' . urlencode(substr($anime['nome'], 0, 1))); ?>" 
                                             alt="<?php echo htmlspecialchars($anime['nome']); ?>"
                                             style="width:100%; height:100%; object-fit:cover; border-radius: 4px;">
                                    </div>
                                     <p class="anime-poster-title" style="font-size:0.8em; color:#c9d1d9; text-align:center; margin-top:5px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?php echo htmlspecialchars($anime['nome']); ?>">
                                        <?php echo htmlspecialchars($anime['nome']); ?>
                                    </p>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: #60758b; padding: 10px 0;">Nenhum anime nesta lista ainda. <?php if($list_title != "Favoritos") { ?><a href="animes.php" style="color: #65ebba;">Adicionar?</a><?php } ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                 <p>Sua lista de animes está completamente vazia. Comece a adicionar <a href="animes.php" style="color: #65ebba;">aqui</a>!</p>
            <?php endif; ?>
        </section>
        
        <aside class="sidebar-details">
            <?php echo $avaliacoes_html_sidebar; ?>
        </aside>
    </main>

</div>
</body>

<script src="js/perfil.js"></script>
</html>

<?php
require_once 'includes/footer.php';
if (isset($conn)) {
    $conn->close();
}
?>