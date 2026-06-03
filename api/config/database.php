<?php
/**
 * Configurare conexiune PDO PostgreSQL
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host = 'localhost';
            $port = '5433';
            $dbname = 'babyinfo';
            $user = 'postgres';
            $pass = 'admin';

            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};";

            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Database connection failed']);
                exit;
            }
        }
        return self::$instance;
    }

    private function __clone() {}
}