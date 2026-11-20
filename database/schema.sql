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
    ('Ação'), ('Aventura'), ('RPG'), ('Corrida'), ('Estratégia')
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO usuarios (nome, email, senha_hash, perfil) VALUES
    ('Administrador de teste', 'admin@gamevault.com', '$2y$12$4uFSrdEDOWsRWnTiGXAQcOCHFPeO3kwF7v8g/nAbwiZ30dylZcepO', 'admin'),
    ('Cliente de teste', 'cliente@gamevault.com', '$2y$12$g3AHet5k/06Hr8GxPTTJp.4WdFmT5yXoaigaoHB1XU.mp6CqHGV26', 'cliente')
ON DUPLICATE KEY UPDATE id = id;

-- A imagem identifica cada produto da carga inicial.
INSERT INTO produtos (categoria_id, nome, preco, estoque, imagem, ativo)
SELECT categorias.id, carga.nome, carga.preco, carga.estoque, carga.imagem, carga.ativo
FROM (
    SELECT 'Ação' AS categoria, 'Neon Run' AS nome, 89.90 AS preco, 12 AS estoque,
           'assets/img/neon-run.jpg' AS imagem, 1 AS ativo
    UNION ALL
    SELECT 'Ação', 'Fronteira Zero', 119.90, 8, 'assets/img/fronteira-zero.jpg', 1
    UNION ALL
    SELECT 'Ação', 'Cerco Orbital', 74.90, 0, 'assets/img/cerco-orbital.jpg', 1
    UNION ALL
    SELECT 'Ação', 'Pulso Metal', 99.90, 7, 'assets/img/pulso-metal.jpg', 1
    UNION ALL
    SELECT 'Aventura', 'Ilhas do Vento', 79.90, 15, 'assets/img/ilhas-do-vento.jpg', 1
    UNION ALL
    SELECT 'Aventura', 'Mapa das Marés', 59.90, 11, 'assets/img/mapa-das-mares.jpg', 1
    UNION ALL
    SELECT 'Aventura', 'Farol do Norte', 69.90, 5, 'assets/img/farol-do-norte.jpg', 1
    UNION ALL
    SELECT 'RPG', 'Crônicas de Aurora', 139.90, 9, 'assets/img/cronicas-de-aurora.jpg', 1
    UNION ALL
    SELECT 'RPG', 'Reino de Cinzas', 129.90, 0, 'assets/img/reino-de-cinzas.jpg', 1
    UNION ALL
    SELECT 'RPG', 'Vale dos Ecos', 109.90, 6, 'assets/img/vale-dos-ecos.jpg', 1
    UNION ALL
    SELECT 'Corrida', 'Asfalto Noturno', 89.90, 14, 'assets/img/asfalto-noturno.jpg', 1
    UNION ALL
    SELECT 'Corrida', 'Rali da Serra', 79.90, 10, 'assets/img/rali-da-serra.jpg', 1
    UNION ALL
    SELECT 'Corrida', 'Curva Final', 64.90, 3, 'assets/img/curva-final.jpg', 0
    UNION ALL
    SELECT 'Estratégia', 'Rota Estelar', 99.90, 13, 'assets/img/rota-estelar.jpg', 1
    UNION ALL
    SELECT 'Estratégia', 'Forte de Inverno', 84.90, 4, 'assets/img/forte-de-inverno.jpg', 1
    UNION ALL
    SELECT 'Estratégia', 'Cidade de Cobre', 74.90, 8, 'assets/img/cidade-de-cobre.jpg', 1
) AS carga
JOIN categorias ON categorias.nome = carga.categoria
WHERE NOT EXISTS (
    SELECT 1 FROM produtos WHERE produtos.imagem = carga.imagem
);

COMMIT;

