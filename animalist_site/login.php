<?php
// Inclui o arquivo de conexão com o banco de dados.
// Este arquivo também inicia a sessão PHP, essencial para manter o usuário logado.
require_once 'includes/db_connect.php';
// Inclui o cabeçalho HTML, que contém a estrutura inicial da página e a navegação.
require_once 'includes/header.php';

// Variáveis para armazenar mensagens de feedback (sucesso ou erro) para o usuário.
$message = '';
$message_type = ''; // Pode ser 'success' ou 'error'

// Verifica se o formulário de login foi enviado usando o método POST.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta e limpa os dados do formulário para evitar espaços em branco indesejados.
    $email = trim($_POST['email']);
    $senha = $_POST['senha']; // A senha está sendo coletada em texto puro.

    // --- Validações BÁSICAS no PHP ---
    // Verifica se os campos de e-mail e senha não estão vazios.
    if (empty($email) || empty($senha)) {
        $message = "E-mail e senha são obrigatórios.";
        $message_type = "error";
    } else {
        // Prepara a query SQL para buscar o usuário no banco de dados pelo e-mail.
        // Usar prepared statements previne ataques de SQL Injection.
        $stmt = $conn->prepare("SELECT id_usuario, nome, email, senha, tipo_usuario FROM Usuarios WHERE email = ?");
        // 's' indica que o parâmetro é uma string.
        $stmt->bind_param("s", $email);
        // Executa a query preparada.
        $stmt->execute();
        // Obtém o resultado da query.
        $result = $stmt->get_result();

        // Verifica se algum usuário foi encontrado com o e-mail fornecido.
        if ($result->num_rows > 0) {
            // Pega os dados do usuário encontrado.
            $user = $result->fetch_assoc();
            
            // --- ATENÇÃO: COMPARAÇÃO DE SENHA EM TEXTO PURO (MENOS SEGURA!) ---
            // Compara a senha digitada pelo usuário diretamente com a senha salva no banco.
            if ($senha === $user['senha']) {
                // Se as senhas forem iguais, o login é bem-sucedido.
                // Inicia a sessão PHP para armazenar informações do usuário logado.
                $_SESSION['user_id'] = $user['id_usuario'];
                $_SESSION['user_name'] = $user['nome'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_type'] = $user['tipo_usuario']; // Salva o tipo de usuário (comum/admin)

                $message = "Login realizado com sucesso! Redirecionando...";
                $message_type = "success";
                // Redireciona o usuário para a página inicial (index.php) após 2 segundos.
                header("Refresh: 2; url=index.php"); 
                exit(); // É importante usar exit() após um header() para garantir o redirecionamento.
            } else {
                // Se as senhas não coincidirem.
                $message = "E-mail ou senha incorretos.";
                $message_type = "error";
            }
        } else {
            // Se nenhum usuário for encontrado com o e-mail fornecido.
            $message = "E-mail ou senha incorretos.";
            $message_type = "error";
        }
        // Fecha o prepared statement.
        $stmt->close();
    }
}
?>

<h2>Login</h2>
<div class="form-container">
    <?php 
    // Exibe a mensagem de sucesso ou erro, se houver.
    if ($message): 
    ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
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
// Inclui o rodapé HTML.
require_once 'includes/footer.php';
// Fecha a conexão com o banco de dados.
$conn->close();
?>