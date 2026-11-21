<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Cart.php';
require_once __DIR__ . '/src/Csrf.php';

$carrinhoUrl = url('carrinho.php');
$post = $_SERVER['REQUEST_METHOD'] === 'POST';
$titulo = 'Carrinho | GameRochas';
$itens = [];
$erro = null;

try {
    if ($post) {
        if (!validar_csrf($_POST['csrf_token'] ?? null)) {
            flash('warning', 'Solicitação inválida. Tente novamente.');
        } else {
            $id = filter_input(INPUT_POST, 'produto_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $qtd = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);
            $acao = $_POST['acao'] ?? null;

            if ($id && isset($_SESSION['carrinho'][$id]) && $acao === 'remover') {
                cart_remover($id);
            } elseif ($id && isset($_SESSION['carrinho'][$id]) && $acao === 'atualizar' && is_int($qtd)) {
                cart_atualizar($id, $qtd);
            } else {
                flash('warning', 'Não foi possível aplicar a alteração ao carrinho.');
            }
        }
    } else {
        $itens = cart_itens();
        $total = cart_total_centavos($itens);
    }
} catch (PDOException $excecao) {
    error_log('GameVault: ' . $excecao->getMessage());
    $erro = 'Não foi possível acessar o carrinho. Tente novamente.';
    if ($post) {
        flash('danger', $erro);
    } else {
        http_response_code(500);
    }
}

if ($post) {
    header('Location: ' . $carrinhoUrl, true, 303);
    exit;
}

require __DIR__ . '/templates/header.php';
?>
<h1 class="mb-4">Carrinho</h1>

<?php if ($erro !== null): ?>
    <p class="alert alert-danger" role="alert"><?= e($erro) ?></p>
<?php elseif (!$itens): ?>
    <section class="vault-panel">
        <p>Seu carrinho está vazio.</p>
        <a class="btn btn-primary" href="<?= e(url('index.php')) ?>">Ir para a vitrine</a>
    </section>
<?php else: ?>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th scope="col">Produto</th>
                    <th scope="col">Preço unitário</th>
                    <th scope="col">Quantidade</th>
                    <th scope="col">Subtotal</th>
                    <th scope="col">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itens as $item): ?>
                    <?php
                    $formId = 'item-' . $item['id'];
                    $subtotal = cart_subtotal_centavos($item);
                    ?>
                    <tr data-preco="<?= e((string) $item['preco']) ?>">
                        <th scope="row">
                            <div class="d-flex align-items-center gap-3">
                                <?php if (!empty($item['imagem'])): ?>
                                    <img class="rounded object-fit-cover" width="80" height="48" loading="lazy"
                                         src="<?= e(url($item['imagem'])) ?>" alt="">
                                <?php endif; ?>
                                <?= e($item['nome']) ?>
                            </div>
                        </th>
                        <td class="text-nowrap"><?= e(moeda($item['preco'])) ?></td>
                        <td>
                            <input class="form-control w-auto" type="number" name="quantidade" min="1"
                                   max="<?= e((string) $item['estoque']) ?>" value="<?= e((string) $item['quantidade']) ?>"
                                   form="<?= e($formId) ?>" aria-label="<?= e('Quantidade de ' . $item['nome']) ?>" required>
                        </td>
                        <td class="text-nowrap" data-subtotal><?= $subtotal === null ? 'Acima do limite' : e(moeda($subtotal / 100)) ?></td>
                        <td>
                            <form class="d-flex gap-2" id="<?= e($formId) ?>" method="post" action="<?= e($carrinhoUrl) ?>">
                                <?= csrf_campo() ?>
                                <input type="hidden" name="produto_id" value="<?= e((string) $item['id']) ?>">
                                <button class="btn btn-sm btn-outline-light" type="submit" name="acao" value="atualizar">
                                    Atualizar
                                </button>
                                <button class="btn btn-sm btn-outline-danger" type="submit" name="acao" value="remover"
                                        formnovalidate>Remover</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="vault-summary d-flex flex-wrap align-items-center justify-content-sm-end gap-3">
        <?php if ($total === null): ?>
            <strong>O total ultrapassa o limite por pedido. Reduza as quantidades para continuar.</strong>
        <?php else: ?>
            <strong class="vault-price fs-5">Total geral <?= e(moeda($total / 100)) ?></strong>
            <a class="btn btn-primary" href="<?= e(url('checkout.php')) ?>">Finalizar compra</a>
        <?php endif; ?>
    </div>
    <script src="<?= e(url('assets/js/cart.js')) ?>" defer></script>
<?php endif; ?>

<?php require __DIR__ . '/templates/footer.php'; ?>
