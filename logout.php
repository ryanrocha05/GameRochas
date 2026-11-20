<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/Csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método não permitido.');
}

if (!validar_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Solicitação inválida. Recarregue a página e tente novamente.');
}

logout();
redirecionar(url('index.php'));
