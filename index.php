<?php

declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/Csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    http_response_code(405);
    header('Allow: GET, HEAD');
    exit;
}

$categoria = filter_input(INPUT_GET, 'categoria', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
$busca = trim((string) filter_input(INPUT_GET, 'busca'));
$pagina = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
$porPagina = 12;
$filtros = [];
$condicoes = ['p.ativo = 1'];
$parametros = [];

if ($categoria !== null) {
    $filtros['categoria'] = $categoria;
    $condicoes[] = 'p.categoria_id = :categoria';
    $parametros['categoria'] = $categoria;
}
if ($busca !== '') {
    $filtros['busca'] = $busca;
    $condicoes[] = "(p.nome LIKE :nome ESCAPE '!' OR p.descricao LIKE :descricao ESCAPE '!')";
    $termo = '%' . strtr($busca, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
    $parametros['nome'] = $parametros['descricao'] = $termo;
}

$erroCatalogo = false;
try {
    $pdo = Database::getConnection();
    $consultaCategorias = $pdo->prepare('SELECT id, nome FROM categorias ORDER BY nome, id');
    $consultaCategorias->execute();
    $categorias = $consultaCategorias->fetchAll();

    $where = implode(' AND ', $condicoes);
    $contagem = $pdo->prepare("SELECT COUNT(*) FROM produtos p WHERE $where");
    $contagem->execute($parametros);
    $totalPaginas = max(1, (int) ceil((int) $contagem->fetchColumn() / $porPagina));
    $pagina = min($pagina, $totalPaginas);

    $consulta = $pdo->prepare(
        "SELECT p.id, p.nome, p.preco, p.estoque, p.imagem, c.nome AS categoria
         FROM produtos p JOIN categorias c ON c.id = p.categoria_id
         WHERE $where ORDER BY p.nome, p.id LIMIT :limite OFFSET :deslocamento"
    );
    foreach ($parametros as $nome => $valor) {
        $consulta->bindValue($nome, $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $consulta->bindValue('limite', $porPagina, PDO::PARAM_INT);
    $consulta->bindValue('deslocamento', ($pagina - 1) * $porPagina, PDO::PARAM_INT);
    $consulta->execute();
    $produtos = $consulta->fetchAll();
} catch (PDOException $erro) {
    error_log('GameVault: ' . $erro->getMessage());
    http_response_code(500);
    $erroCatalogo = true;
}

$fallback = 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="640" height="360"><rect width="100%" height="100%" fill="#151a2a"/><text x="50%" y="50%" fill="#a9b3cd" text-anchor="middle" font-family="sans-serif" font-size="24">Imagem indisponível</text></svg>');
$titulo = 'Catálogo | GameVault';
require __DIR__ . '/templates/header.php';
?>
<h1 class="mb-4">Catálogo</h1>
<?php if ($erroCatalogo): ?>
    <p class="alert alert-danger" role="alert">Não foi possível carregar o catálogo.</p>
<?php else: ?>
    <form method="get" action="<?= e(url('index.php')) ?>" class="row g-3 align-items-end mb-4">
        <div class="col-md-5">
            <label for="busca" class="form-label">Buscar por nome ou descrição</label>
            <input type="search" name="busca" id="busca" class="form-control" value="<?= e($busca) ?>">
        </div>
        <div class="col-md-4">
            <label for="categoria" class="form-label">Categoria</label>
            <select name="categoria" id="categoria" class="form-select">
                <option value="">Todas as categorias</option>
                <?php foreach ($categorias as $opcao): ?>
                    <option value="<?= e((string) $opcao['id']) ?>" <?= (int) $opcao['id'] === $categoria ? 'selected' : '' ?>>
                        <?= e($opcao['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-auto">
            <button class="btn btn-primary" type="submit">Filtrar</button>
            <?php if ($filtros): ?>
                <a class="btn btn-outline-light" href="<?= e(url('index.php')) ?>">Limpar</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (!$produtos): ?>
        <p class="alert alert-secondary">Nenhum produto encontrado.</p>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-4">
            <?php foreach ($produtos as $produto): ?>
                <?php
                $estoque = (int) $produto['estoque'];
                $detalhe = url('produto.php?' . http_build_query(['id' => $produto['id']] + $filtros + ['pagina' => $pagina]));
                $imagem = empty($produto['imagem']) ? $fallback : url($produto['imagem']);
                ?>
                <div class="col">
                    <article class="card h-100">
                        <img class="card-img-top object-fit-cover" style="aspect-ratio: 16 / 9"
                             src="<?= e($imagem) ?>" alt="<?= e('Capa de ' . $produto['nome']) ?>"
                             data-fallback="<?= e($fallback) ?>" onerror="this.onerror=null; this.src=this.dataset.fallback">
                        <div class="card-body d-flex flex-column">
                            <p class="text-body-secondary mb-1"><?= e($produto['categoria']) ?></p>
                            <h2 class="h5"><a href="<?= e($detalhe) ?>" class="link-light"><?= e($produto['nome']) ?></a></h2>
                            <p class="fw-bold mb-2"><?= e(moeda($produto['preco'])) ?></p>
                            <p class="mb-3">
                                <span class="badge <?= $estoque > 0 ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                    <?= $estoque > 0 ? 'Em estoque' : 'Esgotado' ?>
                                </span>
                            </p>
                            <form method="post" action="<?= e($detalhe) ?>" class="mt-auto">
                                <?= csrf_campo() ?>
                                <input type="hidden" name="quantidade" value="1">
                                <input type="hidden" name="origem" value="catalogo">
                                <button class="btn btn-primary w-100" type="submit" <?= $estoque === 0 ? 'disabled' : '' ?>>
                                    Adicionar ao carrinho
                                </button>
                            </form>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($totalPaginas > 1): ?>
        <nav class="mt-4" aria-label="Paginação do catálogo">
            <ul class="pagination flex-wrap align-items-center">
                <?php if ($pagina > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= e(url('index.php?' . http_build_query($filtros + ['pagina' => $pagina - 1]))) ?>">Anterior</a>
                    </li>
                <?php endif; ?>
                <li class="page-item active" aria-current="page">
                    <span class="page-link">Página <?= e((string) $pagina) ?> de <?= e((string) $totalPaginas) ?></span>
                </li>
                <?php if ($pagina < $totalPaginas): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= e(url('index.php?' . http_build_query($filtros + ['pagina' => $pagina + 1]))) ?>">Próxima</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/templates/footer.php'; ?>
