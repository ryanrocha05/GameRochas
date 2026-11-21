<?php

declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

$linksPainel = [
    'admin/index.php' => 'Dashboard',
    'admin/produtos.php' => 'Produtos',
    'admin/categorias.php' => 'Categorias',
    'admin/pedidos.php' => 'Pedidos',
    'index.php' => 'Voltar à Loja',
];
?>
<nav class="mb-4" aria-label="Navegação do painel administrativo">
    <ul class="nav flex-wrap gap-2">
        <?php foreach ($linksPainel as $caminho => $rotuloPainel): ?>
            <li class="nav-item">
                <a class="btn btn-outline-light btn-sm" href="<?= e(url($caminho)) ?>"><?= e($rotuloPainel) ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
