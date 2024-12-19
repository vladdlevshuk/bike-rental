<?php
namespace database;

use Exception;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
require_once (__DIR__.'/../database/dbmanager.php');

$dbManager = new DataBaseManager();

$requestMethod = $_SERVER['REQUEST_METHOD'];
error_log("Request Method: $requestMethod");

$table_name = 'users';
$method = isset($_GET['method']) ? $_GET['method'] : '';

error_log("Method: $method");

$json = file_get_contents('php://input');
$data = json_decode($json, true);
error_log("Request Data: " . json_encode($data));

function validateUserData($dbManager, $data, $excludeId = null) {
    if (empty($data['username'])) {
        return ['error' => 'Имя пользователя обязательно'];
    } elseif (strlen($data['username']) > 16) {
        return ['error' => 'Имя пользователя не должно превышать 16 символов'];
    } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $data['username'])) {
        return ['error' => 'Имя пользователя может содержать только буквы и цифры'];
    } else {
        $allData = $dbManager->get_all_data('users');
        foreach ($allData as $user) {
            if ($user['username'] == $data['username'] && $user['id'] != $excludeId) {
                return ['error' => 'Пользователь с таким именем уже существует'];
            }
        }
    }

    if (empty($data['email'])) {
        return ['error' => 'Email обязательно'];
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return ['error' => 'Неверный формат email'];
    } else {
        $allData = $dbManager->get_all_data('users');
        foreach ($allData as $user) {
            if ($user['email'] == $data['email'] && $user['id'] != $excludeId) {
                return ['error' => 'Пользователь с таким email уже существует'];
            }
        }
    }

    if (empty($data['password_hash'])) {
        return ['error' => 'Хэш пароля обязателен'];
    }

    return ['status' => 'ok'];
}

function createTableIfNotExists($dbManager, $table_name) {
    if (!$dbManager->tableExists($table_name)) {
        $columns = [
            ['id' => 'INT AUTO_INCREMENT PRIMARY KEY'],
            ['username' => 'VARCHAR(255) NOT NULL'],
            ['email' => 'VARCHAR(255) NOT NULL UNIQUE'],
            ['password_hash' => 'VARCHAR(255) NOT NULL']
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
                $validationResult = validateUserData($dbManager, $dataToInsert);
                if (isset($validationResult['status']) && $validationResult['status'] === 'ok') {
                    try {
                        $result = $dbManager->insert_data($table_name, $dataToInsert);
                        echo json_encode(['result' => $result]);
                    } catch (Exception $e) {
                        if (strpos($e->getMessage(), '1062 Duplicate entry') !== false) {
                            echo json_encode(['error' => 'Пользователь с таким username или email уже существует']);
                        } else {
                            echo json_encode(['error' => [$e->getMessage()]]);
                        }
                    }
                } else {
                    echo json_encode($validationResult);
                }
                break;

            case 'delete':
                $id = isset($data['id']) ? $data['id'] : null;
                if ($id === null) {
                    echo json_encode(['error' => 'ID is required for delete operation.']);
                    exit;
                }
                $result = $dbManager->delete_data($table_name, $id);
                echo json_encode(['result' => $result]);
                break;

            case 'update':
                $condition = isset($data['id']) ? $data['id'] : null;
                $newData = isset($data['new_data']) ? $data['new_data'] : null;

                createTableIfNotExists($dbManager, $table_name);
                $validationResult = validateUserData($dbManager, $newData, $condition);
                if (isset($validationResult['status']) && $validationResult['status'] === 'ok') {
                    try {
                        $result = $dbManager->update_data($table_name, $newData, $condition);
                        echo json_encode(['result' => $result]);
                    } catch (Exception $e) {
                        echo json_encode(['error' => [$e->getMessage()]]);
                    }
                } else {
                    echo json_encode($validationResult);
                }
                break;

            default:
                echo json_encode(['error' => 'Invalid method']);
                break;
        }
        break;

    default:
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>
