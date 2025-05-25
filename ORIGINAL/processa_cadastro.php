<?php
require '../conexao2.php'; // Inclui a conexão com o banco de dados

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $senha = filter_input(INPUT_POST, 'senha', FILTER_SANITIZE_STRING);

    if (!$nome || !$email || !$senha) {
        echo "Todos os campos são obrigatórios. Por favor, preencha corretamente.";
        exit;
    }

    // Verifica se o e-mail já está cadastrado
    $sql = "SELECT * FROM usuario WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        echo "O e-mail já está cadastrado. Por favor, use outro.";
        exit;
    }

    // Insere os dados no banco de dados
    $hashSenha = password_hash($senha, PASSWORD_DEFAULT); // Hash seguro para a senha
    $sql = "INSERT INTO usuario (nome, email, senha) VALUES (:nome, :email, :senha)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':nome', $nome);
    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':senha', $hashSenha);

    if ($stmt->execute()) {
        echo "Cadastro realizado com sucesso!";
    } else {
        echo "Erro ao cadastrar. Por favor, tente novamente.";
    }
}
?>
