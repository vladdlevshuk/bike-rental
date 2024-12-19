<?php

namespace DataBase;

use PDO;
use PDOException;

class DataBaseConnection {
    private $host = 'localhost';
    private $db_name = 'bike_rental';
    private $username = 'root';
    private $password = 'root';
    private $pdo;
    private $port = '3306';

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        try {
            $dsn = "mysql:host=$this->host;port=$this->port;dbname=$this->db_name";
            $this->pdo = new PDO($dsn, $this->username, $this->password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $errorLogPath = __DIR__ . '/../logs/myapp_errors.log';
            if (!file_exists(dirname($errorLogPath))) {
                mkdir(dirname($errorLogPath), 0777, true);
            }
            error_log("Error connecting to database: " . $e->getMessage(), 3, $errorLogPath);
            http_response_code(500);
            echo json_encode(['error' => 'Ошибка подключения к базе данных']);
            exit();
        }
    }

    public function get_pdo() {
        return $this->pdo;
    }
}
