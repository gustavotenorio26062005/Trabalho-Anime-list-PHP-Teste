drop database if exists animalist;
create database animalist;
use animalist;

-- Tabela: Usuarios
CREATE TABLE IF NOT EXISTS Usuarios (
    id_usuario INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    celular VARCHAR(22),
    senha VARCHAR(255) NOT NULL,
    foto_perfil_url VARCHAR(2048),
    fundo_perfil_url VARCHAR(2048),
    descricao TEXT,
    tipo_usuario ENUM('usuario_comum', 'administrador') DEFAULT 'usuario_comum',
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_ultima_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Índice: idx_usuario_email
CREATE INDEX idx_usuario_email ON Usuarios(email);

---

-- Tabela: Animes
CREATE TABLE IF NOT EXISTS Animes (
    id_anime INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    ano_lancamento INT,
    sinopse TEXT,
    capa_url VARCHAR(2048),
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_ultima_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_ano_lancamento CHECK (ano_lancamento >= 1900 AND ano_lancamento <= 2030)
);

-- Índice: idx_anime_nome
CREATE FULLTEXT INDEX idx_anime_nome ON Animes(nome);

---

-- Tabela: Generos
CREATE TABLE IF NOT EXISTS Generos (
    id_genero INT PRIMARY KEY AUTO_INCREMENT,
    nome_genero VARCHAR(100) UNIQUE NOT NULL
);

---

-- Tabela: AnimeGeneros
CREATE TABLE IF NOT EXISTS AnimeGeneros (
    id_anime INT,
    id_genero INT,
    PRIMARY KEY (id_anime, id_genero),
    FOREIGN KEY (id_anime) REFERENCES Animes(id_anime) ON DELETE CASCADE,
    FOREIGN KEY (id_genero) REFERENCES Generos(id_genero) ON DELETE CASCADE
);

---

-- Tabela: ListaPessoalAnimes
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

-- Índice: idx_lista_usuario
CREATE INDEX idx_lista_usuario ON ListaPessoalAnimes(id_usuario);

---

-- Tabela: Avaliacoes
CREATE TABLE IF NOT EXISTS Avaliacoes (
    id_avaliacao INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    id_anime INT NOT NULL,
    nota boolean NOT NULL,
    comentario TEXT,
    data_avaliacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_ultima_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES Usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_anime) REFERENCES Animes(id_anime) ON DELETE CASCADE,
    UNIQUE (id_usuario, id_anime)
);

-- Índices: idx_avaliacao_anime, idx_avaliacao_usuario
CREATE INDEX idx_avaliacao_anime ON Avaliacoes(id_anime);
CREATE INDEX idx_avaliacao_usuario ON Avaliacoes(id_usuario);

---

### Triggers

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
DELIMITER ;

---

DELIMITER //
CREATE TRIGGER trg_before_insert_animes
BEFORE INSERT ON Animes
FOR EACH ROW
BEGIN
    IF EXISTS (SELECT 1 FROM Animes WHERE nome = NEW.nome) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro: Já existe um anime com este nome.';
    END IF;
END;
//
DELIMITER ;

