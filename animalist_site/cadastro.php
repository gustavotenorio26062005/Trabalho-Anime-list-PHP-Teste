<?php
// Inclui o arquivo de conexão com o banco de dados.
// Certifique-se de que 'includes/db_connect.php' está no caminho correto.
require_once 'includes/db_connect.php';
// Inclui o cabeçalho HTML, que contém a tag <html>, <head>, e o início do <body>.
require_once 'includes/header.php';

// Variáveis para armazenar mensagens de feedback para o usuário (sucesso ou erro)
$message = '';
$message_type = ''; // Pode ser 'success' ou 'error'

// Verifica se o formulário foi enviado usando o método POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta e limpa os dados do formulário para evitar espaços em branco indesejados.
    // htmlspecialchars() é usado para prevenir ataques XSS ao exibir os dados novamente no formulário.
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $celular = trim($_POST['celular']); // Celular é opcional, mas trim() é bom.
    $senha = $_POST['senha']; // Senha sem hash para fins de exemplo
    $confirmar_senha = $_POST['confirmar_senha'];

    // --- Validações BÁSICAS no PHP ---
    // Verifica se os campos obrigatórios (nome, email, senha, confirmar_senha) não estão vazios.
    if (empty($nome) || empty($email) || empty($senha) || empty($confirmar_senha)) {
        $message = "Todos os campos obrigatórios (Nome, E-mail, Senha) devem ser preenchidos.";
        $message_type = "error";
    }
    // Verifica se o formato do e-mail é válido.
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Formato de e-mail inválido.";
        $message_type = "error";
    }
    // Verifica se as senhas digitadas são iguais.
    elseif ($senha !== $confirmar_senha) {
        $message = "As senhas não coincidem.";
        $message_type = "error";
    }
    // Verifica se a senha tem um comprimento mínimo.
    elseif (strlen($senha) < 6) {
        $message = "A senha deve ter no mínimo 6 caracteres.";
        $message_type = "error";
    }
    // Se todas as validações passarem, tenta inserir no banco de dados.
    else {
        // A senha será salva em texto puro (ATENÇÃO: Não seguro para produção!)
        $senha_para_salvar = $senha;

        // Inicia uma transação no banco de dados.
        // Isso garante que, se houver qualquer erro durante a inserção, todas as operações
        // da transação sejam desfeitas (rollback), mantendo a integridade do banco.
        $conn->begin_transaction();

        try {
            // Prepara a query SQL para inserir os dados do novo usuário.
            // Usamos prepared statements (prepare) para prevenir SQL Injection,
            // que é uma falha de segurança comum e perigosa.
            $stmt = $conn->prepare("INSERT INTO Usuarios (nome, email, celular, senha) VALUES (?, ?, ?, ?)");

            // 'ssss' indica os tipos de dados dos parâmetros: quatro strings.
            // bind_param associa as variáveis PHP aos placeholders (?) na query.
            $stmt->bind_param("ssss", $nome, $email, $celular, $senha_para_salvar);

            // Executa a query preparada.
            if ($stmt->execute()) {
                // Se a execução for bem-sucedida, confirma a transação (salva as mudanças no banco).
                $conn->commit();
                $message = "Cadastro realizado com sucesso! Você já pode fazer login.";
                $message_type = "success";
                // Limpa os campos do formulário para uma nova submissão após o sucesso.
                $nome = $email = $celular = ''; // Define as variáveis como vazias para o formulário
            } else {
                // Se houver um erro na execução da query (ex: um trigger do banco rejeitou a inserção),
                // lança uma exceção para ser capturada pelo bloco catch.
                throw new Exception("Erro ao executar cadastro: " . $stmt->error);
            }
            // Fecha o prepared statement.
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            // Este bloco captura erros específicos do MySQL (ex: erro de sintaxe, ou erro de trigger).
            $conn->rollback(); // Desfaz todas as operações da transação em caso de erro.
            // Verifica se a mensagem de erro contém a indicação do trigger de e-mail duplicado.
            if (strpos($e->getMessage(), 'E-mail informado já está cadastrado') !== false) {
                $message = "Este e-mail já está cadastrado. Tente outro ou faça login.";
            } else {
                $message = "Erro inesperado ao cadastrar: " . $e->getMessage();
            }
            $message_type = "error";
        } catch (Exception $e) {
            // Este bloco captura outras exceções gerais que podem ocorrer.
            $conn->rollback(); // Desfaz todas as operações da transação.
            $message = "Ocorreu um erro: " . $e->getMessage();
            $message_type = "error";
        }
    }
}
?>

<h2>Cadastro de Usuário</h2>
<div class="form-container">
    <?php
    // Exibe a mensagem de sucesso ou erro, se houver.
    if ($message):
    ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
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
// Inclui o rodapé HTML.
require_once 'includes/footer.php';
// Fecha a conexão com o banco de dados para liberar recursos.
$conn->close();
?>