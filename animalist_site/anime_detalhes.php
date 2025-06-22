<?php
// 1. LÓGICA PHP INICIAL E PROCESSAMENTO DE DADOS
require_once 'includes/db_connect.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db_connect.php';

$id_anime = (int)($_GET['id'] ?? 0);
$id_usuario = $_SESSION['user_id'] ?? null;
$user_type = $_SESSION['user_type'] ?? null;

// Lógica para processar a ação dos botões (manter na lista)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'manter_lista' && $id_usuario) {
    $novo_status = $_POST['status_anime'] ?? null;
    $novo_favorito_str = $_POST['is_favorito'] ?? null;
    try {
        $stmt_check = $conn->prepare("SELECT status_anime, is_favorito FROM ListaPessoalAnimes WHERE id_usuario = ? AND id_anime = ?");
        $stmt_check->bind_param("ii", $id_usuario, $id_anime);
        $stmt_check->execute();
        $entry_atual = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();

        $status_final = ($novo_status !== null && $entry_atual && $entry_atual['status_anime'] == $novo_status) ? null : $novo_status;
        $favorito_final = ($novo_favorito_str === 'true') ? 1 : 0;

        if ($novo_status !== null && $entry_atual) {
            $favorito_final = $entry_atual['is_favorito'];
        } elseif ($novo_favorito_str !== null && $entry_atual) {
            $status_final = $entry_atual['status_anime'];
        }

        $stmt = $conn->prepare("CALL adicionar_atualizar_anime_listapessoal(?, ?, ?, ?)");
        $stmt->bind_param("iisi", $id_usuario, $id_anime, $status_final, $favorito_final);
        $stmt->execute();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'new_status' => $status_final, 'new_favorito' => (bool)$favorito_final]);
        exit(); // Encerra o script aqui para requisições AJAX
    } catch (mysqli_sql_exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

$user_type = $_SESSION['user_type'] ?? 'normal';
if ($id_anime <= 0) {
    header("Location: pesquisar.php");
    exit();
}

// LÓGICA PARA PROCESSAR NOVAS AVALIAÇÕES (FORMULÁRIO)
$message = '';
$message_type = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'avaliar' && $id_usuario) {
    try {
        $nota = $_POST['nota'];
        $comentario = trim($_POST['comentario']);
        $stmt_check = $conn->prepare("SELECT id_avaliacao FROM Avaliacoes WHERE id_usuario = ? AND id_anime = ?");
        $stmt_check->bind_param("ii", $id_usuario, $id_anime);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $stmt_update = $conn->prepare("UPDATE Avaliacoes SET nota = ?, comentario = ?, data_ultima_atualizacao = CURRENT_TIMESTAMP WHERE id_usuario = ? AND id_anime = ?");
            $stmt_update->bind_param("ssii", $nota, $comentario, $id_usuario, $id_anime);
            $stmt_update->execute();
            $message = "Sua avaliação foi atualizada!";
        } else {
            $stmt_insert = $conn->prepare("CALL adicionar_avaliacao(?, ?, ?, ?)");
            $stmt_insert->bind_param("iiss", $id_usuario, $id_anime, $nota, $comentario);
            $stmt_insert->execute();
            $message = "Avaliação registrada com sucesso.";
        }
        $message_type = "success";
    } catch (mysqli_sql_exception $e) {
        $message = "Erro: " . $e->getMessage();
        $message_type = "error";
    }
}

// BUSCA DE DADOS PARA EXIBIÇÃO
$stmt_anime = $conn->prepare("SELECT * FROM vw_animes_com_generos WHERE id_anime = ?");
$stmt_anime->bind_param("i", $id_anime);
$stmt_anime->execute();
$anime = $stmt_anime->get_result()->fetch_assoc();
if (!$anime) { echo "<p>Anime não encontrado.</p>"; exit(); }

$stmt_reviews = $conn->prepare("SELECT av.*, u.nome as nome_usuario, u.foto_perfil_url FROM Avaliacoes av JOIN Usuarios u ON av.id_usuario = u.id_usuario WHERE av.id_anime = ? ORDER BY av.data_avaliacao DESC");
$stmt_reviews->bind_param("i", $id_anime);
$stmt_reviews->execute();
$avaliacoes = $stmt_reviews->get_result()->fetch_all(MYSQLI_ASSOC);

