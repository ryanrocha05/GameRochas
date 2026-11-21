<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    http_response_code(405);
    header('Allow: GET, HEAD');
    exit;
}

exigir_login();
$titulo = 'Detalhes do pedido | GameRochas';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$pedido = false;
$itens = [];
$mensagem = 'Pedido não encontrado.';
$metodos = ['pix' => 'Pix', 'boleto' => 'Boleto', 'cartao' => 'Cartão'];

try {
    if ($id) {
        $db = Database::getConnection();
        $consulta = $db->prepare(
            'SELECT id, data_pedido, forma_pagamento, status, total FROM pedidos WHERE id = ? AND usuario_id = ?'
        );
        $consulta->execute([$id, usuario_logado()['id']]);
        $pedido = $consulta->fetch();
        if ($pedido) {
            $consulta = $db->prepare(
                'SELECT p.nome, i.quantidade, i.preco_unitario, i.quantidade * i.preco_unitario AS subtotal
                 FROM itens_pedido i JOIN produtos p ON p.id = i.produto_id
                 WHERE i.pedido_id = ? ORDER BY i.id'
            );
            $consulta->execute([$pedido['id']]);
            $itens = $consulta->fetchAll();
        }
    }
    if (!$pedido) {
        http_response_code(404);
    }
} catch (PDOException $erro) {
    error_log('GameVault: ' . $erro->getMessage());
    http_response_code(500);
    $pedido = false;
    $mensagem = 'Não foi possível carregar o pedido.';
}

require __DIR__ . '/templates/header.php';
?>
<?php if (!$pedido): ?>
    <h1>Detalhes do pedido</h1>
    <p><?= e($mensagem) ?></p>
<?php else: ?>
    <h1 class="mb-3">Pedido #<?= e((string) $pedido['id']) ?></h1>
    <dl class="row">
        <dt class="col-sm-3">Data</dt>
        <dd class="col-sm-9"><?= e(date('d/m/Y H:i', strtotime($pedido['data_pedido']))) ?></dd>
        <dt class="col-sm-3">Status</dt>
        <dd class="col-sm-9"><?= e(ucfirst($pedido['status'])) ?></dd>
        <dt class="col-sm-3">Forma de pagamento</dt>
        <dd class="col-sm-9"><?= e($metodos[$pedido['forma_pagamento']]) ?> (simulado)</dd>
    </dl>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th scope="col">Produto</th>
                    <th scope="col">Quantidade</th>
                    <th scope="col">Preço unitário</th>
                    <th scope="col">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itens as $item): ?>
                    <tr>
                        <th scope="row"><?= e($item['nome']) ?></th>
                        <td><?= e((string) $item['quantidade']) ?></td>
                        <td class="text-nowrap"><?= e(moeda($item['preco_unitario'])) ?></td>
                        <td class="text-nowrap"><?= e(moeda($item['subtotal'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="fw-bold">Total <?= e(moeda($pedido['total'])) ?></p>
<?php endif; ?>
<a class="btn btn-outline-light" href="<?= e(url('meus-pedidos.php')) ?>">Meus Pedidos</a>
<?php require __DIR__ . '/templates/footer.php'; ?>
