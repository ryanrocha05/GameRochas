<div class="vault-cover">
    <?php if (!empty($produto['imagem'])): ?>
        <img src="<?= e(url($produto['imagem'])) ?>" alt="<?= e('Capa de ' . $produto['nome']) ?>"
             onerror="this.hidden = true">
    <?php endif; ?>
    <span class="vault-cover-title" aria-hidden="true"><?= e($produto['nome']) ?></span>
</div>
