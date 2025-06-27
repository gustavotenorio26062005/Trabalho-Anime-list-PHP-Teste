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
        $conn->begin_transaction();
        try {
            $id_anime_afetado = null; // Variável para guardar o ID do anime manipulado

            if ($id_anime_edit) {
                // LÓGICA DE UPDATE (Atualiza um anime existente)
                $stmt_anime = $conn->prepare("UPDATE Animes SET nome = ?, ano_lancamento = ?, sinopse = ?, capa_url = ? WHERE id_anime = ?");
                $stmt_anime->bind_param("sissi", $nome, $ano_lancamento, $sinopse, $capa_url, $id_anime_edit);
                $stmt_anime->execute();
                $stmt_anime->close();
                
                // Exclui todas as associações de gênero existentes para este anime
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
            $generos_array = array_unique(explode(',', $generos_str));
            foreach ($generos_array as $nome_genero) {
                $nome_genero = trim($nome_genero);
                if (empty($nome_genero)) continue;

                $id_genero = null;
                $stmt_check_genre = $conn->prepare("SELECT id_genero FROM Generos WHERE nome_genero = ?");
                $stmt_check_genre->bind_param("s", $nome_genero);
                $stmt_check_genre->execute();
                $result_genre = $stmt_check_genre->get_result();
                if ($row = $result_genre->fetch_assoc()) {
                    $id_genero = $row['id_genero'];
                } else {
                    $stmt_insert_genre = $conn->prepare("INSERT INTO Generos (nome_genero) VALUES (?)");
                    $stmt_insert_genre->bind_param("s", $nome_genero);
                    $stmt_insert_genre->execute();
                    $id_genero = $conn->insert_id;
                    $stmt_insert_genre->close();
                }
                $stmt_check_genre->close();

                $stmt_link_anime_genre = $conn->prepare("INSERT INTO AnimeGeneros (id_anime, id_genero) VALUES (?, ?)");
                $stmt_link_anime_genre->bind_param("ii", $id_anime_afetado, $id_genero);
                $stmt_link_anime_genre->execute();
                $stmt_link_anime_genre->close();
            }

            $conn->commit();
            $tipo_mensagem = "sucesso";
        } catch (Exception $e) {
            $conn->rollback();
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

// Lógica de busca, filtro e ordenação (integrada para a tela de admin também)
$search_query = $_GET['search'] ?? '';
$filter_year = $_GET['year'] ?? '';
$filter_genre = $_GET['genre'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'nome';
$sort_order = $_GET['sort_order'] ?? 'ASC';

// Construção da query SQL base para a listagem (similar à de animes.php)
$sql = "SELECT A.id_anime, A.nome, A.ano_lancamento, A.sinopse, A.capa_url, 
               GROUP_CONCAT(DISTINCT G.nome_genero SEPARATOR ', ') AS generos
        FROM Animes A
        LEFT JOIN AnimeGeneros AG ON A.id_anime = AG.id_anime
        LEFT JOIN Generos G ON AG.id_genero = G.id_genero
        WHERE 1=1 ";

$params = [];
$types = '';

if (!empty($search_query)) {
    $sql .= " AND A.nome LIKE ? ";
    $params[] = '%' . $search_query . '%';
    $types .= 's';
}
if (!empty($filter_year)) {
    $sql .= " AND A.ano_lancamento = ? ";
    $params[] = (int)$filter_year;
    $types .= 'i';
}
if (!empty($filter_genre)) {
    $sql .= " AND A.id_anime IN (
                 SELECT AG_filter.id_anime 
                 FROM AnimeGeneros AG_filter 
                 JOIN Generos G_filter ON AG_filter.id_genero = G_filter.id_genero 
                 WHERE G_filter.nome_genero = ?
               ) ";
    $params[] = $filter_genre;
    $types .= 's';
}

$sql .= " GROUP BY A.id_anime";

$allowed_sort_by = ['nome', 'ano_lancamento'];
$orderByColumn = 'A.nome';
if (in_array($sort_by, $allowed_sort_by)) {
    $orderByColumn = 'A.' . $sort_by;
}
$allowed_sort_order = ['ASC', 'DESC'];
if (!in_array($sort_order, $allowed_sort_order)) {
    $sort_order = 'ASC'; // Default para ordem segura
}
$sql .= " ORDER BY " . $orderByColumn . " " . $sort_order;

// Re-executa a busca para a listagem principal, considerando os filtros
$stmt_list_animes = $conn->prepare($sql);
if (!$stmt_list_animes) {
    error_log("Erro ao preparar a query de listagem em admin.php: " . $conn->error);
    $animes_filtrados = [];
    // É importante mostrar uma mensagem de erro para o admin aqui ou logar extensivamente
    $mensagem = "Erro crítico ao preparar a consulta de animes. Verifique os logs do servidor.";
    $tipo_mensagem = "erro";
} else {
    // Só chama bind_param se houver tipos (e, consequentemente, parâmetros)
    if (!empty($types)) {
        $bind_params_ref = [];
        $bind_params_ref[] = &$types; // Primeiro argumento é a string de tipos
        foreach ($params as $key => $value) {
            $bind_params_ref[] = &$params[$key]; // Adiciona cada parâmetro por referência
        }
        call_user_func_array([$stmt_list_animes, 'bind_param'], $bind_params_ref);
    }
    
    $stmt_list_animes->execute();
    $result_filtered_animes = $stmt_list_animes->get_result();
    $animes_filtrados = $result_filtered_animes->fetch_all(MYSQLI_ASSOC);
    $stmt_list_animes->close();
}

// Obter todos os gêneros e anos para as opções de filtro (para o formulário de pesquisa)
$generos_options = [];
$result_generos = $conn->query("SELECT DISTINCT nome_genero FROM Generos ORDER BY nome_genero ASC");
if ($result_generos) {
    while ($row = $result_generos->fetch_assoc()) {
        $generos_options[] = $row['nome_genero'];
    }
    $result_generos->close();
}

$anos_options = [];
$result_anos = $conn->query("SELECT DISTINCT ano_lancamento FROM Animes WHERE ano_lancamento IS NOT NULL ORDER BY ano_lancamento DESC");
if ($result_anos) {
    while ($row = $result_anos->fetch_assoc()) {
        $anos_options[] = $row['ano_lancamento'];
    }
    $result_anos->close();
}


?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Admin - Animalist</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/universal.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin.css"> 
</head>
<body>

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
                
                <form action="admin.php" method="GET" class="form-filtros-principal">
                    <section class="secao-filtros">
                        <div class="container-filtros">
                            <div class="grupo-input">
                                <label for="search-input">Pesquisar</label>
                                <input type="text" id="search-input" name="search" class="campo-input" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Nome do anime...">
                            </div>

                            <div class="grupo-input">
                                <label for="genre-select">Gênero</label>
                                <select id="genre-select" name="genre" class="campo-input">
                                    <option value="">Todos os Gêneros</option>
                                    <?php foreach ($generos_options as $genero_opt): ?>
                                        <option value="<?php echo htmlspecialchars($genero_opt); ?>" <?php echo ($filter_genre == $genero_opt) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($genero_opt); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="grupo-input">
                                <label for="year-select">Ano de Lançamento</label>
                                <select id="year-select" name="year" class="campo-input">
                                    <option value="">Todos os Anos</option>
                                    <?php foreach ($anos_options as $ano_opt): ?>
                                        <option value="<?php echo $ano_opt; ?>" <?php echo ($filter_year == $ano_opt) ? 'selected' : ''; ?>>
                                            <?php echo $ano_opt; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="grupo-input">
                                <label for="sort_by-select">Ordenar por</label>
                                <select id="sort_by-select" name="sort_by" class="campo-input">
                                    <option value="nome" <?php echo ($sort_by == 'nome') ? 'selected' : ''; ?>>Nome</option>
                                    <option value="ano_lancamento" <?php echo ($sort_by == 'ano_lancamento') ? 'selected' : ''; ?>>Ano</option>
                                </select>
                            </div>
                            
                            <div class="grupo-input">
                                <label for="sort_order-select">Ordem</label>
                                <select id="sort_order-select" name="sort_order" class="campo-input">
                                    <option value="ASC" <?php echo ($sort_order == 'ASC') ? 'selected' : ''; ?>>Ascendente</option>
                                    <option value="DESC" <?php echo ($sort_order == 'DESC') ? 'selected' : ''; ?>>Descendente</option>
                                </select>
                            </div>

                            <button type="submit" class="botao-icone botao-pesquisar" aria-label="Pesquisar"><i class="fas fa-search"></i></button>
                            
                        </div>
                    </section>
                </form>


<div class="anime-grid">
    <?php if (empty($animes_filtrados)): ?>
        <p class="mensagem-vazio">Nenhum anime encontrado com os filtros e termos de pesquisa aplicados.</p>
    <?php else: ?>
        <?php foreach ($animes_filtrados as $anime): ?>
            <div class="anime-link"> <a href="anime_detalhes.php?id=<?php echo $anime['id_anime']; ?>" style="text-decoration: none; color: inherit; display: block;">
                    <div class="anime-card-poster">
                        <div class="anime-card-title-overlay">
                            <?php echo htmlspecialchars($anime['nome']); ?>
                        </div>
                        <img src="<?php echo htmlspecialchars(!empty($anime['capa_url']) ? $anime['capa_url'] : 'img/logo_site.jpg'); ?>" 
                             alt="Capa de <?php echo htmlspecialchars($anime['nome']); ?>">

                        <?php if (!empty($anime['ano_lancamento'])): ?>
                        <span class="ano-anime"><?php echo htmlspecialchars($anime['ano_lancamento']); ?></span>
                        <?php endif; ?>
                    </div>
                </a>
                
                <div class="admin-card-actions">
                    <a href="admin.php?action=edit&id=<?php echo $anime['id_anime']; ?>" class="btn-admin-card edit">Editar</a>
                    
                    <button class="btn-admin-card delete" onclick="event.preventDefault(); confirmDelete(<?php echo $anime['id_anime']; ?>, '<?php echo htmlspecialchars(addslashes($anime['nome'])); ?>')">Deletar</button>
                </div>

            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
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
                genreItem.innerHTML = `${htmlspecialchars(genreName)} <button type="button" onclick="this.parentElement.remove(); atualizarGenerosInput();">🗑️</button>`;
                genreList.appendChild(genreItem);
                genreInput.value = ''; // Limpa o campo de input após adicionar
                atualizarGenerosInput(); // Atualiza o input hidden
            } else {
                alert('O nome do gênero não pode estar vazio.');
            }
        }

        // Função para atualizar o input hidden 'generosInput' com todos os gêneros da lista
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
                // Redireciona para a página admin.php com a ação de deletar e o ID.
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
