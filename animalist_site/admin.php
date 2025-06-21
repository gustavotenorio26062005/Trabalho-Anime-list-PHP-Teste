<?php
// 1. LÓGICA PHP INICIAL
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db_connect.php';
require_once 'includes/header.php';

// Proteção da página
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 1) {
    die("Acesso negado. Esta página é restrita a administradores.");
}

// Lógica para buscar foto de perfil do admin logado
$user_profile_pic = "img/perfil_default.png";
if (isset($_SESSION['user_id'])) {
    $stmt_user = $conn->prepare("SELECT foto_perfil_url FROM Usuarios WHERE id_usuario = ?");
    if ($stmt_user) {
        $stmt_user->bind_param("i", $_SESSION['user_id']);
        $stmt_user->execute();
        $result_user = $stmt_user->get_result()->fetch_assoc();
        if ($result_user && !empty($result_user['foto_perfil_url'])) {
            $user_profile_pic = $result_user['foto_perfil_url'];
        }
        $stmt_user->close();
    }
}

// Lógica de processamento do formulário (Adicionar e Editar)
$mensagem = '';
$tipo_mensagem = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_anime_edit = $_POST['id_anime'] ?? null;
    $nome = $_POST['nome'] ?? '';
    $capa_url = $_POST['capa_url'] ?? '';
    $sinopse = $_POST['sinopse'] ?? '';
    $generos_str = $_POST['generos'] ?? '';
    $ano_lancamento = !empty($_POST['ano_lancamento']) ? (int)$_POST['ano_lancamento'] : null;

    if (empty($nome) || empty($capa_url) || empty($sinopse) || empty($generos_str)) {
        $mensagem = "Erro: Todos os campos, exceto o ano, são obrigatórios.";
        $tipo_mensagem = "erro";
    } else {
        $conn->begin_transaction();
        try {
            if ($id_anime_edit) {
                // LÓGICA DE UPDATE
                $stmt_anime = $conn->prepare("UPDATE Animes SET nome = ?, ano_lancamento = ?, sinopse = ?, capa_url = ? WHERE id_anime = ?");
                $stmt_anime->bind_param("sissi", $nome, $ano_lancamento, $sinopse, $capa_url, $id_anime_edit);
                $stmt_anime->execute();
                $stmt_anime->close();
                $stmt_delete_genres = $conn->prepare("DELETE FROM AnimeGeneros WHERE id_anime = ?");
                $stmt_delete_genres->bind_param("i", $id_anime_edit);
                $stmt_delete_genres->execute();
                $stmt_delete_genres->close();
                $id_anime = $id_anime_edit;
                $mensagem = "Anime atualizado com sucesso!";
            } else {
                // LÓGICA DE INSERT
                $stmt_anime = $conn->prepare("INSERT INTO Animes (nome, ano_lancamento, sinopse, capa_url) VALUES (?, ?, ?, ?)");
                $stmt_anime->bind_param("siss", $nome, $ano_lancamento, $sinopse, $capa_url);
                $stmt_anime->execute();
                $id_anime = $conn->insert_id;
                $stmt_anime->close();
                $mensagem = "Anime adicionado com sucesso!";
            }
            // Lógica para processar gêneros
            $generos_array = array_unique(explode(',', $generos_str));
            foreach ($generos_array as $nome_genero) {
                $nome_genero = trim($nome_genero);
                if (empty($nome_genero)) continue;
                $id_genero = null;
                $stmt_check = $conn->prepare("SELECT id_genero FROM Generos WHERE nome_genero = ?");
                $stmt_check->bind_param("s", $nome_genero);
                $stmt_check->execute();
                $result = $stmt_check->get_result();
                if ($row = $result->fetch_assoc()) {
                    $id_genero = $row['id_genero'];
                } else {
                    $stmt_insert = $conn->prepare("INSERT INTO Generos (nome_genero) VALUES (?)");
                    $stmt_insert->bind_param("s", $nome_genero);
                    $stmt_insert->execute();
                    $id_genero = $conn->insert_id;
                    $stmt_insert->close();
                }
                $stmt_check->close();
                $stmt_link = $conn->prepare("INSERT INTO AnimeGeneros (id_anime, id_genero) VALUES (?, ?)");
                $stmt_link->bind_param("ii", $id_anime, $id_genero);
                $stmt_link->execute();
                $stmt_link->close();
            }
            $conn->commit();
            $tipo_mensagem = "sucesso";
        } catch (Exception $e) {
            $conn->rollback();
            $mensagem = "Erro na operação com o banco de dados: " . $e->getMessage();
            $tipo_mensagem = "erro";
        }
    }
}

