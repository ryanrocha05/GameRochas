<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Csrf.php';

function redirecionar_categorias(string $destino): never
{
    header('Location: ' . $destino, true, 303);
    exit;
}

$paginaCategorias = url('admin/categorias.php');
$post = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($post && !validar_csrf($_POST['csrf_token'] ?? null)) {
    exigir_admin();
    flash('warning', 'Solicitação inválida. Recarregue a página e tente novamente.');
    redirecionar_categorias($paginaCategorias);
}

require_once __DIR__ . '/_guard.php';

function salvar_categoria(PDO $db, ?int $id, mixed $entrada): void
{
    $nome = is_string($entrada) && mb_check_encoding($entrada, 'UTF-8')
        ? preg_replace('/^\s+|\s+$/u', '', $entrada)
        : '';
    if ($nome === '' || mb_strlen($nome, 'UTF-8') > 100) {
        flash('warning', 'Informe um nome de 1 a 100 caracteres.');
        return;
    }

    if ($id === null) {
        $consulta = $db->prepare('INSERT INTO categorias (nome) VALUES (?)');
        $consulta->execute([$nome]);
        flash('success', 'Categoria criada.');
        return;
    }

    $consulta = $db->prepare('UPDATE categorias SET nome = ? WHERE id = ?');
    $consulta->execute([$nome, $id]);
    if ($consulta->rowCount() === 0) {
        $consulta = $db->prepare('SELECT id FROM categorias WHERE id = ?');
        $consulta->execute([$id]);
        if ($consulta->fetchColumn() === false) {
            flash('warning', 'Categoria não encontrada.');
            return;
        }
    }
    flash('success', 'Categoria atualizada.');
}

function excluir_categoria(PDO $db, int $id): void
{
    $consulta = $db->prepare('DELETE FROM categorias WHERE id = ?');
    $consulta->execute([$id]);
    if ($consulta->rowCount() === 1) {
        flash('success', 'Categoria excluída.');
        return;
    }
    flash('warning', 'Categoria não encontrada.');
}

if ($post) {
    $acao = $_POST['acao'] ?? null;
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $destino = $paginaCategorias;
    try {
        if ($acao === 'criar') {
            salvar_categoria(Database::getConnection(), null, $_POST['nome'] ?? null);
        } elseif ($acao === 'editar' && $id) {
            $destino .= '?editar=' . $id;
            salvar_categoria(Database::getConnection(), $id, $_POST['nome'] ?? null);
        } elseif ($acao === 'excluir' && $id) {
            excluir_categoria(Database::getConnection(), $id);
        } else {
            flash('warning', 'Solicitação inválida.');
        }
    } catch (PDOException $erro) {
        $codigo = (int) ($erro->errorInfo[1] ?? 0);
        if ($acao === 'excluir' && $codigo === 1451) {
            flash('warning', 'Esta categoria possui produtos vinculados. Mova os produtos para outra categoria antes de excluí-la.');
        } elseif ($codigo === 1062) {
            flash('warning', 'Já existe uma categoria com esse nome.');
        } else {
            error_log('GameVault: ' . $erro->getMessage());
            flash('danger', 'Não foi possível alterar a categoria. Tente novamente.');
        }
    }
    redirecionar_categorias($destino);
}

$titulo = 'Categorias | GameVault';
$editar = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$categoriaEdicao = null;
$falha = false;
try {
    $consulta = Database::getConnection()->prepare(
        'SELECT c.id, c.nome, COUNT(p.id) AS total_produtos
         FROM categorias c LEFT JOIN produtos p ON p.categoria_id = c.id
         GROUP BY c.id, c.nome ORDER BY c.nome'
    );
    $consulta->execute();
    $categorias = $consulta->fetchAll();
    if ($editar) {
        foreach ($categorias as $categoria) {
            if ((int) $categoria['id'] === $editar) {
                $categoriaEdicao = $categoria;
                break;
            }
        }
        if ($categoriaEdicao === null) {
            flash('warning', 'Categoria não encontrada.');
            redirecionar($paginaCategorias);
        }
    }
} catch (PDOException $erro) {
    error_log('GameVault: ' . $erro->getMessage());
    http_response_code(500);
    $falha = true;
}

require __DIR__ . '/../templates/header.php';
require_once __DIR__ . '/_nav.php';
?>
<h1 class="h2 mb-4">Categorias</h1>
<?php if ($falha): ?>
    <p class="alert alert-danger" role="alert">Não foi possível carregar as categorias.</p>
<?php else: ?>
    <section class="vault-panel mb-4">
        <h2 class="h4"><?= $categoriaEdicao ? 'Editar categoria' : 'Nova categoria' ?></h2>
        <form method="post" action="<?= e($paginaCategorias) ?>">
            <?= csrf_campo() ?>
            <input type="hidden" name="acao" value="<?= $categoriaEdicao ? 'editar' : 'criar' ?>">
            <?php if ($categoriaEdicao): ?>
                <input type="hidden" name="id" value="<?= e((string) $categoriaEdicao['id']) ?>">
            <?php endif; ?>
            <label class="form-label" for="nome-categoria">Nome</label>
            <input class="form-control mb-3" type="text" id="nome-categoria" name="nome" required
                   value="<?= e($categoriaEdicao['nome'] ?? '') ?>" maxlength="100">
            <button class="btn btn-primary" type="submit">Salvar categoria</button>
            <?php if ($categoriaEdicao): ?>
                <a class="btn btn-outline-light" href="<?= e($paginaCategorias) ?>">Cancelar</a>
            <?php endif; ?>
        </form>
    </section>
    <section class="vault-panel">
        <h2 class="h4">Categorias cadastradas</h2>
        <?php if (!$categorias): ?>
            <p class="mb-0">Nenhuma categoria cadastrada.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Nome</th>
                            <th scope="col">Produtos vinculados</th>
                            <th scope="col">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $categoria): ?>
                            <tr>
                                <th scope="row"><?= e($categoria['nome']) ?></th>
                                <td><?= e((string) $categoria['total_produtos']) ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a class="btn btn-sm btn-outline-light"
                                           href="<?= e($paginaCategorias . '?editar=' . $categoria['id']) ?>">Editar</a>
                                        <form method="post" action="<?= e($paginaCategorias) ?>">
                                            <?= csrf_campo() ?>
                                            <input type="hidden" name="acao" value="excluir">
                                            <input type="hidden" name="id" value="<?= e((string) $categoria['id']) ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/../templates/footer.php'; ?>
