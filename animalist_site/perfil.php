<?php
require_once 'includes/db_connect.php';
require_once 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$initial_items_to_show = 5;
$is_admin = (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 1);

// --- Buscar Dados do Usuário ---
$user_data = [
    'nome' => $_SESSION['user_name'] ?? 'Usuário',
    'email' => $_SESSION['user_email'] ?? 'usuario@exemplo.com',
    'foto_perfil_url' => 'img/perfil_default.png',
    'fundo_perfil_url' => 'img/fundo_default.png',
    'descricao' => 'Grande fã de animes...',
    'data_nascimento' => '',
    'tipo_usuario_nome' => $_SESSION['user_type_name'] ?? 'Usuário Comum'
];

$stmt_user = $conn->prepare("SELECT U.nome, U.email, U.data_nascimento, U.foto_perfil_url, U.fundo_perfil_url, U.descricao, TU.tipo AS tipo_usuario_nome
                             FROM Usuarios U
                             JOIN TipoUsuario TU ON U.id_tipo_usuario = TU.id_tipo_usuario
                             WHERE U.id_usuario = ?");
if ($stmt_user) {
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($result_user->num_rows > 0) {
        $db_user_data = $result_user->fetch_assoc();
        $user_data['nome'] = $db_user_data['nome'];
        $user_data['email'] = $db_user_data['email'];
        $user_data['data_nascimento'] = $db_user_data['data_nascimento'];
        $user_data['foto_perfil_url'] = !empty($db_user_data['foto_perfil_url']) ? $db_user_data['foto_perfil_url'] : $user_data['foto_perfil_url'];
        $user_data['fundo_perfil_url'] = !empty($db_user_data['fundo_perfil_url']) ? $db_user_data['fundo_perfil_url'] : $user_data['fundo_perfil_url'];
        $user_data['descricao'] = $db_user_data['descricao'];
        $user_data['tipo_usuario_nome'] = $db_user_data['tipo_usuario_nome'];

        $_SESSION['user_name'] = $user_data['nome'];
        $_SESSION['user_email'] = $user_data['email'];
        $_SESSION['user_type_name'] = $user_data['tipo_usuario_nome'];
    }
    $stmt_user->close();
}

// --- Buscar Lista Pessoal de Animes ---
$animes_pessoais_raw = [];
$stmt_list = $conn->prepare("
    SELECT LA.status_anime, LA.is_favorito, A.id_anime, A.nome, A.ano_lancamento, A.capa_url
    FROM ListaPessoalAnimes LA
    JOIN Animes A ON LA.id_anime = A.id_anime
    WHERE LA.id_usuario = ?
    ORDER BY LA.status_anime, A.nome ASC
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
    "Favoritos" => [], "Assistindo" => [], "Completado" => [],
    "Droppado" => [], "Planejando Assistir" => []
];

foreach ($animes_pessoais_raw as $anime_item) {
    $status_key = $anime_item['status_anime'];
    $item_data = ["id_anime" => $anime_item['id_anime'], "nome" => $anime_item['nome'], "capa_url" => $anime_item['capa_url'] ?? 'https://via.placeholder.com/200x250?text=Sem+Capa'];
    if ($anime_item['is_favorito']) {
        $animes_por_status["Favoritos"][] = $item_data;
    }
    if (isset($animes_por_status[$status_key])) {
        $animes_por_status[$status_key][] = $item_data;
    }
}

$anime_lists_display = [];
$categories_order = ["Favoritos", "Assistindo", "Completado", "Droppado", "Planejando Assistir"];
foreach ($categories_order as $cat) {
    $anime_lists_display[$cat] = $animes_por_status[$cat] ?? [];
}

// --- Buscar Avaliações ---
$avaliacoes = [];
$stmt_reviews = $conn->prepare("
    SELECT AV.nota, AV.comentario, A.nome AS nome_anime, A.id_anime
    FROM Avaliacoes AV JOIN Animes A ON AV.id_anime = A.id_anime
    WHERE AV.id_usuario = ? ORDER BY AV.data_avaliacao DESC
");
if ($stmt_reviews) {
    $stmt_reviews->bind_param("i", $user_id);
    $stmt_reviews->execute();
    $result_reviews = $stmt_reviews->get_result();
    while ($row = $result_reviews->fetch_assoc()) { $avaliacoes[] = $row; }
    $stmt_reviews->close();
}



// --- Gerar HTML das Avaliações para Sidebar ---
$avaliacoes_html_sidebar = '<div class="user-reviews-sidebar-content">';
$avaliacoes_html_sidebar .= '<h3 style="color: #65ebba; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #253750;">Minhas Avaliações</h3>';
if (!empty($avaliacoes)) {
    $avaliacoes_html_sidebar .= '<div class="reviews-list-sidebar" style="display: flex; flex-direction: column; gap: 15px;">';
    $count_reviews = 0;
    foreach ($avaliacoes as $avaliacao) {
        $hidden_class = ($count_reviews >= 5) ? 'initially-hidden-review' : '';
        $avaliacoes_html_sidebar .= '<div class="review-item-sidebar ' . $hidden_class . '" style="background-color: #101c2e; padding: 10px; border-radius: 4px; border: 1px solid #253750;">';
        $avaliacoes_html_sidebar .= '<h4 style="color: #65ebba; margin-bottom: 5px; font-size: 1em;"><a href="anime_detalhes.php?id=' . htmlspecialchars($avaliacao['id_anime']) . '" style="color: #65ebba; text-decoration: none;">' . htmlspecialchars($avaliacao['nome_anime']) . '</a></h4>';
        $nota_cor = ($avaliacao['nota'] == 'Recomendo') ? '#65ebba' : '#eb2c4c';
        $avaliacoes_html_sidebar .= '<p style="margin-bottom: 5px; font-size: 0.9em;"><strong>Nota:</strong> <span style="color: ' . $nota_cor . '; font-weight: bold;">' . htmlspecialchars($avaliacao['nota']) . '</span></p>';
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
} else {
    $avaliacoes_html_sidebar .= '<p style="color: #60758b;">Você ainda não fez nenhuma avaliação.</p>';
}
$avaliacoes_html_sidebar .= '</div>';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animalist - Perfil de <?php echo htmlspecialchars($user_data['nome']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/perfil.css">
    <link rel="stylesheet" href="css/universal.css">

</head>
<body>

<div class="container">
    <header class="profile-header">
        <div class="profile-background-image-container">
            <?php if (!empty($user_data['fundo_perfil_url'])): ?>
                <img src="<?php echo htmlspecialchars($user_data['fundo_perfil_url']); ?>" alt="Fundo do Perfil" class="profile-background-image">
            <?php else: ?>
                <div class="profile-background-placeholder"></div>
            <?php endif; ?>
        </div>

        <div class="profile-content-wrapper">
            <div class="profile-pic-container">
                <img src="<?php echo htmlspecialchars($user_data['foto_perfil_url']); ?>" alt="Foto do Perfil" id="profileImage">
            </div>
            <div class="profile-info">
                <h1 class="profile-name-display"><?php echo htmlspecialchars($user_data['nome']); ?></h1>
                <p class="profile-description-display"><?php echo nl2br(htmlspecialchars($user_data['descricao'])); ?></p>
            </div>
            <div class="profile-actions">
                <a href="editar_perfil.php" class="profile-action-link" aria-label="Editar Perfil Completo" title="Editar Perfil Completo"
                   style="background-color: #2f81f7; color: white; border: none; margin-bottom: 10px; border-radius: 50%; width: 40px; height: 40px; display: flex; justify-content: center; align-items: center; text-decoration: none; font-size: 1.2em;">
                    <i class="fas fa-edit"></i>
                </a>
                <!-- BOTÃO PARA ACIONAR O MODAL -->
                <button id="deleteProfileBtn" aria-label="Deletar perfil" title="Deletar Conta"><i class="fas fa-trash"></i></button>
            </div>
        </div>
    </header>

    <main class="content-area">
        <section class="anime-lists-section" id="lista">
            <?php if (!empty($anime_lists_display)): ?>
                <?php foreach ($anime_lists_display as $list_title => $animes_in_list): ?>
                    <?php 
                    if (empty($animes_in_list) && !in_array($list_title, ["Favoritos", "Assistindo", "Completado", "Droppado", "Planejando Assistir"])) {
                        continue;
                    }
                    $grid_id = 'grid-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($list_title)); 
                    ?>
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
                                <a href="anime_detalhes.php?id=<?php echo htmlspecialchars($anime['id_anime']); ?>" 
                                class="anime-link-perfil <?php echo $index >= $initial_items_to_show ? 'initially-hidden' : ''; ?>" 
                                data-anime-name="<?php echo htmlspecialchars($anime['nome']); ?>"
                                data-anime-id="<?php echo htmlspecialchars($anime['id_anime']); ?>">
                                    <div class="anime-poster">
                                        <div class="poster-placeholder">
                                            <img src="<?php echo htmlspecialchars(!empty($anime['capa_url']) ? $anime['capa_url'] : 'https://via.placeholder.com/200x250?text=Sem+Capa'); ?>" 
                                                    alt="<?php echo htmlspecialchars($anime['nome']); ?>">
                                        </div>
                                        <p class="anime-item-title" title="<?php echo htmlspecialchars($anime['nome']); ?>">
                                            <?php echo htmlspecialchars($anime['nome']); ?>
                                        </p>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                            <p style="color: #60758b; padding: 10px 0;">Nenhum anime nesta lista ainda. 
                                <?php if($list_title != "Favoritos") { ?>
                                    <a href="pesquisar.php" style="color: #65ebba;">Adicionar?</a>
                                <?php } ?>
                            </p>
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

    <!-- Modal de Confirmação para Deletar Conta (VOCÊ JÁ TINHA ESTE HTML) -->
    <div id="deleteAccountModal" class="modal">
        <div class="modal-content">
            <span class="close-button" title="Fechar">×</span>
            <h2>Confirmar Exclusão de Conta</h2>
            <p>Tem certeza de que deseja excluir sua conta permanentemente? Esta ação não pode ser desfeita.</p>
            <p>Todos os seus dados, incluindo listas de animes e avaliações, serão removidos.</p>
            <p style="margin-top: 15px;">Para confirmar, por favor, insira sua senha:</p>
            <input type="password" id="deleteConfirmPassword" placeholder="Sua senha" autocomplete="current-password">
            <div id="deleteAccountError" style="color: #eb2c4c; margin-top: 10px; min-height: 1em;"></div>
            <button id="confirmDeleteAccountBtn" style="margin-top: 15px;">Excluir Minha Conta Permanentemente</button>
        </div>
    </div>

</div> 

<script src="js/perfil.js"></script>

<?php
require_once 'includes/footer.php';
if (isset($conn)) {
    $conn->close();
}
?>
</body>
</html>