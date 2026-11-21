-- Banco e codificação padrão.
CREATE DATABASE IF NOT EXISTS gamevault
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gamevault;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Contas e categorias do catálogo.
CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(254) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    perfil ENUM('cliente','admin') NOT NULL DEFAULT 'cliente',
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categorias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS produtos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL CHECK (preco >= 0),
    estoque INT UNSIGNED NOT NULL DEFAULT 0,
    imagem VARCHAR(255),
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_produtos_nome (nome),
    INDEX idx_produtos_categoria_id (categoria_id),
    CONSTRAINT fk_produtos_categoria FOREIGN KEY (categoria_id)
        REFERENCES categorias (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pedidos e preços registrados na compra.
CREATE TABLE IF NOT EXISTS pedidos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    data_pedido TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    forma_pagamento ENUM('pix','boleto','cartao') NOT NULL,
    status ENUM('pago','pendente','cancelado') NOT NULL DEFAULT 'pago',
    total DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_pedidos_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS itens_pedido (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT UNSIGNED NOT NULL,
    produto_id INT UNSIGNED NOT NULL,
    quantidade INT UNSIGNED NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    UNIQUE KEY uq_itens_pedido_produto (pedido_id, produto_id),
    CONSTRAINT fk_itens_pedido_pedido FOREIGN KEY (pedido_id)
        REFERENCES pedidos (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_itens_pedido_produto FOREIGN KEY (produto_id)
        REFERENCES produtos (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Carga inicial para desenvolvimento.
START TRANSACTION;

INSERT INTO categorias (nome) VALUES
    ('Ação'), ('Esporte'), ('Aventura'), ('RPG'), ('Corrida'), ('Estratégia')
ON DUPLICATE KEY UPDATE id = id;

-- O nome identifica os produtos da carga inicial, que não inclui capas.
INSERT INTO produtos (categoria_id, nome, descricao, preco, estoque, imagem, ativo)
SELECT categorias.id, carga.nome, carga.descricao, carga.preco, carga.estoque, NULL, 1
FROM (
    SELECT 'Ação' AS categoria, 'DOOM Eternal' AS nome,
           'Encare hordas de demônios com um arsenal pesado e combates em ritmo acelerado.' AS descricao, 129.90 AS preco, 12 AS estoque
    UNION ALL
    SELECT 'Ação', 'Hades',
           'Desafie os deuses do Olimpo e encontre novas armas a cada tentativa de fuga do submundo.', 79.90, 8
    UNION ALL
    SELECT 'Esporte', 'EA SPORTS FC 25',
           'Monte seu elenco e dispute partidas com clubes e seleções de todo o mundo.', 199.90, 0
    UNION ALL
    SELECT 'Esporte', 'NBA 2K25',
           'Leve seu time às quadras e dispute a temporada com estrelas da NBA.', 179.90, 7
    UNION ALL
    SELECT 'Aventura', 'Hogwarts Legacy',
           'Explore Hogwarts, aprenda feitiços e desvende segredos do mundo bruxo.', 249.90, 15
    UNION ALL
    SELECT 'Aventura', 'It Takes Two',
           'Resolva desafios em dupla numa aventura cooperativa que muda a cada fase.', 99.90, 11
    UNION ALL
    SELECT 'Aventura', 'Stray',
           'Explore uma cidade habitada por robôs na pele de um gato perdido.', 89.90, 5
    UNION ALL
    SELECT 'RPG', 'Baldur’s Gate 3',
           'Reúna seu grupo e tome decisões que mudam o rumo desta aventura em Faerûn.', 199.90, 9
    UNION ALL
    SELECT 'RPG', 'Elden Ring',
           'Explore as Terras Intermédias e enfrente chefes com sua própria combinação de armas e magia.', 249.90, 0
    UNION ALL
    SELECT 'RPG', 'Cyberpunk 2077',
           'Construa sua reputação como mercenário nas ruas de Night City.', 199.90, 6
    UNION ALL
    SELECT 'Corrida', 'Forza Horizon 5',
           'Explore as estradas do México e dispute provas com carros de várias categorias.', 249.90, 14
    UNION ALL
    SELECT 'Corrida', 'Assetto Corsa Competizione',
           'Dispute corridas de GT em pistas oficiais com foco em simulação.', 129.90, 10
    UNION ALL
    SELECT 'Corrida', 'Need for Speed Heat',
           'Corra de dia para ganhar dinheiro e fuja da polícia à noite em Palm City.', 149.90, 3
    UNION ALL
    SELECT 'Estratégia', 'Sid Meier’s Civilization VI',
           'Guie uma civilização da Antiguidade à era moderna por meio de ciência, diplomacia e conquistas.', 129.90, 13
    UNION ALL
    SELECT 'Estratégia', 'Age of Empires IV',
           'Comande civilizações medievais, fortaleça sua economia e lidere exércitos em batalha.', 149.90, 4
    UNION ALL
    SELECT 'Estratégia', 'XCOM 2',
           'Coordene uma resistência e planeje cada movimento em combates táticos por turnos.', 99.90, 8
) AS carga
JOIN categorias ON categorias.nome = carga.categoria
WHERE NOT EXISTS (
    SELECT 1 FROM produtos WHERE produtos.nome = carga.nome
);

COMMIT;
