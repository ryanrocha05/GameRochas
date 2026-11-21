<?php

declare(strict_types=1);

function e(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function moeda(int|float|string $valor): string
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

function redirecionar(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function flash(string $tipo, string $msg): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'msg' => $msg];
}

function exibir_flash(): void
{
    $mensagens = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    $tipos = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];
    foreach ($mensagens as $mensagem) {
        $tipo = in_array($mensagem['tipo'], $tipos, true) ? $mensagem['tipo'] : 'info';
        echo '<div class="alert alert-' . e($tipo) . '" role="alert">' . e($mensagem['msg']) . '</div>';
    }
}

function requisicao_https(): bool
{
    return !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off';
}

function url(string $caminho = ''): string
{
    $base = (string) env('APP_URL');
    if (requisicao_https() && parse_url($base, PHP_URL_SCHEME) === 'http') {
        // Conserva a origem HTTPS da requisição, inclusive em instalações locais com porta própria.
        $base = (string) parse_url($base, PHP_URL_PATH);
    }
    return rtrim($base, '/') . '/' . ltrim($caminho, '/');
}
