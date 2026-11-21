<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function usuario_logado(): ?array
{
    return $_SESSION['gamevault_usuario'] ?? null;
}

function exigir_login(): void
{
    if (usuario_logado() !== null) {
        return;
    }

    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $base = rtrim((string) parse_url(url(), PHP_URL_PATH), '/') . '/';
    if (str_starts_with($uri, $base) && !str_starts_with($uri, '//')
        && !preg_match('/[\x00-\x20\x7f]/', $uri) && !str_contains($uri, '\\')) {
        $_SESSION['destino_login'] = substr($uri, strlen($base));
    }

    redirecionar(url('login.php'));
}

function exigir_admin(): void
{
    exigir_login();
    if (usuario_logado()['perfil'] === 'admin') {
        try {
            $consulta = Database::getConnection()->prepare('SELECT perfil FROM usuarios WHERE id = ?');
            $consulta->execute([usuario_logado()['id']]);
            $perfil = $consulta->fetchColumn();
        } catch (PDOException $erro) {
            error_log('GameVault: ' . $erro->getMessage());
            http_response_code(500);
            exit('Não foi possível verificar o acesso ao painel.');
        }
        if ($perfil === 'admin') {
            return;
        }
        if ($perfil === false) {
            logout();
            redirecionar(url('login.php'));
        }
        $_SESSION['gamevault_usuario']['perfil'] = $perfil;
    }

    $inicio = url('index.php');
    http_response_code(403);
    header('Refresh: 3; url=' . $inicio);
    header('Content-Type: text/html; charset=UTF-8');
    exit('<!doctype html><html lang="pt-BR"><meta charset="utf-8"><title>Acesso negado</title>'
        . '<p>Acesso restrito a administradores. <a href="' . e($inicio) . '">Voltar ao início</a>.</p></html>');
}

function login(string $email, string $senha): bool
{
    try {
        $conexao = Database::getConnection();
        $consulta = $conexao->prepare('SELECT id, nome, perfil, senha_hash FROM usuarios WHERE email = :email');
        $consulta->execute(['email' => $email]);
        $usuario = $consulta->fetch();
        // Bloqueia os verificadores de senha distribuídos pela antiga carga de demonstração.
        $hashesDeDemonstracao = [
            'b1c0aa0009ef074d741c5357719437b65a882411e8e3df326f2df2a280c95e0b',
            '6d7d98f6d19d04dc8767926f90d76289c7fed5dbd8ed6e9afed1f7558a6be2e8',
        ];
        if ($usuario === false || in_array(hash('sha256', $usuario['senha_hash']), $hashesDeDemonstracao, true)
            || !password_verify($senha, $usuario['senha_hash'])) {
            return false;
        }

        if (password_needs_rehash($usuario['senha_hash'], PASSWORD_DEFAULT)) {
            $atualizar = $conexao->prepare('UPDATE usuarios SET senha_hash = :hash WHERE id = :id AND senha_hash = :anterior');
            $atualizar->execute([
                'hash' => password_hash($senha, PASSWORD_DEFAULT),
                'id' => $usuario['id'],
                'anterior' => $usuario['senha_hash'],
            ]);
        }
    } catch (PDOException | ValueError $erro) {
        error_log('GameVault: ' . $erro->getMessage());
        return false;
    }

    if (!@session_regenerate_id(true)) {
        error_log('GameVault: falha ao renovar a sessão de login.');
        return false;
    }

    unset($_SESSION['csrf_token']);
    $_SESSION['gamevault_usuario'] = [
        'id' => (int) $usuario['id'],
        'nome' => $usuario['nome'],
        'perfil' => $usuario['perfil'],
    ];
    return true;
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $cookie = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $cookie['path'],
            'domain' => $cookie['domain'],
            'secure' => $cookie['secure'],
            'httponly' => $cookie['httponly'],
            'samesite' => $cookie['samesite'],
        ]);
    }

    session_destroy();
}
