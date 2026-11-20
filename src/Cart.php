<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function cart_adicionar(int $id, int $qtd): void
{
    if ($qtd <= 0) {
        flash('warning', 'Informe uma quantidade positiva para adicionar.');
        return;
    }

    $atual = $_SESSION['carrinho'][$id] ?? 0;
    cart_atualizar($id, $atual + min($qtd, PHP_INT_MAX - $atual));
}

function cart_atualizar(int $id, int $qtd): void
{
    if ($qtd <= 0) {
        cart_remover($id);
        return;
    }

    $consulta = Database::getConnection()->prepare(
        'SELECT estoque FROM produtos WHERE id = ? AND ativo = 1 AND estoque > 0'
    );
    $consulta->execute([$id]);
    $estoque = (int) $consulta->fetchColumn();

    if ($estoque === 0) {
        cart_remover($id);
        flash('warning', 'O produto está indisponível.');
        return;
    }

    cart_definir_quantidade($id, $qtd, $estoque);
}

function cart_definir_quantidade(int $id, int $qtd, int $estoque): int
{
    if ($qtd > $estoque) {
        flash('warning', 'A quantidade de um produto foi ajustada ao estoque disponível.');
    }

    return $_SESSION['carrinho'][$id] = min($qtd, $estoque);
}

function cart_remover(int $id): void
{
    unset($_SESSION['carrinho'][$id]);
}

function cart_limpar(): void
{
    unset($_SESSION['carrinho']);
}

function cart_contar(): int
{
    return array_sum($_SESSION['carrinho'] ?? []);
}

function cart_itens(): array
{
    $carrinho = $_SESSION['carrinho'] ?? [];
    if (!$carrinho) {
        return [];
    }

    $ids = array_keys($carrinho);
    $placeholders = implode(', ', array_fill(0, count($ids), '?'));
    $consulta = Database::getConnection()->prepare(
        "SELECT id, nome, preco, estoque, imagem
         FROM produtos WHERE id IN ($placeholders) AND ativo = 1 AND estoque > 0"
    );
    $consulta->execute($ids);

    $disponiveis = [];
    foreach ($consulta->fetchAll() as $produto) {
        $disponiveis[(int) $produto['id']] = $produto;
    }

    $itens = [];
    foreach ($carrinho as $id => $qtd) {
        if (!isset($disponiveis[$id])) {
            cart_remover($id);
            flash('warning', 'Um produto indisponível foi removido do carrinho.');
            continue;
        }

        $produto = $disponiveis[$id];
        $produto['quantidade'] = cart_definir_quantidade($id, $qtd, (int) $produto['estoque']);
        $itens[] = $produto;
    }

    return $itens;
}

function cart_total(): string
{
    $carrinho = $_SESSION['carrinho'] ?? [];
    if (!$carrinho) {
        return '0.00';
    }

    $casos = [];
    $parametros = [];
    $ids = array_keys($carrinho);
    foreach ($carrinho as $id => $qtd) {
        $casos[] = 'WHEN ? THEN CAST(? AS UNSIGNED)';
        $parametros[] = $id;
        $parametros[] = $qtd;
    }

    $quantidades = implode(' ', $casos);
    $placeholders = implode(', ', array_fill(0, count($ids), '?'));
    $consulta = Database::getConnection()->prepare(
        "SELECT COALESCE(SUM(preco * LEAST(estoque, CASE id $quantidades END)), 0.00)
         FROM produtos WHERE id IN ($placeholders) AND ativo = 1 AND estoque > 0"
    );
    $consulta->execute(array_merge($parametros, $ids));

    return (string) $consulta->fetchColumn();
}
