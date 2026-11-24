# GameRochas

Loja de jogos feita em PHP com MariaDB/MySQL (via PDO) e Bootstrap 5, rodando em ambiente XAMPP. Tem catálogo com busca e filtro por categoria, carrinho na sessão, checkout com Pix, boleto ou cartão, histórico de pedidos e painel administrativo para produtos, estoque e pedidos.

## Como rodar localmente

Coloque a pasta do projeto (`gamevault`) dentro de `C:\xampp\htdocs` e suba o Apache e o MySQL no XAMPP. A aplicação usa PHP 8.x com `pdo_mysql`, `mbstring` e `fileinfo` ativos.

Crie o `.env` a partir do exemplo:

```powershell
Copy-Item .env.example .env

```

Ajuste as variáveis no `.env` conforme seu ambiente:

* `DB_HOST`: host do banco (ex.: `localhost`)
* `DB_PORT`: porta (ex.: `3306`)
* `DB_NAME`: `gamevault`
* `DB_USER` e `DB_PASS`: credenciais do banco
* `APP_URL`: URL base da aplicação (ex.: `http://localhost/gamevault`)

## Banco de dados e acesso admin

No phpMyAdmin, importe `database/schema.sql` (em UTF-8). Ele cria o banco `gamevault`, as tabelas e carrega 6 categorias e 16 produtos iniciais.

Para acessar `/admin`, cadastre uma conta comum pela interface da loja e depois promova o usuário no banco:

```sql
UPDATE usuarios
SET perfil = 'admin'
WHERE email = 'seu-email@exemplo.com';

```

Se já estiver logado, saia e entre novamente para atualizar a sessão antes de abrir `/admin/`.

## Conferência rápida do fluxo

1. Busque um jogo com pelo menos 2 unidades em estoque, abra a página dele e anote preço e quantidade disponível.
2. Adicione ao carrinho, suba a quantidade para 2 e atualize. Confira se subtotal e total dobram.
3. Vá ao checkout (faça login se necessário), escolha Pix, boleto ou cartão e finalize. Verifique o pedido em **Meus pedidos** (status pago, quantidade, preço unitário e total).
4. Confirme que o carrinho ficou vazio e que o estoque diminuiu em 2 unidades no painel.
5. Edite apenas o preço do produto no painel: o catálogo deve refletir o valor novo, enquanto o pedido já feito mantém o preço unitário congelado da época da compra.



http://localhost/gamevault
