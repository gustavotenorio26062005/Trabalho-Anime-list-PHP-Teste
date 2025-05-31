<?php
require_once 'includes/db_connect.php'; // Inclui o arquivo de conexão com o banco
require_once 'includes/header.php';    // Inclui o cabeçalho
?>

<h1>Bem-vindo ao Animalist!</h1>
<p>Seu guia completo para o mundo dos animes. Encontre, avalie e gerencie seus animes favoritos.</p>

<?php if (isset($_SESSION['user_id'])): ?>
    <p>Olá, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! Explore a seção de animes ou vá para o seu perfil.</p>
    <div class="profile-actions">
        <a href="animes.php" class="button">Ver Animes</a>
        <a href="perfil.php" class="button">Meu Perfil</a>
    </div>
<?php else: ?>
    <p>Ainda não tem uma conta? <a href="cadastro.php">Cadastre-se</a> agora e comece a organizar sua lista!</p>
    <p>Já é membro? <a href="login.php">Faça login</a> para acessar sua conta.</p>
<?php endif; ?>

<?php
require_once 'includes/footer.php'; // Inclui o rodapé