<?php
require_once 'includes/db_connect.php'; // Conexão com o banco e início da sessão
require_once 'includes/header.php';

$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    if (empty($email) || empty($senha)) {
        $message = "E-mail e senha são obrigatórios.";
        $message_type = "error";
    } else {
        // Prepara a query para buscar o usuário pelo e-mail
        $stmt = $conn->prepare("SELECT id_usuario, nome, email, senha, tipo_usuario FROM Usuarios WHERE email = ?");
        $stmt->bind_param("s", $email); // 's' para um parâmetro string
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            // Verifica a senha (compare o hash)
            if (password_verify($senha, $user['senha'])) {
                // Senha correta, inicia a sessão
                $_SESSION['user_id'] = $user['id_usuario'];
                $_SESSION['user_name'] = $user['nome'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_type'] = $user['tipo_usuario'];

                $message = "Login realizado com sucesso! Redirecionando...";
                $message_type = "success";
                header("Refresh: 2; url=index.php"); // Redireciona após 2 segundos
                exit();
            } else {
                $message = "E-mail ou senha incorretos.";
                $message_type = "error";
            }
        } else {
            $message = "E-mail ou senha incorretos.";
            $message_type = "error";
        }
        $stmt->close();
    }
}
?>

<h2>Login</h2>
<div class="form-container">
    <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <form action="login.php" method="POST">
        <div class="form-group">
            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="senha">Senha:</label>
            <input type="password" id="senha" name="senha" required>
        </div>
        <div class="form-group">
            <button type="submit">Entrar</button>
        </div>
    </form>
    <p>Ainda não tem uma conta? <a href="cadastro.php">Cadastre-se aqui</a>.</p>
    </div>

<?php
require_once 'includes/footer.php';
$conn->close();
?>