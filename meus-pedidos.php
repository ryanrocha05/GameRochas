<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    http_response_code(405);
    header('Allow: GET, HEAD');
    exit;
}

exigir_login();
$titulo = 'Meus Pedidos | GameVault';
$pedidos = [];
$erro = false;
try {
    $consulta = Database::getConnection()->prepare(
        'SELECT id, data_pedido, status, total FROM pedidos WHERE usuario_id = ? ORDER BY data_pedido DESC, id DESC'
    );
    $consulta->execute([usuario_logado()['id']]);
    $pedidos = $consulta->fetchAll();
} catch (PDOException $excecao) {
    error_log('GameVault: ' . $excecao->getMessage());
    http_response_code(500);
    $erro = true;
}

require __DIR__ . '/templates/header.php';
?>
<h1 class="mb-4">Meus Pedidos</h1>
<?php if ($erro): ?>
    <p class="alert alert-danger" role="alert">Não foi possível carregar seus pedidos.</p>
<?php elseif (!$pedidos): ?>
    <p>Você ainda não fez pedidos.</p>
    <a class="btn btn-primary" href="<?= e(url('index.php')) ?>">Ir para a vitrine</a>
<?php else: ?>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th scope="col">Pedido</th>
                    <th scope="col">Data</th>
                    <th scope="col">Status</th>
                    <th scope="col">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pedidos as $pedido): ?>
                    <tr>
                        <th scope="row">
                            <a href="<?= e(url('pedido-sucesso.php?id=' . $pedido['id'])) ?>">#<?= e((string) $pedido['id']) ?></a>
                        </th>
                        <td><?= e(date('d/m/Y H:i', strtotime($pedido['data_pedido']))) ?></td>
                        <td><?= e(ucfirst($pedido['status'])) ?></td>
                        <td class="text-nowrap"><?= e(moeda($pedido['total'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/templates/footer.php'; ?>
