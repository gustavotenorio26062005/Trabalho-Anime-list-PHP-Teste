<?php
// Inicia a sessão PHP para acesso às variáveis de sessão.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inclui o arquivo de conexão com o banco de dados.
require_once 'includes/db_connect.php';
// Inclui o cabeçalho HTML, que contém a estrutura inicial da página e a navegação.
require_once 'includes/header.php';

// Proteção da página:
// Redireciona para a página inicial se o usuário não estiver logado ou não for um administrador.
// Assumimos que '1' é o id_tipo_usuario para 'administrador'.
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 1) {
    header("Location: index.php?error=acesso_negado"); // Redireciona com um parâmetro de erro
    exit(); // É crucial usar exit() após header() para parar a execução do script.
}

// Variáveis para mensagens de feedback (sucesso/erro) após operações no banco.
$mensagem = '';
$tipo_mensagem = ''; // 'sucesso' ou 'erro'

// Lógica de processamento do formulário (Adicionar e Editar Anime)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta os dados do formulário
    $id_anime_edit = $_POST['id_anime'] ?? null; // Presente se for uma edição
    $nome = trim($_POST['nome'] ?? '');
    $capa_url = trim($_POST['capa_url'] ?? '');
    $sinopse = trim($_POST['sinopse'] ?? '');
    $generos_str = trim($_POST['generos'] ?? ''); // String de gêneros separada por vírgula
    $ano_lancamento = !empty($_POST['ano_lancamento']) ? (int)$_POST['ano_lancamento'] : null;

    // Validação básica dos campos obrigatórios
    if (empty($nome) || empty($capa_url) || empty($sinopse) || empty($generos_str)) {
        $mensagem = "Erro: Nome, Capa, Sinopse e Gêneros são obrigatórios.";
        $tipo_mensagem = "erro";
    } else {
        // Inicia uma transação no banco de dados.
        // Isso garante que todas as operações (inserção/atualização do anime e seus gêneros)
        // sejam realizadas com sucesso ou todas sejam desfeitas em caso de erro.
        $conn->begin_transaction();
        try {
            $id_anime_afetado = null; // Variável para guardar o ID do anime manipulado

            if ($id_anime_edit) {
                // LÓGICA DE UPDATE (Atualiza um anime existente)
                $stmt_anime = $conn->prepare("UPDATE Animes SET nome = ?, ano_lancamento = ?, sinopse = ?, capa_url = ? WHERE id_anime = ?");
                $stmt_anime->bind_param("sissi", $nome, $ano_lancamento, $sinopse, $capa_url, $id_anime_edit);
                $stmt_anime->execute();
                $stmt_anime->close();
                
                // Exclui todas as associações de gênero existentes para este anime,
                // para depois inserir as novas associações atualizadas.
                $stmt_delete_genres = $conn->prepare("DELETE FROM AnimeGeneros WHERE id_anime = ?");
                $stmt_delete_genres->bind_param("i", $id_anime_edit);
                $stmt_delete_genres->execute();
                $stmt_delete_genres->close();

                $id_anime_afetado = $id_anime_edit;
                $mensagem = "Anime atualizado com sucesso!";
            } else {
                // LÓGICA DE INSERT (Adiciona um novo anime)
                $stmt_anime = $conn->prepare("INSERT INTO Animes (nome, ano_lancamento, sinopse, capa_url) VALUES (?, ?, ?, ?)");
                $stmt_anime->bind_param("siss", $nome, $ano_lancamento, $sinopse, $capa_url);
                $stmt_anime->execute();
                $id_anime_afetado = $conn->insert_id; // Obtém o ID gerado para o novo anime
                $stmt_anime->close();
                $mensagem = "Anime adicionado com sucesso!";
            }

            // Lógica para processar Gêneros (para INSERT e UPDATE)
            // Divide a string de gêneros (ex: "Ação,Comédia") em um array e remove duplicatas.
            $generos_array = array_unique(explode(',', $generos_str));
            foreach ($generos_array as $nome_genero) {
                $nome_genero = trim($nome_genero); // Remove espaços extras
                if (empty($nome_genero)) continue; // Pula se for uma string vazia

                $id_genero = null;
                // Verifica se o gênero já existe na tabela Generos
                $stmt_check_genre = $conn->prepare("SELECT id_genero FROM Generos WHERE nome_genero = ?");
                $stmt_check_genre->bind_param("s", $nome_genero);
                $stmt_check_genre->execute();
                $result_genre = $stmt_check_genre->get_result();
                if ($row = $result_genre->fetch_assoc()) {
                    $id_genero = $row['id_genero']; // Usa o ID do gênero existente
                } else {
                    // Se o gênero não existe, insere-o na tabela Generos
                    $stmt_insert_genre = $conn->prepare("INSERT INTO Generos (nome_genero) VALUES (?)");
                    $stmt_insert_genre->bind_param("s", $nome_genero);
                    $stmt_insert_genre->execute();
                    $id_genero = $conn->insert_id; // Pega o ID do gênero recém-inserido
                    $stmt_insert_genre->close();
                }
                $stmt_check_genre->close();

                // Associa o anime ao gênero na tabela AnimeGeneros (relação muitos-para-muitos)
                $stmt_link_anime_genre = $conn->prepare("INSERT INTO AnimeGeneros (id_anime, id_genero) VALUES (?, ?)");
                $stmt_link_anime_genre->bind_param("ii", $id_anime_afetado, $id_genero);
                $stmt_link_anime_genre->execute();
                $stmt_link_anime_genre->close();
            }

            $conn->commit(); // Confirma todas as operações da transação no banco
            $tipo_mensagem = "sucesso";
        } catch (Exception $e) {
            $conn->rollback(); // Desfaz todas as operações em caso de qualquer erro
            $mensagem = "Erro na operação com o banco de dados: " . $e->getMessage();
            $tipo_mensagem = "erro";
        }
    }
}

