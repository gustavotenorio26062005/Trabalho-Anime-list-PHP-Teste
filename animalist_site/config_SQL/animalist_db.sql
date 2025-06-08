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
    senha VARCHAR(255) NOT NULL,
    foto_perfil_url VARCHAR(2048),
    fundo_perfil_url VARCHAR(2048),
    descricao TEXT,
    id_tipo_usuario INT NOT NULL DEFAULT 2,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_ultima_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_tipo_usuario) REFERENCES TipoUsuario(id_tipo_usuario) ON DELETE RESTRICT,
    CONSTRAINT chk_idade_minima CHECK (TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) >= 13)
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
    status_anime ENUM('Favorito', 'Assistindo', 'Completado', 'Planejando Assistir') NOT NULL,
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
    data_alteracao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES Usuarios(id_usuario) ON DELETE CASCADE
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
('Gabriel Dias', 'gabriel.dias@example.com', '1990-01-01', 'senha123', 1, 'https://via.placeholder.com/150/0000FF/FFFFFF?text=G.D.', 'https://via.placeholder.com/800x200/FF0000/FFFFFF?text=Fundo+G.D.', 'Admin principal do Animalist. Gosto de tudo que é otimizado.'),
('Gustavo Barros', 'gustavo.barros@example.com', '1991-03-15', 'senha123', 1, 'https://via.placeholder.com/150/00FF00/FFFFFF?text=G.B.', 'https://via.placeholder.com/800x200/00FF00/FFFFFF?text=Fundo+G.B.', 'Admin e especialista em usabilidade. Paixão por animes de fantasia.'),
('Luiz Gonçalves', 'luiz.goncalves@example.com', '1992-07-22', 'senha123', 2, 'https://via.placeholder.com/150/FFFF00/000000?text=L.G.', 'https://via.placeholder.com/800x200/FFFF00/000000?text=Fundo+L.G.', 'Fã de animes de ação e aventura, sempre em busca da próxima grande batalha.'),
('Maycon Cabral', 'maycon.cabral@example.com', '1993-11-05', 'senha123', 2, 'https://via.placeholder.com/150/FF00FF/FFFFFF?text=M.C.', 'https://via.placeholder.com/800x200/FF00FF/FFFFFF?text=Fundo+M.C.', 'Adora animes de fantasia e slice of life, para relaxar e se inspirar.'),
('Renan Rodrigues', 'renan.rodrigues@example.com', '1994-04-30', 'senha123', 2, 'https://via.placeholder.com/150/00FFFF/000000?text=R.R.', 'https://via.placeholder.com/800x200/00FFFF/000000?text=Fundo+R.R.', 'Crítico de animes e mangás, sempre com uma opinião sincera e bem fundamentada.'),
('Ana Santos', 'ana.santos@example.com', '1988-08-10', 'senha123', 2, NULL, NULL, 'Gosta de animes mais antigos e cult, buscando sempre novas pérolas.'),
('Pedro Lima', 'pedro.lima@example.com', '1995-02-28', 'senha123', 2, NULL, NULL, 'Em busca de novos animes para assistir, aberto a todos os gêneros.'),
('Usuario Menor', 'menor@example.com', '2015-05-10', 'senha123', 2, NULL, NULL, 'Este usuário é menor de idade (deve falhar o cadastro devido à restrição CHK_IDADE_MINIMA).');

INSERT INTO Generos (nome_genero) VALUES
('Ação'), ('Aventura'), ('Comédia'), ('Drama'), ('Fantasia'), ('Ficção Científica'),
('Romance'), ('Slice of Life'), ('Suspense'), ('Mecha'), ('Esporte'), ('Terror'), ('Mistério');

INSERT INTO Generos (nome_genero) VALUES ('Psicológico');
INSERT INTO Generos (nome_genero) VALUES ('Sobrenatural');
INSERT INTO Generos (nome_genero) VALUES ('Magia');

INSERT INTO Animes (nome, ano_lancamento, sinopse, capa_url) VALUES
('Attack on Titan', 2013, 'A humanidade vive dentro de cidades cercadas por enormes muralhas para se proteger de gigantes humanóides devoradores de homens chamados Titãs. Uma história de sobrevivência e mistério.', 'https://i.pinimg.com/736x/e4/d3/85/e4d38524090e4b1f9d2fb31e894c6c97.jpg'),
('Jujutsu Kaisen', 2020, 'Yuji Itadori, um estudante do ensino médio, se envolve no mundo do Jujutsu ao tentar salvar um amigo de um monstro, e acaba engolindo um objeto amaldiçoado, tornando-se um receptáculo de uma maldição poderosa.', 'https://www.ubuy.com.br/productimg/?image=aHR0cHM6Ly9tLm1lZGlhLWFtYXpvbi5jb20vaW1hZ2VzL0kvODFzK2p4RTVLRUwuX0FDX1NMMTUwMF_uanBn.jpg'),
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

INSERT INTO ListaPessoalAnimes (id_usuario, id_anime, status_anime)
SELECT u.id_usuario, a.id_anime, 'Assistindo'
FROM Usuarios u, Animes a
WHERE u.email = 'luiz.goncalves@example.com' AND a.nome = 'Jujutsu Kaisen';

INSERT INTO ListaPessoalAnimes (id_usuario, id_anime, status_anime)
SELECT u.id_usuario, a.id_anime, 'Completado'
FROM Usuarios u, Animes a
WHERE u.email = 'luiz.goncalves@example.com' AND a.nome = 'Attack on Titan';

INSERT INTO ListaPessoalAnimes (id_usuario, id_anime, status_anime)
SELECT u.id_usuario, a.id_anime, 'Favorito'
FROM Usuarios u, Animes a
WHERE u.email = 'maycon.cabral@example.com' AND a.nome = 'Spy x Family';

INSERT INTO ListaPessoalAnimes (id_usuario, id_anime, status_anime)
SELECT u.id_usuario, a.id_anime, 'Planejando Assistir'
FROM Usuarios u, Animes a
WHERE u.email = 'renan.rodrigues@example.com' AND a.nome = 'Fullmetal Alchemist: Brotherhood';

INSERT INTO ListaPessoalAnimes (id_usuario, id_anime, status_anime)
SELECT u.id_usuario, a.id_anime, 'Assistindo'
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
    SELECT COUNT(*) INTO v_total_recomendacoes FROM Avaliacoes WHERE id_anime = p_id_anime AND nota = 'Recomendo';
    RETURN v_total_recomendacoes;
END //

DELIMITER ;