$status_usuario = null;
$avaliacao_usuario = null;
if ($id_usuario) {
    $stmt_lista = $conn->prepare("SELECT status_anime, is_favorito FROM ListaPessoalAnimes WHERE id_usuario = ? AND id_anime = ?");
    $stmt_lista->bind_param("ii", $id_usuario, $id_anime);
    $stmt_lista->execute();
    $status_usuario = $stmt_lista->get_result()->fetch_assoc();
    $stmt_lista->close();

    $stmt_aval = $conn->prepare("SELECT nota, comentario FROM Avaliacoes WHERE id_usuario = ? AND id_anime = ?");
    $stmt_aval->bind_param("ii", $id_usuario, $id_anime);
    $stmt_aval->execute();
    $avaliacao_usuario = $stmt_aval->get_result()->fetch_assoc();
    $stmt_aval->close();
}


require_once 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($anime['nome_anime'] ?? 'Anime'); ?> - Animalist</title>
    <link rel="stylesheet" href="css/universal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/anime_detalhes.css">
    <style>
        .user-actions-container .status-btn.active,
        .user-actions-container .favorite-btn.active {
            background-color: #65EBB9; color: #0d1117;
            box-shadow: 0 0 10px rgba(101, 235, 185, 0.5);
        }
    </style>
