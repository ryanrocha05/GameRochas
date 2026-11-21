<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Csrf.php';

const PRODUTOS_UPLOAD = 'assets/img/uploads/';

function voltar_produtos(string $destino): never
{
    header('Location: ' . $destino, true, 303);
    exit;
}

$paginaProdutos = url('admin/produtos.php');
$post = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($post && !validar_csrf($_POST['csrf_token'] ?? null)) {
    exigir_admin();
    flash('warning', 'Solicitação inválida. Recarregue a página e tente novamente.');
    voltar_produtos($paginaProdutos);
}

require_once __DIR__ . '/_guard.php';

function guardar_imagem_produto(array $arquivo): ?string
{
    $erro = $arquivo['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($erro === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (in_array($erro, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
        throw new DomainException('A imagem deve ter no máximo 2 MB.');
    }
    $temporario = $arquivo['tmp_name'] ?? null;
    if ($erro !== UPLOAD_ERR_OK || !is_string($temporario) || !is_uploaded_file($temporario)) {
        throw new DomainException('Não foi possível receber a imagem.');
    }

    $tamanho = filesize($temporario);
    if ($tamanho === false || $tamanho < 1 || $tamanho > 2 * 1024 * 1024) {
        throw new DomainException('Envie uma imagem de até 2 MB.');
    }
    $extensoes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = finfo_file(new finfo(FILEINFO_MIME_TYPE), $temporario);
    $extensao = $extensoes[$mime] ?? null;
    if ($extensao === null) {
        throw new DomainException('Envie uma imagem JPEG, PNG ou WebP.');
    }

    $raiz = dirname(__DIR__) . '/';
    $diretorio = $raiz . PRODUTOS_UPLOAD;
    if (!is_dir($diretorio) && !@mkdir($diretorio, 0755, true) && !is_dir($diretorio)) {
        throw new RuntimeException('Falha ao criar o diretório de imagens.');
    }
    $protecao = <<<'APACHE'
Options -Indexes -ExecCGI
<FilesMatch "^[a-f0-9]{32}\.(?:jpg|png|webp)$">
    SetHandler default-handler
</FilesMatch>
<FilesMatch "^(?![a-f0-9]{32}\.(?:jpg|png|webp)$)">
    Require all denied
</FilesMatch>

APACHE;
    $htaccess = $diretorio . '.htaccess';
    if (!is_file($htaccess)) {
        $provisorio = $diretorio . '.protecao-' . bin2hex(random_bytes(16));
        if (@file_put_contents($provisorio, $protecao) !== strlen($protecao) || !@rename($provisorio, $htaccess)) {
            if (is_file($provisorio)) {
                @unlink($provisorio);
            }
            throw new RuntimeException('Falha ao proteger o diretório de imagens.');
        }
    }

    $imagem = PRODUTOS_UPLOAD . bin2hex(random_bytes(16)) . '.' . $extensao;
    if (!@move_uploaded_file($temporario, $raiz . $imagem)) {
        throw new RuntimeException('Falha ao salvar a imagem enviada.');
    }
    return $imagem;
}

function remover_imagem_produto(?string $imagem): void
{
    if ($imagem === null || !preg_match('~\Aassets/img/uploads/[a-f0-9]{32}\.(?:jpe?g|png|webp)\z~', $imagem)) {
        return;
    }
    $arquivo = dirname(__DIR__) . '/' . $imagem;
    if (is_file($arquivo) && !@unlink($arquivo)) {
        error_log('GameVault: falha ao remover imagem de produto ' . $imagem);
    }
}

function dados_produto(): array
{
    $nome = is_string($_POST['nome'] ?? null) ? trim($_POST['nome']) : '';
    $descricao = is_string($_POST['descricao'] ?? null) ? trim($_POST['descricao']) : '';
    $preco = is_string($_POST['preco'] ?? null) ? str_replace(',', '.', trim($_POST['preco'])) : '';
    $categoria = filter_input(INPUT_POST, 'categoria_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $estoque = filter_input(INPUT_POST, 'estoque', FILTER_VALIDATE_INT, ['options' => [
        'min_range' => 0,
        'max_range' => 4294967295,
    ]]);

    if ($nome === '' || !mb_check_encoding($nome, 'UTF-8') || mb_strlen($nome, 'UTF-8') > 150) {
        throw new DomainException('Informe um nome de 1 a 150 caracteres.');
    }
    if (!mb_check_encoding($descricao, 'UTF-8') || strlen($descricao) > 65535) {
        throw new DomainException('Informe uma descrição válida de até 65.535 bytes.');
    }
    if (!preg_match('/\A[0-9]{1,8}(?:\.[0-9]{1,2})?\z/', $preco)) {
        throw new DomainException('Informe um preço entre 0 e 99999999,99 com até duas casas decimais.');
    }
    if (!$categoria || !is_int($estoque)) {
        throw new DomainException('Selecione uma categoria e informe um estoque inteiro válido.');
    }
    return [$categoria, $nome, $descricao, $preco, $estoque];
}

