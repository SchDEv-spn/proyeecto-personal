<?php
class Database {
    public $conn;

    public function __construct() {
        $cfg = require __DIR__ . '/../config/config.php';

        try {
            $this->conn = new PDO(
                "mysql:host={$cfg['db_host']};dbname={$cfg['db_name']};charset=utf8mb4",
                $cfg['db_user'],
                $cfg['db_password'],
                [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"]
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log("DB connection error: " . $e->getMessage());
            die("Error interno del servidor. Por favor intenta más tarde.");
        }
    }
}
