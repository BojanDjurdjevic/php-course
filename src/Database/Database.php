<?php

namespace App\Database;

use PDO;

final class Database
{
    private ?PDO $conn = null;

    public function __construct(
        private string $host,
        private string $dbName,
        private string $dbUser,
        private string $dbPass,
    ) {}

    public function connect(): PDO
    {
        if ($this->conn !== null) {
            return $this->conn;
        }

        $dsn = "mysql:host=$this->host;dbname=$this->dbName;charset=utf8mb4";

        $this->conn = new PDO(
            $dsn,
            $this->dbUser,
            $this->dbPass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        return $this->conn;
    }
}