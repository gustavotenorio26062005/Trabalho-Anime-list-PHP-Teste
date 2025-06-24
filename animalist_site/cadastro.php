<?php
// Inclui o arquivo de conexão com o banco de dados.
require_once 'includes/db_connect.php';
// Inclui o cabeçalho HTML.
require_once 'includes/header.php';

// Variáveis para armazenar mensagens de feedback.
$message = '';
$message_type = '';

// Variáveis para pré-popular o formulário em caso de erro, mantendo o que o usuário digitou.
$nome = '';
$email = '';
$data_nascimento = ''; 

// Verifica se o formulário foi enviado usando o método POST.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta e limpa os dados do formulário.
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $data_nascimento = trim($_POST['data_nascimento']); // Coleta a data de nascimento
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    // --- Validações no PHP ---
    // Verifica se os campos obrigatórios não estão vazios.
    if (empty($nome) || empty($email) || empty($data_nascimento) || empty($senha) || empty($confirmar_senha)) {
        $message = "Todos os campos obrigatórios (Nome, E-mail, Data de Nascimento, Senha) devem ser preenchidos.";
        $message_type = "error";
    }
    // Valida o formato do e-mail.
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Formato de e-mail inválido.";
        $message_type = "error";
    }
    // Valida se as senhas coincidem.
    elseif ($senha !== $confirmar_senha) {
        $message = "As senhas não coincidem.";
        $message_type = "error";
    }
    // Valida o comprimento mínimo da senha.
    elseif (strlen($senha) < 6) {
        $message = "A senha deve ter no mínimo 6 caracteres.";
        $message_type = "error";
    }

    else {
        try {
            $data_nasc_obj = new DateTime($data_nascimento);
            $hoje = new DateTime();
            $idade = $data_nasc_obj->diff($hoje)->y; // Obtém a diferença em anos

            if ($idade < 13) {
                $message = "Você deve ter pelo menos 13 anos para se cadastrar.";
                $message_type = "error";
            }
            // Verifica se a data de nascimento não está no futuro
            elseif ($data_nasc_obj > $hoje) {
                $message = "A data de nascimento não pode ser no futuro.";
                $message_type = "error";
            }
            // Se todas as validações passarem, tenta inserir no banco de dados.
            else {
                $senha_para_salvar = $senha; 

                $conn->begin_transaction(); // Inicia a transação

                try {
                    // Prepara a query SQL para inserir o usuário.
                    $stmt = $conn->prepare("INSERT INTO Usuarios (nome, email, data_nascimento, senha) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $nome, $email, $data_nascimento, $senha_para_salvar);

                    if ($stmt->execute()) {
                        $conn->commit(); // Confirma a transação
                        $message = "Cadastro realizado com sucesso! Você já pode fazer login.";
                        $message_type = "success";
                        // Limpa os campos do formulário após o sucesso.
                        $nome = $email = $data_nascimento = ''; // Reset para campos vazios
                    } else {
                        throw new Exception("Erro ao executar cadastro: " . $stmt->error);
                    }
                    $stmt->close();
                } catch (mysqli_sql_exception $e) {
                    $conn->rollback(); // Desfaz a transação em caso de erro SQL.
                    if (strpos($e->getMessage(), 'E-mail informado já está cadastrado') !== false) {
                        $message = "Este e-mail já está cadastrado. Tente outro ou faça login.";
                    } elseif (strpos($e->getMessage(), 'chk_idade_minima') !== false) {
                        // Este erro pode ocorrer se a validação do banco (CHECK CONSTRAINT) for ativada primeiro.
                        $message = "Você deve ter pelo menos 13 anos para se cadastrar.";
                    }
                    else {
                        $message = "Erro inesperado ao cadastrar: " . $e->getMessage();
                    }
                    $message_type = "error";
                } catch (Exception $e) {
                    $conn->rollback(); // Desfaz a transação em caso de outros erros.
                    $message = "Ocorreu um erro: " . $e->getMessage();
                    $message_type = "error";
                }
            }
        } catch (Exception $e) {
            // Captura erros da criação de DateTime (ex: formato de data inválido)
            $message = "Formato de data de nascimento inválido. Use AAAA-MM-DD.";
            $message_type = "error";
        }
    }
}
?>


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- === CSS === -->
    <link rel="stylesheet" href="css/universal.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- === FONTES === -->
     <!--<?php include __DIR__ . '/pages/constante/fontes.php' ?> -->
</head>

<div class="form-container">
    <h2>Cadastro de Usuário</h2>
    <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form action="cadastro.php" method="POST">
        <div class="form-group">
            <label for="nome">Nome Completo:</label>
            <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required>
        </div>
        <div class="form-group">
            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>
        <div class="form-group">
            <label for="data_nascimento">Data de Nascimento:</label>
            <input type="date" id="data_nascimento" name="data_nascimento" value="<?php echo htmlspecialchars($data_nascimento); ?>" required>
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
$conn->close();
?>