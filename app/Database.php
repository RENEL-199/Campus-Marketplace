<?php

class Database {
    private string $host = "localhost";
    private string $db = "IskoHub";
    private string $user = "root";
    private string $pass = "";
    private string $charset = "utf8mb4";

    public PDO $pdo;

    public function __construct() {
        $dsn = "mysql:host=$this->host;dbname=$this->db;charset=$this->charset";

        $this->pdo = new PDO($dsn, $this->user, $this->pass);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
}