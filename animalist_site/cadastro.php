<?php
require_once 'includes/db_connect.php';
require_once 'includes/header.php';

$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $celular = trim($_POST['celular']);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    // Validações básicas no PHP
    if (empty($nome) || empty($email) || empty($senha) || empty($confirmar_senha)) {
        $message = "Todos os campos obrigatórios (Nome, E-mail, Senha) devem ser preenchidos.";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Formato de e-mail inválido.";
        $message_type = "error";
    } elseif ($senha !== $confirmar_senha) {
        $message = "As senhas não coincidem.";
        $message_type = "error";
    } elseif (strlen($senha) < 6) {
        $message = "A senha deve ter no mínimo 6 caracteres.";
        $message_type = "error";
    } else {
        // Gera o hash da senha (MUITO IMPORTANTE para segurança)
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        // Inicia a transação
        $conn->begin_transaction();

        try {
            // Prepara a query para inserir o usuário
            $stmt = $conn->prepare("INSERT INTO Usuarios (nome, email, celular, senha) VALUES (?, ?, ?, ?)");
            // 'ssss' indica 4 parâmetros do tipo string
            $stmt->bind_param("ssss", $nome, $email, $celular, $senha_hash);

            if ($stmt->execute()) {
                $conn->commit(); // Confirma a transação
                $message = "Cadastro realizado com sucesso! Você já pode fazer login.";
                $message_type = "success";
                // Limpa os campos do formulário após o sucesso
                $nome = $email = $celular = '';
            } else {
                // Se houver um erro de execução (ex: trigger de e-mail duplicado)
                throw new Exception("Erro ao executar cadastro: " . $stmt->error);
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $conn->rollback(); // Reverte a transação em caso de erro SQL
            // Captura a mensagem de erro do trigger (se houver)
            if (strpos($e->getMessage(), 'E-mail informado já está cadastrado') !== false) {
                $message = "Este e-mail já está cadastrado. Tente outro ou faça login.";
            } elseif (strpos($e->getMessage(), 'nome de usuário já existente') !== false) { // Se você tivesse um trigger para nome de usuário
                 $message = "Este nome de usuário já está em uso. Escolha outro.";
            } else {
                $message = "Erro inesperado ao cadastrar: " . $e->getMessage();
            }
            $message_type = "error";
        } catch (Exception $e) {
            $conn->rollback(); // Reverte a transação em caso de outros erros
            $message = "Ocorreu um erro: " . $e->getMessage();
            $message_type = "error";
        }
    }
}
?>

<h2>Cadastro de Usuário</h2>
<div class="form-container">
    <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <form action="cadastro.php" method="POST">
        <div class="form-group">
            <label for="nome">Nome Completo:</label>
            <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($nome ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="celular">Celular (opcional):</label>
            <input type="text" id="celular" name="celular" value="<?php echo htmlspecialchars($celular ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="senha">Senha:</label>
            <input type="password" id="senha" name="senha" required>
        </div>
        <div class="form-group">
            <label for="confirmar_senha">Confirmar Senha:</label>
            <input type="password" id="confirmar_senha" name="confirmar_senha" required>
        </div>
        <div class="form-group">
            <button type="submit">Cadastrar</button>
        </div>
    </form>
    <p>Já tem uma conta? <a href="login.php">Faça login aqui</a>.</p>
</div>

<?php
require_once 'includes/footer.php';
$conn->close(); // Fecha a conexão com o banco de dados
?>