// Lógica de Deletar Anime (acionada via GET)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_para_deletar = (int)$_GET['id'];

    $conn->begin_transaction();
    try {
        // A exclusão de um anime irá acionar triggers e regras ON DELETE CASCADE
        // para limpar suas associações (gêneros, listas pessoais, avaliações) automaticamente.
        $stmt_delete_anime = $conn->prepare("DELETE FROM Animes WHERE id_anime = ?");
        $stmt_delete_anime->bind_param("i", $id_para_deletar);
        
        if ($stmt_delete_anime->execute()) {
            $conn->commit();
            $mensagem = "Anime excluído com sucesso!";
            $tipo_mensagem = "sucesso";
        } else {
            throw new Exception("Erro ao excluir anime: " . $stmt_delete_anime->error);
        }
        $stmt_delete_anime->close();
    } catch (Exception $e) {
        $conn->rollback();
        $mensagem = "Erro ao excluir anime: " . $e->getMessage();
        $tipo_mensagem = "erro";
    }
    // Redireciona de volta para a lista após a operação de exclusão
    header("Location: admin.php?mensagem=" . urlencode($mensagem) . "&tipo_mensagem=" . urlencode($tipo_mensagem));
    exit();
}


// Lógica de exibição (roteador): Define qual parte da página será mostrada
$action = $_GET['action'] ?? 'list'; // Padrão é mostrar a lista
$anime_para_editar = null; // Variável para guardar dados do anime em modo de edição
$todos_animes = []; // Array para guardar a lista de todos os animes

