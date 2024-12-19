<?php
namespace database;

use Exception;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once (__DIR__.'/../database/dbmanager.php');

$dbManager = new DataBaseManager();

$requestMethod = $_SERVER['REQUEST_METHOD'];
$table_name = 'notifications';
$method = isset($_GET['method']) ? $_GET['method'] : '';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

function createTableIfNotExists($dbManager, $table_name) {
    if (!$dbManager->tableExists($table_name)) {
        $columns = [
            ['id' => 'INT AUTO_INCREMENT PRIMARY KEY'],
            ['guest_name' => 'VARCHAR(255) NOT NULL'],
            ['state' => "ENUM('прочитано', 'не прочитано') DEFAULT 'не прочитано'"],
            ['isConfirmed' => 'BOOLEAN DEFAULT FALSE']
        ];
        $dbManager->create_table($table_name, $columns);
    }
}

switch ($requestMethod) {
    case 'GET':
        $data = $dbManager->get_all_data($table_name);
        echo json_encode($data);
        break;

    case 'POST':
        switch ($method) {
            case 'create_table':
                createTableIfNotExists($dbManager, $table_name);
                echo json_encode(['result' => 'Table checked/created']);
                break;

            case 'insert':
                createTableIfNotExists($dbManager, $table_name);
                $dataToInsert = isset($data) ? $data : [];

                if (empty($dataToInsert['guest_name']) || !isset($dataToInsert['isConfirmed'])) {
                    echo json_encode(['error' => 'Поля guest_name, isConfirmed и room_number обязательны']);
                } else {
                    try {
                        $result = $dbManager->insert_data($table_name, $dataToInsert);
                        echo json_encode(['result' => $result]);
                    } catch (Exception $e) {
                        echo json_encode(['error' => $e->getMessage()]);
                    }
                }
                break;

            case 'delete':
                $id = isset($data['id']) ? $data['id'] : null;
                if ($id === null) {
                    echo json_encode(['error' => 'ID обязателен для удаления записи.']);
                    exit;
                }
                $result = $dbManager->delete_data($table_name, $id);
                echo json_encode(['result' => $result]);
                break;

            case 'update':
                $id = isset($data['id']) ? $data['id'] : null;
                $newData = isset($data['new_data']) ? $data['new_data'] : null;

                if ($id === null || !$newData) {
                    echo json_encode(['error' => 'ID и new_data обязательны для обновления записи.']);
                    exit;
                }

                createTableIfNotExists($dbManager, $table_name);
                try {
                    $result = $dbManager->update_data($table_name, $newData, $id);
                    echo json_encode(['result' => $result]);
                } catch (Exception $e) {
                    echo json_encode(['error' => $e->getMessage()]);
                }
                break;

            default:
                echo json_encode(['error' => 'Неверный метод']);
                break;
        }
        break;

    default:
        echo json_encode(['error' => 'Метод не разрешен']);
        break;
}
?>
