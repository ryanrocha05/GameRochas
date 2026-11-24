<?php

declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    http_response_code(405);
    header('Allow: GET, HEAD');
    exit;
}

$titulo = 'Suporte | GameRochas';
$secaoLoja = 'suporte';
require __DIR__ . '/templates/header.php';
?>
<div class="vault-guide">
    <header class="vault-guide-intro">
        <h1>Suporte</h1>
        <p>Consulte seus pedidos e tire dúvidas sobre acesso à conta, estoque e formas de pagamento.</p>
        <nav aria-label="Atalhos de atendimento">
            <a href="<?= e(url('meus-pedidos.php')) ?>">Consultar meus pedidos</a>
            <a href="<?= e(url('carrinho.php')) ?>">Revisar meu carrinho</a>
        </nav>
    </header>
    <section class="vault-guide-body" aria-labelledby="duvidas">
        <h2 id="duvidas">Dúvidas frequentes</h2>
        <details class="vault-question" open>
            <summary>Onde vejo os detalhes do meu pedido?</summary>
            <p>Entre na conta usada na compra e abra Meus pedidos. Selecione o número do pedido para ver a data, os jogos, o preço de cada item e o valor total.</p>
        </details>
        <details class="vault-question">
            <summary>Em que momento preciso entrar na conta?</summary>
            <p>Você pode escolher os jogos e montar o carrinho antes de entrar. Para finalizar o pedido, use seu e-mail e senha ou selecione Criar conta. Depois, use a mesma conta para consultar seu histórico.</p>
        </details>
        <details class="vault-question">
            <summary>Adicionar um jogo ao carrinho garante a reserva?</summary>
            <p>O estoque é verificado quando você confirma o pedido. Até lá, a disponibilidade pode mudar. Se a quantidade desejada estiver indisponível, ajuste os itens no carrinho e confira o total antes de concluir a compra.</p>
        </details>
        <details class="vault-question">
            <summary>Como confiro o status e a forma de pagamento?</summary>
            <p>Na finalização, escolha Pix, boleto ou cartão. Depois de confirmar, abra o pedido em Meus pedidos. Os campos Status e Forma de pagamento mostram a situação do pedido na loja e a opção que você escolheu.</p>
        </details>
        <details class="vault-question">
            <summary>Confirmei a compra, mas o pedido ainda não apareceu. E agora?</summary>
            <p>Verifique se você entrou na conta usada na compra e atualize Meus pedidos. Procure pela data e pelo valor total. Se encontrar o pedido, abra os detalhes. Se ele continuar ausente, volte ao carrinho e confira se há algum item ou quantidade a revisar antes de tentar novamente.</p>
        </details>
        <p class="vault-guide-note">Saiba como cuidamos dos dados da sua conta na <a href="<?= e(url('privacidade.php')) ?>">política de privacidade</a>.</p>
    </section>
</div>
<?php require __DIR__ . '/templates/footer.php'; ?>
