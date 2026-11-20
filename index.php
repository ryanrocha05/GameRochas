<?php

declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';

Database::getConnection();

$titulo = 'GameVault';
require __DIR__ . '/templates/header.php';
?>
<section class="vault-panel">
    <h1 class="h2 mb-0">Conexão OK</h1>
</section>
<?php require __DIR__ . '/templates/footer.php'; ?>
