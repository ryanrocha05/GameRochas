<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Csrf.php';

$usuario = usuario_logado();
$quantidade = array_sum($_SESSION['carrinho'] ?? []);
$busca = is_string($_GET['busca'] ?? null) ? $_GET['busca'] : '';
?>
<header class="vault-navbar">
    <div class="container vault-masthead">
        <a class="vault-brand" href="<?= e(url()) ?>">
            <img src="<?= e(url('assets/img/favicon.png')) ?>" alt="" width="38" height="38">
            <span>Game<span class="vault-brand-accent">Rochas</span></span>
        </a>
        <form class="vault-search" role="search" method="get" action="<?= e(url()) ?>">
            <label class="visually-hidden" for="busca-jogos">Buscar jogos</label>
            <input class="form-control" id="busca-jogos" name="busca" type="search"
                   placeholder="Buscar jogos" value="<?= e($busca) ?>">
            <button type="submit" aria-label="Buscar jogos">
                <svg class="vault-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg>
            </button>
        </form>
        <nav class="vault-actions" aria-label="Carrinho e conta">
            <a class="vault-cart-link" href="<?= e(url('carrinho.php')) ?>">
                <svg class="vault-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 4h3l3 11h11l2-8H6"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
                <span class="vault-cart-label">Carrinho</span><span class="vault-badge"><?= e((string) $quantidade) ?></span>
            </a>
            <?php if ($usuario !== null): ?>
                <details class="vault-account">
                    <summary><span><?= e($usuario['nome']) ?></span></summary>
                    <div class="vault-account-links">
                        <a href="<?= e(url('meus-pedidos.php')) ?>">Meus pedidos</a>
                        <?php if ($usuario['perfil'] === 'admin'): ?>
                            <a href="<?= e(url('admin/')) ?>">Painel</a>
                        <?php endif; ?>
                        <form method="post" action="<?= e(url('logout.php')) ?>">
                            <?= csrf_campo() ?>
                            <button type="submit">Sair</button>
                        </form>
                    </div>
                </details>
            <?php else: ?>
                <a class="vault-signup" href="<?= e(url('cadastro.php')) ?>">Criar conta</a>
                <a class="vault-login" href="<?= e(url('login.php')) ?>">Entrar</a>
            <?php endif; ?>
        </nav>
    </div>
    <nav class="vault-rail" aria-label="Navegação principal">
        <div class="container">
            <a href="<?= e(url('index.php')) ?>"<?= ($secaoLoja ?? '') === 'catalogo' ? ' aria-current="page"' : '' ?>>Catálogo</a>
            <a href="<?= e(url('suporte.php')) ?>"<?= ($secaoLoja ?? '') === 'suporte' ? ' aria-current="page"' : '' ?>>Suporte</a>
            <a href="<?= e(url('privacidade.php')) ?>"<?= ($secaoLoja ?? '') === 'privacidade' ? ' aria-current="page"' : '' ?>>Privacidade</a>
        </div>
    </nav>
</header>