// Lógica de exibição (roteador)
$action = $_GET['action'] ?? 'list';
$anime_para_editar = null;
$todos_animes = [];
if ($action == 'edit' && isset($_GET['id'])) {
    $id_para_editar = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM Animes WHERE id_anime = ?");
    $stmt->bind_param("i", $id_para_editar);
    $stmt->execute();
    $anime_para_editar = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $stmt_genres = $conn->prepare("SELECT g.nome_genero FROM Generos g JOIN AnimeGeneros ag ON g.id_genero = ag.id_genero WHERE ag.id_anime = ?");
    $stmt_genres->bind_param("i", $id_para_editar);
    $stmt_genres->execute();
    $result_genres = $stmt_genres->get_result();
    $generos_atuais = [];
    while($row = $result_genres->fetch_assoc()){ $generos_atuais[] = $row['nome_genero']; }
    $anime_para_editar['generos'] = $generos_atuais;
    $stmt_genres->close();
} elseif ($action == 'list' || !in_array($action, ['add', 'edit'])) {
    $action = 'list';
    $result_animes = $conn->query("SELECT id_anime, nome FROM Animes ORDER BY nome ASC");
    $todos_animes = $result_animes->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Admin - Animalist</title>

    <?php require_once 'includes/fontes.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/universal.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/admin.css">

    <style>
        body {
            background-color: #1a2430; /* Fundo escuro padrão do site */
        }
    </style>
</head>
<body>

    <main>
        <div class="admin-container">
            <?php if ($action == 'add' || $action == 'edit'): ?>
                <h1><?php echo $action == 'edit' ? 'Edição do Anime' : 'Adicionar novo Anime'; ?></h1>
                <?php if (!empty($mensagem)): ?>
                    <p class="form-message <?php echo $tipo_mensagem; ?>"><?php echo htmlspecialchars($mensagem); ?></p>
                <?php endif; ?>
                <div class="form-wrapper">
                    <form class="form" action="admin.php" method="POST" onsubmit="return enviarDados()">
                        <?php if ($action == 'edit'): ?>
                            <input type="hidden" name="id_anime" value="<?php echo $anime_para_editar['id_anime']; ?>">
                        <?php endif; ?>
                        <input type="text" name="nome" placeholder="Nome do Anime" value="<?php echo htmlspecialchars($anime_para_editar['nome'] ?? ''); ?>" required>
                        <input type="number" name="ano_lancamento" placeholder="Ano de Lançamento (ex: 2024)" value="<?php echo htmlspecialchars($anime_para_editar['ano_lancamento'] ?? ''); ?>">
                        <input type="text" name="capa_url" placeholder="Link da Imagem (Capa)" value="<?php echo htmlspecialchars($anime_para_editar['capa_url'] ?? ''); ?>" required>
                        <textarea name="sinopse" placeholder="Descrição/Sinopse do Anime" required><?php echo htmlspecialchars($anime_para_editar['sinopse'] ?? ''); ?></textarea>
                        <div class="genre-input">
                            <input type="text" id="genreInput" placeholder="Insira um Gênero por vez">
                            <button type="button" onclick="addGenre()">Adicionar</button>
                        </div>
                        <div id="genreList" class="genre-list">
                            <?php if (!empty($anime_para_editar['generos'])): ?>
                                <?php foreach($anime_para_editar['generos'] as $genre): ?>
                                    <div class="genre-item"><?php echo htmlspecialchars($genre); ?><button onclick="this.parentElement.remove()" type="button">🗑️</button></div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="generos" id="generosInput" required>
                        <button type="submit" class="submit-btn"><?php echo $action == 'edit' ? 'Salvar Alterações' : 'Salvar Anime'; ?></button>
                    </form>
                </div>
                <a href="admin.php" class="back-link">← Voltar para a lista</a>
            <?php else: ?>
                <h1>Gerenciar Animes</h1>
                <a href="admin.php?action=add" class="btn-add-new">Adicionar Novo Anime</a>
                <table class="anime-list-table">
                    <thead><tr><th>Nome do Anime</th><th>Ações</th></tr></thead>
                    <tbody>
                        <?php foreach ($todos_animes as $anime): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($anime['nome']); ?></td>
                                <td><a href="admin.php?action=edit&id=<?php echo $anime['id_anime']; ?>" class="btn-edit">Editar</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>

    <script src="js/admin.js"></script>

    <?php require_once 'includes/footer.php'; ?>
</body>

</html>