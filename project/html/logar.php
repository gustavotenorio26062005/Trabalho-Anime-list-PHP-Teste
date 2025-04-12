<?php
if (isset($_POST['email']) && !empty($_POST['email']) && isset($_POST['senha']) && !empty($_POST['senha'])) {    
    require_once '../conexao2.php'; // Conexão com o banco de dados
    require_once '../usuario.class.php'; // Classe de usuário

    $login = htmlspecialchars($_POST['email']); // Filtrar entrada de forma segura
    $senha = htmlspecialchars($_POST['senha']); // Filtrar entrada de forma segura

    $u = new usuario(); // Instância da classe de usuário
    if ($u->Login($login, $senha) === true) { // Chamada corrigida do método
        echo "Logado com sucesso!";
        header("Location: index.php"); // Redirecionamento para a página inicial após login
        exit;
    } else {
        header("Refresh: 5; url=CARR.php");
        header("Refresh: 5; url=login.php");
        exit;
    }
} else {
    echo "Preencha todos os campos";
    header("Refresh: 5; url=login.php");
    exit;
}
?>

