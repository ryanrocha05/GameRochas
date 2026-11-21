<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/Csrf.php';

$email = '';
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validar_csrf($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Solicitação inválida. Recarregue a página e tente novamente.');
    }

    $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
    $senha = is_string($_POST['senha'] ?? null) ? $_POST['senha'] : '';
    if (filter_var($email, FILTER_VALIDATE_EMAIL) && $senha !== '' && login($email, $senha)) {
        $destino = $_SESSION['destino_login'] ?? 'index.php';
        unset($_SESSION['destino_login']);
        redirecionar(url(usuario_logado()['perfil'] === 'admin' ? 'admin/' : $destino));
    }

    $erro = 'E-mail ou senha inválidos.';
}

$titulo = 'Entrar | GameRochas';
require __DIR__ . '/templates/header.php';
?>
<section class="vault-panel mx-auto col-md-7 col-lg-5">
    <h1 class="h2 mb-4">Entrar</h1>
    <?php if ($erro !== ''): ?>
        <div class="alert alert-danger" role="alert"><?= e($erro) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('login.php')) ?>">
        <?= csrf_campo() ?>
        <div class="mb-3">
            <label class="form-label" for="email">E-mail</label>
            <input class="form-control" id="email" name="email" type="email" autocomplete="username"
                   maxlength="254" value="<?= e($email) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="senha">Senha</label>
            <input class="form-control" id="senha" name="senha" type="password" autocomplete="current-password" required>
        </div>
        <button class="btn btn-primary" type="submit">Entrar</button>
        <a class="btn btn-link" href="<?= e(url('cadastro.php')) ?>">Criar conta</a>
    </form>
</section>
<?php require __DIR__ . '/templates/footer.php'; ?>
