<?php

declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/Csrf.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$categoria = filter_input(INPUT_GET, 'categoria', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$busca = trim((string) filter_input(INPUT_GET, 'busca'));
$pagina = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$filtros = array_filter(['categoria' => $categoria, 'pagina' => $pagina]);
if ($busca !== '') {
    $filtros['busca'] = $busca;
}
$voltar = url('index.php' . ($filtros ? '?' . http_build_query($filtros) : ''));
$detalhe = url('produto.php?' . http_build_query(['id' => $id] + $filtros));

$produto = false;
$mensagem = 'Este produto está indisponível no catálogo.';
$titulo = 'Produto não encontrado';
$post = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($post && !validar_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    $titulo = 'Solicitação inválida';
    $mensagem = 'Volte ao catálogo e tente adicionar o produto novamente.';
} else {
    try {
        if ($id) {
            $consulta = Database::getConnection()->prepare(
                'SELECT p.nome, p.descricao, p.preco, p.estoque, p.imagem, c.nome AS categoria
                 FROM produtos p JOIN categorias c ON c.id = p.categoria_id
                 WHERE p.id = :id AND p.ativo = 1'
            );
            $consulta->bindValue('id', $id, PDO::PARAM_INT);
            $consulta->execute();
            $produto = $consulta->fetch();
        }
        if (!$produto) {
            http_response_code(404);
        }
    } catch (PDOException $erro) {
        error_log('GameVault: ' . $erro->getMessage());
        http_response_code(500);
        $titulo = 'Produto indisponível';
        $mensagem = 'Não foi possível carregar o produto.';
    }
}

if ($produto) {
    $estoque = (int) $produto['estoque'];
    $noCarrinho = (int) ($_SESSION['carrinho'][$id] ?? 0);
    $disponivel = max(0, $estoque - $noCarrinho);

    if ($post) {
        $quantidade = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$quantidade || $quantidade > $disponivel) {
            flash('warning', 'A quantidade solicitada deve respeitar o estoque disponível, considerando os itens no carrinho.');
        } else {
            $_SESSION['carrinho'][$id] = $noCarrinho + $quantidade;
            flash('success', 'Produto adicionado ao carrinho.');
        }
        header('Location: ' . (($_POST['origem'] ?? null) === 'catalogo' ? $voltar : $detalhe), true, 303);
        exit;
    }

    $titulo = $produto['nome'];
    [$status, $cor] = match (true) {
        $estoque === 0 => ['Esgotado', 'text-bg-secondary'],
        $estoque <= 5 => ["Últimas $estoque unidades", 'text-bg-warning'],
        default => ['Em estoque', 'text-bg-success'],
    };
    $fallback = 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="640" height="360"><rect width="100%" height="100%" fill="#151a2a"/><text x="50%" y="50%" fill="#a9b3cd" text-anchor="middle" font-family="sans-serif" font-size="24">Imagem indisponível</text></svg>');
    $imagem = empty($produto['imagem']) ? $fallback : url($produto['imagem']);
}

require __DIR__ . '/templates/header.php';
?>
<a class="btn btn-outline-light mb-4" href="<?= e($voltar) ?>">Voltar ao catálogo</a>
<?php if (!$produto): ?>
    <section class="vault-panel">
        <h1 class="h2"><?= e($titulo) ?></h1>
        <p class="mb-0"><?= e($mensagem) ?></p>
    </section>
<?php else: ?>
    <article class="row g-4">
        <div class="col-lg-6">
            <img class="img-fluid rounded w-100" src="<?= e($imagem) ?>" alt="<?= e('Capa de ' . $produto['nome']) ?>"
                 data-fallback="<?= e($fallback) ?>" onerror="this.onerror=null; this.src=this.dataset.fallback">
        </div>
        <div class="col-lg-6">
            <p class="text-body-secondary mb-1"><?= e($produto['categoria']) ?></p>
            <h1><?= e($produto['nome']) ?></h1>
            <p class="fs-4 fw-bold"><?= e(moeda($produto['preco'])) ?></p>
            <p><span class="badge <?= e($cor) ?>"><?= e($status) ?></span></p>
            <p><?= nl2br(e($produto['descricao'] ?? 'Descrição indisponível.')) ?></p>
            <form method="post" action="<?= e($detalhe) ?>">
                <?= csrf_campo() ?>
                <?php if ($disponivel > 0): ?>
                    <div class="mb-3">
                        <label class="form-label" for="quantidade">Quantidade</label>
                        <input class="form-control w-auto" type="number" id="quantidade" name="quantidade"
                               min="1" max="<?= e((string) $disponivel) ?>" value="1" required>
                    </div>
                <?php elseif ($estoque > 0): ?>
                    <p class="text-body-secondary">Você já adicionou ao carrinho toda a quantidade disponível.</p>
                <?php endif; ?>
                <button class="btn btn-primary" type="submit" <?= $disponivel === 0 ? 'disabled' : '' ?>>
                    Adicionar ao carrinho
                </button>
            </form>
        </div>
    </article>
<?php endif; ?>
<?php require __DIR__ . '/templates/footer.php'; ?>
