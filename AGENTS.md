# GameVault

## Stack

- Front-end com HTML5, CSS3, Bootstrap 5 via CDN e JavaScript puro.
- Back-end com PHP 8.x puro, sem framework e sem Composer, executado em Apache (XAMPP).
- Banco de dados MySQL ou MariaDB, acessado via PDO.

## Estrutura planejada

```text
/
├── index.php
├── produto.php
├── carrinho.php
├── checkout.php
├── login.php
├── cadastro.php
├── logout.php
├── meus-pedidos.php
├── pedido-sucesso.php
├── .env.example
├── .gitignore
├── README.md
├── AGENTS.md
├── admin/
│   ├── index.php
│   ├── produtos.php
│   ├── produto-form.php
│   ├── produto-excluir.php
│   ├── pedidos.php
│   └── pedido-detalhe.php
├── src/
│   ├── env.php
│   ├── Database.php
│   ├── helpers.php
│   ├── Auth.php
│   ├── Cart.php
│   └── Csrf.php
├── templates/
│   ├── header.php
│   ├── footer.php
│   └── navbar.php
├── assets/
│   ├── css/
│   ├── js/
│   └── img/
├── uploads/
│   └── produtos/
└── database/
    └── schema.sql
```

## Segurança

- Leia as credenciais exclusivamente de `.env` e nunca as fixe no código.
- Use prepared statements em todas as consultas e comandos SQL.
- Armazene senhas com `password_hash()` e valide-as com `password_verify()`.
- Valide a sessão e o perfil de administrador no servidor em todas as rotas de `/admin`.
- Escape os dados exibidos em HTML com `e()`, que deve usar `htmlspecialchars()`.
- Valide o token CSRF em toda requisição POST.
- Execute `session_regenerate_id(true)` após o login bem-sucedido.
- Valide a extensão, o MIME com `finfo` e o tamanho dos uploads. Salve os arquivos com nomes aleatórios.
- Registre detalhes técnicos apenas com `error_log()` e exiba uma mensagem genérica ao usuário.

## Convenções

- Use `snake_case` sem acentos nos nomes de tabelas e colunas. As tabelas incluem `usuarios`, `categorias`, `produtos`, `pedidos` e `itens_pedido`.
- Escreva a interface em português do Brasil (pt-BR).
- Exiba valores em R$ com `number_format()` e armazene-os no banco como `DECIMAL(10,2)`.
- Mantenha as funções curtas e elimine código morto.

## Entrega

- Faça entregas pequenas e testáveis.
- Ao final de cada entrega, liste os arquivos criados ou alterados, explique como testar e sugira uma mensagem de commit no padrão Conventional Commits, com a descrição em pt-BR.
