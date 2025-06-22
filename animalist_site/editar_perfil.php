<?php
require_once 'includes/db_connect.php'; // Conexão com o banco e início da sessão
require_once 'includes/header.php';     // Inclui o cabeçalho

// 1. Redireciona se o usuário não estiver logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Variáveis para pré-popular o formulário
$nome = '';
$email = '';
$data_nascimento = '';
$foto_perfil_url = '';
$fundo_perfil_url = '';
$descricao = '';

// --- 2. Buscar dados atuais do usuário para pré-popular o formulário ---
$stmt_fetch = $conn->prepare("SELECT nome, email, data_nascimento, foto_perfil_url, fundo_perfil_url, descricao FROM Usuarios WHERE id_usuario = ?");
if ($stmt_fetch) {
    $stmt_fetch->bind_param("i", $user_id);
    $stmt_fetch->execute();
    $result_fetch = $stmt_fetch->get_result();
    if ($result_fetch->num_rows > 0) {
        $user_current_data = $result_fetch->fetch_assoc();
        $nome = htmlspecialchars($user_current_data['nome']);
        $email = htmlspecialchars($user_current_data['email']);
        $data_nascimento = htmlspecialchars($user_current_data['data_nascimento']);
        $foto_perfil_url = htmlspecialchars($user_current_data['foto_perfil_url']);
        $fundo_perfil_url = htmlspecialchars($user_current_data['fundo_perfil_url']);
        $descricao = htmlspecialchars($user_current_data['descricao']);
    } else {
        $message = "Erro: Dados do usuário não encontrados.";
        $message_type = "error";
    }
    $stmt_fetch->close();
} else {
    $message = "Erro ao preparar consulta de dados do usuário.";
    $message_type = "error";
}