// Se a ação for 'edit' e um ID for fornecido, busca os dados do anime para edição.
if ($action == 'edit' && isset($_GET['id'])) {
    $id_para_editar = (int)$_GET['id'];
    $stmt_edit_anime = $conn->prepare("SELECT * FROM Animes WHERE id_anime = ?");
    $stmt_edit_anime->bind_param("i", $id_para_editar);
    $stmt_edit_anime->execute();
    $anime_para_editar = $stmt_edit_anime->get_result()->fetch_assoc();
    $stmt_edit_anime->close();

    // Busca os gêneros associados ao anime para pré-popular o campo de gêneros no formulário.
    $stmt_genres_for_edit = $conn->prepare("SELECT g.nome_genero FROM Generos g JOIN AnimeGeneros ag ON g.id_genero = ag.id_genero WHERE ag.id_anime = ?");
    $stmt_genres_for_edit->bind_param("i", $id_para_editar);
    $stmt_genres_for_edit->execute();
    $result_genres_for_edit = $stmt_genres_for_edit->get_result();
    $generos_atuais_array = [];
    while($row = $result_genres_for_edit->fetch_assoc()){ $generos_atuais_array[] = $row['nome_genero']; }
    // Converte o array de gêneros em uma string separada por vírgulas para o input.
    $anime_para_editar['generos'] = implode(', ', $generos_atuais_array); 
    $stmt_genres_for_edit->close();

} elseif ($action == 'list' || !in_array($action, ['add', 'edit', 'delete'])) {
    // Se a ação for 'list' ou inválida, mostra a lista de todos os animes.
    $action = 'list'; 
    $result_animes = $conn->query("SELECT id_anime, nome FROM Animes ORDER BY nome ASC");
    $todos_animes = $result_animes->fetch_all(MYSQLI_ASSOC);

    // Verifica se há mensagens de redirecionamento (após delete, por exemplo)
    if (isset($_GET['mensagem']) && isset($_GET['tipo_mensagem'])) {
        $mensagem = htmlspecialchars($_GET['mensagem']);
        $tipo_mensagem = htmlspecialchars($_GET['tipo_mensagem']);
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Admin - Animalist</title>
    <!-- === CSS === -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/universal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- === FONTES === -->
</head>
<body style="background-color: black;">

    <?php require_once 'includes/header.php'; ?>

    <main>
        <div class="admin-container">
            <?php if (!empty($mensagem)): // Exibe mensagens de feedback se existirem ?>
                <p class="form-message <?php echo $tipo_mensagem; ?>"><?php echo htmlspecialchars($mensagem); ?></p>
            <?php endif; ?>

            <?php if ($action == 'add' || $action == 'edit'): ?>
                <h1><?php echo $action == 'edit' ? 'Edição do Anime' : 'Adicionar novo Anime'; ?></h1>
                
                <div class="form-wrapper">
                    <form class="form" action="admin.php" method="POST" onsubmit="return enviarDados()">
                        <?php if ($action == 'edit'): // Campo oculto para o ID do anime em edição ?>
                            <input type="hidden" name="id_anime" value="<?php echo htmlspecialchars($anime_para_editar['id_anime']); ?>">
                        <?php endif; ?>
                        
                        <input type="text" name="nome" placeholder="Nome do Anime" value="<?php echo htmlspecialchars($anime_para_editar['nome'] ?? ''); ?>" required>
                        <input type="number" name="ano_lancamento" placeholder="Ano de Lançamento (ex: 2024)" value="<?php echo htmlspecialchars($anime_para_editar['ano_lancamento'] ?? ''); ?>">
                        <input type="text" name="capa_url" placeholder="Link da Imagem (Capa)" value="<?php echo htmlspecialchars($anime_para_editar['capa_url'] ?? ''); ?>" required>
                        <textarea name="sinopse" placeholder="Descrição/Sinopse do Anime" required><?php echo htmlspecialchars($anime_para_editar['sinopse'] ?? ''); ?></textarea>
                        
                        <div class="genre-input">
                            <input type="text" id="genreInput" placeholder="Insira um Gênero por vez">
                            <button type="button" onclick="addGenre()">Adicionar</button>
                        </div>
                        <div id="genreList" class="genre-list">
                            <?php 
                            // Exibe os gêneros atuais para edição, se houver
                            $generos_exibir = [];
                            if (isset($anime_para_editar['generos']) && !empty($anime_para_editar['generos'])) {
                                // Divide a string de gêneros (ex: "Ação, Comédia") em um array
                                $generos_exibir = explode(', ', $anime_para_editar['generos']);
                            }
                            ?>
                            <?php foreach($generos_exibir as $genre): ?>
                                <div class="genre-item"><?php echo htmlspecialchars($genre); ?><button type="button" onclick="this.parentElement.remove(); atualizarGenerosInput();">🗑️</button></div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="generos" id="generosInput" value="<?php echo htmlspecialchars($anime_para_editar['generos'] ?? ''); ?>" required>
                        
                        <button type="submit" class="submit-btn"><?php echo $action == 'edit' ? 'Salvar Alterações' : 'Salvar Anime'; ?></button>
                    </form>
                </div>
                <a href="admin.php" class="back-link">← Voltar para a lista de Animes</a>
            <?php else: // Mostra a lista de animes e opções de gerenciamento ?>
                <h1>Gerenciar Animes</h1>
                <a href="admin.php?action=add" class="btn-add-new">Adicionar Novo Anime</a>
                
                <table class="anime-list-table">
                    <thead>
                        <tr>
                            <th>Nome do Anime</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($todos_animes)): ?>
                            <tr><td colspan="2" style="text-align: center;">Nenhum anime cadastrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($todos_animes as $anime): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($anime['nome']); ?></td>
                                    <td>
                                        <a href="admin.php?action=edit&id=<?php echo $anime['id_anime']; ?>" class="btn-edit">Editar</a>
                                        <button class="btn-delete" onclick="confirmDelete(<?php echo $anime['id_anime']; ?>, '<?php echo htmlspecialchars($anime['nome']); ?>')">Deletar</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Função para adicionar um gênero dinamicamente ao formulário de anime
        function addGenre() {
            const genreInput = document.getElementById('genreInput');
            const genreList = document.getElementById('genreList');
            const genreName = genreInput.value.trim();

            if (genreName) {
                // Evita adicionar gêneros duplicados na lista visível
                const existingGenres = Array.from(genreList.children).map(item => item.firstChild.textContent.trim());
                if (existingGenres.includes(genreName)) {
                    alert('Este gênero já foi adicionado.');
                    return;
                }

                const genreItem = document.createElement('div');
                genreItem.className = 'genre-item';
                // htmlspecialchars para prevenir XSS ao exibir o nome do gênero
                genreItem.innerHTML = `${htmlspecialchars(genreName)} <button type="button" onclick="this.parentElement.remove(); atualizarGenerosInput();">🗑️</button>`;
                genreList.appendChild(genreItem);
                genreInput.value = ''; // Limpa o campo de input após adicionar
                atualizarGenerosInput(); // Atualiza o input hidden
            } else {
                alert('O nome do gênero não pode estar vazio.');
            }
        }

        // Função para atualizar o input hidden 'generosInput' com todos os gêneros da lista
        // Isso é crucial para que os gêneros sejam enviados corretamente no formulário
        function atualizarGenerosInput() {
            const genreList = document.getElementById('genreList');
            const generosInput = document.getElementById('generosInput');
            const generos = [];
            // Pega o texto de cada item de gênero na lista
            Array.from(genreList.children).forEach(item => {
                const text = item.firstChild.textContent.trim(); // Pega o texto antes do botão de lixo
                generos.push(text);
            });
            // Converte o array de gêneros em uma string separada por vírgulas
            generosInput.value = generos.join(','); 
        }

        // Função para validar o formulário antes do envio
        function enviarDados() {
            // Garante que o input hidden de gêneros está atualizado antes de enviar o formulário
            atualizarGenerosInput();
            const generosInput = document.getElementById('generosInput');
            if (!generosInput.value.trim()) { // Verifica se a string de gêneros não está vazia
                alert('Por favor, adicione pelo menos um gênero.');
                return false; // Impede o envio do formulário
            }
            return true; // Permite o envio do formulário
        }

        // Função para escapar caracteres HTML em strings (segurança contra XSS)
        function htmlspecialchars(str) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }

        // Função para confirmar a exclusão de um anime
        function confirmDelete(id, nome) {
            if (confirm(`Tem certeza que deseja deletar o anime "${nome}"? Esta ação é irreversível.`)) {
                // Se o usuário confirmar, redireciona para a página admin.php com a ação de deletar e o ID.
                window.location.href = `admin.php?action=delete&id=${id}`;
            }
        }

        // Garante que o input hidden de gêneros seja preenchido corretamente ao carregar a página
        // Isso é importante para o modo de edição, onde os gêneros vêm do banco.
        window.onload = function() {
            atualizarGenerosInput();
        };

    </script>

    <?php 
    require_once 'includes/footer.php'; 
    // Fecha a conexão com o banco de dados.
    if (isset($conn)) {
        $conn->close();
    }
    ?>
</body>
</html>
