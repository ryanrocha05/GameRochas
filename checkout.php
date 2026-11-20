<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/Cart.php';
require_once __DIR__ . '/src/Csrf.php';

const METODOS_PAGAMENTO = ['pix' => 'Pix', 'boleto' => 'Boleto', 'cartao' => 'Cartão'];

function registrar_compra(PDO $db, int $usuarioId, mixed $pagamento, array $carrinho): int
{
    $db->beginTransaction();
    try {
        if (!is_string($pagamento) || !isset(METODOS_PAGAMENTO[$pagamento])) {
            throw new InvalidArgumentException('Selecione uma forma de pagamento válida.');
        }

        ksort($carrinho, SORT_NUMERIC);
        $travar = $db->prepare('SELECT preco, ativo, estoque FROM produtos WHERE id = ? FOR UPDATE');
        $precos = [];
        $centavos = 0;
        foreach ($carrinho as $id => $qtd) {
            $travar->execute([$id]);
            $produto = $travar->fetch();
            $travar->closeCursor();
            if (!$produto || (int) $produto['ativo'] !== 1 || $qtd < 1 || (int) $produto['estoque'] < $qtd) {
                throw new DomainException('O estoque mudou ou um produto ficou indisponível. Revise o carrinho.');
            }

            $precos[$id] = (string) $produto['preco'];
            $unitario = (int) str_replace('.', '', $precos[$id]);
            if ($unitario > intdiv(9999999999 - $centavos, $qtd)) {
                throw new DomainException('O valor do pedido ultrapassa o limite permitido. Revise o carrinho.');
            }
            $centavos += $unitario * $qtd;
        }

        $total = sprintf('%d.%02d', intdiv($centavos, 100), $centavos % 100);
        $pedido = $db->prepare(
            "INSERT INTO pedidos (usuario_id, forma_pagamento, status, total) VALUES (?, ?, 'pago', ?)"
        );
        $pedido->execute([$usuarioId, $pagamento, $total]);
        $pedidoId = (int) $db->lastInsertId();

        $item = $db->prepare(
            'INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)'
        );
        $baixa = $db->prepare(
            'UPDATE produtos SET estoque = estoque - ? WHERE id = ? AND ativo = 1 AND estoque >= ?'
        );
        foreach ($carrinho as $id => $qtd) {
            $item->execute([$pedidoId, $id, $qtd, $precos[$id]]);
            $baixa->execute([$qtd, $id, $qtd]);
            if ($baixa->rowCount() !== 1) {
                throw new DomainException('Não foi possível reservar o estoque. Revise o carrinho.');
            }
        }

        $db->commit();
        return $pedidoId;
    } catch (Throwable $erro) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $erro;
    }
}

exigir_login();
$usuarioId = usuario_logado()['id'];
$post = $_SERVER['REQUEST_METHOD'] === 'POST';
$carrinho = $_SESSION['carrinho'] ?? [];
$checkoutUrl = url('checkout.php');
$carrinhoUrl = url('carrinho.php');

if ($post && !validar_csrf($_POST['csrf_token'] ?? null)) {
    flash('warning', 'Solicitação inválida. Tente novamente.');
    header('Location: ' . $checkoutUrl, true, 303);
    exit;
}

if (!$carrinho) {
    flash('warning', 'Adicione um produto ao carrinho para finalizar a compra.');
    header('Location: ' . $carrinhoUrl, true, 303);
    exit;
}

if ($post) {
    $destino = $checkoutUrl;
    try {
        $pedidoId = registrar_compra(Database::getConnection(), $usuarioId, $_POST['forma_pagamento'] ?? null, $carrinho);
        cart_limpar();
        $destino = url('pedido-sucesso.php?id=' . $pedidoId);
    } catch (InvalidArgumentException $erro) {
        flash('warning', $erro->getMessage());
    } catch (DomainException $erro) {
        flash('warning', $erro->getMessage());
        $destino = $carrinhoUrl;
    } catch (PDOException $erro) {
        error_log('GameVault: ' . $erro->getMessage());
        flash('danger', 'Não foi possível concluir a compra. Tente novamente.');
    }
    header('Location: ' . $destino, true, 303);
    exit;
}

$titulo = 'Finalizar compra | GameVault';
$comprador = false;
try {
    $consulta = Database::getConnection()->prepare('SELECT nome, email FROM usuarios WHERE id = ?');
    $consulta->execute([$usuarioId]);
    $comprador = $consulta->fetch();
    $itens = cart_itens();
    if (!$itens) {
        redirecionar($carrinhoUrl);
    }
    $total = cart_total();
} catch (PDOException $erro) {
    error_log('GameVault: ' . $erro->getMessage());
    http_response_code(500);
    $comprador = false;
}

require __DIR__ . '/templates/header.php';
?>
<h1 class="mb-4">Finalizar compra</h1>
<?php if (!$comprador): ?>
    <p class="alert alert-danger" role="alert">Não foi possível carregar os dados da compra.</p>
<?php else: ?>
    <div class="row g-4">
        <section class="col-md-6">
            <div class="vault-panel">
                <h2 class="h4">Resumo do carrinho</h2>
                <ul class="list-group list-group-flush mb-3">
                    <?php foreach ($itens as $item): ?>
                        <li class="list-group-item d-flex justify-content-between gap-3">
                            <span><?= e($item['nome']) ?> × <?= e((string) $item['quantidade']) ?></span>
                            <span class="text-nowrap"><?= e(moeda($item['preco'] * $item['quantidade'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p class="fw-bold">Total <?= e(moeda($total)) ?></p>
                <a href="<?= e($carrinhoUrl) ?>">Voltar ao carrinho</a>
            </div>
        </section>
        <section class="col-md-6">
            <div class="vault-panel">
                <h2 class="h4">Dados do comprador</h2>
                <p><?= e($comprador['nome']) ?><br><?= e($comprador['email']) ?></p>
                <form method="post" action="<?= e($checkoutUrl) ?>">
                    <?= csrf_campo() ?>
                    <fieldset class="mb-3">
                        <legend class="h4">Pagamento simulado</legend>
                        <p>Esta compra é uma simulação e não gera cobrança. Use somente dados fictícios.</p>
                        <?php foreach (METODOS_PAGAMENTO as $valor => $rotulo): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="forma_pagamento"
                                       id="<?= e($valor) ?>" value="<?= e($valor) ?>" required>
                                <label class="form-check-label" for="<?= e($valor) ?>"><?= e($rotulo) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </fieldset>
                    <details class="mb-3">
                        <summary>Campos de cartão para demonstração</summary>
                        <label class="form-label mt-2" for="numero-cartao">Número fictício</label>
                        <input class="form-control" id="numero-cartao" inputmode="numeric" autocomplete="off">
                        <div class="row mt-2">
                            <div class="col">
                                <label class="form-label" for="validade-cartao">Validade fictícia</label>
                                <input class="form-control" id="validade-cartao" placeholder="MM/AA" autocomplete="off">
                            </div>
                            <div class="col">
                                <label class="form-label" for="codigo-cartao">CVV fictício</label>
                                <input class="form-control" id="codigo-cartao" inputmode="numeric" autocomplete="off">
                            </div>
                        </div>
                    </details>
                    <button type="submit" class="btn btn-primary">Confirmar compra simulada</button>
                </form>
            </div>
        </section>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/templates/footer.php'; ?>
