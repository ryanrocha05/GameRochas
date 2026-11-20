'use strict';

const moedaCarrinho = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

document.querySelectorAll('tr[data-preco]').forEach((linha) => {
    const quantidade = linha.querySelector('input[name="quantidade"]');
    const subtotal = linha.querySelector('[data-subtotal]');
    const preco = Number(linha.dataset.preco);

    quantidade.addEventListener('input', () => {
        subtotal.textContent = quantidade.validity.valid
            ? moedaCarrinho.format(preco * quantidade.valueAsNumber)
            : '—';
    });
});
