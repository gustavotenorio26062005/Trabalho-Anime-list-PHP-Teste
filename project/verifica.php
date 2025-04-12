<?php
require '../conexao2.php'; // Inclui o arquivo de conexão com o banco de dados

if (isset($_SESSION['iduser']) && !empty($_SESSION['iduser'])) {
    require_once '../usuario.class.php'; // Inclui a classe do usuário
    $u = new usuario(); // Cria uma instância da classe do usuário
    
    $listlogged = $u->logged($_SESSION['iduser']); // Chama o método logged para obter os dados do usuário
} else {
    echo "Você não está logado! Redirecionando para a página de login..."; // Exibe uma mensagem de erro
    header("Refresh: 5; url=login.php");
    exit; // Garante que nenhum código seja executado após o redirecionamento
}
?>
