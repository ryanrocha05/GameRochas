<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Csrf.php';

$usuario = usuario_logado();
$quantidade = array_sum($_SESSION['carrinho'] ?? []);
$busca = is_string($_GET['busca'] ?? null) ? $_GET['busca'] : '';
?>
<nav class="navbar navbar-expand-lg vault-navbar" aria-label="Navegação principal">
    <div class="container gap-3">
        <a class="navbar-brand" href="<?= e(url()) ?>">Game<span>Rochas</span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu-principal"
                aria-controls="menu-principal" aria-expanded="false" aria-label="Alternar navegação">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse gap-4" id="menu-principal">
            <form class="vault-search d-flex gap-2 me-lg-auto my-3 my-lg-0" role="search" method="get" action="<?= e(url()) ?>">
                <label class="visually-hidden" for="busca-jogos">Buscar jogos</label>
                <input class="form-control" id="busca-jogos" name="busca" type="search"
                       placeholder="Buscar jogos" value="<?= e($busca) ?>">
                <button class="btn btn-primary" type="submit">Buscar</button>
            </form>
            <ul class="navbar-nav align-items-lg-center gap-lg-2">
                <li class="nav-item">
                    <a class="nav-link" href="<?= e(url('carrinho.php')) ?>">
                        Carrinho <span class="badge vault-badge"><?= e((string) $quantidade) ?></span>
                    </a>
                </li>
                <?php if ($usuario !== null): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('meus-pedidos.php')) ?>">Meus Pedidos</a></li>
                    <li class="nav-item"><span class="navbar-text"><?= e($usuario['nome']) ?></span></li>
                    <?php if ($usuario['perfil'] === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= e(url('admin/')) ?>">Painel</a></li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <form method="post" action="<?= e(url('logout.php')) ?>">
                            <?= csrf_campo() ?>
                            <button class="nav-link" type="submit">Sair</button>
                        </form>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('login.php')) ?>">Entrar</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('cadastro.php')) ?>">Cadastrar</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
