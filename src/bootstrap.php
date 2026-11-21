<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Database.php';

if (session_status() === PHP_SESSION_NONE) {
    $caminho = parse_url((string) env('APP_URL'), PHP_URL_PATH);
    $sessaoIniciada = @session_start([
        'name' => 'GAMEVAULTSESSID',
        'cookie_path' => rtrim((string) $caminho, '/') ?: '/',
        'use_only_cookies' => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => parse_url((string) env('APP_URL'), PHP_URL_SCHEME) === 'https'
            || requisicao_https(),
        'use_strict_mode' => true,
    ]);
    if (!$sessaoIniciada) {
        error_log('GameVault: ' . (error_get_last()['message'] ?? 'falha ao iniciar a sessão.'));
        http_response_code(500);
        exit('Não foi possível iniciar a sessão.');
    }
}
