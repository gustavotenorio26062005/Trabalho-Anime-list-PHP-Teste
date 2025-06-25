<?php
// LÓGICA PHP INICIAL E PROCESSAMENTO DE DADOS
require_once 'includes/db_connect.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$id_anime = (int)($_GET['id'] ?? 0);
$id_usuario = $_SESSION['user_id'] ?? null;
$user_type = $_SESSION['user_type'] ?? null; // Initial check for user_type

// LÓGICA PARA PROCESSAR EXCLUSÃO DE AVALIAÇÃO (ADMIN)
$message = ''; // Initialize message variables
$message_type = ''; // Initialize message_type

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_review') {
    if (isset($id_usuario) && isset($user_type) && $user_type == 1) { // Check if admin (type 1)
        $id_avaliacao_para_deletar = (int)($_POST['id_avaliacao'] ?? 0);

        if ($id_avaliacao_para_deletar > 0) {
            try {
                $stmt_delete_review = $conn->prepare("DELETE FROM Avaliacoes WHERE id_avaliacao = ?");
                $stmt_delete_review->bind_param("i", $id_avaliacao_para_deletar);
                if ($stmt_delete_review->execute()) {
                    $message = "Avaliação removida com sucesso.";
                    $message_type = "success";
                } else {
                    $message = "Erro ao remover a avaliação.";
                    $message_type = "error";
                }
                $stmt_delete_review->close();
            } catch (mysqli_sql_exception $e) {
                $message = "Erro no servidor ao deletar avaliação: " . $e->getMessage();
                $message_type = "error";
            }
        } else {
            $message = "ID da avaliação inválido para exclusão.";
            $message_type = "error";
        }
    } else {
        $message = "Ação não permitida.";
        $message_type = "error";
    }
}


// Lógica para processar a ação dos botões (manter na lista)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'manter_lista' && $id_usuario) {

    // Buscar a entrada atual na lista pessoal para comparar e para usar como base
    $stmt_check = $conn->prepare("SELECT status_anime, is_favorito FROM ListaPessoalAnimes WHERE id_usuario = ? AND id_anime = ?");
    $stmt_check->bind_param("ii", $id_usuario, $id_anime);
    $stmt_check->execute();
    $entry_atual = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    $status_para_salvar = $entry_atual['status_anime'] ?? null;
    $favorito_para_salvar = $entry_atual['is_favorito'] ?? 0; 

    if (array_key_exists('status_anime', $_POST)) {
        $status_enviado_do_js = $_POST['status_anime'];
        if ($status_enviado_do_js === 'null') {
            $status_para_salvar = null;
        } else {
            $status_para_salvar = $status_enviado_do_js;
        }
    }

    if (array_key_exists('is_favorito', $_POST)) {
        $favorito_enviado_do_js_str = $_POST['is_favorito']; 
        $favorito_para_salvar = ($favorito_enviado_do_js_str === 'true') ? 1 : 0;
    }

    try {
        if ($status_para_salvar === null && $favorito_para_salvar == 0) {
            if ($entry_atual) {
                $stmt_delete = $conn->prepare("DELETE FROM ListaPessoalAnimes WHERE id_usuario = ? AND id_anime = ?");
                $stmt_delete->bind_param("ii", $id_usuario, $id_anime);
                $stmt_delete->execute();
                $stmt_delete->close();
            }
        } else {
            $stmt = $conn->prepare("CALL adicionar_atualizar_anime_listapessoal(?, ?, ?, ?)");
            $stmt->bind_param("iisi", $id_usuario, $id_anime, $status_para_salvar, $favorito_para_salvar);
            $stmt->execute();
            $stmt->close();
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'new_status' => $status_para_salvar, 'new_favorito' => (bool)$favorito_para_salvar]);
        exit();

    } catch (mysqli_sql_exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => "Erro no servidor: " . $e->getMessage()]);
        exit();
    }
}

// Re-fetch user_type after potential session changes or for general page load
$user_type = $_SESSION['user_type'] ?? 'normal'; // Now it's 'normal' if not set, or the actual value (e.g., 1 for admin)

if ($id_anime <= 0) {
    header("Location: pesquisar.php");
    exit();
}

// LÓGICA PARA PROCESSAR NOVAS AVALIAÇÕES (FORMULÁRIO)
// $message, $message_type are initialized above to catch delete messages as well
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
        $message_type = "success"; // Ensure message_type is set for avaliação
    } catch (mysqli_sql_exception $e) {
        $message = "Erro ao avaliar: " . $e->getMessage();
        $message_type = "error"; // Ensure message_type is set for avaliação
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
        .review-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            font-size: 0.85em;
            color: #888; /* Cor da data */
        }
        .review-footer form { /* Formulário de delete */
            margin: 0; 
        }
        .review-footer .btn-delete { /* Botão de delete no rodapé da avaliação */
            background-color: transparent;
            color: var(--cor-erro); /* Vermelho para erro/delete */
            border: none;
            padding: 0 5px; 
            cursor: pointer;
            font-size: 1em; 
            line-height: 1;
        }
        .review-footer .btn-delete:hover {
            color: #a0232f; /* Vermelho mais escuro no hover */
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
                <div class="message <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div>
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
                            <?php if (isset($user_type) && $user_type == 1): // Verifica se é admin (tipo 1) ?>
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
    
    function updateButtonStates() {
        const currentStatus = actionsSection.dataset.currentStatus;
        const isFav = actionsSection.dataset.isFavorito === 'true';

        statusButtons.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.status === currentStatus);
        });
        favoriteBtn.classList.toggle('active', isFav);
    }
    
    updateButtonStates();

    async function handleAction(data) {
        const formData = new FormData();
        formData.append('action', 'manter_lista');

        if (data.hasOwnProperty('status_anime')) { 
            formData.append('status_anime', data.status_anime);
        }
        if (data.hasOwnProperty('is_favorito')) { 
            formData.append('is_favorito', data.is_favorito);
        }

        try {
            const response = await fetch(`anime_detalhes.php?id=${animeId}`, {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                console.error('Resposta do servidor não OK:', response.status, await response.text());
                throw new Error(`Falha na resposta do servidor (${response.status})`);
            }
            const result = await response.json();
            
            if (result.success) {
                if (data.hasOwnProperty('status_anime')) { 
                    actionsSection.dataset.currentStatus = result.new_status || '';
                }
                if (data.hasOwnProperty('is_favorito')) { 
                    actionsSection.dataset.isFavorito = result.new_favorito ? 'true' : 'false';
                }
                updateButtonStates();
            } else {
                alert('Ocorreu um erro ao atualizar sua lista: ' + (result.message || 'Erro desconhecido.'));
            }
        } catch (error) {
            console.error('Erro ao executar ação:', error);
            alert('Ocorreu um erro de conexão ou processamento. Tente novamente. Detalhes: ' + error.message);
        }
    }

    statusButtons.forEach(button => {
        button.addEventListener('click', function() {
            const statusClicado = this.dataset.status;
            const statusAtualNoDataset = actionsSection.dataset.currentStatus;

            let statusParaEnviar;
            if (statusAtualNoDataset === statusClicado) {
                statusParaEnviar = null; 
            } else {
                statusParaEnviar = statusClicado;
            }
            handleAction({ status_anime: statusParaEnviar }); 
        });
    });

    if (favoriteBtn) { 
        favoriteBtn.addEventListener('click', () => {
            const currentlyFavorito = actionsSection.dataset.isFavorito === 'true';
            handleAction({ is_favorito: !currentlyFavorito });
        });
    }
});
    </script>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>