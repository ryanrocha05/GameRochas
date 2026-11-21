<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/Csrf.php';

function cadastrar_cliente(string $nome, string $email, string $senha): ?string
{
    $emailDuplicado = 'Já existe uma conta com este e-mail.';

    try {
        $conexao = Database::getConnection();
        $consulta = $conexao->prepare('SELECT 1 FROM usuarios WHERE email = :email');
        $consulta->execute(['email' => $email]);
        if ($consulta->fetchColumn() !== false) {
            return $emailDuplicado;
        }

        $inserir = $conexao->prepare(
            "INSERT INTO usuarios (nome, email, senha_hash, perfil) VALUES (:nome, :email, :hash, 'cliente')"
        );
        $inserir->execute(['nome' => $nome, 'email' => $email, 'hash' => password_hash($senha, PASSWORD_DEFAULT)]);
        return null;
    } catch (PDOException | ValueError $erro) {
        if ($erro instanceof PDOException && (string) $erro->getCode() === '23000') {
            return $emailDuplicado;
        }

        error_log('GameVault: ' . $erro->getMessage());
        return 'Não foi possível concluir o cadastro. Tente novamente.';
    }
}

$nome = '';
$email = '';
$erros = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validar_csrf($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Solicitação inválida. Recarregue a página e tente novamente.');
    }

    $nome = is_string($_POST['nome'] ?? null) ? trim($_POST['nome']) : '';
    $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
    $senha = is_string($_POST['senha'] ?? null) ? $_POST['senha'] : '';
    $confirmacao = is_string($_POST['confirmacao'] ?? null) ? $_POST['confirmacao'] : '';

    if (preg_match('/\A.{1,150}\z/u', $nome) !== 1) {
        $erros[] = 'Informe um nome de até 150 caracteres.';
    }
    if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Informe um e-mail válido.';
    }
    if (preg_match('/\A.{8,}\z/us', $senha) !== 1 || strlen($senha) > 72 || str_contains($senha, "\0")) {
        $erros[] = 'Use uma senha com pelo menos 8 caracteres e até 72 bytes.';
    }
    if ($senha !== $confirmacao) {
        $erros[] = 'A confirmação deve ser igual à senha.';
    }

    if ($erros === []) {
        $erro = cadastrar_cliente($nome, $email, $senha);
        if ($erro === null) {
            flash('success', 'Conta criada. Entre com seu e-mail e sua senha.');
            redirecionar(url('login.php'));
        }
        $erros[] = $erro;
    }
}

$titulo = 'Criar conta | GameRochas';
require __DIR__ . '/templates/header.php';
?>
<section class="vault-panel mx-auto col-md-7 col-lg-5">
    <h1 class="h2 mb-4">Criar conta</h1>
    <?php foreach ($erros as $erro): ?>
        <div class="alert alert-danger" role="alert"><?= e($erro) ?></div>
    <?php endforeach; ?>
    <form method="post" action="<?= e(url('cadastro.php')) ?>">
        <?= csrf_campo() ?>
        <div class="mb-3">
            <label class="form-label" for="nome">Nome</label>
            <input class="form-control" id="nome" name="nome" autocomplete="name"
                   maxlength="150" value="<?= e($nome) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="email">E-mail</label>
            <input class="form-control" id="email" name="email" type="email" autocomplete="username"
                   maxlength="254" value="<?= e($email) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="senha">Senha</label>
            <input class="form-control" id="senha" name="senha" type="password" autocomplete="new-password"
                   minlength="8" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="confirmacao">Confirme a senha</label>
            <input class="form-control" id="confirmacao" name="confirmacao" type="password"
                   autocomplete="new-password" minlength="8" required>
        </div>
        <button class="btn btn-primary" type="submit">Cadastrar</button>
        <a class="btn btn-link" href="<?= e(url('login.php')) ?>">Já tenho conta</a>
    </form>
</section>
<?php require __DIR__ . '/templates/footer.php'; ?>
