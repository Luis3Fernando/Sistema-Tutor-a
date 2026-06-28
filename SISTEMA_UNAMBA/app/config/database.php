<?php
declare(strict_types=1);

/**
 * Carga variables de entorno desde .env (parser simple).
 */
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        $value = trim($value, "\"'");

        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

/**
 * Retorna conexión PDO singleton para todo el sistema.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $rootPath = dirname(__DIR__, 2);
    loadEnv($rootPath . DIRECTORY_SEPARATOR . '.env');

    $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
    $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
    $name = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'sistema_tutoria_unamba';
    $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
    $pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}
