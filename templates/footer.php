    </main>
    <footer class="vault-footer">
        <div class="container vault-footer-grid">
            <div class="vault-footer-identity">
                <a class="vault-brand" href="<?= e(url()) ?>">
                    <img src="<?= e(url('assets/img/favicon.png')) ?>" alt="" width="38" height="38">
                    <span>Game<span class="vault-brand-accent">Rochas</span></span>
                </a>
                <p>Plataforma digital de jogos</p>
            </div>
            <nav aria-label="Loja no rodapé">
                <h2>Loja</h2>
                <a href="<?= e(url('index.php')) ?>">Catálogo de jogos</a>
                <a href="<?= e(url('carrinho.php')) ?>">Carrinho</a>
            </nav>
            <nav aria-label="Conta no rodapé">
                <h2>Conta</h2>
                <a href="<?= e(url('meus-pedidos.php')) ?>">Meus pedidos</a>
                <?php if (usuario_logado() === null): ?>
                    <a href="<?= e(url('login.php')) ?>">Entrar</a>
                    <a href="<?= e(url('cadastro.php')) ?>">Criar conta</a>
                <?php endif; ?>
            </nav>
            <nav aria-label="Ajuda no rodapé">
                <h2>Ajuda e informações</h2>
                <a href="<?= e(url('suporte.php')) ?>">Suporte</a>
                <a href="<?= e(url('privacidade.php')) ?>">Privacidade</a>
            </nav>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
