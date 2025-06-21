DROP DATABASE IF EXISTS animalist_db;

CREATE DATABASE IF NOT EXISTS animalist_db;

USE animalist_db;

CREATE TABLE IF NOT EXISTS TipoUsuario (
    id_tipo_usuario INT PRIMARY KEY AUTO_INCREMENT,
    tipo ENUM('usuario_comum', 'administrador') UNIQUE NOT NULL
);

CREATE TABLE IF NOT EXISTS Usuarios (
    id_usuario INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    data_nascimento DATE NOT NULL,
    senha VARCHAR(255) NOT NULL, #hash nesse contexto é para criptografar as senhas do banco em si
    foto_perfil_url VARCHAR(2048),
    fundo_perfil_url VARCHAR(2048),
    descricao TEXT,
    id_tipo_usuario INT NOT NULL DEFAULT 2,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_ultima_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_tipo_usuario) REFERENCES TipoUsuario(id_tipo_usuario) ON DELETE RESTRICT
);

CREATE INDEX idx_usuario_email ON Usuarios(email);

CREATE TABLE IF NOT EXISTS Animes (
    id_anime INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    ano_lancamento INT,
    sinopse TEXT,
    capa_url VARCHAR(2048),
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_ultima_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_anime_nome UNIQUE (nome),
    CONSTRAINT chk_ano_lancamento CHECK (ano_lancamento >= 1900 AND ano_lancamento <= 2030)
);

CREATE FULLTEXT INDEX idx_anime_nome ON Animes(nome);

CREATE TABLE IF NOT EXISTS Generos (
    id_genero INT PRIMARY KEY AUTO_INCREMENT,
    nome_genero VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE IF NOT EXISTS AnimeGeneros (
    id_anime INT,
    id_genero INT,
    PRIMARY KEY (id_anime, id_genero),
    FOREIGN KEY (id_anime) REFERENCES Animes(id_anime) ON DELETE CASCADE,
    FOREIGN KEY (id_genero) REFERENCES Generos(id_genero) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS ListaPessoalAnimes (
    id_lista INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    id_anime INT NOT NULL,
    status_anime ENUM('Assistindo', 'Completado', 'Planejando Assistir', 'Droppado') NOT NULL,
    is_favorito BOOLEAN DEFAULT FALSE,
    data_adicao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_ultima_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES Usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_anime) REFERENCES Animes(id_anime) ON DELETE CASCADE,
    UNIQUE (id_usuario, id_anime)
);


CREATE INDEX idx_lista_usuario ON ListaPessoalAnimes(id_usuario);

CREATE TABLE IF NOT EXISTS Avaliacoes (
    id_avaliacao INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    id_anime INT NOT NULL,
    nota ENUM('Recomendo', 'Não Recomendo') NOT NULL,
    comentario TEXT,
    data_avaliacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_ultima_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES Usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_anime) REFERENCES Animes(id_anime) ON DELETE CASCADE,
    UNIQUE (id_usuario, id_anime)
);

CREATE INDEX idx_avaliacao_anime ON Avaliacoes(id_anime);
CREATE INDEX idx_avaliacao_usuario ON Avaliacoes(id_usuario);

