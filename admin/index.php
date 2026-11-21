<?php

declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

$titulo = 'Painel administrativo | GameRochas';
$erro = false;
try {
    $db = Database::getConnection();
    $consulta = $db->prepare(
        'SELECT COUNT(CASE WHEN ativo = 1 THEN 1 END) AS ativos,
                COUNT(CASE WHEN estoque <= 5 THEN 1 END) AS estoque_baixo
         FROM produtos'
    );
    $consulta->execute();
    $produtos = $consulta->fetch();

    $consulta = $db->prepare(
        'SELECT COUNT(*) AS pagos, COALESCE(SUM(total), 0) AS faturamento FROM pedidos WHERE status = ?'
    );
    $consulta->execute(['pago']);
    $vendas = $consulta->fetch();

    $consulta = $db->prepare(
        'SELECT p.id, u.nome AS cliente, p.total, p.status, p.data_pedido
         FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id
         ORDER BY p.data_pedido DESC, p.id DESC LIMIT 5'
    );
    $consulta->execute();
    $pedidos = $consulta->fetchAll();

    $resumo = [
        'Produtos ativos' => (string) $produtos['ativos'],
        'Pedidos pagos' => (string) $vendas['pagos'],
        'Faturamento total' => moeda($vendas['faturamento']),
        'Produtos com estoque baixo (até 5)' => (string) $produtos['estoque_baixo'],
    ];
} catch (PDOException $excecao) {
    error_log('GameVault: ' . $excecao->getMessage());
    http_response_code(500);
    $erro = true;
}

require __DIR__ . '/../templates/header.php';
require __DIR__ . '/_nav.php';
?>
<h1 class="h2 mb-4">Painel administrativo</h1>
<?php if ($erro): ?>
    <p class="alert alert-danger" role="alert">Não foi possível carregar os dados do painel.</p>
<?php else: ?>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 mb-4">
        <?php foreach ($resumo as $rotulo => $valor): ?>
            <div class="col">
                <div class="vault-panel h-100">
                    <h2 class="h6"><?= e($rotulo) ?></h2>
                    <p class="fs-4 fw-bold mb-0"><?= e($valor) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <section class="vault-panel">
        <h2 class="h4 mb-3">Pedidos mais recentes</h2>
        <?php if (!$pedidos): ?>
            <p class="mb-0">Nenhum pedido registrado.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Cliente</th>
                            <th scope="col">Total</th>
                            <th scope="col">Status</th>
                            <th scope="col">Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <th scope="row"><?= e((string) $pedido['id']) ?></th>
                                <td><?= e($pedido['cliente']) ?></td>
                                <td class="text-nowrap"><?= e(moeda($pedido['total'])) ?></td>
                                <td><?= e(ucfirst($pedido['status'])) ?></td>
                                <td class="text-nowrap"><?= e(date('d/m/Y H:i', strtotime($pedido['data_pedido']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/../templates/footer.php'; ?>
