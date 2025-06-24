<?php
// Certifique-se de que os erros do PHP sejam exibidos para depuração (remova em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/db_connect.php'; 
// Inicie a sessão se você for pegar o user_id dela
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json'); 

$response = ['success' => false, 'message' => 'Ocorreu um erro inicial.'];

// Verifique se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Usuário não autenticado. Faça login novamente.';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['password'])) {
        $entered_password = $_POST['password'];
        $user_id = $_SESSION['user_id'];

        $stmt_check_password = $conn->prepare("SELECT senha FROM Usuarios WHERE id_usuario = ?");
        if (!$stmt_check_password) {
            $response['message'] = "Erro ao preparar a consulta de senha: " . $conn->error;
            error_log("Erro SQL (prepare senha): " . $conn->error); // Log para o servidor
            echo json_encode($response);
            exit();
        }
        $stmt_check_password->bind_param("i", $user_id);
        $stmt_check_password->execute();
        $result = $stmt_check_password->get_result();

        if ($result->num_rows === 1) {
            $user_row = $result->fetch_assoc();
            $stored_plain_password = $user_row['senha']; 

            //  COMPARAR A SENHA FORNECIDA COM A SENHA ARMAZENADA
            if ($entered_password === $stored_plain_password) {
                $conn->begin_transaction();
                try {
                    
                    $stmt_delete_dependents1 = $conn->prepare("DELETE FROM Avaliacoes WHERE id_usuario = ?");
                    $stmt_delete_dependents1->bind_param("i", $user_id);
                    $stmt_delete_dependents1->execute();
                    $stmt_delete_dependents1->close();

                    $stmt_delete_dependents2 = $conn->prepare("DELETE FROM ListaPessoalAnimes WHERE id_usuario = ?");
                    $stmt_delete_dependents2->bind_param("i", $user_id);
                    $stmt_delete_dependents2->execute();
                    $stmt_delete_dependents2->close();
                    
                    //deletar o usuário da tabela principal
                    $stmt_delete_user = $conn->prepare("DELETE FROM Usuarios WHERE id_usuario = ?");
                    if (!$stmt_delete_user) {
                         throw new Exception("Erro ao preparar a exclusão do usuário: " . $conn->error);
                    }
                    $stmt_delete_user->bind_param("i", $user_id);
                    $stmt_delete_user->execute();

                    if ($stmt_delete_user->affected_rows > 0) {
                        $conn->commit();
                        $response['success'] = true;
                        $response['message'] = 'Sua conta foi excluída permanentemente.';
                        
                        // Destruir a sessão e deslogar o usuário
                        session_unset();
                        session_destroy();

                    } else {
                        throw new Exception('Nenhum usuário foi afetado pela exclusão. Verifique o ID do usuário.');
                    }
                    $stmt_delete_user->close();

                } catch (Exception $e) {
                    $conn->rollback();
                    $response['message'] = "Erro durante a exclusão da conta: " . $e->getMessage();
                    error_log("Erro ao excluir conta user_id {$user_id}: " . $e->getMessage()); // Log para o servidor
                }
            } else {
                $response['message'] = 'Senha incorreta. A exclusão foi cancelada.';
            }
        } else {
            $response['message'] = 'Usuário não encontrado ou inconsistência de dados.';
        }
        $stmt_check_password->close();
    } else {
        $response['message'] = 'O campo senha é obrigatório.';
    }
} else {
    $response['message'] = 'Método de requisição inválido. Use POST.';
}

if (isset($conn)) {
    $conn->close();
}

echo json_encode($response);
exit();
?>