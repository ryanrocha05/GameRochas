<?php

declare(strict_types=1);

function env(string $chave, mixed $padrao = null): mixed
{
    return $_ENV[$chave] ?? $padrao;
}

$arquivoEnv = dirname(__DIR__) . '/.env';
if (!is_file($arquivoEnv)) {
    http_response_code(500);
    exit('Arquivo .env ausente. Copie .env.example para .env.');
}

$linhasEnv = @file($arquivoEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if ($linhasEnv === false) {
    error_log('GameVault: falha ao ler o arquivo .env.');
    http_response_code(500);
    exit('Não foi possível carregar a configuração da aplicação.');
}

foreach ($linhasEnv as $linha) {
    $linha = trim($linha);
    if ($linha === '' || str_starts_with($linha, '#') || !str_contains($linha, '=')) {
        continue;
    }

    [$chave, $valor] = array_map('trim', explode('=', $linha, 2));
    if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $chave) !== 1) {
        continue;
    }

    $_ENV[$chave] = $valor;
    putenv($chave . '=' . $valor);
}