</head>
<body>
    <header class="anime-header">
        <div class="banner" style="background-image: url('<?php echo htmlspecialchars(!empty($anime['capa_url']) ? $anime['capa_url'] : 'img/banner_default.jpg'); ?>');"></div>
        <div class="header-content-wrapper">
            <div class="header-content">
                <div class="poster-container">
                    <img src="<?php echo htmlspecialchars(!empty($anime['capa_url']) ? $anime['capa_url'] : 'img/logo_site.jpg'); ?>" alt="Capa de <?php echo htmlspecialchars($anime['nome_anime'] ?? ''); ?>">
                </div>
                <div class="info">
                    <h1><?php echo htmlspecialchars($anime['nome_anime'] ?? ''); ?></h1>
                    
                    <?php if (isset($user_type) && $user_type == 1): // Verifica se é admin (tipo 1) ?>
                        <a href="admin.php?action=edit&id=<?php echo $id_anime; ?>" class="btn-admin-editar-anime">
                            <i class="fas fa-pencil-alt"></i> Editar Anime (Admin)
                        </a>
                    <?php endif; ?>
                    
                    <div class="meta-info">
                        <span><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($anime['ano_lancamento'] ?? ''); ?></span>
                        <span class="genres"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($anime['generos'] ?? ''); ?></span>
                    </div>
                    <p class="sinopse"><?php echo nl2br(htmlspecialchars($anime['sinopse'] ?? '')); ?></p>
                </div>
            </div>
        </div>
    </header>

    <div class="main-container">
        <?php if (isset($_SESSION['user_id'])): ?>
        <section id="user-actions-section" 
                 class="user-actions-container" 
                 data-anime-id="<?php echo $id_anime; ?>"
                 data-current-status="<?php echo htmlspecialchars($status_usuario['status_anime'] ?? ''); ?>"
                 data-is-favorito="<?php echo ($status_usuario['is_favorito'] ?? 0) == 1 ? 'true' : 'false'; ?>">
            
            <div class="lista-pessoal-controls">
                <button class="status-btn" data-status="Assistindo">Assistindo</button>
                <button class="status-btn" data-status="Completado">Completado</button>
                <button class="status-btn" data-status="Planejando Assistir">Planejo Assistir</button>
                <button class="status-btn" data-status="Droppado">Droppado</button>
            </div>
            <button id="favorite-btn" class="favorite-btn"><i class="fas fa-heart"></i> Favoritar</button>
        </section>
        <?php endif; ?>

        <main class="reviews-section">
            <h2 class="section-title">Avaliações da Comunidade</h2>
            
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="review-form-container card">
                <h3><?php echo $avaliacao_usuario ? 'Editar sua avaliação' : 'Deixe sua avaliação'; ?></h3>
                <form action="anime_detalhes.php?id=<?php echo $id_anime; ?>" method="POST">
                    <input type="hidden" name="action" value="avaliar">
                    <textarea name="comentario" placeholder="Escreva seu comentário/review..."><?php echo htmlspecialchars($avaliacao_usuario['comentario'] ?? ''); ?></textarea>
                    <div class="form-actions">
                        <button type="submit" name="nota" value="Recomendo" class="btn-recomendo">Recomendo</button>
                        <button type="submit" name="nota" value="Não Recomendo" class="btn-nao-recomendo">Não Recomendo</button>
                    </div>
                </form>
            </div>
            <?php else: ?>
                <p class="login-prompt card"><a href="login.php">Faça login</a> para avaliar ou adicionar à sua lista.</p>
            <?php endif; ?>
            
            <div class="reviews-grid">
                <?php if (empty($avaliacoes)): ?>
                    <p>Seja o primeiro a avaliar este anime!</p>
                <?php else: ?>
                    <?php foreach ($avaliacoes as $aval): ?>
                    <div class="review-card card">
                        <div class="review-card-header">
                            <div class="review-author">
                                <img src="<?php echo htmlspecialchars(!empty($aval['foto_perfil_url']) ? $aval['foto_perfil_url'] : 'img/perfil_default.png'); ?>" alt="Foto de <?php echo htmlspecialchars($aval['nome_usuario']); ?>">
                                <span><?php echo htmlspecialchars($aval['nome_usuario']); ?></span>
                            </div>
                            <div class="review-rating <?php echo $aval['nota'] == 'Recomendo' ? 'recomendo' : 'nao-recomendo'; ?>">
                                <i class="fas <?php echo $aval['nota'] == 'Recomendo' ? 'fa-thumbs-up' : 'fa-thumbs-down'; ?>"></i>
                                <span><?php echo htmlspecialchars($aval['nota']); ?></span>
                            </div>
                        </div>
                        <p class="review-comment"><?php echo nl2br(htmlspecialchars($aval['comentario'])); ?></p>
                        <div class="review-footer">
                            <small>Postado em <?php echo date('d/m/Y', strtotime($aval['data_avaliacao'])); ?></small>
                            <?php if (($user_type ?? 'normal') === 'admin'): ?>
                                <form action="anime_detalhes.php?id=<?php echo $id_anime; ?>" method="POST">
                                    <input type="hidden" name="action" value="delete_review">
                                    <input type="hidden" name="id_avaliacao" value="<?php echo $aval['id_avaliacao']; ?>">
                                    <button type="submit" class="btn-delete" title="Deletar avaliação"><i class="fas fa-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
    const actionsSection = document.getElementById('user-actions-section');
    if (!actionsSection) return;

    const animeId = actionsSection.dataset.animeId;
    const statusButtons = actionsSection.querySelectorAll('.status-btn');
    const favoriteBtn = document.getElementById('favorite-btn');
    
    // Função que atualiza a aparência dos botões baseado nos dados do container
    function updateButtonStates() {
        const currentStatus = actionsSection.dataset.currentStatus;
        const isFav = actionsSection.dataset.isFavorito === 'true';

        statusButtons.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.status === currentStatus);
        });
        favoriteBtn.classList.toggle('active', isFav);
    }
    
    // Aplica o estado visual inicial assim que a página carrega
    updateButtonStates();

    // Função assíncrona para lidar com a ação de clique
    async function handleAction(data) {
        const formData = new FormData();
        formData.append('action', 'manter_lista');
        for (const key in data) {
            formData.append(key, data[key]);
        }

        try {
            const response = await fetch(`anime_detalhes.php?id=${animeId}`, {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) throw new Error('Falha na resposta do servidor.');
            const result = await response.json();
            
            if (result.success) {
                // Atualiza o estado local para feedback instantâneo
                if (data.status_anime !== undefined) {
                    actionsSection.dataset.currentStatus = result.new_status || '';
                }
                if (data.is_favorito !== undefined) {
                    actionsSection.dataset.isFavorito = result.new_favorito.toString();
                }
                updateButtonStates(); // Atualiza a aparência de todos os botões
            } else {
                alert('Ocorreu um erro ao atualizar sua lista.');
            }
        } catch (error) {
            alert('Ocorreu um erro de conexão. Tente novamente.');
        }
    }

    // Adiciona os event listeners aos botões
    statusButtons.forEach(button => {
        button.addEventListener('click', () => handleAction({ status_anime: button.dataset.status }));
    });

    favoriteBtn.addEventListener('click', () => {
        const currentlyFavorito = actionsSection.dataset.isFavorito === 'true';
        handleAction({ is_favorito: !currentlyFavorito });
    });
});
    </script>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>