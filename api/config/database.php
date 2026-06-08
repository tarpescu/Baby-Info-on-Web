<?php
/**
 * Configurare conexiune PDO PostgreSQL.
 * Credentialele sunt citite din fisierul .env aflat in radacina proiectului.
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
            self::loadEnv();

            $host   = $_ENV['DB_HOST'] ?? 'localhost';
            $port   = $_ENV['DB_PORT'] ?? '5432';
            $dbname = $_ENV['DB_NAME'] ?? 'babyinfo';
            $user   = $_ENV['DB_USER'] ?? 'postgres';
            $pass   = $_ENV['DB_PASS'] ?? '';

            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};";

            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
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

    /**
     * Incarca variabilele din fisierul .env in $_ENV si putenv().
     * Parsare manuala — nu necesita librarii externe.
     * Apelat o singura data la primul getConnection().
     */
    private static function loadEnv(): void
    {
        // Cauta .env in radacina proiectului (doua niveluri deasupra /api/config/)
        $envFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';

        if (!file_exists($envFile)) {
            return; // fara .env — se folosesc valorile implicite sau env-ul sistemului
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            // Ignora comentariile
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Parsare KEY=VALUE
            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);

            // Elimina ghilimele optionale din valoare ("value" sau 'value')
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            if ($key === '') {
                continue;
            }

            // Seteaza doar daca nu exista deja (env-ul sistemului are prioritate)
            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }

    private function __clone() {}
}