function alterar_produto(PDO $db, string $acao, ?int $id): string
{
    $novaImagem = null;
    $imagemAntiga = null;
    $db->beginTransaction();
    try {
        if ($acao !== 'criar') {
            $consulta = $db->prepare('SELECT imagem FROM produtos WHERE id = ? FOR UPDATE');
            $consulta->execute([$id]);
            $produto = $consulta->fetch();
            if (!$produto) {
                throw new DomainException('Produto não encontrado.');
            }
            $imagemAntiga = $produto['imagem'];
        }

        switch ($acao) {
            case 'criar':
            case 'editar':
                $dados = dados_produto();
                $novaImagem = guardar_imagem_produto($_FILES['imagem'] ?? []);
                if ($acao === 'criar') {
                    $consulta = $db->prepare(
                        'INSERT INTO produtos (categoria_id, nome, descricao, preco, estoque, imagem)
                         VALUES (?, ?, ?, ?, ?, ?)'
                    );
                    $consulta->execute([...$dados, $novaImagem]);
                    $mensagem = 'Produto criado.';
                } else {
                    $consulta = $db->prepare(
                        'UPDATE produtos SET categoria_id = ?, nome = ?, descricao = ?, preco = ?, estoque = ?,
                         imagem = ? WHERE id = ?'
                    );
                    $consulta->execute([...$dados, $novaImagem ?? $imagemAntiga, $id]);
                    $mensagem = 'Produto atualizado.';
                }
                break;
            case 'excluir':
                $consulta = $db->prepare('DELETE FROM produtos WHERE id = ?');
                $consulta->execute([$id]);
                $mensagem = 'Produto excluído.';
                break;
            default:
                $ativo = $acao === 'ativar' ? 1 : 0;
                $consulta = $db->prepare('UPDATE produtos SET ativo = ? WHERE id = ?');
                $consulta->execute([$ativo, $id]);
                $mensagem = $ativo ? 'Produto ativado.' : 'Produto desativado.';
        }
        $db->commit();
    } catch (Throwable $erro) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        remover_imagem_produto($novaImagem);
        throw $erro;
    }

    if ($novaImagem !== null || $acao === 'excluir') {
        remover_imagem_produto($imagemAntiga);
    }
    return $mensagem;
}

if ($post) {
    $acao = $_POST['acao'] ?? null;
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $destino = $paginaProdutos;
    try {
        if (!in_array($acao, ['criar', 'editar', 'ativar', 'desativar', 'excluir'], true)
            || ($acao !== 'criar' && !$id)) {
            throw new DomainException('Solicitação inválida.');
        }
        if ($acao === 'editar') {
            $destino .= '?editar=' . $id;
        }
        flash('success', alterar_produto(Database::getConnection(), $acao, $id ?: null));
    } catch (DomainException $erro) {
        flash('warning', $erro->getMessage());
    } catch (Throwable $erro) {
        $codigo = $erro instanceof PDOException ? (int) ($erro->errorInfo[1] ?? 0) : 0;
        if ($acao === 'excluir' && $codigo === 1451) {
            flash('warning', 'Este produto está vinculado a pedidos. Desative-o para retirá-lo da loja.');
        } elseif ($codigo === 1452) {
            flash('warning', 'A categoria selecionada não existe mais.');
        } else {
            error_log('GameVault: ' . $erro->getMessage());
            flash('danger', 'Não foi possível alterar o produto. Tente novamente.');
        }
    }
    voltar_produtos($destino);
}

