<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Csrf.php';

$pagina = url('admin/pedidos.php');
$post = $_SERVER['REQUEST_METHOD'] === 'POST';

// Aplica PRG ao token inválido antes da resposta 403 padrão do guard.
if ($post && !validar_csrf($_POST['csrf_token'] ?? null)) {
    exigir_admin();
    flash('danger', 'Solicitação inválida. Tente novamente.');
    header('Location: ' . $pagina, true, 303);
    exit;
}

require_once __DIR__ . '/_guard.php';

$transicoes = [
    'pendente' => ['pago', 'cancelado'],
    'pago' => ['cancelado'],
    'cancelado' => [],
];
$rotulos = ['pendente' => 'Pendente', 'pago' => 'Pago', 'cancelado' => 'Cancelado'];

function devolver_estoque_pedido(PDO $db, int $pedidoId): void
{
    $consulta = $db->prepare(
        'SELECT produto_id, quantidade FROM itens_pedido WHERE pedido_id = ? ORDER BY produto_id FOR UPDATE'
    );
    $consulta->execute([$pedidoId]);
    $itens = $consulta->fetchAll();

    $atualizar = $db->prepare('UPDATE produtos SET estoque = estoque + ? WHERE id = ?');
    foreach ($itens as $item) {
        $atualizar->execute([$item['quantidade'], $item['produto_id']]);
    }
}

if ($post) {
    $pedidoId = filter_input(INPUT_POST, 'pedido_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $destino = $pedidoId ? $pagina . '?id=' . $pedidoId : $pagina;
    $novoStatus = $_POST['status'] ?? null;

    if (!$pedidoId || !is_string($novoStatus) || !isset($rotulos[$novoStatus])) {
        flash('danger', 'Informe um pedido e um status válidos.');
    } else {
        $db = Database::getConnection();
        try {
            $db->beginTransaction();
            $consulta = $db->prepare('SELECT status FROM pedidos WHERE id = ? FOR UPDATE');
            $consulta->execute([$pedidoId]);
            $statusAtual = $consulta->fetchColumn();

            if ($statusAtual === false || !in_array($novoStatus, $transicoes[$statusAtual] ?? [], true)) {
                $db->rollBack();
                flash('warning', 'O pedido não existe ou a alteração de status não é permitida.');
            } else {
                if ($novoStatus === 'cancelado') {
                    devolver_estoque_pedido($db, $pedidoId);
                }

                $atualizar = $db->prepare('UPDATE pedidos SET status = ? WHERE id = ?');
                $atualizar->execute([$novoStatus, $pedidoId]);
                $db->commit();
                flash('success', 'Status do pedido atualizado.');
            }
        } catch (PDOException $erro) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('GameVault: ' . $erro->getMessage());
            flash('danger', 'Não foi possível atualizar o pedido.');
        }
    }

    header('Location: ' . $destino, true, 303);
    exit;
}

$titulo = 'Pedidos | GameVault';
$pedidoId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$erroPagina = null;
$db = Database::getConnection();
try {
    $sql = 'SELECT p.id, p.status, p.total, p.data_pedido, u.nome AS cliente, u.email
            FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id';

    if ($pedidoId === null) {
        $consulta = $db->prepare($sql . ' ORDER BY p.data_pedido DESC, p.id DESC');
        $consulta->execute();
        $pedidos = $consulta->fetchAll();
    } else {
        $consulta = $db->prepare($sql . ' WHERE p.id = ?');
        $consulta->execute([$pedidoId ?: 0]);
        $pedido = $consulta->fetch();

        if (!$pedido) {
            http_response_code(404);
            $erroPagina = 'Pedido não encontrado.';
        } else {
            $consulta = $db->prepare(
                'SELECT p.nome, i.quantidade, i.preco_unitario
                 FROM itens_pedido i JOIN produtos p ON p.id = i.produto_id
                 WHERE i.pedido_id = ? ORDER BY i.id'
            );
            $consulta->execute([$pedidoId]);
            $itens = $consulta->fetchAll();
        }
    }
} catch (PDOException $erro) {
    error_log('GameVault: ' . $erro->getMessage());
    http_response_code(500);
    $erroPagina = 'Não foi possível carregar os pedidos.';
}

