<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Core;

use App\Config\Database;
use PDO;

abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }
}