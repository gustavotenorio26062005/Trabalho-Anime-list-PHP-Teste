<?php
require_once 'includes/db_connect.php';




$message = '';
$message_type = ''; 

// Verifica se o formulário de login foi enviado usando o POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    // --- Validações BÁSICAS no PHP ---
    if (empty($email) || empty($senha)) {
        $message = "E-mail e senha são obrigatórios.";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare("SELECT id_usuario, nome, email, senha, id_tipo_usuario FROM Usuarios WHERE email = ?");
        
        $stmt->bind_param("s", $email);
        
        $stmt->execute();
        
        $result = $stmt->get_result();

        // Verifica se algum usuário foi encontrado com o e-mail fornecido.
        if ($result->num_rows > 0) {
            
            $user = $result->fetch_assoc();
            
    
            // password_verify valida senhas com hash. O hash_equals mantém compatibilidade
            // com contas antigas (ex.: dados de exemplo do SQL) gravadas em texto puro,
            // que são convertidas para hash no primeiro login bem-sucedido.
            $senha_ok = password_verify($senha, $user['senha']);
            if (!$senha_ok && password_get_info($user['senha'])['algo'] === null && hash_equals($user['senha'], $senha)) {
                $senha_ok = true;
                $user['senha'] = ''; // força o rehash abaixo
            }

            if ($senha_ok) {
                if (password_needs_rehash($user['senha'], PASSWORD_DEFAULT)) {
                    $novo_hash = password_hash($senha, PASSWORD_DEFAULT);
                    $stmt_rehash = $conn->prepare("UPDATE Usuarios SET senha = ? WHERE id_usuario = ?");
                    $stmt_rehash->bind_param("si", $novo_hash, $user['id_usuario']);
                    $stmt_rehash->execute();
                    $stmt_rehash->close();
                }
                session_regenerate_id(true);
                            
                
                // Armazena os dados do usuário na sessão
                $_SESSION['user_id'] = $user['id_usuario'];
                $_SESSION['user_name'] = $user['nome'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_type'] = $user['id_tipo_usuario'];

               
                header("Location: index.php"); 

                exit();

            } else {
                
                $message = "E-mail ou senha incorretos.";
                $message_type = "error";
            }
        } else {
            // Se nenhum usuário for encontrado com o e-mail fornecido.
            $message = "E-mail ou senha incorretos.";
            $message_type = "error";
        }

        $stmt->close();
    }
}


require_once 'includes/header.php';
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- === CSS === -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/universal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<div class="form-container">
    <div style="text-align: center;">
    <h2>Login</h2>
    </div>
    <?php 
    
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

require_once 'includes/footer.php';



$conn->close();
?>