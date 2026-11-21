<?php
require_once __DIR__ . '/config.php';

/**
 * Returns one shared PDO connection (created on first call).
 * - Exceptions on errors, so problems are never silent.
 * - Real prepared statements (no emulation) to block SQL injection.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Database connection failed. Is MySQL running and is the "vaxtrack" database imported?');
        }
    }
    return $pdo;
}