$titulo = 'Produtos | GameVault';
$editar = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$produtoEdicao = null;
$falha = false;
try {
    $db = Database::getConnection();
    $consulta = $db->prepare('SELECT id, nome FROM categorias ORDER BY nome');
    $consulta->execute();
    $categorias = $consulta->fetchAll();
    $consulta = $db->prepare(
        'SELECT p.id, p.nome, p.descricao, p.preco, p.estoque, p.ativo, p.imagem, p.categoria_id,
                c.nome AS categoria
         FROM produtos p JOIN categorias c ON c.id = p.categoria_id ORDER BY p.nome, p.id'
    );
    $consulta->execute();
    $produtos = $consulta->fetchAll();
    if ($editar) {
        foreach ($produtos as $produto) {
            if ((int) $produto['id'] === $editar) {
                $produtoEdicao = $produto;
                break;
            }
        }
        if ($produtoEdicao === null) {
            flash('warning', 'Produto não encontrado.');
            redirecionar($paginaProdutos);
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
<h1 class="h2 mb-4">Produtos</h1>
<?php if ($falha): ?>
    <p class="alert alert-danger" role="alert">Não foi possível carregar os produtos.</p>
<?php else: ?>
    <section class="vault-panel mb-4">
        <h2 class="h4"><?= $produtoEdicao ? 'Editar produto' : 'Novo produto' ?></h2>
        <?php if (!$categorias): ?>
            <p class="mb-0">Cadastre uma categoria antes de criar produtos.</p>
        <?php else: ?>
            <form method="post" action="<?= e($paginaProdutos) ?>" enctype="multipart/form-data">
                <?= csrf_campo() ?>
                <input type="hidden" name="acao" value="<?= $produtoEdicao ? 'editar' : 'criar' ?>">
                <?php if ($produtoEdicao): ?>
                    <input type="hidden" name="id" value="<?= e((string) $produtoEdicao['id']) ?>">
                <?php endif; ?>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="nome">Nome</label>
                        <input class="form-control" id="nome" name="nome" required maxlength="150"
                               value="<?= e($produtoEdicao['nome'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="categoria_id">Categoria</label>
                        <select class="form-select" id="categoria_id" name="categoria_id" required>
                            <option value="">Selecione</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= e((string) $categoria['id']) ?>"
                                    <?= (int) $categoria['id'] === (int) ($produtoEdicao['categoria_id'] ?? 0) ? 'selected' : '' ?>>
                                    <?= e($categoria['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="preco">Preço (R$)</label>
                        <input class="form-control" id="preco" name="preco" inputmode="decimal" maxlength="11"
                               value="<?= e(str_replace('.', ',', (string) ($produtoEdicao['preco'] ?? ''))) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="estoque">Estoque</label>
                        <input class="form-control" id="estoque" name="estoque" type="number" min="0" max="4294967295"
                               value="<?= e((string) ($produtoEdicao['estoque'] ?? 0)) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="descricao">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"><?= e($produtoEdicao['descricao'] ?? '') ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="imagem">Imagem opcional</label>
                        <input class="form-control" id="imagem" name="imagem" type="file"
                               accept="image/jpeg,image/png,image/webp" aria-describedby="imagem-ajuda">
                        <div class="form-text" id="imagem-ajuda">JPEG, PNG ou WebP de até 2 MB.</div>
                        <?php if (!empty($produtoEdicao['imagem'])): ?>
                            <img class="rounded mt-2" width="100" src="<?= e(url($produtoEdicao['imagem'])) ?>" alt="Imagem atual">
                        <?php endif; ?>
                    </div>
                </div>
                <button class="btn btn-primary" type="submit">Salvar produto</button>
                <?php if ($produtoEdicao): ?>
                    <a class="btn btn-outline-light" href="<?= e($paginaProdutos) ?>">Cancelar</a>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </section>
    <section class="vault-panel">
        <h2 class="h4">Produtos cadastrados</h2>
        <?php if (!$produtos): ?>
            <p class="mb-0">Nenhum produto cadastrado.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Nome</th>
                            <th scope="col">Categoria</th>
                            <th scope="col">Preço</th>
                            <th scope="col">Estoque</th>
                            <th scope="col">Status</th>
                            <th scope="col">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos as $produto): ?>
                            <tr>
                                <th scope="row"><?= e($produto['nome']) ?></th>
                                <td><?= e($produto['categoria']) ?></td>
                                <td><?= e(moeda($produto['preco'])) ?></td>
                                <td><?= e((string) $produto['estoque']) ?></td>
                                <td><?= $produto['ativo'] ? 'Ativo' : 'Inativo' ?></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a class="btn btn-sm btn-outline-light"
                                           href="<?= e($paginaProdutos . '?editar=' . $produto['id']) ?>">Editar</a>
                                        <form method="post" action="<?= e($paginaProdutos) ?>">
                                            <?= csrf_campo() ?>
                                            <input type="hidden" name="id" value="<?= e((string) $produto['id']) ?>">
                                            <button class="btn btn-sm btn-outline-light" name="acao"
                                                    value="<?= $produto['ativo'] ? 'desativar' : 'ativar' ?>" type="submit">
                                                <?= $produto['ativo'] ? 'Desativar' : 'Ativar' ?>
                                            </button>
                                        </form>
                                        <form method="post" action="<?= e($paginaProdutos) ?>">
                                            <?= csrf_campo() ?>
                                            <input type="hidden" name="id" value="<?= e((string) $produto['id']) ?>">
                                            <button class="btn btn-sm btn-outline-danger" name="acao"
                                                    value="excluir" type="submit">Excluir</button>
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
