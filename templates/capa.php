<div class="vault-cover">
    <?php if (!empty($produto['imagem'])): ?>
        <img src="<?= e(url($produto['imagem'])) ?>" alt="<?= e('Capa de ' . $produto['nome']) ?>"
             onerror="this.hidden = true">
    <?php endif; ?>
    <div class="vault-cover-placeholder" aria-hidden="true">
        <svg viewBox="0 0 64 40" fill="none" stroke="currentColor" stroke-width="2.5"
             stroke-linecap="round" stroke-linejoin="round" focusable="false">
            <path d="M20 7h24c6 0 10 4 12 11l3 12c1 6-5 9-9 5l-7-7H21l-7 7c-4 4-10 1-9-5l3-12c2-7 6-11 12-11Z"/>
            <path d="M19 13v10m-5-5h10"/>
            <circle cx="42" cy="20" r="1.5"/>
            <circle cx="48" cy="15" r="1.5"/>
        </svg>
    </div>
</div>