CREATE TABLE IF NOT EXISTS Avaliacoes_Log (
    id_log INT PRIMARY KEY AUTO_INCREMENT,
    id_avaliacao_excluida INT,
    id_usuario INT,
    id_anime INT,
    nota ENUM('Recomendo', 'Não Recomendo'),
    comentario TEXT,
    data_avaliacao_original TIMESTAMP,
    data_exclusao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS Usuarios_Nome_Log (
    id_log INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT,
    nome_antigo TEXT,
    nome_novo TEXT,
    data_alteracao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS Generos_Log (
    id_log INT PRIMARY KEY AUTO_INCREMENT,
    nome_genero VARCHAR(100),
    data_insercao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS Animes_Log_Remocoes (
    id_log INT PRIMARY KEY AUTO_INCREMENT,
    id_anime INT,
    nome_anime VARCHAR(255),
    ano_lancamento INT,
    sinopse TEXT,
    capa_url VARCHAR(2048),
    data_cadastro_original TIMESTAMP,
    data_remocao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

DELIMITER //

CREATE TRIGGER trg_before_insert_usuarios
BEFORE INSERT ON Usuarios
FOR EACH ROW
BEGIN
    IF EXISTS (SELECT 1 FROM Usuarios WHERE email = NEW.email) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro: O e-mail informado já está cadastrado.';
    END IF;
END;
//

CREATE TRIGGER trg_before_insert_animes
BEFORE INSERT ON Animes
FOR EACH ROW
BEGIN
    IF EXISTS (SELECT 1 FROM Animes WHERE nome = NEW.nome) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro: Já existe um anime com este nome.';
    END IF;
END;
//

CREATE TRIGGER trg_after_delete_avaliacoes
AFTER DELETE ON Avaliacoes
FOR EACH ROW
BEGIN
    INSERT INTO Avaliacoes_Log (id_avaliacao_excluida, id_usuario, id_anime, nota, comentario, data_avaliacao_original)
    VALUES (OLD.id_avaliacao, OLD.id_usuario, OLD.id_anime, OLD.nota, OLD.comentario, OLD.data_avaliacao);
END;
//

CREATE TRIGGER trg_log_update_nome_usuario
BEFORE UPDATE ON Usuarios
FOR EACH ROW
BEGIN
    IF OLD.nome != NEW.nome THEN
        INSERT INTO Usuarios_Nome_Log (
            id_usuario,
            nome_antigo,
            nome_novo
        )
        VALUES (
            OLD.id_usuario,
            OLD.nome,
            NEW.nome
        );
    END IF;
END;
//

CREATE TRIGGER trg_after_insert_genero
AFTER INSERT ON Generos
FOR EACH ROW
BEGIN
    INSERT INTO Generos_Log (nome_genero)
    VALUES (NEW.nome_genero);
END;
//

CREATE TRIGGER trg_after_delete_anime
AFTER DELETE ON Animes
FOR EACH ROW
BEGIN
    INSERT INTO Animes_Log_Remocoes (
        id_anime,
        nome_anime,
        ano_lancamento,
        sinopse,
        capa_url,
        data_cadastro_original
    )
    VALUES (
        OLD.id_anime,
        OLD.nome,
        OLD.ano_lancamento,
        OLD.sinopse,
        OLD.capa_url,
        OLD.data_cadastro
    );
END;
//

DELIMITER ;

INSERT INTO TipoUsuario (tipo) VALUES
('administrador'),
('usuario_comum');

INSERT INTO Usuarios (nome, email, data_nascimento, senha, id_tipo_usuario, foto_perfil_url, fundo_perfil_url, descricao) VALUES
('Gabriel Dias', 'gabriel.dias@example.com', '1990-01-01', 'senha123', 1, 'https://avatars.githubusercontent.com/u/153240026?v=4', 'https://via.placeholder.com/800x200/FF0000/FFFFFF?text=Fundo+G.D.', 'Admin principal do Animalist. Gosto de tudo que é otimizado.'),
('Gustavo Barros', 'gustavo.barros@example.com', '1991-03-15', 'senha123', 1, 'https://avatars.githubusercontent.com/u/129299486?v=4', 'https://via.placeholder.com/800x200/00FF00/FFFFFF?text=Fundo+G.B.', 'Admin e especialista em usabilidade. Paixão por animes de fantasia.'),
('Luiz Gonçalves', 'luiz.goncalves@example.com', '1992-07-22', 'senha123', 2, 'https://avatars.githubusercontent.com/u/163657618?s=400&u=a5fd444ec209497f4b754979c48dc7d66b25e261&v=4', 'https://via.placeholder.com/800x200/FFFF00/000000?text=Fundo+L.G.', 'Fã de animes de ação e aventura, sempre em busca da próxima grande batalha.'),
('Maycon Cabral', 'maycon.cabral@example.com', '1993-11-05', 'senha123', 2, 'https://avatars.githubusercontent.com/u/118577513?v=4', 'https://via.placeholder.com/800x200/FF00FF/FFFFFF?text=Fundo+M.C.', 'Adora animes de fantasia e slice of life, para relaxar e se inspirar.'),
('Renan Rodrigues', 'renan.rodrigues@example.com', '1994-04-30', 'senha123', 2, 'https://avatars.githubusercontent.com/u/163357233?v=4', 'https://via.placeholder.com/800x200/00FFFF/000000?text=Fundo+R.R.', 'Crítico de animes e mangás, sempre com uma opinião sincera e bem fundamentada.'),
('Ana Santos', 'ana.santos@example.com', '1988-08-10', 'senha123', 2, NULL, NULL, 'Gosta de animes mais antigos e cult, buscando sempre novas pérolas.'),
('Pedro Lima', 'pedro.lima@example.com', '1995-02-28', 'senha123', 2, NULL, NULL, 'Em busca de novos animes para assistir, aberto a todos os gêneros.'),
('Usuario Menor', 'menor@example.com', '2015-05-10', 'senha123', 2, NULL, NULL, 'Este usuário é menor de idade (deve falhar o cadastro devido à restrição CHK_IDADE_MINIMA, mas agora só será validado no PHP).');

INSERT INTO Generos (nome_genero) VALUES
('Ação'), ('Aventura'), ('Comédia'), ('Drama'), ('Fantasia'), ('Ficção Científica'),
('Romance'), ('Slice of Life'), ('Suspense'), ('Mecha'), ('Esporte'), ('Terror'), ('Mistério');

INSERT INTO Generos (nome_genero) VALUES ('Psicológico');
INSERT INTO Generos (nome_genero) VALUES ('Sobrenatural');
INSERT INTO Generos (nome_genero) VALUES ('Magia');

INSERT INTO Animes (nome, ano_lancamento, sinopse, capa_url) VALUES
('Attack on Titan', 2013, 'A humanidade vive dentro de cidades cercadas por enormes muralhas para se proteger de gigantes humanóides devoradores de homens chamados Titãs. Uma história de sobrevivência e mistério.', 'https://i.pinimg.com/736x/e4/d3/85/e4d38524090e4b1f9d2fb31e894c6c97.jpg'),
('Jujutsu Kaisen', 2020, 'Yuji Itadori, um estudante do ensino médio, se envolve no mundo do Jujutsu ao tentar salvar um amigo de um monstro, e acaba engolindo um objeto amaldiçoado, tornando-se um receptáculo de uma maldição poderosa.', 'https://s4.anilist.co/file/anilistcdn/media/anime/cover/large/bx113415-LHBAeoZDIsnF.jpg'),
('Fullmetal Alchemist: Brotherhood', 2009, 'Dois irmãos, Edward e Alphonse Elric, tentam usar a alquimia para trazer sua mãe de volta à vida, mas pagam um preço terrível. Agora, eles buscam a Pedra Filosofal para recuperar seus corpos.', 'https://s4.anilist.co/file/anilistcdn/media/anime/cover/large/bx5114-nSWCgQlmOMtj.jpg'),
('Spy x Family', 2022, 'Um espião, uma assassina e uma telepata se reúnem para formar uma família falsa para cumprir uma missão secreta, mas ninguém sabe a verdadeira identidade um do outro, levando a situações hilárias e emocionantes.', 'https://s4.anilist.co/file/anilistcdn/media/anime/cover/large/bx140960-Kb6R5nYQfjmP.jpg'),
('My Hero Academia', 2016, 'Em um mundo onde superpoderes (Quirks) são comuns, Izuku Midoriya nasce sem um, mas sonha em se tornar um herói. Ele é escolhido pelo maior herói, All Might, para herdar seu poder.', 'https://s4.anilist.co/file/anilistcdn/media/anime/cover/large/bx21459-nYh85uj2Fuwr.jpg'),
('Cowboy Bebop', 1998, 'Um grupo de caçadores de recompensas viaja pelo sistema solar em sua nave, a Bebop, em busca de criminosos e aventuras, enfrentando um passado que os assombra.', 'https://waru.world/cdn/shop/files/Coyboy_bebop.jpg?v=1711982673&width=1946'),
('Demon Slayer: Kimetsu no Yaiba', 2019, 'Tanjiro Kamado, um jovem que teve sua família massacrada por demônios, parte em uma jornada para se tornar um caçador de demônios e salvar sua irmã, que foi transformada.', 'https://wallpapercat.com/w/full/0/5/8/183167-1080x2340-phone-hd-demon-slayer-kimetsu-no-yaiba-background.jpg');

INSERT INTO AnimeGeneros (id_anime, id_genero) SELECT Animes.id_anime, Generos.id_genero FROM Animes, Generos WHERE Animes.nome = 'Attack on Titan' AND Generos.nome_genero = 'Ação';
INSERT INTO AnimeGeneros (id_anime, id_genero) SELECT Animes.id_anime, Generos.id_genero FROM Animes, Generos WHERE Animes.nome = 'Attack on Titan' AND Generos.nome_genero = 'Drama';
INSERT INTO AnimeGeneros (id_anime, id_genero) SELECT Animes.id_anime, Generos.id_genero FROM Animes, Generos WHERE Animes.nome = 'Attack on Titan' AND Generos.nome_genero = 'Fantasia';

INSERT INTO AnimeGeneros (id_anime, id_genero) SELECT Animes.id_anime, Generos.id_genero FROM Animes, Generos WHERE Animes.nome = 'Jujutsu Kaisen' AND Generos.nome_genero = 'Ação';
INSERT INTO AnimeGeneros (id_anime, id_genero) SELECT Animes.id_anime, Generos.id_genero FROM Animes, Generos WHERE Animes.nome = 'Jujutsu Kaisen' AND Generos.nome_genero = 'Fantasia';
INSERT INTO AnimeGeneros (id_anime, id_genero) SELECT Animes.id_anime, Generos.id_genero FROM Animes, Generos WHERE Animes.nome = 'Jujutsu Kaisen' AND Generos.nome_genero = 'Suspense';

INSERT INTO AnimeGeneros (id_anime, id_genero) SELECT Animes.id_anime, Generos.id_genero FROM Animes, Generos WHERE Animes.nome = 'Fullmetal Alchemist: Brotherhood' AND Generos.nome_genero = 'Ação';
INSERT INTO AnimeGeneros (id_anime, id_genero) SELECT Animes.id_anime, Generos.id_genero FROM Animes, Generos WHERE Animes.nome = 'Fullmetal Alchemist: Brotherhood' AND Generos.nome_genero = 'Aventura';
INSERT INTO AnimeGeneros (id_anime, id_genero) SELECT Animes.id_anime, Generos.id_genero FROM Animes, Generos WHERE Animes.nome = 'Fullmetal Alchemist: Brotherhood' AND Generos.nome_genero = 'Fantasia';
INSERT INTO AnimeGeneros (id_anime, id_genero) SELECT Animes.id_anime, Generos.id_genero FROM Animes, Generos WHERE Animes.nome = 'Fullmetal Alchemist: Brotherhood' AND Generos.nome_genero = 'Drama';

INSERT INTO AnimeGeneros (id_anime, id_genero) SELECT Animes.id_anime, Generos.id_genero FROM Animes, Generos WHERE Animes.nome = 'Spy x Family' AND Generos.nome_genero = 'Comédia';
INSERT INTO AnimeGeneros (id_anime, id_genero) SELECT Animes.id_anime, Generos.id_genero FROM Animes, Generos WHERE Animes.nome = 'Spy x Family' AND Generos.nome_genero = 'Ação';
INSERT INTO AnimeGeneros (id_anime, id_genero) SELECT Animes.id_anime, Generos.id_genero FROM Animes, Generos WHERE Animes.nome = 'Spy x Family' AND Generos.nome_genero = 'Slice of Life';

INSERT INTO AnimeGeneros (id_anime, id_genero) SELECT Animes.id_anime, Generos.id_genero FROM Animes, Generos WHERE Animes.nome = 'My Hero Academia' AND Generos.nome_genero = 'Ação';
INSERT INTO AnimeGeneros (id_anime, id_genero) SELECT Animes.id_anime, Generos.id_genero FROM Animes, Generos WHERE Animes.nome = 'My Hero Academia' AND Generos.nome_genero = 'Aventura';
INSERT INTO AnimeGeneros (id_anime, id_genero) SELECT Animes.id_anime, Generos.id_genero FROM Animes, Generos WHERE Animes.nome = 'My Hero Academia' AND Generos.nome_genero = 'Ficção Científica';

INSERT INTO AnimeGeneros (id_anime, id_genero) SELECT Animes.id_anime, Generos.id_genero FROM Animes, Generos WHERE Animes.nome = 'Cowboy Bebop' AND Generos.nome_genero = 'Ficção Científica';
INSERT INTO AnimeGeneros (id_anime, id_genero) SELECT Animes.id_anime, Generos.id_genero FROM Animes, Generos WHERE Animes.nome = 'Cowboy Bebop' AND Generos.nome_genero = 'Ação';
INSERT INTO AnimeGeneros (id_anime, id_genero) SELECT Animes.id_anime, Generos.id_genero FROM Animes, Generos WHERE Animes.nome = 'Cowboy Bebop' AND Generos.nome_genero = 'Drama';

INSERT INTO AnimeGeneros (id_anime, id_genero) SELECT Animes.id_anime, Generos.id_genero FROM Animes, Generos WHERE Animes.nome = 'Demon Slayer: Kimetsu no Yaiba' AND Generos.nome_genero = 'Ação';
INSERT INTO AnimeGeneros (id_anime, id_genero) SELECT Animes.id_anime, Generos.id_genero FROM Animes, Generos WHERE Animes.nome = 'Demon Slayer: Kimetsu no Yaiba' AND Generos.nome_genero = 'Fantasia';
INSERT INTO AnimeGeneros (id_anime, id_genero) SELECT Animes.id_anime, Generos.id_genero FROM Animes, Generos WHERE Animes.nome = 'Demon Slayer: Kimetsu no Yaiba' AND Generos.nome_genero = 'Aventura';

INSERT INTO ListaPessoalAnimes (id_usuario, id_anime, status_anime, is_favorito)
SELECT u.id_usuario, a.id_anime, 'Assistindo', FALSE
FROM Usuarios u, Animes a
WHERE u.email = 'luiz.goncalves@example.com' AND a.nome = 'Jujutsu Kaisen';

INSERT INTO ListaPessoalAnimes (id_usuario, id_anime, status_anime, is_favorito)
SELECT u.id_usuario, a.id_anime, 'Completado', TRUE
FROM Usuarios u, Animes a
WHERE u.email = 'luiz.goncalves@example.com' AND a.nome = 'Attack on Titan';

INSERT INTO ListaPessoalAnimes (id_usuario, id_anime, status_anime, is_favorito)
SELECT u.id_usuario, a.id_anime, 'Completado', FALSE
FROM Usuarios u, Animes a
WHERE u.email = 'maycon.cabral@example.com' AND a.nome = 'Spy x Family';

INSERT INTO ListaPessoalAnimes (id_usuario, id_anime, status_anime, is_favorito)
SELECT u.id_usuario, a.id_anime, 'Planejando Assistir', FALSE
FROM Usuarios u, Animes a
WHERE u.email = 'renan.rodrigues@example.com' AND a.nome = 'Fullmetal Alchemist: Brotherhood';

INSERT INTO ListaPessoalAnimes (id_usuario, id_anime, status_anime, is_favorito)
SELECT u.id_usuario, a.id_anime, 'Assistindo', FALSE
FROM Usuarios u, Animes a
WHERE u.email = 'renan.rodrigues@example.com' AND a.nome = 'Cowboy Bebop';

INSERT INTO Avaliacoes (id_usuario, id_anime, nota, comentario)
SELECT u.id_usuario, a.id_anime, 'Recomendo', 'Muito bom! A história é envolvente e as cenas de ação são incríveis.'
FROM Usuarios u, Animes a
WHERE u.email = 'luiz.goncalves@example.com' AND a.nome = 'Jujutsu Kaisen';

INSERT INTO Avaliacoes (id_usuario, id_anime, nota, comentario)
SELECT u.id_usuario, a.id_anime, 'Não Recomendo', 'O final foi decepcionante. Tinha muito potencial.'
FROM Usuarios u, Animes a
WHERE u.email = 'maycon.cabral@example.com' AND a.nome = 'Attack on Titan';

INSERT INTO Avaliacoes (id_usuario, id_anime, nota, comentario)
SELECT u.id_usuario, a.id_anime, 'Recomendo', 'Um clássico atemporal. A história é profunda e os personagens são cativantes.'
FROM Usuarios u, Animes a
WHERE u.email = 'renan.rodrigues@example.com' AND a.nome = 'Fullmetal Alchemist: Brotherhood';

INSERT INTO Avaliacoes (id_usuario, id_anime, nota, comentario)
SELECT u.id_usuario, a.id_anime, 'Recomendo', 'Engraçado e cheio de ação! A dinâmica da família é ótima.'
FROM Usuarios u, Animes a
WHERE u.email = 'luiz.goncalves@example.com' AND a.nome = 'Spy x Family';

CREATE VIEW vw_animes_com_generos AS
SELECT
    A.id_anime,
    A.nome AS nome_anime,
    A.ano_lancamento,
    A.sinopse,
    A.capa_url,
    GROUP_CONCAT(G.nome_genero SEPARATOR ', ') AS generos
FROM
    Animes AS A
LEFT JOIN
    AnimeGeneros AS AG ON A.id_anime = AG.id_anime
LEFT JOIN
    Generos AS G ON AG.id_genero = G.id_genero
GROUP BY
    A.id_anime, A.nome, A.ano_lancamento, A.sinopse, A.capa_url;

CREATE VIEW vw_informacoes_usuario AS
SELECT
    U.nome AS apelido,
    U.email AS email,
    TU.tipo AS permissao
FROM
    Usuarios AS U
JOIN
    TipoUsuario AS TU ON U.id_tipo_usuario = TU.id_tipo_usuario;

CREATE VIEW vw_lista_pessoal_usuarios_animes AS
SELECT
    u.id_usuario,
    u.nome AS nome_usuario,
    u.email,
    a.id_anime,
    a.nome AS nome_anime,
    a.ano_lancamento,
    l.status_anime,
    l.data_adicao,
    l.data_ultima_atualizacao
FROM ListaPessoalAnimes l
JOIN Usuarios u ON l.id_usuario = u.id_usuario
JOIN Animes a ON l.id_anime = a.id_anime;

CREATE OR REPLACE VIEW vw_comentarios_por_anime AS
SELECT
    AN.id_anime,
    AN.nome AS nome_anime,
    U.id_usuario,
    U.nome AS nome_usuario,
    AV.id_avaliacao,
    AV.nota,
    AV.comentario,
    AV.data_avaliacao,
    AV.data_ultima_atualizacao AS data_ultima_atualizacao_comentario
FROM
    Avaliacoes AS AV
JOIN
    Usuarios AS U ON AV.id_usuario = U.id_usuario
JOIN
    Animes AS AN ON AV.id_anime = AN.id_anime
ORDER BY
    AN.nome ASC,
    AV.data_avaliacao DESC;

DELIMITER //

CREATE FUNCTION fn_total_animes_usuario(p_id_usuario INT)
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_total_animes INT;
    SELECT COUNT(*) INTO v_total_animes FROM ListaPessoalAnimes WHERE id_usuario = p_id_usuario;
    RETURN v_total_animes;
END //

CREATE FUNCTION fn_contar_recomendacoes_anime(p_id_anime INT)
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_total_recomendacoes INT;
SELECT 
    COUNT(*)
INTO v_total_recomendacoes FROM
    Avaliacoes
WHERE
    id_anime = p_id_anime
        AND nota = 'Recomendo';
    RETURN v_total_recomendacoes;
END //



DELIMITER ;

#PROCEDURES PARA ATENDER REGRAS DE NEGÓCIO



DELIMITER $$

DROP PROCEDURE IF EXISTS inserir_usuario_se_valido$$

CREATE PROCEDURE inserir_usuario_se_valido(
    IN p_nome VARCHAR(255),
    IN p_email VARCHAR(255),
    IN p_data_nascimento DATE,
    IN p_senha VARCHAR(255),
    IN p_foto_perfil_url VARCHAR(2048),
    IN p_fundo_perfil_url VARCHAR(2048),
    IN p_descricao TEXT,
    IN p_id_tipo_usuario INT
)
BEGIN
    DECLARE idade INT;

    SET idade = TIMESTAMPDIFF(YEAR, p_data_nascimento, CURDATE());
    
    #ATENDENDO REGRA DE NEGÓCIO: USUÁRIO DEVE TER PELO MENOS 13 ANOS PARA SE CADASTRAR

    IF idade >= 13 THEN 
        INSERT INTO Usuarios (
            nome, email, data_nascimento, senha,
            foto_perfil_url, fundo_perfil_url, descricao, id_tipo_usuario
        )
        VALUES (
            p_nome, p_email, p_data_nascimento, p_senha,
            p_foto_perfil_url, p_fundo_perfil_url, p_descricao, p_id_tipo_usuario
        );
    ELSE
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Usuário não pode ser cadastrado: idade menor a 13 anos.';
    END IF;
END$$


DROP PROCEDURE IF EXISTS adicionar_avaliacao$$

CREATE PROCEDURE adicionar_avaliacao(
    IN p_id_usuario INT,
    IN p_id_anime INT,
    IN p_nota ENUM('Recomendo', 'Não Recomendo'),
    IN p_comentario TEXT
)
BEGIN
    DECLARE existe INT;

    # ATENDENDO REGRA DE NEGÓCIO: USUÁRIO SÓ PODE DEIXAR UMA AVALIAÇÃO NO SISTEMA POR ANIME
    SELECT COUNT(*) INTO existe
    FROM Avaliacoes
    WHERE id_usuario = p_id_usuario AND id_anime = p_id_anime;

    IF existe = 0 THEN
        INSERT INTO Avaliacoes (id_usuario, id_anime, nota, comentario, data_avaliacao)
        VALUES (p_id_usuario, p_id_anime, p_nota, p_comentario, NOW());
    ELSE
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Usuário já avaliou este anime.';
    END IF;
END$$




DROP PROCEDURE IF EXISTS cadastrar_anime$$

DELIMITER $$

CREATE PROCEDURE cadastrar_anime(
    IN p_id_usuario INT,
    IN p_nome VARCHAR(255),
    IN p_ano_lancamento INT,
    IN p_sinopse TEXT,
    IN p_capa_url VARCHAR(2048)
)
BEGIN
    DECLARE tipo_usuario INT;

    # ATENDENDO REGRAS DE NEGÓCIO: SÓ ADMINISTRADOR PODE ADICIONAR ANIME + ANIME DEVE TER TODOS OS DADOS PARA SER PREENCHIDO.
    SELECT id_tipo_usuario INTO tipo_usuario
    FROM Usuarios
    WHERE id_usuario = p_id_usuario;

    IF tipo_usuario = 1 THEN
        
        IF p_nome IS NOT NULL AND p_ano_lancamento > 1900  AND p_sinopse IS NOT NULL AND p_capa_url IS NOT NULL THEN
            INSERT INTO Animes (nome, ano_lancamento, sinopse, capa_url)
            VALUES (p_nome, p_ano_lancamento, p_sinopse, p_capa_url);
        ELSE
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Todos os dados obrigatórios devem ser preenchidos corretamente.';
        END IF;
    ELSE
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Apenas administradores podem cadastrar animes.';
    END IF;
END$$



DROP PROCEDURE IF EXISTS adicionar_atualizar_anime_listapessoal$$

CREATE PROCEDURE adicionar_atualizar_anime_listapessoal(
    IN p_id_usuario INT,
    IN p_id_anime INT,
    IN p_status_anime ENUM('Assistindo', 'Completado', 'Planejando Assistir', 'Droppado'),
    IN p_is_favorito BOOLEAN
)
BEGIN
    DECLARE existe_anime_na_lista INT;

    SELECT COUNT(*) INTO existe_anime_na_lista
    FROM ListaPessoalAnimes
    WHERE id_usuario = p_id_usuario AND id_anime = p_id_anime;
    
    # ATENDENDO REGRAS DE NEGÓCIO: APENAS O PRÓPRIO USUÁRIO PODE ADICIONAR/ATUALIZAR ANIMES DA PRÓPRIA LISTA PESSOAL

    IF existe_anime_na_lista = 0 THEN
        INSERT INTO ListaPessoalAnimes (id_usuario, id_anime, status_anime, is_favorito)
        VALUES (p_id_usuario, p_id_anime, p_status_anime, p_is_favorito);
    ELSE
        UPDATE ListaPessoalAnimes
        SET status_anime = p_status_anime,
            is_favorito = p_is_favorito,
            data_ultima_atualizacao = CURRENT_TIMESTAMP
        WHERE id_usuario = p_id_usuario AND id_anime = p_id_anime;
    END IF;
END$$



DELIMITER ;

INSERT IGNORE INTO Animes (nome, ano_lancamento, sinopse, capa_url) VALUES
('One-Punch Man', 2015, 'Saitama é um herói tão poderoso que consegue derrotar qualquer inimigo com um único soco, o que o leva a uma crise existencial por falta de desafios.', 'https://animeflix.com.br/wp-content/uploads/2025/06/One-Punch-Man-3-temporada.jpg'),
('Naruto Shippuden', 2007, 'A continuação da jornada de Naruto Uzumaki, que busca salvar seu amigo Sasuke Uchiha e proteger o mundo ninja de uma organização criminosa.', 'https://m.media-amazon.com/images/I/810Xo+d8xlL.jpg'),
('Hunter x Hunter', 2011, 'Gon Freecss aspira se tornar um Hunter, um membro de elite da sociedade, para encontrar seu pai desaparecido, que também é um Hunter lendário.', 'https://m.media-amazon.com/images/M/MV5BYzYxOTlkYzctNGY2MC00MjNjLWIxOWMtY2QwYjcxZWIwMmEwXkEyXkFqcGc@._V1_.jpg'),
('Mob Psycho 100', 2016, 'Um garoto com poderes psíquicos poderosos tenta manter suas emoções sob controle para evitar que seus poderes saiam de controle.', 'https://br.web.img2.acsta.net/pictures/20/11/12/14/25/3371142.jpg'),
('Vinland Saga', 2019, 'Um jovem islandês busca vingança contra o homem que assassinou seu pai, em meio à era dos vikings.', 'https://br.web.img3.acsta.net/pictures/19/09/16/17/09/4903250.jpg'),
('Black Lagoon', 2006, 'Um empresário japonês é sequestrado por um grupo de mercenários e acaba se juntando a eles, navegando pelo submundo do crime no Sudeste Asiático.', 'https://upload.wikimedia.org/wikipedia/en/8/82/Black_Lagoon_%28TV_series%29_key_visual.png'),
('Akame ga Kill!', 2014, 'Um jovem do interior viaja para a capital para se juntar ao exército, mas acaba se envolvendo com um grupo de assassinos que luta contra a corrupção do império.', 'https://animenew.com.br/wp-content/uploads/2024/07/Akame-ga-Kill-jpg.webp'),
('Chainsaw Man', 2022, 'Denji, um jovem que trabalha como caçador de demônios para a Yakuza, ganha a habilidade de se transformar em um demônio com motosserras.', 'https://a.storyblok.com/f/178900/500x707/d01abe04d2/chainsaw-man-the-movie-reze-arc-anime-film-date-visual.jpg/m/filters:quality(95)format(webp)'),
('Cyberpunk: Edgerunners', 2022, 'Em uma distopia movida a tecnologia e modificações corporais, um jovem de rua tenta sobreviver se tornando um mercenário conhecido como edgerunner.', 'https://upload.wikimedia.org/wikipedia/pt/6/68/Cyberpunk_mercenarios.jpg'),
('Fate/Zero', 2011, 'Sete magos invocam heróis lendários para lutar em uma guerra secreta pelo Santo Graal, um artefato que pode realizar qualquer desejo.', 'https://m.media-amazon.com/images/M/MV5BMTEyMjRiYjUtMzJkOC00NDBmLWI4Y2YtNDk5ZTQyMDNhMjgxXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('One Piece', 1999, 'Monkey D. Luffy, um garoto cujo corpo ganhou as propriedades de borracha, parte em uma jornada para encontrar o tesouro supremo, o One Piece, e se tornar o Rei dos Piratas.', 'https://i0.wp.com/geekpopnews.com.br/wp-content/uploads/2024/04/One-Piece-Egghead.jpg?resize=424%2C600&ssl=1'),
('Made in Abyss', 2017, 'Uma garota órfã e seu amigo robô descem em um abismo misterioso e perigoso em busca da mãe dela, que desapareceu no local.', 'https://m.media-amazon.com/images/M/MV5BNzYzZGQzMWQtODhkZC00NzU1LTkxYzQtMTQzMmQxNDA0MjdkXkEyXkFqcGc@._V1_.jpg'),
('The Promised Neverland', 2019, 'Um grupo de crianças superdotadas descobre a verdade sombria sobre o orfanato em que vivem e planeja uma fuga perigosa.', 'https://m.media-amazon.com/images/M/MV5BMGQ4ZGJhZTUtZDQ5Mi00NTI1LWEyYjItMzIxM2VlNmY4MDEyXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Dr. Stone', 2019, 'Após toda a humanidade ser transformada em pedra, um gênio da ciência desperta milhares de anos depois e tenta reconstruir a civilização.', 'https://assets.gamearena.gg/wp-content/uploads/2023/06/29234448/dr-stone.webp'),
('Mushoku Tensei: Jobless Reincarnation', 2021, 'Um homem de 34 anos reencarna em um mundo de fantasia como um bebê e decide viver sua nova vida ao máximo, se tornando um mago poderoso.', 'https://a.storyblok.com/f/178900/2000x3000/c7f4da36b5/mushoku-tensei-jobless-reincarnation-staffel-2-base-asset-2x3.png/m/filters:quality(95)format(webp)'),
('JoJo''s Bizarre Adventure', 2012, 'As aventuras bizarras de várias gerações da família Joestar, que possuem poderes únicos e enfrentam inimigos sobrenaturais.', 'https://image.api.playstation.com/vulcan/ap/rnd/202203/0821/tKixgw7BoRSwwhrGzrHARkLl.png'),
('Golden Kamuy', 2018, 'Um veterano de guerra e uma jovem Ainu procuram por um tesouro escondido no início do século 20 em Hokkaido, Japão.', 'https://m.media-amazon.com/images/M/MV5BYTliMTU1OTEtOTE0Yi00YmUxLWFiN2YtYzBlNzdkNjg2MmY3XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('To Your Eternity', 2021, 'Um ser imortal é enviado à Terra e assume a forma de diferentes seres e objetos, aprendendo sobre a vida e a humanidade ao longo de sua jornada.', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQBWZtr7snj_89kF1_XQ1POb6REwh40ItXqhg&s'),
('Dororo', 2019, 'Um jovem ronin, cujas partes do corpo foram roubadas por demônios, viaja pelo Japão feudal para derrotar os demônios e recuperar seu corpo.', 'https://br.web.img2.acsta.net/pictures/19/06/26/11/13/1639915.jpg'),
('Spice and Wolf', 2008, 'Um mercador viajante encontra uma deusa loba que deseja retornar à sua terra natal no norte. Juntos, eles viajam e se envolvem em intrigas econômicas.', 'https://a.storyblok.com/f/178900/1200x1696/fc09b55c7e/spice-and-wolf-abundant-harvest-visual.jpg/m/filters:quality(95)format(webp)'),
('Somali and the Forest Spirit', 2020, 'Em um mundo governado por espíritos e monstros, um golem da floresta encontra uma garotinha humana e se torna seu protetor em uma jornada para encontrar outros humanos.', 'https://m.media-amazon.com/images/M/MV5BMGI5NzcyYmItNjI1ZS00YWU4LWE1ODYtYmZhN2E1YmU1MTk4XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Girls'' Last Tour', 2017, 'Duas garotas viajam pelas ruínas de uma civilização em um mundo pós-apocalíptico, buscando comida e suprimentos para sobreviver.', 'https://m.media-amazon.com/images/I/81F8arIWW2L._UF1000,1000_QL80_.jpg'),
('Kino''s Journey: The Beautiful World', 2017, 'Kino, uma jovem viajante, e sua motocicleta falante, Hermes, exploram diferentes países com costumes e culturas únicas.', 'https://m.media-amazon.com/images/M/MV5BYjdjZmMwODQtZTU4YS00YWQ3LTg2NzktNjZiZjZhNGMwNWZhXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('A Place Further Than the Universe', 2018, 'Quatro garotas do ensino médio se juntam a uma expedição para a Antártica, cada uma com suas próprias razões para embarcar nesta aventura.', 'https://m.media-amazon.com/images/M/MV5BNmQzYjI2NjgtMDY1Yy00OWViLWE3NjUtZmQxZTYwMzRhYzY1XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Ranking of Kings', 2021, 'Bojji, um príncipe surdo e fraco, sonha em se tornar o maior dos reis, apesar das dúvidas de seu povo. Ele embarca em uma aventura com seu amigo Kage.', 'https://m.media-amazon.com/images/I/71N4cbnys0L._UF1000,1000_QL80_.jpg'),
('KonoSuba: God''s Blessing on This Wonderful World!', 2016, 'Um garoto que morre de forma patética é enviado para um mundo de fantasia com uma deusa inútil, e juntos eles formam um grupo disfuncional de aventureiros.', 'https://m.media-amazon.com/images/M/MV5BNTQ5NzJjMjgtNDliNC00YTdmLWJiOTQtYWRiMzY4OWU5NGQ3XkEyXkFqcGc@._V1_.jpg'),
('Gintama', 2006, 'Em um Japão feudal invadido por alienígenas, um samurai preguiçoso faz qualquer trabalho para sobreviver, resultando em situações hilárias e, ocasionalmente, sérias.', 'https://m.media-amazon.com/images/M/MV5BNTMzNjE0N2ItNjFiYi00NmIzLTk1MzMtZWFjNThjMzI5MTJlXkEyXkFqcGc@._V1_.jpg'),
('The Disastrous Life of Saiki K.', 2016, 'Um estudante do ensino médio com uma vasta gama de poderes psíquicos tenta viver uma vida normal e tranquila, apesar de seus amigos excêntricos e seus poderes.', 'https://m.media-amazon.com/images/M/MV5BMzJhYWEyMWUtZDYwNS00NTU4LTgwODItYjBlNzEwMTc5MTc2XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Grand Blue Dreaming', 2018, 'Um calouro universitário é arrastado para um clube de mergulho cheio de festas e bebedeiras, levando a situações cômicas e embaraçosas.', 'https://images.justwatch.com/poster/76484961/s718/guranburu.jpg'),
('Kaguya-sama: Love Is War', 2019, 'Dois gênios do conselho estudantil, que estão apaixonados um pelo outro, travam uma batalha psicológica para fazer o outro confessar seu amor primeiro.', 'https://es.web.img3.acsta.net/pictures/20/04/08/16/07/4929472.jpg'),
('Nichijou', 2011, 'As vidas cotidianas surreais e absurdas de um grupo de estudantes do ensino médio em uma cidade excêntrica.', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTH26hCKdcIv9_zV1RMNUMV0rLy0AVOSz40pw&s'),
('Asobi Asobase', 2018, 'Três garotas do ensino médio formam um clube onde jogam jogos simples que geralmente se transformam em competições caóticas e hilárias.', 'https://hanasu.com.br/wp-content/uploads/2023/06/AsobiAsobase-1.jpg'),
('Daily Lives of High School Boys', 2012, 'Uma comédia sobre as vidas mundanas e imaginativas de Tadakuni, Hidenori e Yoshitake e seus colegas de uma escola só para meninos.', 'https://m.media-amazon.com/images/M/MV5BMDVhOTNmMzgtNWNhNy00OGEwLWI4ODEtM2IxZTFjYTQ4ZTU4XkEyXkFqcGc@._V1_.jpg'),
('Haven''t You Heard? I''m Sakamoto', 2016, 'Sakamoto é o estudante perfeito: bonito, inteligente e popular. Ele lida com qualquer situação com uma graça e estilo impecáveis, para o espanto de todos.', 'https://m.media-amazon.com/images/M/MV5BOTE0MzUzZDktNjZlMS00NjU4LWFkMjYtNTkxMGQxMzZjOGZhXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Detroit Metal City', 2008, 'Um jovem músico tímido e gentil sonha em ser um cantor de pop, mas acaba se tornando o vocalista de uma banda de death metal extremamente popular.', 'https://m.media-amazon.com/images/M/MV5BNjYxMjg5ZDktOTI0NC00NDM3LWIxNGUtZTRiOTg3ZTc5NzAyXkEyXkFqcGc@._V1_.jpg'),
('Hinamatsuri', 2018, 'Um membro da yakuza de repente se vê cuidando de uma garota com poderes psicocinéticos que apareceu em seu apartamento.', 'https://imusic.b-cdn.net/images/item/original/147/5022366969147.jpg?anime-2022-hinamatsuri-the-complete-series-blu-ray&class=scaled&v=1664608676'),
('Monthly Girls'' Nozaki-kun', 2014, 'Uma estudante do ensino médio confessa seu amor por seu colega de classe, apenas para descobrir que ele é um famoso artista de mangá shoujo e se torna sua assistente.', 'https://m.media-amazon.com/images/M/MV5BNTJhMjRjYzMtNDYyMC00NTQ0LThiNzctMjY3MjIyYWI4NTNjXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Osomatsu-san', 2015, 'As aventuras cômicas e surreais dos sextuplos da família Matsuno, que são adultos preguiçosos e desempregados.', 'https://m.media-amazon.com/images/M/MV5BNGU1Y2I1YTYtOTMwNi00MTUyLTlmODItYzU2NDEzNTc2MGU2XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('The Way of the Househusband', 2021, 'Um ex-chefe da yakuza lendário se aposenta do crime para se tornar um dono de casa em tempo integral, abordando as tarefas domésticas com a mesma intensidade de sua vida anterior.', 'https://a.storyblok.com/f/178900/1500x2111/7047742245/054d7c50a6845564d25fa8e32f754b2e1603765731_main.jpg/m/filters:quality(95)format(webp)'),
('Your Lie in April', 2014, 'Um prodígio do piano que perdeu a capacidade de ouvir sua própria música encontra uma violinista de espírito livre que o ajuda a redescobrir a alegria da música.', 'https://imusic.b-cdn.net/images/item/original/825/4988010063825.jpg?v-a-2014-your-lie-in-april-shigatsu-wa-kimi-no-uso-cd&class=scaled&v=1647835095'),
('Clannad: After Story', 2008, 'A continuação de Clannad, que explora os desafios da vida adulta, família e paternidade, com momentos emocionantes e comoventes.', 'https://media.fstatic.com/UyIAlRJ5WV1j9cW1t4fNqny8Z2M=/322x478/smart/filters:format(webp)/media/movies/covers/2020/11/dp9038.jpg'),
('Anohana: The Flower We Saw That Day', 2011, 'Um grupo de amigos de infância se reúne anos após a morte trágica de uma de suas amigas, quando seu fantasma aparece para o líder do grupo.', 'https://m.media-amazon.com/images/M/MV5BNTc1NzEwOTU0MV5BMl5BanBnXkFtZTgwNTMxMzY5MDE@._V1_.jpg'),
('Violet Evergarden', 2018, 'Uma ex-soldado que foi criada como uma arma tenta se reintegrar à sociedade trabalhando como uma \"Autômata de Automemórias\", escrevendo cartas para os outros e aprendendo sobre as emoções humanas.', 'https://m.media-amazon.com/images/M/MV5BMWUwNDFiNjQtYjQ0MC00MTcxLWE0MGQtNTdkYTlhZGU2NDFmXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('March Comes in Like a Lion', 2016, 'A história de Rei Kiriyama, um jogador profissional de shogi de 17 anos que lida com a solidão e a depressão enquanto desenvolve relacionamentos com três irmãs.', 'https://m.media-amazon.com/images/M/MV5BMDJmZGZmNjQtMzE4NS00ZGRmLWFiMTQtM2EzZDhhZGNkY2FmXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('A Silent Voice', 2016, 'Um garoto que intimidou uma colega de classe surda no ensino fundamental tenta se redimir anos depois, ao encontrá-la novamente.', 'https://m.media-amazon.com/images/I/719QuSqLbQL._UF894,1000_QL80_.jpg'),
('Fruits Basket', 2019, 'Uma órfã gentil se envolve com a misteriosa família Sohma, que é amaldiçoada a se transformar nos animais do zodíaco chinês quando abraçada pelo sexo oposto.', 'https://upload.wikimedia.org/wikipedia/pt/6/64/Arte_Fruits_Basket.jpg'),
('Showa Genroku Rakugo Shinju', 2016, 'A história de dois contadores de histórias rakugo e sua jornada através das eras, explorando temas de arte, tradição, amor e perda.', 'https://imgsrv.crunchyroll.com/cdn-cgi/image/fit=contain,format=auto,quality=85,width=480,height=720/catalog/crunchyroll/1a5cf74930e3f840d1e4722a243ba2c6.jpg'),
('Angel Beats!', 2010, 'Um grupo de estudantes em um purgatório escolar se rebela contra Deus enquanto lida com seus passados trágicos.', 'https://upload.wikimedia.org/wikipedia/pt/thumb/c/c6/Angel_Beats_poster.jpg/250px-Angel_Beats_poster.jpg'),
('Steins;Gate', 2011, 'Um cientista louco e seus amigos descobrem uma maneira de enviar mensagens para o passado, o que os leva a uma conspiração perigosa e consequências inesperadas.', 'https://m.media-amazon.com/images/M/MV5BZjI1YjZiMDUtZTI3MC00YTA5LWIzMmMtZmQ0NTZiYWM4NTYwXkEyXkFqcGc@._V1_.jpg'),
('Orange', 2016, 'Uma estudante do ensino médio recebe uma carta de seu eu futuro, pedindo-lhe para evitar uma série de arrependimentos, especialmente em relação a um novo aluno transferido.', 'https://down-br.img.susercontent.com/file/br-11134207-7r98o-ly2ebns1lg9eba'),
('Maquia: When the Promised Flower Blooms', 2018, 'Uma garota de uma raça que para de envelhecer na adolescência encontra um bebê humano órfão e decide criá-lo, enfrentando os desafios da maternidade e da mortalidade.', 'https://upload.wikimedia.org/wikipedia/en/a/a8/SayoAsa_Theatrical_Release_Poster.jpg'),
('I Want to Eat Your Pancreas', 2018, 'Um estudante introvertido descobre que sua colega de classe popular tem uma doença pancreática terminal e decide passar seus últimos dias com ela, guardando seu segredo.', 'https://m.media-amazon.com/images/M/MV5BMTQ1ODIzOGQtOGFkZC00MWViLTgyYmUtNWJkNmIxZjYxMTdmXkEyXkFqcGc@._V1_.jpg'),
('Plastic Memories', 2015, 'Em um futuro próximo, um jovem trabalha em uma empresa que recupera \"Giftias\", androides quase humanos com uma vida útil limitada, antes que eles percam suas memórias e se tornem hostis.', 'https://images.justwatch.com/poster/302154237/s718/purasuteitsuku-memorizu.jpg'),
('Erased', 2016, 'Um jovem mangaká com a habilidade de voltar no tempo por breves momentos é enviado 18 anos ao passado para impedir uma série de sequestros e assassinatos que o afetaram.', 'https://br.web.img3.acsta.net/c_310_420/pictures/19/09/20/16/59/1993201.jpg'),
('Haikyuu!!', 2014, 'Um garoto de baixa estatura se inspira em um jogador de vôlei famoso e decide se tornar o melhor jogador de vôlei, apesar de sua altura.', 'https://www.intoxianime.com/wp-content/uploads/2021/05/visual-2-9-725x1024.jpg'),
('Kuroko''s Basketball', 2012, 'A \"Geração dos Milagres\", uma equipe de basquete escolar invicta, se separa. O \"jogador fantasma\" da equipe se junta a um novo time para derrotar seus antigos companheiros.', 'https://images.justwatch.com/poster/181383869/s718/hei-zi-nobasuke.jpg'),
('Hajime no Ippo', 2000, 'Um estudante tímido que sofre bullying é salvo por um boxeador e descobre sua paixão pelo boxe, iniciando uma jornada para se tornar um campeão.', 'https://m.media-amazon.com/images/M/MV5BN2UzMmM5NTQtYjUxYy00OWVjLTkwOWMtYzFhOGQxN2VlZjI5XkEyXkFqcGc@._V1_.jpg'),
('Yuri!!! on Ice', 2016, 'Um patinador artístico japonês à beira da aposentadoria tem sua carreira revivida quando seu ídolo, o campeão russo Victor Nikiforov, decide se tornar seu treinador.', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQlOCAGMILj3KwQtJACvZfST2ecP-IDlnEcvRhpQHzk3XIbg5XA4Y_psOgqNFEvKwydBBI&usqp=CAU'),
('Slam Dunk', 1993, 'Um delinquente de cabelo vermelho se junta ao time de basquete da escola para impressionar uma garota, mas acaba se apaixonando pelo esporte.', 'https://pbs.twimg.com/media/GHG0zn_XIAAS9JW?format=jpg&name=large'),
('Ping Pong the Animation', 2014, 'Dois amigos de infância com personalidades opostas competem no mundo do tênis de mesa competitivo, explorando temas de talento, trabalho duro e amizade.', 'https://m.media-amazon.com/images/M/MV5BNmYzMDRkMmYtZjNiZS00ZGVlLWI2NWQtMjc2NTE1OThmZTBiXkEyXkFqcGc@._V1_.jpg'),
('Chihayafuru', 2011, 'Uma garota do ensino médio se dedica ao karuta, um jogo de cartas competitivo japonês, com o objetivo de se tornar a melhor jogadora do Japão e reencontrar seus amigos de infância.', 'https://m.media-amazon.com/images/I/81JG0p5e+1L.jpg'),
('Run with the Wind', 2018, 'Um ex-corredor de elite é persuadido a se juntar ao clube de atletismo de sua universidade e a participar da maratona de revezamento Hakone Ekiden com um grupo de novatos.', 'https://m.media-amazon.com/images/M/MV5BODc4NjczYjAtMjAwMi00YWQ3LWI1NTQtZWNlZDk4YmIzYWM4XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Free!', 2013, 'Quatro amigos de infância que compartilham uma paixão pela natação se reúnem no ensino médio e formam um clube de natação, reacendendo sua amizade e rivalidade.', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRyqaTM2u6it1OWScVoiHsQqQIQPFhxoNkftg&s'),
('Megalo Box', 2018, 'Em um futuro distópico, boxeadores lutam com equipamentos mecânicos. Um lutador clandestino conhecido como \"Junk Dog\" decide entrar no torneio de elite Megalonia.', 'https://m.media-amazon.com/images/M/MV5BODQwZGFkODktZDk4MS00MTY2LWIzOTgtNTc5YjM4YTAxMjcwXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Eyeshield 21', 2005, 'Um estudante tímido e veloz é forçado a se juntar ao time de futebol americano da escola como o running back secreto, \"Eyeshield 21\".', 'https://m.media-amazon.com/images/M/MV5BMDkwNmU2NzYtYzA4ZS00NTRhLWIzN2ItNWI5M2Q4OGYwNTQ5XkEyXkFqcGc@._V1_.jpg'),
('Diamond no Ace', 2013, 'Um arremessador de beisebol de uma cidade pequena é recrutado por uma escola de elite em Tóquio e compete com outros jogadores talentosos por um lugar no time.', 'https://a.storyblok.com/f/178900/417x600/c8c319a831/115fda936dc47e3a0eb18e96743501401550696815_full.jpg/m/417x600'),
('Sk8 the Infinity', 2021, 'Um estudante do ensino médio que adora andar de skate se envolve em \"S\", uma corrida de skate secreta e perigosa em uma mina abandonada.', 'https://m.media-amazon.com/images/M/MV5BMWYzYWE3N2YtZDI0Yy00ZWU2LWI3YzgtMzEyNTg0ZjFmNWZjXkEyXkFqcGc@._V1_.jpg'),
('Initial D', 1998, 'Um entregador de tofu se envolve no mundo das corridas de rua ilegais nas montanhas do Japão, usando suas habilidades de direção aprimoradas em seu trabalho.', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQihg3zQ3uiVDviMUYONkurNUmeM3BoV8JuZ74SfctoIN_nDnuMHVuuoNruQ45br4ImuB8&usqp=CAU'),
('Tsurune', 2018, 'Um jovem que desistiu do kyudo (arco e flecha japonês) devido a um incidente traumático se junta ao clube de kyudo de sua nova escola e tenta superar seu medo.', 'https://m.media-amazon.com/images/S/pv-target-images/179cf3c1e5f890a2f811c3d2b0cc791838f4eb4b8025db8b73e9fa33b258aeb4.jpg'),
('Frieren: Beyond Journey''s End', 2023, 'Uma elfa, membro de um grupo de heróis que derrotou o Rei Demônio, embarca em uma nova jornada para entender melhor os humanos após a morte de seus companheiros.', 'https://m.media-amazon.com/images/M/MV5BZTI4ZGMxN2UtODlkYS00MTBjLWE1YzctYzc3NDViMGI0ZmJmXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Re:Zero - Starting Life in Another World', 2016, 'Um jovem é subitamente transportado para um mundo de fantasia e descobre que tem a habilidade de voltar no tempo após a morte, usando-a para salvar a si mesmo e aos outros.', 'https://a.storyblok.com/f/178900/1064x1503/b5245a2097/re-zero-starting-life-in-another-world-season-3-key-visual-3.jpg/m/filters:quality(95)format(webp)'),
('That Time I Got Reincarnated as a Slime', 2018, 'Um homem é reencarnado como um slime em um mundo de fantasia e, com suas habilidades únicas, constrói uma nação de monstros.', 'https://m.media-amazon.com/images/M/MV5BNWU3OTRlNWMtZGQyOC00YzJhLWIyNjctYmI2YzgyZTQ3ZDNmXkEyXkFqcGc@._V1_.jpg'),
('Overlord', 2015, 'Um jogador fica preso em um jogo de RPG online como seu personagem, um poderoso lorde morto-vivo, e decide conquistar o novo mundo em que se encontra.', 'https://m.media-amazon.com/images/M/MV5BYjNjNDBmZjAtMGZiMS00ODBkLWFjYWItZWQ1ZjEwOGNmZDBjXkEyXkFqcGc@._V1_.jpg'),
('The Ancient Magus'' Bride', 2017, 'Uma jovem órfã que se vende em um leilão é comprada por um mago não humano e se torna sua aprendiz e noiva, sendo introduzida a um mundo de magia e fadas.', 'https://m.media-amazon.com/images/M/MV5BZjUzM2U3YjctMDg5My00NTE3LWE5ZGUtOTRkYWJlM2YyOTk2XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('No Game No Life', 2014, 'Dois irmãos gamers são transportados para um mundo onde todos os conflitos são resolvidos através de jogos, e eles planejam conquistar este novo mundo.', 'https://upload.wikimedia.org/wikipedia/pt/2/28/No_game_no_life_zero.jpg'),
('Log Horizon', 2013, 'Milhares de jogadores ficam presos no mundo de um MMORPG popular e devem aprender a sobreviver e construir uma nova sociedade neste mundo virtual.', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQg1mZoke4WsDjsUWTgcRXQ1D6ZrGKHZiz-5w&s'),
('Magi: The Labyrinth of Magic', 2012, 'Inspirado em \"As Mil e Uma Noites\", a história segue Aladdin, um jovem mago, e seus amigos Alibaba e Morgiana enquanto exploram masmorras e lutam contra o destino.', 'https://m.media-amazon.com/images/M/MV5BZmU1N2FlNjEtNGZmYS00ZjgwLWIyMWYtOGNiM2RjZjU0MzdkXkEyXkFqcGc@._V1_.jpg'),
('Princess Mononoke', 1997, 'Um príncipe amaldiçoado se envolve no conflito entre os deuses da floresta e os humanos que consomem seus recursos, ao lado de San, a Princesa Mononoke.', 'https://m.media-amazon.com/images/I/81jSJRqb9IL.jpg'),
('Berserk', 1997, 'Em um mundo de fantasia sombria medieval, um mercenário solitário chamado Guts busca vingança contra seu antigo líder, que se tornou um demônio.', 'https://m.media-amazon.com/images/M/MV5BOTY0ZGMwZmEtNDVmMy00OWJmLWFlNjEtMGZkNTdlN2EzOWVmXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('The Seven Deadly Sins', 2014, 'Uma princesa procura um grupo de cavaleiros lendários conhecidos como os Sete Pecados Capitais para ajudá-la a retomar seu reino dos tiranos Cavaleiros Sagrados.', 'https://br.web.img2.acsta.net/pictures/20/08/11/20/56/3761241.jpg'),
('Goblin Slayer', 2018, 'Em um mundo de fantasia, um aventureiro se dedica exclusivamente a exterminar goblins, monstros que são frequentemente subestimados, mas que representam uma grande ameaça.', 'https://m.media-amazon.com/images/M/MV5BMTk1MGM5ZDQtMWFkZS00YTUyLWIzYWYtZTQwYWYzNzQ3MTMyXkEyXkFqcGc@._V1_.jpg'),
('Howl''s Moving Castle', 2004, 'Uma jovem chapeleira é amaldiçoada por uma bruxa e transformada em uma velha. Ela busca a ajuda de um mago excêntrico chamado Howl para quebrar a maldição.', 'https://i0.wp.com/gkids.com/wp-content/uploads/2025/04/GF25_Posters_6_Howls_A-min.jpg?fit=692%2C1024&ssl=1'),
('Spirited Away', 2001, 'Uma garota de 10 anos se perde em um mundo de deuses, espíritos e monstros e deve trabalhar em uma casa de banhos para encontrar uma maneira de voltar para casa.', 'https://m.media-amazon.com/images/I/71zqphejhbL._UF894,1000_QL80_.jpg'),
('Ascendance of a Bookworm', 2019, 'Uma jovem amante de livros reencarna em um mundo de fantasia como uma garota frágil em uma sociedade onde os livros são raros e caros. Ela decide fazer seus próprios livros.', 'https://m.media-amazon.com/images/M/MV5BYmFhMmUzMDMtMWEwZi00NjBlLTljZmUtZTY5OGI4YWEzYTQxXkEyXkFqcGc@._V1_.jpg'),
('Ghost in the Shell: Stand Alone Complex', 2002, 'Em um futuro cyberpunk, a Major Motoko Kusanagi e sua equipe da Seção 9 de Segurança Pública investigam crimes cibernéticos e terrorismo.', 'https://images.justwatch.com/poster/319547224/s718/o-fantasma-do-futuro-complexo-da-solidao.jpg'),
('Psycho-Pass', 2012, 'Em um futuro onde um sistema pode medir o potencial criminoso de uma pessoa, inspetores e executores caçam criminosos latentes antes que eles possam cometer crimes.', 'https://upload.wikimedia.org/wikipedia/pt/b/b6/Psycho_Pass.jpeg'),
('Code Geass: Lelouch of the Rebellion', 2006, 'Em um mundo alternativo, um príncipe exilado ganha um poder misterioso chamado Geass e lidera uma rebelião contra o império que conquistou seu país.', 'https://m.media-amazon.com/images/M/MV5BY2U3M2ZkMzktZTg2MC00YWI2LThmNTItNDJmOTkyZDAwYzQxXkEyXkFqcGc@._V1_.jpg'),
('Neon Genesis Evangelion', 1995, 'Em um mundo pós-apocalíptico, adolescentes são recrutados para pilotar mechas gigantes chamados Evangelions e lutar contra seres monstruosos conhecidos como Anjos.', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRO9I02qGcWtNx46IgRlXc5L72EkPMDRLyg-yQ0cvQC-y0NLwzRP7V4mEQeioW30YVGQ3g&usqp=CAU'),
('Akira', 1988, 'Em uma Neo-Tóquio distópica, o líder de uma gangue de motoqueiros tenta salvar seu amigo que desenvolveu poderes telecinéticos perigosos após um acidente.', 'https://upload.wikimedia.org/wikipedia/pt/d/d8/Akira_p%C3%B4ster.jpg'),
('Legend of the Galactic Heroes', 1988, 'Uma ópera espacial épica que narra a longa guerra entre o Império Galáctico e a Aliança dos Planetas Livres, focando nos gênios estratégicos de ambos os lados.', 'https://m.media-amazon.com/images/M/MV5BM2UxMDkxM2ItYTNmNS00MjJjLWEyZDQtOTdhNDRhMTg1ZTgwXkEyXkFqcGc@._V1_.jpg'),
('Planetes', 2003, 'A história segue uma equipe de coletores de lixo espacial em 2075, explorando seus sonhos, relacionamentos e o desafio de viver e trabalhar no espaço.', 'https://a.storyblok.com/f/178900/960x1344/6a09b2bebd/planetes2.jpg/m/filters:quality(95)format(webp)'),
('Texhnolyze', 2003, 'Em uma cidade subterrânea decadente, um boxeador órfão perde um braço e uma perna e é equipado com próteses cibernéticas, envolvendo-se nos conflitos de poder da cidade.', 'https://m.media-amazon.com/images/M/MV5BNzc0NTY4ZWQtZTkyMC00ZDI5LWE3NjAtYmQ4NGM4NmUwZmMwXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Serial Experiments Lain', 1998, 'Uma garota introvertida se envolve com a Wired, uma rede de comunicação global semelhante à internet, e sua percepção da realidade começa a se desfazer.', 'https://images.justwatch.com/poster/330291089/s332/temporada-1'),
('Ergo Proxy', 2006, 'Em uma cidade-domo pós-apocalíptica, uma inspetora investiga uma série de assassinatos cometidos por robôs infectados com um vírus, descobrindo uma conspiração sombria.', 'https://m.media-amazon.com/images/M/MV5BYmZmOGM3MjAtYWI3My00N2QyLWJmNTctNTE5MTU2ZjcxYzNiXkEyXkFqcGc@._V1_.jpg'),
('From the New World', 2012, 'Em um futuro distante onde os humanos desenvolveram poderes psíquicos, um grupo de crianças descobre a verdade sombria sobre sua sociedade utópica.', 'https://m.media-amazon.com/images/M/MV5BMDY4N2Y4YTgtMTlmYy00ZmY1LWFhMjktNDVkNjc4MTlhNWI5XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Space Dandy', 2014, 'As aventuras cômicas de Dandy, um caçador de alienígenas que viaja pelo universo em busca de espécies raras e desconhecidas.', 'https://m.media-amazon.com/images/M/MV5BYTc3Zjc3OTktNTQ4Yy00NjQxLWI5NmEtZjU5Yzk2OWQ4ODZlXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Vivy: Fluorite Eye''s Song', 2021, 'Uma IA autônoma, criada para cantar e trazer felicidade às pessoas, é encarregada por uma IA do futuro de impedir uma guerra entre humanos e IAs.', 'https://preview.redd.it/u9p8gwgnqgb61.jpg?auto=webp&s=44d6e95987bb4a2274d3efd90e1e71c4bc905471'),
('Gargantia on the Verdurous Planet', 2013, 'Um soldado de uma aliança galáctica em guerra com alienígenas cai em um planeta oceânico e deve se adaptar a uma nova vida com os habitantes locais.', 'https://m.media-amazon.com/images/M/MV5BNjVhMDMyMDAtMTM2OC00YmFiLTgwNTUtY2Y5NWExMmU0ZWE5XkEyXkFqcGc@._V1_.jpg'),
('Black Clover', 2017, 'Em um mundo onde a magia é tudo, um garoto nascido sem magia sonha em se tornar o Rei Mago, o mago mais forte do reino.', 'https://m.media-amazon.com/images/M/MV5BZmZkZjNhMWMtM2U0Mi00MjdlLTk3NmMtMTMwZjgwOTJmODMzXkEyXkFqcGc@._V1_.jpg'),
('Little Witch Academia', 2017, 'Atsuko Kagari se matricula em uma prestigiosa academia de bruxas, inspirada por sua ídolo, e embarca em aventuras mágicas com suas amigas, apesar de não ter talento natural.', 'https://m.media-amazon.com/images/M/MV5BMjA0YTdmOTYtZWQyOS00NjU4LTgyMGItOWRiMWFiZTAzNTYwXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Puella Magi Madoka Magica', 2011, 'Uma estudante do ensino médio e sua amiga fazem um contrato com uma criatura mágica para se tornarem garotas mágicas, mas descobrem a dura realidade e o custo de seus poderes.', 'https://upload.wikimedia.org/wikipedia/pt/9/97/Puella_Magi_Madoka_Magica_%28Capa_do_volume_1%2C_Blu-ray%29.jpg'),
('The Irregular at Magic High School', 2014, 'Em uma escola onde os alunos são divididos por suas habilidades mágicas, um irmão e uma irmã causam um alvoroço com seus talentos extraordinários e não convencionais.', 'https://m.media-amazon.com/images/I/91-qQHhtfSL._UF1000,1000_QL80_.jpg'),
('Fairy Tail', 2009, 'Lucy Heartfilia, uma jovem maga, se junta à infame guilda de magos Fairy Tail, embarcando em missões e aventuras com seus novos amigos.', 'https://sm.ign.com/ign_br/tv/f/fairy-tail/fairy-tail_btqy.jpg'),
('Mashle: Magic and Muscles', 2023, 'Em um mundo onde a posição social é determinada pela habilidade mágica, Mash Burnedead, que não tem magia, busca se tornar um Visionário Divino usando sua força física sobre-humana.', 'https://m.media-amazon.com/images/M/MV5BMTUwMjM2ZTctMjI5Yi00MjIwLWJlYzAtNjk5MDQ0NTlmZWJjXkEyXkFqcGc@._V1_.jpg'),
('Cardcaptor Sakura', 1998, 'Uma garota do ensino fundamental liberta acidentalmente um conjunto de cartas mágicas e deve se tornar uma \"Cardcaptor\" para capturá-las antes que causem problemas.', 'https://m.media-amazon.com/images/M/MV5BZTllNzI2NTQtZWJlYi00YTJlLWFlZDYtODZmMGJkN2NmMWY1XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Sailor Moon', 1992, 'Usagi Tsukino, uma estudante chorona, descobre que é a reencarnação de uma princesa guerreira e, com outras Sailor Guardians, luta para proteger a Terra.', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRSlhrqEeR8aAl5SEYl65NqBrzBEEuJRFOHbA&s'),
('Blue Exorcist', 2011, 'Rin Okumura descobre que é filho de Satã e decide se tornar um exorcista para derrotar seu pai e proteger seus amigos.', 'https://a.storyblok.com/f/178900/1024x1536/93b67c8d18/blue-exorcist-shimane-illuminati-saga-kv-high-res.jpg/m/filters:quality(95)format(webp)'),
('Land of the Lustrous', 2017, 'Em um futuro distante, formas de vida imortais baseadas em gemas lutam contra os \"Lunarians\", que as caçam por suas joias. A mais jovem e frágil das gemas, Phos, busca um propósito.', 'https://m.media-amazon.com/images/I/91EKWaQmLKL.jpg'),
('Flying Witch', 2016, 'Uma jovem bruxa se muda para o interior para viver com seus parentes como parte de seu treinamento, explorando as maravilhas da magia na vida cotidiana.', 'https://m.media-amazon.com/images/I/61sLZBG+3OL._UF1000,1000_QL80_.jpg'),
('A Certain Magical Index', 2008, 'Em uma cidade de estudantes com superpoderes, um jovem com a habilidade de negar qualquer poder encontra uma freira que possui 103.000 grimórios mágicos em sua mente.', 'https://upload.wikimedia.org/wikipedia/en/0/07/Index_III.jpg'),
('Magi: Sinbad no Bouken', 2016, 'Uma prequela de Magi que narra as aventuras juvenis de Sinbad, desde sua vida em uma vila pobre até se tornar o lendário conquistador de masmorras e rei.', 'https://a.storyblok.com/f/178900/599x847/7f7881f608/2aeb7b1035bb9868e667a7ad5502809f1443625226_full.jpg/m/599x847'),
('Is It Wrong to Try to Pick Up Girls in a Dungeon?', 2015, 'Em um mundo onde os deuses vivem entre os mortais, um jovem aventureiro busca fama e fortuna em uma masmorra perigosa, servindo à deusa Hestia.', 'https://imusic.b-cdn.net/images/item/original/047/5022366713047.jpg?is-it-wrong-to-try-to-pick-up-2020-is-it-wrong-to-try-to-pick-up-girls-in-a-dungeon-arrow-of-the-orion-dvd&class=scaled&v=1646176317'),
('Slayers', 1995, 'As aventuras cômicas de Lina Inverse, uma feiticeira poderosa e gananciosa, e seus companheiros enquanto viajam, lutam contra monstros e buscam tesouros.', 'https://m.media-amazon.com/images/I/81BMx3wdGuL._AC_UF894,1000_QL80_.jpg'),
('Gurren Lagann', 2007, 'Dois jovens de uma vila subterrânea descobrem um robô e o usam para perfurar a superfície, liderando uma rebelião contra o tirano que governa o mundo.', 'https://m.media-amazon.com/images/M/MV5BMGJlODA2ZmItOTRiZS00NWM5LWJlNTQtYzI5MTNiZjA2MGFjXkEyXkFqcGc@._V1_.jpg'),
('Mobile Suit Gundam', 1979, 'A série original que definiu o gênero \"real robot\", focando na guerra entre a Federação da Terra e o Principado de Zeon e o jovem piloto do protótipo Gundam.', 'https://a.storyblok.com/f/178900/1003x1504/ef815d4bd6/54d0c4b65a4a0b9f5c95e1cc456f6b291611536767_main.jpg/m/filters:quality(95)format(webp)'),
('Macross Frontier', 2008, 'Em uma frota de colonização espacial, um jovem piloto de acrobacias, uma aspirante a cantora e uma estrela pop se envolvem em uma guerra contra uma raça alienígena.', 'https://imusic.b-cdn.net/images/item/original/102/4934569368102.jpg?kawamori-shoji-2023-macross-f-blu-ray-box-limited-mbd&class=scaled&v=1664747598'),
('The Big O', 1999, 'Em uma cidade com amnésia coletiva, um negociador habilidoso usa um mecha gigante chamado Big O para proteger a cidade e descobrir os segredos de seu passado.', 'https://m.media-amazon.com/images/I/51XR9NNJX5L._AC_UF894,1000_QL80_.jpg'),
('Knights of Sidonia', 2014, 'Em uma nave espacial que carrega os últimos vestígios da humanidade, um jovem piloto treina para lutar contra uma raça alienígena que destruiu a Terra.', 'https://m.media-amazon.com/images/M/MV5BYjBlNDEzODctMTUzYi00YWI4LTg5MTQtNDk3Yjc5YTI3NGNlXkEyXkFqcGc@._V1_.jpg'),
('Aldnoah.Zero', 2014, 'Após a descoberta de um portal para Marte, a humanidade se divide em duas facções. A guerra irrompe quando a princesa de Marte é supostamente assassinada na Terra.', 'https://a.storyblok.com/f/178900/750x1061/f3d6b0410f/aldnoah_zero_re_plus_key_art.jpg/m/filters:quality(95)format(webp)'),
('Eureka Seven', 2005, 'O filho de um herói de guerra se junta a um grupo de renegados que pilotam mechas que surfam em ondas de energia, se apaixonando pela misteriosa piloto Eureka.', 'https://upload.wikimedia.org/wikipedia/en/1/10/Eureka_Seven_key_visual.png'),
('86 - Eighty Six', 2021, 'Em uma guerra contra drones autônomos, a República de San Magnolia usa seus próprios drones pilotados remotamente, mas a verdade é que eles são pilotados por humanos da minoria \"86\".', 'https://m.media-amazon.com/images/I/81ZzkjStT5L._UF1000,1000_QL80_.jpg'),
('Mobile Suit Gundam: Iron-Blooded Orphans', 2015, 'Um grupo de crianças-soldado em Marte se rebela contra seus mestres e forma sua própria companhia de segurança, usando um antigo mecha Gundam.', 'https://m.media-amazon.com/images/M/MV5BNzkzZGIyYTYtMWFlMy00MjUxLWFiYTktNTY0NTRmOTJmNzFhXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('SSSS.Gridman', 2018, 'Um estudante com amnésia se funde com um ser interdimensional para se tornar Gridman e lutar contra monstros gigantes que atacam sua cidade.', 'https://m.media-amazon.com/images/M/MV5BNmRmZDY0OWYtNmJiMC00MjgxLWIyMjEtZDY2YzY3MGQyMDJhXkEyXkFqcGc@._V1_.jpg'),
('Patlabor: The Mobile Police', 1989, 'Em um futuro próximo, a polícia de Tóquio usa mechas chamados \"Labors\" para combater crimes. A história segue as desventuras da Segunda Divisão de Veículos Especiais.', 'https://cdn.myanimelist.net/images/about_me/ranking_items/8488844-c4469a96-7b14-4db6-83ac-4f51f32088ba.jpg?t=1660557544'),
('Gunbuster', 1988, 'A filha de um famoso almirante espacial se junta a um programa de treinamento de pilotos de mecha para lutar contra alienígenas que ameaçam a Terra.', 'https://m.media-amazon.com/images/M/MV5BMzAwOTQzMDYtMzdhMy00ZjBiLTgzZTMtZTU2Y2JmNTU1NWU2XkEyXkFqcGc@._V1_.jpg'),
('Full Metal Panic!', 2002, 'Um sargento militar adolescente é encarregado de proteger uma estudante japonesa, levando a situações cômicas e cheias de ação enquanto ele tenta se adaptar à vida escolar.', 'https://m.media-amazon.com/images/M/MV5BYjVlOGUzNzMtZjI3My00NDhhLWIzYWMtNDE3ZmQyNzkyN2NkXkEyXkFqcGc@._V1_.jpg'),
('Darling in the Franxx', 2018, 'Em um futuro pós-apocalíptico, crianças são criadas em pares para pilotar mechas chamados Franxx e defender os últimos redutos da humanidade contra monstros.', 'https://upload.wikimedia.org/wikipedia/pt/e/eb/Darling_in_the_Franxx_Poster.jpg'),
('Getter Robo Armageddon', 1998, 'Anos após uma guerra devastadora, os pilotos do super robô Getter Robo se reúnem para enfrentar uma nova ameaça que pode destruir o mundo.', 'https://m.media-amazon.com/images/I/81krs7Yl8IL._AC_UF894,1000_QL80_DpWeblab_.jpg'),
('Death Note', 2006, 'Um estudante genial encontra um caderno que mata qualquer pessoa cujo nome seja escrito nele. Ele decide usá-lo para criar um novo mundo sem criminosos, sendo perseguido por um detetive genial.', 'https://preview.redd.it/gmgpycnruhk71.jpg?auto=webp&s=f9e1719484f6b32e61e5794ea9502feee89bf67c'),
('Monster', 2004, 'Um neurocirurgião brilhante salva a vida de um garoto em vez da do prefeito, apenas para descobrir anos depois que o garoto se tornou um monstro sociopata. Ele parte em uma jornada para detê-lo.', 'https://m.media-amazon.com/images/M/MV5BYzU2MWQ5NGQtYmNlMC00ZjJkLWJmODItZDM5MDM3YmUyMWJkXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Hyouka', 2012, 'Um estudante do ensino médio que economiza energia se junta ao Clube de Literatura Clássica a pedido de sua irmã e, com os outros membros, resolve mistérios cotidianos.', 'https://m.media-amazon.com/images/M/MV5BMjMyM2M2MTctY2EwNi00Y2RkLWI0M2EtM2M3MDkxYzM3ZDk0XkEyXkFqcGc@._V1_.jpg'),
('Odd Taxi', 2021, 'A história de um taxista morsa misantropo e excêntrico cujas conversas com seus passageiros se entrelaçam em um mistério sobre uma garota desaparecida.', 'https://m.media-amazon.com/images/M/MV5BMGZiNWNlZDYtNjVhOS00MjMxLWFjZDgtYmE1NjI5YjA4MzdkXkEyXkFqcGc@._V1_QL75_UY281_CR8,0,190,281_.jpg'),
('Durarara!!', 2010, 'A vida de vários personagens se cruza em Ikebukuro, Tóquio, envolvendo gangues, um cavaleiro sem cabeça e conspirações misteriosas.', 'https://m.media-amazon.com/images/I/81Rt5wuj8zL.jpg'),
('Paranoia Agent', 2004, 'Um misterioso garoto de patins com um taco de beisebol dourado ataca pessoas em Tóquio. Dois detetives investigam os ataques, que parecem estar conectados.', 'https://m.media-amazon.com/images/M/MV5BZWI0OTc4MGUtYTQ4ZS00MzBmLTg5NjktNzFmMzNkNzgzMzQzXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Gosick', 2011, 'Em uma academia europeia de 1924, um estudante de intercâmbio japonês conhece uma garota misteriosa e genial que resolve crimes complexos sem sair da biblioteca da escola.', 'https://upload.wikimedia.org/wikipedia/pt/3/3f/GOSICK.jpg'),
('Another', 2012, 'Um estudante transferido para uma nova classe se vê no meio de uma maldição que causa a morte de alunos e seus parentes de maneiras misteriosas e horríveis.', 'https://cinema10.com.br/upload/series/series_2580_another_Easy-Resize.com.jpg'),
('Baccano!', 2007, 'Uma história não linear que envolve alquimistas, mafiosos e imortais em Chicago e Nova York dos anos 1930, com eventos interligados e um elixir da imortalidade.', 'https://m.media-amazon.com/images/M/MV5BMDdkYjc2MmQtNTJlZS00ZDM4LWI0ZTYtZGNkZTgzZTg0YTNmXkEyXkFqcGc@._V1_.jpg'),
('UN-GO', 2011, 'Em um Japão do futuro devastado pela guerra, um detetive conhecido como \"O Detetive Derrotado\" e seu assistente sobrenatural resolvem crimes em um mundo onde a verdade é controlada.', 'https://m.media-amazon.com/images/M/MV5BMTE2MmE4OWQtYWZjOS00MmJmLTg0ZjctZDVjZDcxNjU4NjJhXkEyXkFqcGc@._V1_.jpg'),
('Beautiful Bones: Sakurako''s Investigation', 2015, 'Um estudante do ensino médio se torna assistente de uma osteologista genial que tem uma paixão por ossos e resolve casos de assassinato analisando restos mortais.', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRfx5O63BPYIiBEq-M6zjTdmr-Zu2aG-2LApA&s'),
('ID: Invaded', 2020, 'Detetives usam um sistema que lhes permite entrar na mente de assassinos para resolver crimes. Um detetive genial, agora prisioneiro, mergulha nesses \"poços de id\" para encontrar pistas.', 'https://m.media-amazon.com/images/I/61WRTBkToxL._AC_UF894,1000_QL80_.jpg'),
('The Garden of Sinners', 2007, 'Uma série de filmes que segue Shiki Ryougi, uma garota com o poder de ver a \"morte\" das coisas, enquanto ela lida com vários mistérios sobrenaturais.', 'https://media.fstatic.com/XfRQtPFouUC-NL7A3_RHEuq5YpY=/322x478/smart/filters:format(webp)/media/movies/covers/2010/06/a5154e4f25f554f5cc00024a87e2bab0.jpg'),
('Moriarty the Patriot', 2020, 'Uma reimaginação da história de Sherlock Holmes do ponto de vista de seu arqui-inimigo, James Moriarty, que busca derrubar a nobreza corrupta da Inglaterra.', 'https://m.media-amazon.com/images/M/MV5BZGFhMWUwMmEtMGExMy00MDExLWJiYWItZTYyMzA1NjcwZTkzXkEyXkFqcGc@._V1_QL75_UX145_.jpg'),
('Detective Conan', 1996, 'Um detetive adolescente genial é transformado em uma criança por uma organização criminosa e, sob um pseudônimo, resolve crimes enquanto busca uma cura.', 'https://m.media-amazon.com/images/M/MV5BNGNjMjVmODYtMGMzZi00MWUyLTk1ZDQtYzI2ZTk2MmYzYTZiXkEyXkFqcGc@._V1_.jpg'),
('Welcome to the N.H.K.', 2006, 'Um jovem hikikomori (recluso social) acredita em uma grande conspiração por trás de seu estilo de vida, até que uma garota misteriosa aparece e tenta curá-lo.', 'https://m.media-amazon.com/images/M/MV5BMDNjZDMyYTctMmZlMi00NDY2LTg1OGQtOTA2MWU0N2E1M2ZkXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Perfect Blue', 1997, 'Uma ex-ídolo pop que se torna atriz é perseguida por um fã obcecado, fazendo com que sua percepção da realidade comece a se confundir com a ficção.', 'https://m.media-amazon.com/images/I/91mKWo5f7gS._UF894,1000_QL80_.jpg'),
('Paprika', 2006, 'No futuro, uma tecnologia permite que terapeutas entrem nos sonhos de seus pacientes. Quando o dispositivo é roubado, a realidade e os sonhos começam a se fundir.', 'https://m.media-amazon.com/images/M/MV5BZGJkYjkyMDUtM2U3ZC00NDM3LWI0MzItZjU5MmYwYjg2YmIwXkEyXkFqcGc@._V1_.jpg'),
('Kaiji: Ultimate Survivor', 2007, 'Um homem endividado é forçado a participar de jogos de azar de alto risco em um navio, onde sua vida e a de outros estão em jogo.', 'https://m.media-amazon.com/images/I/81UDJSeIAjL._UF1000,1000_QL80_.jpg'),
('The Tatami Galaxy', 2010, 'Um estudante universitário repetidamente revive seus dois primeiros anos de faculdade, tentando alcançar a \"vida universitária cor-de-rosa\" perfeita.', 'https://upload.wikimedia.org/wikipedia/en/thumb/2/2a/Tatami_Galaxy_cover.png/250px-Tatami_Galaxy_cover.png'),
('Death Parade', 2015, 'Quando as pessoas morrem, elas são enviadas para um bar misterioso onde devem participar de jogos mortais para decidir se suas almas serão reencarnadas ou enviadas para o vazio.', 'https://animeserie.com/product_images/j/060/Happy_Sugar_Life__47997_std.jpg'),
('Happy Sugar Life', 2018, 'Uma jovem acredita ter encontrado o verdadeiro amor ao viver com uma garotinha. Ela fará de tudo, incluindo assassinato, para proteger esse sentimento.', 'https://animeserie.com/product_images/j/060/Happy_Sugar_Life__47997_std.jpg'),
('Wonder Egg Priority', 2021, 'Uma garota com heterocromia, após o suicídio de sua melhor amiga, é guiada a um mundo dos sonhos onde compra ovos que chocam em garotas que ela deve proteger.', 'https://upload.wikimedia.org/wikipedia/pt/a/a7/Wonder_Egg_Priority.png'),
('Kakegurui – Compulsive Gambler', 2017, 'Em uma academia de elite onde os alunos são classificados por suas habilidades de jogo, uma estudante transferida joga não por dinheiro, mas pela emoção do risco.', 'https://upload.wikimedia.org/wikipedia/en/7/70/Kakegurui_anime_key_visual.jpg'),
('Classroom of the Elite', 2017, 'Em uma escola de prestígio onde os alunos são julgados por seus méritos, um estudante quieto e discreto da classe mais baixa manipula seus colegas para subir na hierarquia.', 'https://images.justwatch.com/poster/298573879/s718/youkososhi-li-zhi-shang-zhu-yi-nojiao-shi-he.jpg'),
('Revolutionary Girl Utena', 1997, 'Uma jovem que deseja se tornar um príncipe se envolve em uma série de duelos de espada para ganhar a mão da \"Noiva Rosa\", que detém o poder de revolucionar o mundo.', 'https://upload.wikimedia.org/wikipedia/en/9/91/Characters_of_Revolutionary_Girl_Utena.png'),
('Flowers of Evil', 2013, 'Um estudante que rouba as roupas de ginástica de sua paixão é chantageado por uma colega de classe estranha, que o força a um \"contrato\" que explora sua perversidade.', 'https://m.media-amazon.com/images/I/81+4IbMlrmL.jpg'),
('Devilman Crybaby', 2018, 'Quando demônios antigos retornam para retomar o mundo, um jovem sensível se funde com um demônio para se tornar Devilman, lutando contra as forças do mal.', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTqs8xVdn2Ql5fy2405F8iHBCh7TmUL0HZuxQ&s'),
('Shiki', 2010, 'Em uma vila isolada, uma série de mortes misteriosas começa após a chegada de uma nova família em uma mansão no estilo europeu. O médico local suspeita de uma epidemia, mas a verdade é muito mais sombria.', 'https://media.fstatic.com/ezcDxPrpRkeK4ajX0o4Rxd0823o=/322x478/smart/filters:format(webp)/media/movies/covers/2014/08/shiki_t44422_19.jpg'),
('Parasyte -the maxim-', 2014, 'Um estudante do ensino médio tem sua mão direita infectada por um parasita alienígena. Eles formam uma aliança relutante para sobreviver contra outros parasitas que se alimentam de humanos.', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT36LTbJOIUa6oI0MDS31PGCGm8ZL94F8365w&s'),
('Toradora!', 2008, 'Dois estudantes do ensino médio, um com uma aparência intimidadora e uma garota baixinha e feroz, fazem um pacto para ajudar um ao outro a conquistar seus respectivos melhores amigos.', 'https://static.thcdn.com/images/large/original//productimg/1600/1600/13624470-6824980426553662.jpg'),
('My Dress-Up Darling', 2022, 'Um artesão de bonecas hina recluso e uma garota popular e extrovertida se unem por sua paixão secreta por cosplay, desenvolvendo um relacionamento próximo.', 'https://a.storyblok.com/f/178900/1064x1596/537892a7ad/my-dress-up-darling-season-2-key-art.png/m/filters:quality(95)format(webp)'),
('Horimiya', 2021, 'Dois estudantes do ensino médio, uma popular e um quieto, descobrem os lados secretos um do outro e formam um vínculo inesperado que se transforma em romance.', 'https://static.wikia.nocookie.net/dublagem/images/f/fd/Horimiya_%28Capa%29.jpg/revision/latest/scale-to-width-down/1200?cb=20220823222024&path-prefix=pt-br'),
('Maid Sama!', 2010, 'A presidente do conselho estudantil, conhecida por sua rigidez, secretamente trabalha em um maid café. Seu segredo é descoberto pelo garoto mais popular da escola.', 'https://media.themoviedb.org/t/p/w500/igkn0M1bgMeATz59LShvVxZNdVd.jpg'),
('Kamisama Kiss', 2012, 'Uma garota sem-teto se torna a nova divindade de um santuário e deve lidar com seu familiar raposa mal-humorado, com quem ela lentamente desenvolve um romance.', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSFaNAoBY0xYOCDHgV8f904i0J8334FlkAcIw&s'),
('Your Name.', 2016, 'Dois adolescentes, um garoto de Tóquio e uma garota do interior, misteriosamente começam a trocar de corpos, formando uma conexão profunda enquanto buscam um ao outro.', 'https://br.web.img3.acsta.net/pictures/17/10/04/19/01/4966397.jpg'),
('Tomo-chan Is a Girl!', 2023, 'Tomo Aizawa finalmente confessa seu amor para seu amigo de infância, Jun, que não a vê como uma garota. A história segue suas tentativas hilárias de mudar a percepção dele.', 'https://sucodemanga.com.br/wp-content/uploads/2022/07/Tomo-chan-Is-a-Girl-Teaser-Visual-Final.jpg'),
('My Love Story!!', 2015, 'Takeo Gouda, um estudante grande e intimidador com um coração de ouro, finalmente encontra o amor com uma garota doce que não se intimida com sua aparência.', 'https://images.justwatch.com/poster/204490404/s332/temporada-1'),
('Wotakoi: Love Is Hard for Otaku', 2018, 'Dois amigos de infância otakus se reencontram no trabalho e começam a namorar, navegando pelos desafios de um relacionamento romântico entre adultos que amam anime e jogos.', 'https://m.media-amazon.com/images/M/MV5BZDY3MTc2YzMtMWM5Ny00Y2M3LTkxOTEtNzdmYWNmZDczOTE5XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Rascal Does Not Dream of Bunny Girl Senpai', 2018, 'Um estudante do ensino médio encontra uma atriz famosa vestida de coelhinha em uma biblioteca. Ela sofre de uma condição sobrenatural que a torna invisível, e ele decide ajudá-la.', 'https://m.media-amazon.com/images/M/MV5BZWRjNWY5YmItZTFkNS00ZjdiLWJiMWMtODFjYzk1NjU2MWU0XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Snow White with the Red Hair', 2015, 'Uma herbalista com cabelos vermelhos raros foge de seu país para evitar se tornar concubina de um príncipe e encontra um príncipe de um reino vizinho que a ajuda.', 'https://m.media-amazon.com/images/M/MV5BNmM5NzM4ZDgtNGZhOC00YzljLWE0ZTEtZTNjNjg0MjFjYzk3XkEyXkFqcGc@._V1_.jpg'),
('Tsuki ga Kirei', 2017, 'Um romance doce e realista sobre dois estudantes do ensino fundamental que se apaixonam pela primeira vez e se comunicam principalmente através de mensagens de texto.', 'https://m.media-amazon.com/images/M/MV5BNjBhOGVkN2MtMzQwZC00ZTI1LTlmMzgtN2RhZDVkNWE0ZDFiXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Golden Time', 2013, 'Um estudante de direito com amnésia se apaixona por uma garota, mas seu passado e suas memórias perdidas ameaçam seu novo relacionamento.', 'https://m.media-amazon.com/images/I/71ftaeaZVUL._UF894,1000_QL80_.jpg'),
('Tonikawa: Over the Moon for You', 2020, 'Um jovem se apaixona à primeira vista por uma garota que o salva de um acidente. Ela concorda em sair com ele, mas apenas se eles se casarem primeiro.', 'https://br.web.img3.acsta.net/pictures/20/09/28/15/00/1876925.jpg'),
('Given', 2019, 'Um guitarrista perde sua paixão pela música até que ele conhece um colega de classe com uma voz incrível. Juntos, eles formam uma banda e exploram o amor, a perda e a cura.', 'https://br.web.img3.acsta.net/c_310_420/pictures/19/09/25/11/49/3389910.jpg'),
('Natsume''s Book of Friends', 2008, 'Um garoto que pode ver espíritos (youkai) herda um livro de sua avó contendo os nomes de espíritos que ela derrotou. Ele passa seus dias devolvendo os nomes aos seus donos.', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRJ8MseO6ACjpqZ6250_RL10jmQTELVdyQyYw&s'),
('Barakamon', 2014, 'Um calígrafo profissional é exilado em uma ilha rural após socar um crítico. Lá, ele aprende sobre a vida e encontra nova inspiração através de suas interações com os moradores.', 'https://m.media-amazon.com/images/I/71EUhjJJOLL._UF1000,1000_QL80_.jpg'),
('Mushishi', 2005, 'Ginko, um \"Mushi Master\", viaja para pesquisar e ajudar pessoas afligidas por Mushi, criaturas etéreas que podem causar fenômenos sobrenaturais.', 'https://m.media-amazon.com/images/M/MV5BMmUxZjA2ZTgtYmIzNy00YWE0LTliMjktZGY5NTQ5ZDEzYmM1XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Non Non Biyori', 2013, 'A vida tranquila e relaxante de quatro garotas de diferentes idades que vivem em uma pequena vila rural, onde a escola tem apenas cinco alunos.', 'https://m.media-amazon.com/images/I/81sClTCx9ML._UF894,1000_QL80_.jpg'),
('K-On!', 2009, 'Quatro garotas do ensino médio se juntam ao clube de música leve para salvar o clube de ser dissolvido, passando seus dias praticando, comendo bolo e se divertindo.', 'https://m.media-amazon.com/images/M/MV5BNjRjYTc2ZDctNTlhNS00NTdiLTllNjItMWNjMzlmNWQ2MGEwXkEyXkFqcGc@._V1_.jpg'),
('Yuru Camp (Laid-Back Camp)', 2018, 'Um grupo de garotas do ensino médio explora as alegrias do acampamento ao ar livre durante o inverno, visitando vários locais cênicos no Japão.', 'https://m.media-amazon.com/images/M/MV5BOTk2NzZjNzctMDEyZC00MDdlLTk3M2EtNGNmYmJlZWU4ZGQ2XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Aria the Animation', 2005, 'Em uma cidade futurista em Marte que replica Veneza, uma jovem treina para se tornar uma gondoleira, descobrindo as belezas e maravilhas da cidade.', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTHyRjXTIPY_I9FfZxBQgZVE_h0l3xgGnW6CQ&s'),
('Usagi Drop', 2011, 'Um solteiro de 30 anos decide criar a filha ilegítima de seu avô falecido, aprendendo os desafios e as alegrias da paternidade.', 'https://imgsrv.crunchyroll.com/cdn-cgi/image/fit=contain,format=auto,quality=85,width=480,height=720/catalog/crunchyroll/5cbf0fdfe8d3c0aafca4732d2c1f3df7.jpe'),
('Tanaka-kun is Always Listless', 2016, 'A vida cotidiana de Tanaka, um estudante do ensino médio que é mestre da preguiça e só quer passar seus dias dormindo, e seu amigo que o ajuda a conseguir isso.', 'https://m.media-amazon.com/images/M/MV5BMDdhNzlmYTAtZTQzNi00MWY0LTk5YTItYzUzMGVmODQ5N2UxXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Silver Spoon', 2013, 'Um estudante de uma escola preparatória da cidade se matricula em uma escola agrícola no interior para escapar das pressões acadêmicas, aprendendo sobre agricultura e a vida.', 'https://m.media-amazon.com/images/M/MV5BMzUzMWJiMWQtZTI0Yy00ZmRmLThmOWEtNGVjYTVkMjAzMjJlXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('The Helpful Fox Senko-san', 2019, 'Uma deusa raposa de 800 anos é enviada para cuidar de um assalariado sobrecarregado, ajudando-o a relaxar e a se livrar do estresse.', 'https://m.media-amazon.com/images/M/MV5BNGQwZmI4YjAtYWEzZC00ZmQxLTliNTktMzFmNDJiM2Q3ZDU5XkEyXkFqcGc@._V1_.jpg'),
('Do It Yourself!!', 2022, 'A história segue um grupo de garotas do ensino médio que redescobrem a alegria de fazer as coisas com as próprias mãos em um clube de DIY (faça você mesmo).', 'https://m.media-amazon.com/images/M/MV5BNWE2OTM5ODItOWM2Ni00NjhjLWJhZWMtZjkxMjE3MTU2OTg0XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Super Cub', 2021, 'Uma garota solitária do ensino médio sem pais, hobbies ou amigos encontra uma nova alegria e um novo mundo se abre para ela depois que ela compra uma motocicleta Honda Super Cub usada.', 'https://a.storyblok.com/f/178900/637x900/1a2b643817/6925f2778431c8ee6de7c04c53b7010e1610687033_main.png/m/filters:quality(95)format(webp)'),
('Miss Kobayashi''s Dragon Maid', 2017, 'Uma programadora de escritório salva a vida de uma dragoa, que se apaixona por ela e se torna sua empregada doméstica, atraindo outros seres míticos para seu apartamento.', 'https://a.storyblok.com/f/178900/1196x1619/e777a17c46/cfa8ac0ef09c4c73729a98ce825c9d7e1597074256_main.jpg/m/filters:quality(95)format(webp)'),
('Bakemonogatari', 2009, 'Um estudante do ensino médio que sobreviveu a um ataque de vampiro se envolve com outras garotas afligidas por diferentes \"esquisitices\" sobrenaturais.', 'https://m.media-amazon.com/images/I/61JGwebb9ZL._UF894,1000_QL80_.jpg'),
('Noragami', 2014, 'Yato, um deus menor sem santuário, faz biscates para ganhar dinheiro e ser reconhecido. Sua vida muda quando ele conhece uma garota do ensino médio que se envolve em seus assuntos.', 'https://images.justwatch.com/poster/209587398/s718/noragami.jpg'),
('Bleach', 2004, 'Um adolescente com a habilidade de ver fantasmas ganha os poderes de um Shinigami (Ceifador de Almas) e assume o dever de proteger os humanos de espíritos malignos e guiar almas para o além.', 'https://i.redd.it/aphu74h5y3g91.jpg'),
('Toilet-Bound Hanako-kun', 2020, 'Uma estudante que anseia por romance invoca \"Hanako-san do Banheiro\", uma famosa lenda urbana, mas descobre que Hanako é um garoto e se torna sua assistente.', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQbYIH-KbF4IQKds0SjJMhVHRqDG_fFzJ_-qw&s'),
('The Case Study of Vanitas', 2021, 'Em uma Paris do século XIX, um jovem vampiro se junta a um humano que herdou um livro mágico capaz de curar vampiros de sua maldição.', 'https://m.media-amazon.com/images/M/MV5BMzYwMmI1YWMtOTc0My00OGI3LWI3ZDctZmMwYjczYzcxMjI4XkEyXkFqcGc@._V1_.jpg'),
('Bungo Stray Dogs', 2016, 'Indivíduos com poderes sobrenaturais, baseados em figuras literárias famosas, formam a Agência de Detetives Armados para resolver crimes que a polícia não consegue.', 'https://cdn.fstatic.com/media/movies/covers/2019/01/1animess_M3b7h2m.jpg'),
('Hell''s Paradise: Jigokuraku', 2023, 'Um ninja lendário no corredor da morte recebe a chance de perdão se conseguir encontrar o elixir da imortalidade em uma ilha misteriosa e perigosa.', 'https://fr.web.img2.acsta.net/c_300_300/pictures/23/03/22/17/10/3028192.jpg'),
('Kekkaishi', 2006, 'Dois herdeiros de clãs rivais de \"kekkaishi\" (mestres de barreiras) trabalham juntos para proteger sua escola, construída em uma terra sagrada, de demônios (ayakashi).', 'https://play-lh.googleusercontent.com/mGxgOe3EVtAL3ji2nzNkKoC_phOYFa4r_uxmeBFr38B7CHERxap_QTelm1_r5s_dsPA'),
('xxxHOLiC', 2006, 'Um estudante que é atormentado por sua habilidade de ver espíritos começa a trabalhar para uma bruxa dimensional para ter seu desejo de não vê-los mais concedido.', 'https://m.media-amazon.com/images/M/MV5BNGE5NDNmNTMtM2UwNC00YmNhLWI5ZGEtOWVjNTI1YTUzN2IxXkEyXkFqcGc@._V1_QL75_UY281_CR2,0,190,281_.jpg'),
('In/Spectre', 2020, 'Uma garota que se tornou a \"Deusa da Sabedoria\" para o mundo sobrenatural e um jovem com poderes de imortalidade e precognição resolvem mistérios envolvendo youkais.', 'https://m.media-amazon.com/images/M/MV5BNjU5NjkzMWEtMDNmYS00Y2M4LWFlZDgtM2E4Zjk0ZWVmYmI3XkEyXkFqcGc@._V1_.jpg'),
('Mononoke', 2007, 'Um vendedor de remédios misterioso viaja pelo Japão feudal, enfrentando espíritos malignos chamados \"mononoke\" e exorcizando-os ao descobrir sua Forma, Verdade e Razão.', 'https://media.filmelier.com/images/tit/27352/poster/mononoke-the-movie-the-phantom-in-the-rain69831.webp'),
('GeGeGe no Kitaro', 2018, 'Kitaro, um garoto youkai, e seus amigos lutam para manter a paz entre humanos e youkais em um mundo moderno que esqueceu o sobrenatural.', 'https://m.media-amazon.com/images/M/MV5BMTg0M2M1MDQtODQ5NC00MGM4LWEyNDYtM2I0ZTMzNDcwODJlXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Tokyo Ghoul', 2014, 'Um estudante universitário é transformado em um meio-ghoul após um encontro quase fatal e deve aprender a sobreviver em um mundo onde ghouls se alimentam de humanos.', 'https://m.media-amazon.com/images/M/MV5BZWI2NzZhMTItOTM3OS00NjcyLThmN2EtZGZjMjlhYWMwODMzXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('D.Gray-man', 2006, 'Em uma versão alternativa do século XIX, um jovem exorcista com um braço anti-Akuma e um olho amaldiçoado se junta à Ordem Negra para lutar contra o Conde do Milênio e seu exército de demônios.', 'https://m.media-amazon.com/images/M/MV5BNzNlYzc0MDUtMDFhNi00ZDI3LThjMTctYjA4MjY0ZDg3MjUyXkEyXkFqcGc@._V1_.jpg'),
('Summertime Rendering', 2022, 'Após a morte de sua amiga de infância, Shinpei Ajiro retorna à sua ilha natal e descobre uma conspiração sombria envolvendo \"Sombras\" que duplicam pessoas.', 'https://a.storyblok.com/f/178900/725x1000/8bd14bd6dc/9405ef3812a8d682e31b3d06b8990cf11644195435_main.jpg/m/filters:quality(95)format(webp)'),
('Higurashi: When They Cry', 2006, 'Em uma vila rural tranquila, um grupo de amigos desfruta de seu tempo juntos, mas uma maldição antiga leva a paranoia, violência e mortes misteriosas a cada ano durante o festival local.', 'https://upload.wikimedia.org/wikipedia/en/1/1f/Higurashi_Hou_cover.png'),
('Future Diary', 2011, 'Doze pessoas recebem diários que preveem o futuro e são forçadas a participar de um jogo de sobrevivência onde o último em pé se tornará o novo deus do tempo e espaço.', 'https://m.media-amazon.com/images/M/MV5BYjZjNmIxYTgtYzdkZC00Yzc5LWI5YTAtZWQ5YWNjZmQ1NTUyXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Zankyou no Terror (Terror in Resonance)', 2014, 'Dois adolescentes misteriosos, que se autodenominam \"Sphinx\", realizam ataques terroristas em Tóquio, deixando pistas enigmáticas para a polícia.', 'https://m.media-amazon.com/images/M/MV5BOGZmNjNiY2MtNWE0YS00Y2MxLWJlNzEtMjIyMzZjODE5NGMwXkEyXkFqcGc@._V1_QL75_UY281_CR5,0,190,281_.jpg'),
('Talentless Nana', 2020, 'Em uma ilha, estudantes com superpoderes treinam para lutar contra os \"Inimigos da Humanidade\". Uma nova aluna transferida parece amigável, mas tem uma missão secreta e mortal.', 'https://m.media-amazon.com/images/M/MV5BYmUwNTUyYWMtY2U2Yy00Y2Y1LWFmZmMtZTNlNmU3NmY3N2FhXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Gleipnir', 2020, 'Um estudante do ensino médio ganha a habilidade de se transformar em um monstro de pelúcia com um zíper nas costas. Ele se envolve em uma batalha por moedas misteriosas com uma colega sádica.', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTXQ-KqO1VsSsAwoQGAUC1yHG8Ur09C4dHcMA&s'),
('Dorohedoro', 2020, 'Em uma cidade sombria e caótica, um homem com cabeça de réptil e amnésia caça feiticeiros para encontrar aquele que o amaldiçoou, com a ajuda de sua amiga Nikaido.', 'https://preview.redd.it/season-2-de-dorohedoro-anunciada-ansiosos-v0-dfjuh4ym1fbc1.jpeg?auto=webp&s=ef9bbe0d2e093f4961c90b1f86968bdb602ff8ef'),
('Link Click', 2021, 'Dois amigos administram um estúdio de fotografia onde usam seus poderes para entrar nas fotos dos clientes e reviver momentos do passado, mas alterar o passado pode ter consequências graves.', 'https://m.media-amazon.com/images/I/61ha384HtCL._UF1000,1000_QL80_.jpg'),
('Tomodachi Game', 2022, 'Um grupo de amigos é forçado a participar de um jogo psicológico para pagar uma dívida enorme, testando a força de sua amizade com mentiras e traições.', 'https://m.media-amazon.com/images/M/MV5BYjM1Yjk4NjYtM2I2Yi00MDFmLThmNGMtNTI2ZWYzOTM3MWFjXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Deadman Wonderland', 2011, 'Um estudante é falsamente condenado pelo assassinato de toda a sua classe e enviado para uma prisão privatizada onde os prisioneiros participam de jogos mortais para o entretenimento do público.', 'https://m.media-amazon.com/images/M/MV5BMGExMGVjOWQtODI2Ny00NzE2LWJkYWItOTk1Zjc4MTQ3NWM2XkEyXkFqcGc@._V1_QL75_UX190_CR0,1,190,281_.jpg'),
('Darwin''s Game', 2020, 'Um estudante do ensino médio aceita um convite online para um jogo de celular, apenas para descobrir que é um jogo de sobrevivência na vida real com superpoderes.', 'https://imgsrv.crunchyroll.com/cdn-cgi/image/fit=contain,format=auto,quality=85,width=480,height=720/catalog/crunchyroll/d2280990a50b7a201392d2a0cd7b2cff.jpe'),
('Heavenly Delusion', 2023, 'Em um mundo pós-apocalíptico, um garoto e uma garota viajam pelo Japão em busca de um lugar chamado \"Paraíso\", enquanto crianças vivem em uma instalação isolada do mundo exterior.', 'https://gkpb.com.br/wp-content/uploads/2023/02/heavenly-delusion-teaser1-1024x1536.jpg'),
('Magical Girl Site', 2018, 'Uma garota que sofre bullying severo recebe poderes de garota mágica de um site misterioso, mas descobre que usar seus poderes encurta sua vida e a envolve em um conflito sombrio.', 'https://images.justwatch.com/poster/242786823/s718/magical-girl-site.jpg'),
('Corpse Party: Tortured Souls', 2013, 'Um grupo de estudantes realiza um feitiço de amizade e é transportado para uma escola primária amaldiçoada, onde devem sobreviver aos fantasmas vingativos de crianças assassinadas.', 'https://m.media-amazon.com/images/M/MV5BYTMyZjM4MzQtNmQ1MC00MGFjLWJlMzQtZTZhNGI0NWVhYTZkXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Yamishibai: Japanese Ghost Stories', 2013, 'Uma série de curtas que contam lendas urbanas e histórias de fantasmas japonesas em um estilo de animação que imita o teatro de papel kamishibai.', 'https://m.media-amazon.com/images/M/MV5BNGJlZDJmZjYtOGNiZS00MzBkLTk2OWMtNDZiMDMyNjg0NzEzXkEyXkFqcGc@._V1_.jpg'),
('Junji Ito Collection', 2018, 'Uma antologia que adapta várias histórias curtas de terror do famoso mangaká Junji Ito, explorando o horror corporal, o sobrenatural e o bizarro.', 'https://m.media-amazon.com/images/S/pv-target-images/3e1d50a78cfd195d1d79ba19bc9396f152fc1b6de2d343d3e7008b43525346ed.jpg'),
('Gantz', 2004, 'Pessoas que morreram são ressuscitadas e forçadas a participar de um jogo mortal onde devem caçar e matar alienígenas com armas e trajes futuristas.', 'https://m.media-amazon.com/images/I/81t45iywxXL._UF894,1000_QL80_.jpg'),
('Blood-C', 2011, 'Uma garota aparentemente normal vive uma vida tranquila durante o dia e luta contra monstros sedentos de sangue à noite, mas sua realidade começa a se desvendar.', 'https://m.media-amazon.com/images/M/MV5BZjRmZjQxZjctOTFhYS00ZjQ0LWJkOTQtZjBiMjFkMDlkMjk0XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
('Elfen Lied', 2004, 'Uma jovem \"Diclonius\", uma mutante com chifres e poderes telecinéticos, escapa de um laboratório e é encontrada por dois primos, desencadeando uma série de eventos violentos.', 'https://m.media-amazon.com/images/I/81dpKMg4+OL._UF894,1000_QL80_.jpg'),
('Kagewani', 2015, 'Um cientista investiga o aparecimento súbito de monstros misteriosos e violentos, conhecidos como \"Kagewani\" (crocodilos-sombra), que atacam humanos.', 'https://upload.wikimedia.org/wikipedia/en/0/04/Kagewani_DVD_cover.jpg');

-- Gêneros para Animes de Ação/Aventura Principais
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'One-Punch Man'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Ação');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'One-Punch Man'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Comédia');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'One-Punch Man'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Ficção Científica');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Naruto Shippuden'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Ação');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Naruto Shippuden'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Aventura');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Naruto Shippuden'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Fantasia');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Hunter x Hunter'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Ação');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Hunter x Hunter'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Aventura');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Hunter x Hunter'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Fantasia');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Mob Psycho 100'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Ação');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Mob Psycho 100'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Comédia');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Mob Psycho 100'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Sobrenatural');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Vinland Saga'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Ação');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Vinland Saga'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Aventura');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Vinland Saga'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Drama');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Black Lagoon'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Ação');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Black Lagoon'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Drama');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Akame ga Kill!'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Ação');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Akame ga Kill!'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Fantasia');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Akame ga Kill!'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Drama');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Chainsaw Man'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Ação');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Chainsaw Man'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Fantasia');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Chainsaw Man'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Sobrenatural');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Cyberpunk: Edgerunners'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Ficção Científica');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Cyberpunk: Edgerunners'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Ação');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Cyberpunk: Edgerunners'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Drama');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Fate/Zero'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Ação');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Fate/Zero'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Fantasia');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Fate/Zero'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Sobrenatural');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'One Piece'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Aventura');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'One Piece'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Ação');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'One Piece'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Fantasia');

-- Gêneros para Animes de Aventura/Fantasia
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Made in Abyss'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Aventura');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Made in Abyss'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Fantasia');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Made in Abyss'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Mistério');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'The Promised Neverland'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Suspense');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'The Promised Neverland'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Mistério');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'The Promised Neverland'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Psicológico');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Dr. Stone'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Aventura');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Dr. Stone'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Ficção Científica');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Dr. Stone'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Comédia');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Mushoku Tensei: Jobless Reincarnation'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Fantasia');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Mushoku Tensei: Jobless Reincarnation'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Aventura');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Mushoku Tensei: Jobless Reincarnation'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Drama');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Golden Kamuy'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Aventura');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Golden Kamuy'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Ação');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Golden Kamuy'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Drama');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'To Your Eternity'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Fantasia');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'To Your Eternity'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Drama');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'To Your Eternity'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Sobrenatural');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Ranking of Kings'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Fantasia');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Ranking of Kings'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Aventura');

-- Gêneros para Comédias
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'KonoSuba: God''s Blessing on This Wonderful World!'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Comédia');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'KonoSuba: God''s Blessing on This Wonderful World!'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Fantasia');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'KonoSuba: God''s Blessing on This Wonderful World!'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Aventura');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Gintama'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Comédia');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Gintama'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Ação');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Gintama'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Ficção Científica');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Grand Blue Dreaming'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Comédia');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Grand Blue Dreaming'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Slice of Life');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Kaguya-sama: Love Is War'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Comédia');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Kaguya-sama: Love Is War'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Romance');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Kaguya-sama: Love Is War'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Psicológico');

-- Gêneros para Dramas / Slice of Life
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Your Lie in April'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Drama');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Your Lie in April'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Romance');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Your Lie in April'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Slice of Life');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Clannad: After Story'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Drama');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Clannad: After Story'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Romance');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Clannad: After Story'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Slice of Life');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Violet Evergarden'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Drama');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Violet Evergarden'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Fantasia');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Violet Evergarden'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Slice of Life');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'A Silent Voice'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Drama');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'A Silent Voice'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Slice of Life');

-- Gêneros para Suspense / Mistério / Psicológico
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Steins;Gate'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Ficção Científica');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Steins;Gate'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Suspense');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Steins;Gate'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Psicológico');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Erased'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Mistério');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Erased'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Suspense');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Erased'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Sobrenatural');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Death Note'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Suspense');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Death Note'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Psicológico');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Death Note'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Sobrenatural');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Monster'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Drama');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Monster'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Mistério');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Monster'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Psicológico');