require __DIR__ . '/../templates/header.php';
require_once __DIR__ . '/_nav.php';
?>
<h1 class="h2 mb-4"><?= $pedidoId ? 'Pedido #' . e((string) $pedidoId) : 'Pedidos' ?></h1>
<?php if ($pedidoId !== null): ?>
    <a class="btn btn-outline-light mb-3" href="<?= e($pagina) ?>">Voltar aos pedidos</a>
<?php endif; ?>

<?php if ($erroPagina !== null): ?>
    <p class="alert alert-danger" role="alert"><?= e($erroPagina) ?></p>
<?php elseif ($pedidoId !== null): ?>
    <section class="vault-panel mb-4">
        <h2 class="h4">Dados do pedido</h2>
        <dl class="row mb-0">
            <dt class="col-sm-3">Cliente</dt>
            <dd class="col-sm-9"><?= e($pedido['cliente']) ?> (<?= e($pedido['email']) ?>)</dd>
            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9"><?= e($rotulos[$pedido['status']] ?? $pedido['status']) ?></dd>
            <dt class="col-sm-3">Total</dt>
            <dd class="col-sm-9"><?= e(moeda($pedido['total'])) ?></dd>
            <dt class="col-sm-3">Data</dt>
            <dd class="col-sm-9"><?= e(date('d/m/Y H:i', strtotime($pedido['data_pedido']))) ?></dd>
        </dl>
        <?php $opcoes = $transicoes[$pedido['status']] ?? []; ?>
        <?php if ($opcoes): ?>
            <form method="post" action="<?= e($pagina) ?>" class="d-flex flex-wrap align-items-end gap-2 mt-3">
                <?= csrf_campo() ?>
                <input type="hidden" name="pedido_id" value="<?= e((string) $pedidoId) ?>">
                <div>
                    <label class="form-label" for="status">Novo status</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="">Selecione</option>
                        <?php foreach ($opcoes as $opcao): ?>
                            <option value="<?= e($opcao) ?>"><?= e($rotulos[$opcao]) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-primary" type="submit">Atualizar status</button>
            </form>
        <?php endif; ?>
    </section>
    <section class="vault-panel">
        <h2 class="h4">Itens do pedido</h2>
        <?php if (!$itens): ?>
            <p class="mb-0">Nenhum item registrado.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Produto</th>
                            <th scope="col">Quantidade</th>
                            <th scope="col">Preço unitário</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itens as $item): ?>
                            <tr>
                                <th scope="row"><?= e($item['nome']) ?></th>
                                <td><?= e((string) $item['quantidade']) ?></td>
                                <td><?= e(moeda($item['preco_unitario'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php elseif (!$pedidos): ?>
    <p>Nenhum pedido registrado.</p>
<?php else: ?>
    <div class="vault-panel table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th scope="col">Pedido</th>
                    <th scope="col">Cliente</th>
                    <th scope="col">Status</th>
                    <th scope="col">Total</th>
                    <th scope="col">Data</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pedidos as $pedido): ?>
                    <tr>
                        <th scope="row">
                            <a href="<?= e($pagina . '?id=' . $pedido['id']) ?>">Pedido #<?= e((string) $pedido['id']) ?></a>
                        </th>
                        <td><?= e($pedido['cliente']) ?><br><small><?= e($pedido['email']) ?></small></td>
                        <td><?= e($rotulos[$pedido['status']] ?? $pedido['status']) ?></td>
                        <td class="text-nowrap"><?= e(moeda($pedido['total'])) ?></td>
                        <td class="text-nowrap"><?= e(date('d/m/Y H:i', strtotime($pedido['data_pedido']))) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../templates/footer.php'; ?>
