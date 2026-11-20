<?php

declare(strict_types=1);

final class Database
{
    private static ?PDO $conexao = null;

    private function __construct()
    {
    }

    public static function getConnection(): PDO
    {
        if (self::$conexao !== null) {
            return self::$conexao;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            env('DB_HOST'),
            env('DB_PORT'),
            env('DB_NAME')
        );

        try {
            self::$conexao = new PDO($dsn, env('DB_USER'), env('DB_PASS'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $erro) {
            error_log('GameVault: ' . $erro->getMessage());
            http_response_code(500);
            exit('Não foi possível conectar ao banco de dados.');
        }

        return self::$conexao;
    }
}