-- Tabela para log de auditoria: Avaliacoes_Log
CREATE TABLE IF NOT EXISTS Avaliacoes_Log (
    id_log INT PRIMARY KEY AUTO_INCREMENT,
    id_avaliacao_excluida INT,
    id_usuario INT,
    id_anime INT,
    nota boolean,
    comentario TEXT,
    data_avaliacao_original TIMESTAMP,
    data_exclusao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Trigger: trg_after_delete_avaliacoes
DELIMITER //
CREATE TRIGGER trg_after_delete_avaliacoes
AFTER DELETE ON Avaliacoes
FOR EACH ROW
BEGIN
    INSERT INTO Avaliacoes_Log (id_avaliacao_excluida, id_usuario, id_anime, nota, comentario, data_avaliacao_original)
    VALUES (OLD.id_avaliacao, OLD.id_usuario, OLD.id_anime, OLD.nota, OLD.comentario, OLD.data_avaliacao);
END;
//
DELIMITER ;

### Dados de Exemplo (INSERTs)

-- Inserindo Usuários
INSERT INTO Usuarios (nome, email, celular, senha, tipo_usuario, foto_perfil_url, fundo_perfil_url, descricao) VALUES
('Gabriel Dias', 'gabriel.dias@example.com', '11987654321', 'senha123', 'administrador', 'https://via.placeholder.com/150/0000FF/FFFFFF?text=G.D.', 'https://via.placeholder.com/800x200/FF0000/FFFFFF?text=Fundo+G.D.', 'Admin principal do Animalist. Gosto de tudo que é otimizado.'),
('Gustavo Barros', 'gustavo.barros@example.com', '11998765432', 'senha123', 'administrador', 'https://via.placeholder.com/150/00FF00/FFFFFF?text=G.B.', 'https://via.placeholder.com/800x200/00FF00/FFFFFF?text=Fundo+G.B.', 'Admin e especialista em usabilidade. Paixão por animes de fantasia.'),
('Luiz Gonçalves', 'luiz.goncalves@example.com', '11976543210', 'senha123', 'usuario_comum', 'https://via.placeholder.com/150/FFFF00/000000?text=L.G.', 'https://via.placeholder.com/800x200/FFFF00/000000?text=Fundo+L.G.', 'Fã de animes de ação e aventura, sempre em busca da próxima grande batalha.'),
('Maycon Cabral', 'maycon.cabral@example.com', '11965432109', 'senha123', 'usuario_comum', 'https://via.placeholder.com/150/FF00FF/FFFFFF?text=M.C.', 'https://via.placeholder.com/800x200/FF00FF/FFFFFF?text=Fundo+M.C.', 'Adora animes de fantasia e slice of life, para relaxar e se inspirar.'),
('Renan Rodrigues', 'renan.rodrigues@example.com', '11954321098', 'senha123', 'usuario_comum', 'https://via.placeholder.com/150/00FFFF/000000?text=R.R.', 'https://via.placeholder.com/800x200/00FFFF/000000?text=Fundo+R.R.', 'Crítico de animes e mangás, sempre com uma opinião sincera e bem fundamentada.'),
('Ana Santos', 'ana.santos@example.com', '21912345678', 'senha123', 'usuario_comum', NULL, NULL, 'Gosta de animes mais antigos e cult, buscando sempre novas pérolas.'),
('Pedro Lima', 'pedro.lima@example.com', '31909876543', 'senha123', 'usuario_comum', NULL, NULL, 'Em busca de novos animes para assistir, aberto a todos os gêneros.');

-- Inserindo Gêneros
INSERT INTO Generos (nome_genero) VALUES
('Ação'), ('Aventura'), ('Comédia'), ('Drama'), ('Fantasia'), ('Ficção Científica'),
('Romance'), ('Slice of Life'), ('Suspense'), ('Mecha'), ('Esporte'), ('Terror'), ('Mistério');

-- Inserindo Animes
INSERT INTO Animes (nome, ano_lancamento, sinopse, capa_url) VALUES
('Attack on Titan', 2013, 'A humanidade vive dentro de cidades cercadas por enormes muralhas para se proteger de gigantes humanóides devoradores de homens chamados Titãs. Uma história de sobrevivência e mistério.', 'https://upload.wikimedia.org/wikipedia/en/d/d6/Attack_on_Titan_manga_volume_1.jpeg'),
('Jujutsu Kaisen', 2020, 'Yuji Itadori, um estudante do ensino médio, se envolve no mundo do Jujutsu ao tentar salvar um amigo de um monstro, e acaba engolindo um objeto amaldiçoado, tornando-se um receptáculo de uma maldição poderosa.', 'https://upload.wikimedia.org/wikipedia/en/7/7b/Jujutsu_Kaisen_vol_1.jpg'),
('Fullmetal Alchemist: Brotherhood', 2009, 'Dois irmãos, Edward e Alphonse Elric, tentam usar a alquimia para trazer sua mãe de volta à vida, mas pagam um preço terrível. Agora, eles buscam a Pedra Filosofal para recuperar seus corpos.', 'https://upload.wikimedia.org/wikipedia/en/f/f9/Fullmetal_Alchemist_Brotherhood_key_visual.png'),
('Spy x Family', 2022, 'Um espião, uma assassina e uma telepata se reúnem para formar uma família falsa para cumprir uma missão secreta, mas ninguém sabe a verdadeira identidade um do outro, levando a situações hilárias e emocionantes.', 'https://upload.wikimedia.org/wikipedia/en/thumb/4/4b/Spy_%C3%97_Family_volume_1.jpg/220px-Spy_%C3%97_Family_volume_1.jpg'),
('My Hero Academia', 2016, 'Em um mundo onde superpoderes (Quirks) são comuns, Izuku Midoriya nasce sem um, mas sonha em se tornar um herói. Ele é escolhido pelo maior herói, All Might, para herdar seu poder.', 'https://upload.wikimedia.org/wikipedia/en/thumb/5/52/My_Hero_Academia_Volume_1.png/220px-My_Hero_Academia_Volume_1.png'),
('Cowboy Bebop', 1998, 'Um grupo de caçadores de recompensas viaja pelo sistema solar em sua nave, a Bebop, em busca de criminosos e aventuras, enfrentando um passado que os assombra.', 'https://upload.wikimedia.org/wikipedia/en/f/f7/Cowboy_Bebop_Key_Art.jpg'),
('Demon Slayer: Kimetsu no Yaiba', 2019, 'Tanjiro Kamado, um jovem que teve sua família massacrada por demônios, parte em uma jornada para se tornar um caçador de demônios e salvar sua irmã, que foi transformada.', 'https://upload.wikimedia.org/wikipedia/en/thumb/d/d7/Demon_Slayer_Kimetsu_no_Yaiba_manga_volume_1.jpg/220px-Demon_Slayer_Kimetsu_no_Yaiba_manga_volume_1.jpg');

-- Inserindo relacionamentos Anime-Gênero
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

-- Inserindo Itens na Lista Pessoal dos Usuários
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
WHERE u.email = 'ana.santos@example.com' AND a.nome = 'Cowboy Bebop';

-- Inserindo Avaliações
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
WHERE u.email = 'pedro.lima@example.com' AND a.nome = 'Spy x Family';

---

### Procedures e Views

DELIMITER //
CREATE PROCEDURE sp_obter_animes_por_genero(IN p_nome_genero VARCHAR(100))
BEGIN
    SELECT
        A.nome,
        A.ano_lancamento,
        A.sinopse,
        A.capa_url
    FROM
        Animes AS A
    JOIN
        AnimeGeneros AS AG ON A.id_anime = AG.id_anime
    JOIN
        Generos AS G ON AG.id_genero = G.id_genero
    WHERE
        G.nome_genero = p_nome_genero;
END;
//
DELIMITER ;

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
    
##Inserindo os os generos Psicológico Sobrenatural,Magia
INSERT INTO Generos (nome_genero) VALUES ('Psicológico');
INSERT INTO Generos (nome_genero) VALUES ('Sobrenatural');
INSERT INTO Generos (nome_genero) VALUES ('Magia');
    


#tabela log de mudança no perfil

CREATE TABLE IF NOT EXISTS Usuarios_Descricao_Log (
    id_log INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT,
    descricao_antiga TEXT,
    descricao_nova TEXT,
    data_alteracao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_sistema VARCHAR(255),
    FOREIGN KEY (id_usuario) REFERENCES Usuarios(id_usuario) ON DELETE CASCADE
);


#tabela log_adição_genero

CREATE TABLE IF NOT EXISTS Generos_Log (
    id_log INT PRIMARY KEY AUTO_INCREMENT,
    nome_genero VARCHAR(100),
    data_insercao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

#log de remoção de animes

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


# --- VIEWS

CREATE VIEW vw_ultimo_comentario as
select
	U.nome as apelido,
	U.email as email,
	U.tipo_usuario as 'permissão'
from
	usuarios as U
left join
	Avaliacoes_Log as AL on U.id_usuario = AL.id_usuario
group by
	U.id_usuario;
    


#view para verificar a listagem de animes:

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


#view para checar log de comentários removidos
CREATE VIEW vw_comentarios_removidos_admin AS
SELECT 
    al.id_log,
    u.nome AS nome_usuario,
    u.email AS email_usuario,
    an.nome AS nome_anime,
    al.nota,
    al.comentario,
    al.data_avaliacao_original,
    al.data_exclusao
FROM Avaliacoes_Log al
JOIN Usuarios u ON al.id_usuario = u.id_usuario
JOIN Animes an ON al.id_anime = an.id_anime
WHERE al.comentario IS NOT NULL AND TRIM(al.comentario) != '';


# --- TRIGGERS

#auditoria de mudança de descrição do perfil

DELIMITER //
CREATE TRIGGER trg_log_update_descricao_usuario
BEFORE UPDATE ON Usuarios
FOR EACH ROW
BEGIN
    IF OLD.descricao != NEW.descricao THEN
        INSERT INTO Usuarios_Descricao_Log (
            id_usuario,
            descricao_antiga,
            descricao_nova,
            usuario_sistema
        )
        VALUES (
            OLD.id_usuario,
            OLD.descricao,
            NEW.descricao,
            USER()
        );
    END IF;
END;
//
DELIMITER ;

#log de adição de Gênero de anime

DELIMITER //
CREATE TRIGGER trg_after_insert_genero
AFTER INSERT ON Generos
FOR EACH ROW
BEGIN
    INSERT INTO Generos_Log (nome_genero)
    VALUES (NEW.nome_genero, USER());
END;
//
DELIMITER ;

#trigger de remoção

DELIMITER //
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
        OLD.data_cadastro,
        USER()
    );
END;
//
DELIMITER ;

#FUNÇÕES

# 1. Função para Contar o Total de Animes na Lista de um Usuário 
DELIMITER //

CREATE FUNCTION fn_total_animes_usuario(p_id_usuario INT)
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_total_animes INT;

    SELECT COUNT(*)
    INTO v_total_animes
    FROM ListaPessoalAnimes
    WHERE id_usuario = p_id_usuario;

    RETURN v_total_animes;
END //

DELIMITER ;

#2. Função para Contar Quantas Vezes um Anime foi Recomendado (fn_contar_recomendacoes_anime)

DELIMITER //

CREATE FUNCTION fn_contar_recomendacoes_anime(p_id_anime INT)
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_total_recomendacoes INT;

    SELECT COUNT(*)
    INTO v_total_recomendacoes
    FROM Avaliacoes
    WHERE id_anime = p_id_anime AND nota = 'Recomendo';

    RETURN v_total_recomendacoes;
END //

DELIMITER ;

SELECT fn_contar_recomendacoes_anime(1) AS total_recomendacoes_anime_1; 