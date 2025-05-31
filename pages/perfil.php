<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil de Animes</title>
    <link rel="stylesheet" href="css/perfil.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php
        // Dados do usuário (exemplo - viriam de um banco de dados em uma aplicação real)
        $userName = "Luiz Henrique";
        $userDescription = "Amante de animes e mangás. Sempre em busca da próxima grande aventura!";
        $profilePic = "images/default_profile.png"; // Caminho para uma imagem de perfil padrão

        // Listas de animes (exemplo com mais itens)
        $anime_lists = [
            "Favoritos" => array_fill(0, 20, ["nome" => "Anime Favorito", "imagem_placeholder" => "images/poster_placeholder.png"]),
            "Assistindo" => array_fill(0, 20, ["nome" => "Anime Assistindo", "imagem_placeholder" => "images/poster_placeholder.png"]),
            "Completado" => array_fill(0, 20, ["nome" => "Anime Completado", "imagem_placeholder" => "images/poster_placeholder.png"]),
            "Parou/Droppado" => array_fill(0, 20, ["nome" => "Anime Droppado", "imagem_placeholder" => "images/poster_placeholder.png"])
        ];

        // Detalhes de animes na barra lateral (exemplo)
        $sidebar_animes = array_fill(0, 4, ["nome" => "Nome do Anime Exemplo", "descricao" => "Descrição breve do anime com limite de caracteres."]);
        
        $initial_items_to_show = 7; // Define how many items to show initially
    ?>

    <div class="container">
        <header class="profile-header">
            <div class="profile-pic-container">
                <img src="<?php echo $profilePic; ?>" alt="Foto do Perfil" id="profileImage">
                </div>
            <div class="profile-info">
                <input type="text" id="userName" value="<?php echo htmlspecialchars($userName); ?>" placeholder="Nome (Editável)">
                <textarea id="userDescription" placeholder="Descrição (com limite de caracteres)"><?php echo htmlspecialchars($userDescription); ?></textarea>
            </div>
            <div class="profile-actions">
                <button id="addContentBtn" aria-label="Adicionar novo item"><i class="fas fa-plus"></i></button>
                <button id="deleteProfileBtn" aria-label="Deletar perfil"><i class="fas fa-trash"></i></button>
            </div>
        </header>

        <main class="content-area">
            <section class="anime-lists-section">
                <?php foreach ($anime_lists as $list_title => $animes): ?>
                <?php $grid_id = 'grid-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($list_title)); ?>
                <div class="anime-list-category">
                    <div class="list-header">
                        <h2><?php echo htmlspecialchars($list_title); ?></h2>
                        <a href="#" class="view-all" data-target-grid="<?php echo $grid_id; ?>">Ver Tudo</a>
                    </div>
                    <div class="anime-grid" id="<?php echo $grid_id; ?>">
                        <?php foreach ($animes as $index => $anime): ?>
                        <div class="anime-poster <?php echo $index >= $initial_items_to_show ? 'initially-hidden' : ''; ?>" 
                             data-anime-name="<?php echo htmlspecialchars($anime['nome']) . ' ' . ($index + 1); ?>">
                            <div class="poster-placeholder">
                                </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </section>
            
            <aside class="sidebar-details">
                <?php foreach ($sidebar_animes as $index => $anime_detail): ?>
                <div class="anime-detail-card">
                    <h3><?php echo htmlspecialchars($anime_detail['nome']); ?> <?php echo $index + 1; ?></h3>
                    <p><?php echo htmlspecialchars($anime_detail['descricao']); ?></p>
                </div>
                <?php endforeach; ?>
            </aside>
        </main>
    </div>

    <script src="js/perfil.js"></script>
</body>
</html>