<?php
require '../conexao2.php'; // Inclui a conexão com o banco de dados

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    if (!$email) {
        echo "Por favor, insira um e-mail válido.";
        exit;
    }

    $sql = "SELECT * FROM usuario WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        // Simula o envio de um link de redefinição de senha
        echo "Um link de redefinição de senha foi enviado para o e-mail: $email.";
    } else {
        echo "E-mail não encontrado. Por favor, verifique e tente novamente.";
    }
}
?>
