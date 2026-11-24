<?php

declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    http_response_code(405);
    header('Allow: GET, HEAD');
    exit;
}

$titulo = 'Privacidade | GameRochas';
$secaoLoja = 'privacidade';
require __DIR__ . '/templates/header.php';
?>
<article class="vault-guide">
    <header class="vault-guide-intro">
        <h1>Privacidade</h1>
        <p>Veja quais dados a GameRochas usa para identificar sua conta, manter o carrinho e registrar suas compras.</p>
        <nav aria-label="Nesta política">
            <a href="#conta">Dados da conta</a>
            <a href="#navegacao">Sessão e navegação</a>
            <a href="#pedidos">Histórico de compras</a>
            <a href="#recursos">Recursos externos</a>
        </nav>
    </header>
    <div class="vault-guide-body">
        <section id="conta">
            <h2>Dados da conta</h2>
            <p>Usamos seu e-mail para identificar a conta no login. Na finalização do pedido, exibimos seu nome e e-mail para que você confira os dados do comprador. Para validar a senha, armazenamos um código de verificação derivado dela (hash).</p>
        </section>
        <section id="navegacao">
            <h2>Sessão e navegação</h2>
            <p>Um cookie identifica sua sessão enquanto você navega pela loja. Ele permite manter a conta conectada e recuperar os itens do carrinho ao mudar de página. A sessão também ajuda a verificar os envios de formulários. Ao selecionar Sair, você encerra a sessão e limpa o carrinho.</p>
            <p>Os termos de busca e os filtros aparecem no endereço do catálogo e podem ficar no histórico do navegador. Você pode apagar esse histórico nas configurações do navegador.</p>
        </section>
        <section id="pedidos">
            <h2>Histórico de compras</h2>
            <p>Ao confirmar uma compra, registramos na sua conta a data do pedido, os jogos, as quantidades, os preços, o total, o status e a forma de pagamento escolhida. Você encontra essas informações em Meus pedidos. A equipe administrativa pode consultá-las para gerenciar os pedidos da loja.</p>
            <p>O pedido registra se você escolheu Pix, boleto ou cartão. O formulário não envia à GameRochas o número do cartão, a validade nem o código de segurança.</p>
        </section>
        <section id="recursos">
            <h2>Recursos externos</h2>
            <p>Seu navegador busca a fonte Inter no Google Fonts e os arquivos de estilo e de navegação do Bootstrap no jsDelivr. Ao solicitar esses arquivos, o navegador informa a esses serviços o endereço IP da conexão e dados técnicos da requisição.</p>
        </section>
        <p class="vault-guide-note">Para consultar um pedido ou tirar dúvidas sobre a conta, <a href="<?= e(url('suporte.php')) ?>">acesse o suporte</a>.</p>
    </div>
</article>
<?php require __DIR__ . '/templates/footer.php'; ?>
