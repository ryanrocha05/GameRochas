<!doctype html>
<html lang="pt-BR" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo ?? 'GameRochas') ?></title>
    <link rel="icon" type="image/png" href="<?= e(url('assets/img/favicon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&amp;display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          data-local="<?= e(url('assets/css/bootstrap-5.3.3.min.css')) ?>" onerror="this.onerror = null; this.href = this.dataset.local">
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css?v=' . filemtime(__DIR__ . '/../assets/css/style.css'))) ?>">
</head>
<body>
    <a class="vault-skip" href="#conteudo">Pular para o conteúdo</a>
    <?php require __DIR__ . '/navbar.php'; ?>
    <main id="conteudo" tabindex="-1" class="container flex-grow-1 vault-main">
        <?php exibir_flash(); ?>