// --- 3. Processar envio do formulário (POST request) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta os dados do formulário
    $new_nome = trim($_POST['nome']);
    $new_email = trim($_POST['email']);
    $new_data_nascimento = trim($_POST['data_nascimento']);
    $new_foto_perfil_url = trim($_POST['foto_perfil_url']);
    $new_fundo_perfil_url = trim($_POST['fundo_perfil_url']);
    $new_descricao = trim($_POST['descricao']);
    $new_senha = $_POST['senha_nova']; // Campo para nova senha (opcional)
    $confirm_senha = $_POST['confirmar_senha_nova']; // Confirmação de nova senha

    $errors = [];

    // Validações
    if (empty($new_nome)) { $errors[] = "O nome é obrigatório."; }
    if (empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) { $errors[] = "E-mail inválido ou vazio."; }
    if (empty($new_data_nascimento)) { $errors[] = "Data de nascimento é obrigatória."; }

    // Validação da idade mínima (replicada do cadastro.php)
    if (empty($errors)) { // Só valida idade se outros campos estiverem ok
        try {
            $data_nasc_obj = new DateTime($new_data_nascimento);
            $hoje = new DateTime();
            $idade = $data_nasc_obj->diff($hoje)->y;

            if ($idade < 13) {
                $errors[] = "Você deve ter pelo menos 13 anos.";
            } elseif ($data_nasc_obj > $hoje) {
                $errors[] = "A data de nascimento não pode ser no futuro.";
            }
        } catch (Exception $e) {
            $errors[] = "Formato de data de nascimento inválido. Use AAAA-MM-DD.";
        }
    }

    // Validação de nova senha (se preenchida)
    $update_password = false;
    $senha_para_salvar = '';
    if (!empty($new_senha)) {
        if ($new_senha !== $confirm_senha) {
            $errors[] = "As novas senhas não coincidem.";
        } elseif (strlen($new_senha) < 6) {
            $errors[] = "A nova senha deve ter no mínimo 6 caracteres.";
        } else {
            $senha_para_salvar = $new_senha; // Senha em texto puro (ATENÇÃO: INSEGURO EM PRODUÇÃO)
            $update_password = true;
        }
    }

    // Se houver erros, exibe e não processa
    if (!empty($errors)) {
        $message = "Erro ao atualizar perfil: " . implode("<br>", $errors);
        $message_type = "error";
        // Mantém os valores digitados no formulário em caso de erro
        $nome = htmlspecialchars($new_nome);
        $email = htmlspecialchars($new_email);
        $data_nascimento = htmlspecialchars($new_data_nascimento);
        $foto_perfil_url = htmlspecialchars($new_foto_perfil_url);
        $fundo_perfil_url = htmlspecialchars($new_fundo_perfil_url);
        $descricao = htmlspecialchars($new_descricao);
    } else {
        // --- Inicia a transação para garantir que a atualização seja atômica ---
        $conn->begin_transaction();

        try {
            // Constrói a query UPDATE dinamicamente
            $sql_update = "UPDATE Usuarios SET nome = ?, email = ?, data_nascimento = ?, foto_perfil_url = ?, fundo_perfil_url = ?, descricao = ?";
            $params = [$new_nome, $new_email, $new_data_nascimento, $new_foto_perfil_url, $new_fundo_perfil_url, $new_descricao];
            $types = "ssssss"; // Tipos para os parâmetros padrão

            if ($update_password) {
                $sql_update .= ", senha = ?";
                $params[] = $senha_para_salvar;
                $types .= "s";
            }
            $sql_update .= " WHERE id_usuario = ?";
            $params[] = $user_id;
            $types .= "i"; // 'i' para o id_usuario (integer)

            $stmt_update = $conn->prepare($sql_update);

            // bind_param precisa dos parâmetros como referências, então usamos call_user_func_array
            // Cria um array de referências para os parâmetros
            $bind_names[] = $types;
            for ($i = 0; $i < count($params); $i++) {
                $bind_name = 'bind' . $i;
                $$bind_name = $params[$i];
                $bind_names[] = &$$bind_name;
            }
            call_user_func_array([$stmt_update, 'bind_param'], $bind_names);
            
            if ($stmt_update->execute()) {
                $conn->commit(); // Confirma as mudanças
                $message = "Perfil atualizado com sucesso!";
                $message_type = "success";

                // Atualiza as variáveis de sessão para refletir as mudanças imediatamente
                $_SESSION['user_name'] = $new_nome;
                $_SESSION['user_email'] = $new_email;

                // Atualiza as variáveis de formulário com os novos valores para exibição
                $nome = htmlspecialchars($new_nome);
                $email = htmlspecialchars($new_email);
                $data_nascimento = htmlspecialchars($new_data_nascimento);
                $foto_perfil_url = htmlspecialchars($new_foto_perfil_url);
                $fundo_perfil_url = htmlspecialchars($new_fundo_perfil_url);
                $descricao = htmlspecialchars($new_descricao);

            } else {
                throw new Exception("Erro ao atualizar perfil no banco de dados: " . $stmt_update->error);
            }
            $stmt_update->close();
        } catch (mysqli_sql_exception $e) {
            $conn->rollback(); // Desfaz a transação em caso de erro SQL
            if (strpos($e->getMessage(), 'Duplicate entry') !== false && strpos($e->getMessage(), 'email') !== false) {
                $message = "Erro: Este e-mail já está em uso por outro usuário.";
            } else {
                $message = "Erro no banco de dados: " . $e->getMessage();
            }
            $message_type = "error";
        } catch (Exception $e) {
            $conn->rollback(); // Desfaz a transação em caso de outros erros
            $message = "Ocorreu um erro inesperado: " . $e->getMessage();
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - Animalist</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/universal.css">
    <link rel="stylesheet" href="css/perfil_edit.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php require_once 'includes/header.php'; // Inclua o header para a barra de navegação ?>

    <main>
        <div class="form-container">
            <div style="text-align: center;">
                <h2>Cadastro de usuário</h2>
            </div>
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <form action="editar_perfil.php" method="POST">
                <div class="form-group">
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" value="<?php echo $nome; ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">E-mail:</label>
                    <input type="email" id="email" name="email" value="<?php echo $email; ?>" required>
                </div>
                <div class="form-group">
                    <label for="data_nascimento">Data de Nascimento:</label>
                    <input type="date" id="data_nascimento" name="data_nascimento" value="<?php echo $data_nascimento; ?>" required>
                </div>
                <div class="form-group">
                    <label for="foto_perfil_url">URL da Foto de Perfil:</label>
                    <input type="text" id="foto_perfil_url" name="foto_perfil_url" value="<?php echo $foto_perfil_url; ?>">
                    <?php if (!empty($foto_perfil_url)): ?><img src="<?php echo $foto_perfil_url; ?>" alt="Foto Atual" style="max-width: 100px; max-height: 100px; margin-top: 5px; border-radius: 5px;"><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="fundo_perfil_url">URL do Fundo de Perfil:</label>
                    <input type="text" id="fundo_perfil_url" name="fundo_perfil_url" value="<?php echo $fundo_perfil_url; ?>">
                    <?php if (!empty($fundo_perfil_url)): ?><img src="<?php echo $fundo_perfil_url; ?>" alt="Fundo Atual" style="max-width: 200px; max-height: 50px; margin-top: 5px; border-radius: 5px;"><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="descricao">Descrição:</label>
                    <textarea id="descricao" name="descricao" rows="4"><?php echo $descricao; ?></textarea>
                </div>

                <h3>Alterar Senha (Opcional)</h3>
                <p>Preencha apenas se desejar mudar sua senha.</p>
                <div class="form-group">
                    <label for="senha_nova">Nova Senha:</label>
                    <input type="password" id="senha_nova" name="senha_nova">
                </div>
                <div class="form-group">
                    <label for="confirmar_senha_nova">Confirmar Nova Senha:</label>
                    <input type="password" id="confirmar_senha_nova" name="confirmar_senha_nova">
                </div>

                <div class="form-group">
                    <button type="submit">Salvar Alterações</button>
                </div>
            </form>
            <p><a href="perfil.php">Voltar para o Perfil</a></p>
        </div>
    </main>

    <?php
    require_once 'includes/footer.php';
    if (isset($conn)) {
        $conn->close();
    }
    ?>
</body>
</html>