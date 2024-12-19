<?php
namespace DataBase;

require_once (__DIR__.'/dbconnection.php');

use PDO;
use PDOException;

class DataBaseManager
{
    private $pdo;

    public function __construct() {
        $db = new DataBaseConnection();
        $this->pdo = $db->get_pdo();
    }

    public function get_all_data($table_name) {
        try {
            $query = "SELECT * FROM {$table_name}";
            $statement = $this->pdo->prepare($query);
            $statement->execute();
            $data = $statement->fetchAll(PDO::FETCH_ASSOC);
            return $data;
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function insert_data($table_name, $data) {
        $columns = array_keys($data);
        $columnNames = implode(', ', $columns);

        $placeholders = array_map(function ($column) {
            return ':' . $column;
        }, $columns);
        $placeholderNames = implode(', ', $placeholders);

        $sql = "INSERT INTO {$table_name} ({$columnNames}) VALUES ({$placeholderNames})";
        $statement = $this->pdo->prepare($sql);

        foreach ($data as $column => $value) {
            $statement->bindValue(':' . $column, $value);
        }

        $statement->execute();
        return $this->pdo->lastInsertId();
    }

    public function get_pdo(){
        return $this->pdo;
    }

    public function create_table($table_name, $columns) {
        $columnDefinitions = [];

        foreach ($columns as $column) {
            foreach ($column as $name => $type) {
                $columnDefinitions[] = "{$name} {$type}";
            }
        }

        $columnDefinitionsString = implode(', ', $columnDefinitions);
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} ({$columnDefinitionsString})";

        $statement = $this->pdo->prepare($sql);
        $statement->execute();

        return 'ok';
    }

    public function delete_data($table_name, $id) {
        $query = "DELETE FROM {$table_name} WHERE id = :id";
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':id', $id);
        $statement->execute();

        return $statement->rowCount();
    }

    public function update_data($table_name, $data, $condition) {
        $columns = array_keys($data);
        $setStatements = [];

        foreach ($columns as $column) {
            $setStatements[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $setStatements);

        $sql = "UPDATE {$table_name} SET {$setClause} WHERE id = {$condition}";
        $statement = $this->pdo->prepare($sql);

        foreach ($data as $column => $value) {
            $statement->bindValue(':' . $column, $value);
        }

        $statement->execute();
        return $statement->rowCount();
    }

    public function tableExists($tableName) {
        $query = "SHOW TABLES LIKE ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$tableName]);
        return $stmt->rowCount() > 0;
    }

    public function create_table_bikes() {
        $createTableSQL = "CREATE TABLE IF NOT EXISTS bikes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bike_number INT NOT NULL,
            bike_type ENUM('городской', 'горный', 'электровелосипед') NOT NULL,
            price FLOAT NOT NULL,
            status ENUM('доступен', 'забронирован', 'на ремонте') NOT NULL
        )";

        try {
            $this->pdo->exec($createTableSQL);
            $data = $this->get_all_data('bikes');
        } catch (PDOException $e) {
            $data = ['error' => "Не удалось создать таблицу: " . $e->getMessage()];
        }
    }

    public function get_bike_price($bike_id) {
        try {
            $query = "SELECT price FROM bikes WHERE id = :bike_id";
            $statement = $this->pdo->prepare($query);
            $statement->bindValue(':bike_id', $bike_id);
            $statement->execute();
            $bike = $statement->fetch(PDO::FETCH_ASSOC);
            return $bike ? $bike['price'] : null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function get_bookings_for_bike($bikeId) {
        $query = "SELECT * FROM bookings WHERE bike_id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$bikeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_data_by_id($tableName, $id)
    {
        try {
            $query = $this->pdo->prepare("SELECT * FROM $tableName WHERE id = :id");
            $query->bindParam(':id', $id, PDO::PARAM_INT);
            $query->execute();

            $result = $query->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return ['error' => "Запись с ID $id не найдена в таблице $tableName"];
            }

            return $result;
        } catch (\PDOException $e) {
            return ['error' => 'Ошибка базы данных: ' . $e->getMessage()];
        }
    }


}
