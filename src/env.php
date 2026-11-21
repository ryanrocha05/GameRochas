<?php

declare(strict_types=1);

function env(string $chave, mixed $padrao = null): mixed
{
    return GAMEVAULT_CONFIG[$chave] ?? $padrao;
}

$arquivoEnv = dirname(__DIR__) . '/.env';
$linhasEnv = @file($arquivoEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if ($linhasEnv === false) {
    error_log('GameVault: falha ao ler o arquivo .env.');
    http_response_code(500);
    exit('Não foi possível carregar a configuração da aplicação.');
}

$configuracao = [];
foreach ($linhasEnv as $linha) {
    $linha = trim($linha);
    if ($linha === '' || str_starts_with($linha, '#') || !str_contains($linha, '=')) {
        continue;
    }

    [$chave, $valor] = array_map('trim', explode('=', $linha, 2));
    if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $chave) !== 1) {
        continue;
    }

    $configuracao[$chave] = $valor;
}

foreach (['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'APP_URL'] as $obrigatoria) {
    if (!isset($configuracao[$obrigatoria]) || ($obrigatoria !== 'DB_PASS' && $configuracao[$obrigatoria] === '')) {
        error_log('GameVault: chave de configuração ausente ou vazia: ' . $obrigatoria);
        http_response_code(500);
        exit('Não foi possível carregar a configuração da aplicação.');
    }
}

define('GAMEVAULT_CONFIG', $configuracao);
unset($configuracao, $linhasEnv, $linha, $chave, $valor);
