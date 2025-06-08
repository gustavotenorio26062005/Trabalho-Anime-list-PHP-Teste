<?php
// 1. INCLUIR CONEXÃO COM O BANCO E INICIAR LÓGICA DE PROCESSAMENTO
require_once 'includes/db_connect.php'; // Este arquivo não deve produzir output se chamar session_start()

$message = '';
$message_type = '';

// 2. PROCESSAR O FORMULÁRIO DE LOGIN (SE SUBMETIDO) ANTES DE QUALQUER HTML
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Iniciar a sessão AQUI se ainda não foi iniciada e se você vai definir variáveis de sessão
    // antes de incluir o header.php (que também tenta iniciar a sessão).
    // O header.php já tem session_start() no topo, o que é bom.
    // Mas se você redirecionar COM header() ANTES de incluir header.php,
    // você precisa garantir que a sessão está ativa para definir $_SESSION.
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    if (empty($email) || empty($senha)) {
        $message = "E-mail e senha são obrigatórios.";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare("SELECT u.id_usuario, u.nome, u.email, u.senha, tu.tipo AS tipo_usuario FROM Usuarios u JOIN TipoUsuario tu ON u.id_tipo_usuario = tu.id_tipo_usuario WHERE u.email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($senha, $user['senha'])) {
                // Sessão já deve estar iniciada pelo bloco acima ou pelo header.php se não redirecionar.
                $_SESSION['user_id'] = $user['id_usuario'];
                $_SESSION['user_name'] = $user['nome'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_type'] = $user['tipo_usuario'];

                // Redirecionamento direto. A mensagem de "sucesso" não será vista.
                // Se você quer mostrar mensagem ANTES de redirecionar, não use header() assim.
                header("Location: index.php");
                exit(); // É crucial chamar exit() após um header de redirecionamento.

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

// 3. AGORA, SE NÃO HOUVE REDIRECIONAMENTO, INCLUIR O HEADER E MOSTRAR O RESTANTE DA PÁGINA
// O header.php (nosso arquivo fundido) já contém session_start() no topo dele,
// o que é correto e necessário para que ele possa ler as variáveis de sessão
// para exibir os links corretos (Perfil/Sair ou Login/Cadastro).
require_once 'includes/header.php'; // Ou header_integrado.php, o nome que você deu ao arquivo fundido.
?>

<h2>Login</h2>
<div class="form-container">
    <?php if ($message): // Esta mensagem só será exibida se o login falhar (pois se tiver sucesso, redireciona) ?>
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
if (isset($conn)) { // Boa prática verificar se $conn existe antes de fechar
    $conn->close();
}
?>