-- Gêneros para Esportes
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Haikyuu!!'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Esporte');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Haikyuu!!'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Comédia');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Haikyuu!!'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Drama');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Kuroko''s Basketball'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Esporte');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Kuroko''s Basketball'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Ação');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Slam Dunk'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Esporte');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Slam Dunk'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Comédia');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Slam Dunk'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Drama');

-- Gêneros para Mecha / Ficção Científica
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Gurren Lagann'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Ação');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Gurren Lagann'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Aventura');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Gurren Lagann'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Mecha');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Code Geass: Lelouch of the Rebellion'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Ação');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Code Geass: Lelouch of the Rebellion'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Mecha');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Code Geass: Lelouch of the Rebellion'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Drama');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Neon Genesis Evangelion'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Psicológico');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Neon Genesis Evangelion'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Mecha');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Neon Genesis Evangelion'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Ficção Científica');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = '86 - Eighty Six'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Ação');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = '86 - Eighty Six'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Mecha');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = '86 - Eighty Six'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Drama');

-- Gêneros para Romance
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Toradora!'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Romance');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Toradora!'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Comédia');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Toradora!'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Slice of Life');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'My Dress-Up Darling'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Romance');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'My Dress-Up Darling'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Comédia');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'My Dress-Up Darling'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Slice of Life');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Horimiya'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Romance');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Horimiya'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Slice of Life');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Horimiya'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Comédia');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Your Name.'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Romance');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Your Name.'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Drama');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Your Name.'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Sobrenatural');

-- Gêneros para Terror / Sobrenatural
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Parasyte -the maxim-'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Terror');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Parasyte -the maxim-'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Ação');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Parasyte -the maxim-'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Psicológico');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Devilman Crybaby'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Terror');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Devilman Crybaby'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Ação');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Devilman Crybaby'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Sobrenatural');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Shiki'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Terror');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Shiki'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Suspense');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Shiki'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Sobrenatural');

INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Tokyo Ghoul'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Ação');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Tokyo Ghoul'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Terror');
INSERT IGNORE INTO AnimeGeneros (id_anime, id_genero) SELECT (SELECT id_anime FROM Animes WHERE nome = 'Tokyo Ghoul'), (SELECT id_genero FROM Generos WHERE nome_genero = 'Psicológico');
