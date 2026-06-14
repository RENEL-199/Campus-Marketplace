<?php

class Database {
    private string $host;
    private string $db;
    private string $user;
    private string $pass;
    private string $charset;

    public PDO $pdo;

    public function __construct() {
        $this->host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
        $this->db = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'Iskohub';
        $this->user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
        $this->pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';
        $this->charset = $_ENV['DB_CHARSET'] ?? getenv('DB_CHARSET') ?: 'utf8mb4';

        $dsn = "mysql:host={$this->host};dbname={$this->db};charset={$this->charset}";
        $this->pdo = new PDO($dsn, $this->user, $this->pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
