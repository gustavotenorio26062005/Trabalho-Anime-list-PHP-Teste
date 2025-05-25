<?php
// Inclui a conexão com o banco de dados
require_once 'conexao2.php'; // Utilize require_once para evitar múltiplas inclusões do mesmo arquivo

// Define a página com base no parâmetro "page"
$page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING) ?? 'login'; // Página padrão: login

// Estrutura switch para determinar a página a ser incluída
switch ($page) {
    case 'login':
        // Inclui a página de login
        include_once 'html/login.php'; // Utilize include_once para evitar conflitos
        break;

    case 'esqueci-senha':
        // Inclui a página de "Esqueci a Senha"
        include_once 'html/esqueci_senha.php';
        break;

    case 'cadastrar':
        // Inclui a página de "Cadastrar-se"
        include_once 'html/cadastrar.php';
        break;
    case 'CARR':
        // Inclui a página de "Cadastrar-se"
        include_once 'html/CARR.php';
        break;

    default:
        // Exibe um erro para páginas inválidas
        http_response_code(404); // Define o cabeçalho de erro 404
        echo "<h1>Erro 404: Página Não Encontrada</h1>";
        break;
}
?>
