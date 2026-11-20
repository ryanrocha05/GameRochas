<?php

declare(strict_types=1);

function csrf_token(): string
{
    return $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function validar_csrf(mixed $token): bool
{
    $esperado = $_SESSION['csrf_token'] ?? null;
    return is_string($esperado) && is_string($token) && hash_equals($esperado, $token);
}
