<?php
class Database {
    private $host = "localhost";
    private $db_name = "tienda_db";
    private $username = "root";
    private $password = "";
    public $conn;

    public function __construct() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            die("Error de conexión: " . $exception->getMessage());
        }
    }
}
