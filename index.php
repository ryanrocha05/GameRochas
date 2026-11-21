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

$titulo = 'Catálogo | GameRochas';
require __DIR__ . '/templates/header.php';
?>
<?php if (!$erroCatalogo && !$filtros && $pagina === 1): ?>
    <?php
    $arteDestaque = null;
    foreach (['webp', 'jpg', 'jpeg', 'png'] as $extensao) {
        $caminhoArte = 'assets/img/gta6-banner.' . $extensao;
        if (is_file(__DIR__ . '/' . $caminhoArte)) {
            $arteDestaque = $caminhoArte;
            break;
        }
    }
    ?>
    <section class="vault-feature mb-4" aria-labelledby="destaque-gta">
        <?php if ($arteDestaque !== null): ?>
            <div class="vault-feature-art" aria-hidden="true">
                <img src="<?= e(url($arteDestaque)) ?>" alt="">
            </div>
        <?php else: ?>
            <span class="vault-feature-mark" aria-hidden="true">VI</span>
        <?php endif; ?>
        <div class="vault-feature-content">
            <span class="vault-feature-label">Destaque</span>
            <h2 id="destaque-gta">GTA <span>6</span></h2>
            <p>De volta a Vice City. Explore as ruas de Leonida com Lucia e Jason.</p>
            <a class="btn btn-primary" href="<?= e(url('index.php?' . http_build_query(['busca' => 'GTA']))) ?>">Ver GTA no catálogo</a>
        </div>
    </section>
<?php endif; ?>
<h1 class="vault-catalog-title mb-3">Catálogo</h1>
<?php if ($erroCatalogo): ?>
    <p class="alert alert-danger" role="alert">Não foi possível carregar o catálogo.</p>
<?php else: ?>
    <form method="get" action="<?= e(url('index.php')) ?>" class="vault-filter mb-4">
        <div>
            <label for="busca" class="form-label">Buscar por nome ou descrição</label>
            <input type="search" name="busca" id="busca" class="form-control" value="<?= e($busca) ?>">
        </div>
        <div>
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
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-primary" type="submit">Filtrar</button>
            <?php if ($filtros): ?>
                <a class="btn btn-outline-light" href="<?= e(url('index.php')) ?>">Limpar</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (!$produtos): ?>
        <p class="alert alert-secondary">Nenhum produto encontrado.</p>
    <?php else: ?>
        <div class="vault-catalog-grid">
            <?php foreach ($produtos as $produto): ?>
                <?php
                $estoque = (int) $produto['estoque'];
                $detalhe = url('produto.php?' . http_build_query(['id' => $produto['id']] + $filtros + ['pagina' => $pagina]));
                ?>
                <article class="card vault-product-card">
                    <a class="d-block" href="<?= e($detalhe) ?>" aria-label="<?= e('Ver detalhes de ' . $produto['nome']) ?>">
                        <?php require __DIR__ . '/templates/capa.php'; ?>
                    </a>
                    <div class="card-body d-flex flex-column">
                        <p class="vault-category mb-1"><?= e($produto['categoria']) ?></p>
                        <h2 class="h5 mb-3"><a href="<?= e($detalhe) ?>"><?= e($produto['nome']) ?></a></h2>
                        <div class="vault-card-meta mt-auto mb-3">
                            <p class="vault-price h4 mb-0"><?= e(moeda($produto['preco'])) ?></p>
                            <span class="badge <?= $estoque > 0 ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                <?= $estoque > 0 ? 'Em estoque' : 'Esgotado' ?>
                            </span>
                        </div>
                        <form method="post" action="<?= e($detalhe) ?>">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="quantidade" value="1">
                            <input type="hidden" name="origem" value="catalogo">
                            <button class="btn btn-primary w-100" type="submit" <?= $estoque === 0 ? 'disabled' : '' ?>>
                                Adicionar ao carrinho
                            </button>
                        </form>
                    </div>
                </article>
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
