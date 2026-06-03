<?php

/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Core;

use App\Config\Constants;
use App\Config\Database;

class App
{
    public function __construct()
    {
        date_default_timezone_set(Constants::TIMEZONE);
        $this->initSession();
        Database::getConnection();
    }

    private function initSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.gc_maxlifetime', (string)Constants::SESSION_LIFETIME);
            session_start();
        }
    }

    public function run(): void
    {
        $router = new Router();
        $router->dispatch();
    }
}