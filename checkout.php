<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/Cart.php';
require_once __DIR__ . '/src/Csrf.php';

const METODOS_PAGAMENTO = ['pix' => 'Pix', 'boleto' => 'Boleto', 'cartao' => 'Cartão'];

function assinatura_resumo(array $itens): string
{
    $valores = [];
    foreach ($itens as $item) {
        $valores[(int) $item['id']] = [(string) $item['preco'], (int) $item['quantidade']];
    }
    ksort($valores, SORT_NUMERIC);
    return hash('sha256', serialize($valores));
}

function registrar_compra(PDO $db, int $usuarioId, mixed $pagamento, array $carrinho, mixed $resumo): int
{
    $db->beginTransaction();
    try {
        if (!is_string($pagamento) || !isset(METODOS_PAGAMENTO[$pagamento])) {
            throw new InvalidArgumentException('Selecione uma forma de pagamento válida.');
        }

        ksort($carrinho, SORT_NUMERIC);
        $travar = $db->prepare('SELECT preco, ativo, estoque FROM produtos WHERE id = ? FOR UPDATE');
        $itens = [];
        foreach ($carrinho as $id => $qtd) {
            $travar->execute([$id]);
            $produto = $travar->fetch();
            $travar->closeCursor();
            if (!$produto || (int) $produto['ativo'] !== 1 || $qtd < 1 || (int) $produto['estoque'] < $qtd) {
                throw new DomainException('O estoque mudou ou um produto ficou indisponível. Revise o carrinho.');
            }

            $itens[] = ['id' => $id, 'preco' => $produto['preco'], 'quantidade' => $qtd];
        }

        $centavos = cart_total_centavos($itens);
        if ($centavos === null) {
            throw new DomainException('O valor do pedido ultrapassa o limite permitido. Revise o carrinho.');
        }
        if (!is_string($resumo) || !hash_equals(assinatura_resumo($itens), $resumo)) {
            throw new DomainException('Os itens ou preços mudaram. Revise o carrinho e confirme o novo total.');
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
        foreach ($itens as $linha) {
            $id = $linha['id'];
            $qtd = $linha['quantidade'];
            $item->execute([$pedidoId, $id, $qtd, $linha['preco']]);
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
        $pedidoId = registrar_compra(
            Database::getConnection(), $usuarioId, $_POST['forma_pagamento'] ?? null, $carrinho, $_POST['resumo'] ?? null
        );
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

$titulo = 'Finalizar compra | GameRochas';
$comprador = false;
try {
    $consulta = Database::getConnection()->prepare('SELECT nome, email FROM usuarios WHERE id = ?');
    $consulta->execute([$usuarioId]);
    $comprador = $consulta->fetch();
    $itens = cart_itens();
    if (!$itens) {
        redirecionar($carrinhoUrl);
    }
    $total = cart_total_centavos($itens);
    if ($total === null) {
        flash('warning', 'O valor do pedido ultrapassa o limite permitido. Revise o carrinho.');
        redirecionar($carrinhoUrl);
    }
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
        <section class="col-lg-5">
            <div class="vault-panel">
                <h2 class="h4">Resumo do carrinho</h2>
                <ul class="list-group list-group-flush mb-3">
                    <?php foreach ($itens as $item): ?>
                        <li class="list-group-item d-flex justify-content-between gap-3">
                            <span><?= e($item['nome']) ?> × <?= e((string) $item['quantidade']) ?></span>
                            <span class="text-nowrap"><?= e(moeda(cart_subtotal_centavos($item) / 100)) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p class="vault-price fs-5">Total <?= e(moeda($total / 100)) ?></p>
                <a href="<?= e($carrinhoUrl) ?>">Voltar ao carrinho</a>
            </div>
        </section>
        <section class="col-lg-7">
            <div class="vault-panel">
                <h2 class="h4">Dados do comprador</h2>
                <p><?= e($comprador['nome']) ?><br><?= e($comprador['email']) ?></p>
                <form method="post" action="<?= e($checkoutUrl) ?>">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="resumo" value="<?= e(assinatura_resumo($itens)) ?>">
                    <fieldset class="mb-3">
                        <legend class="h4">Forma de pagamento</legend>
                        <p>Escolha a opção que ficará registrada no pedido. A confirmação do pedido não gera cobrança.</p>
                        <?php foreach (METODOS_PAGAMENTO as $valor => $rotulo): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="forma_pagamento"
                                       id="<?= e($valor) ?>" value="<?= e($valor) ?>" required>
                                <label class="form-check-label" for="<?= e($valor) ?>"><?= e($rotulo) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </fieldset>
                    <details class="mb-3">
                        <summary>Dados do cartão (deixe os campos em branco)</summary>
                        <label class="form-label mt-2" for="numero-cartao">Número do cartão</label>
                        <input class="form-control" id="numero-cartao" inputmode="numeric" autocomplete="off">
                        <div class="row mt-2">
                            <div class="col">
                                <label class="form-label" for="validade-cartao">Validade</label>
                                <input class="form-control" id="validade-cartao" placeholder="MM/AA" autocomplete="off">
                            </div>
                            <div class="col">
                                <label class="form-label" for="codigo-cartao">Código de segurança</label>
                                <input class="form-control" id="codigo-cartao" inputmode="numeric" autocomplete="off">
                            </div>
                        </div>
                    </details>
                    <button type="submit" class="btn btn-primary w-100">Confirmar pedido</button>
                </form>
            </div>
        </section>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/templates/footer.php'; ?>
