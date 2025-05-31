<?php
require_once 'includes/db_connect.php'; // Conexão e sessão
require_once 'includes/header.php';

// Redireciona se o usuário não estiver logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
$user_type = $_SESSION['user_type'];

$user_data = [];
$animes_pessoais = [];
$avaliacoes = [];

// Obter dados completos do usuário
$stmt_user = $conn->prepare("SELECT nome, email, celular, foto_perfil_url, fundo_perfil_url, descricao FROM Usuarios WHERE id_usuario = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if ($result_user->num_rows > 0) {
    $user_data = $result_user->fetch_assoc();
}
$stmt_user->close();

// Obter lista pessoal de animes do usuário
$stmt_list = $conn->prepare("
    SELECT LA.status_anime, A.nome, A.ano_lancamento, A.capa_url
    FROM ListaPessoalAnimes LA
    JOIN Animes A ON LA.id_anime = A.id_anime
    WHERE LA.id_usuario = ?
    ORDER BY LA.status_anime, A.nome ASC
");
$stmt_list->bind_param("i", $user_id);
$stmt_list->execute();
$result_list = $stmt_list->get_result();
while ($row = $result_list->fetch_assoc()) {
    $animes_pessoais[] = $row;
}
$stmt_list->close();

// Obter avaliações do usuário
$stmt_reviews = $conn->prepare("
    SELECT AV.nota, AV.comentario, A.nome
    FROM Avaliacoes AV
    JOIN Animes A ON AV.id_anime = A.id_anime
    WHERE AV.id_usuario = ?
    ORDER BY A.nome ASC
");
$stmt_reviews->bind_param("i", $user_id);
$stmt_reviews->execute();
$result_reviews = $stmt_reviews->get_result();
while ($row = $result_reviews->fetch_assoc()) {
    $avaliacoes[] = $row;
}
$stmt_reviews->close();

?>

<h2>Perfil de <?php echo htmlspecialchars($user_data['nome'] ?? $user_name); ?></h2>

<div class="profile-info">
    <img src="<?php echo htmlspecialchars($user_data['foto_perfil_url'] ?? 'https://via.placeholder.com/150/CCCCCC/000000?text=Foto'); ?>" alt="Foto de Perfil">
    <h3><?php echo htmlspecialchars($user_data['nome'] ?? $user_name); ?></h3>
    <p><strong>E-mail:</strong> <?php echo htmlspecialchars($user_data['email'] ?? $user_email); ?></p>
    <?php if (!empty($user_data['celular'])): ?>
        <p><strong>Celular:</strong> <?php echo htmlspecialchars($user_data['celular']); ?></p>
    <?php endif; ?>
    <?php if (!empty($user_data['descricao'])): ?>
        <p><strong>Sobre mim:</strong> <?php echo nl2br(htmlspecialchars($user_data['descricao'])); ?></p>
    <?php endif; ?>
    <p><strong>Tipo de Usuário:</strong> <?php echo htmlspecialchars($user_type); ?></p>

    <div class="profile-actions">
        <a href="editar_perfil.php" class="button">Editar Perfil</a>
        <button onclick="alert('Funcionalidade de deletar conta a ser implementada!')">Deletar Conta</button>
    </div>
</div>

<h3>Minha Lista de Animes</h3>
<?php if (!empty($animes_pessoais)): ?>
    <div class="anime-grid">
        <?php foreach ($animes_pessoais as $anime): ?>
            <div class="anime-card">
                <img src="<?php echo htmlspecialchars($anime['capa_url'] ?? 'https://via.placeholder.com/200x250?text=Sem+Capa'); ?>" alt="<?php echo htmlspecialchars($anime['nome']); ?>">
                <h3><?php echo htmlspecialchars($anime['nome']); ?></h3>
                <p>Status: <strong><?php echo htmlspecialchars($anime['status_anime']); ?></strong></p>
                </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p>Sua lista de animes está vazia. Comece a adicionar <a href="animes.php">aqui</a>!</p>
<?php endif; ?>

<h3>Minhas Avaliações</h3>
<?php if (!empty($avaliacoes)): ?>
    <div class="reviews-list">
        <?php foreach ($avaliacoes as $avaliacao): ?>
            <div class="review-item" style="border: 1px solid #eee; padding: 10px; margin-bottom: 10px; border-radius: 5px;">
                <h4>Anime: <?php echo htmlspecialchars($avaliacao['nome']); ?></h4>
                <p>Nota: <strong><?php echo htmlspecialchars($avaliacao['nota']); ?></strong></p>
                <?php if (!empty($avaliacao['comentario'])): ?>
                    <p>Comentário: <?php echo nl2br(htmlspecialchars($avaliacao['comentario'])); ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p>Você ainda não fez nenhuma avaliação. <a href="animes.php">Avalie alguns animes</a>!</p>
<?php endif; ?>

<?php
require_once 'includes/footer.php';
$conn->close();
?>