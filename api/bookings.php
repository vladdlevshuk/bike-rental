<?php
namespace database;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once (__DIR__.'/../database/dbmanager.php');

$dbManager = new DataBaseManager();

$requestMethod = $_SERVER['REQUEST_METHOD'];
$method = isset($_GET['method']) ? $_GET['method'] : '';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

switch ($requestMethod) {
    case 'GET':
        if (isset($_GET['bike_id'])) {
            $bikeId = $_GET['bike_id'];
            $bikeData = $dbManager->get_data_by_id('bikes', $bikeId);
            echo json_encode($bikeData);
        } else {
            $bikes = $dbManager->get_all_data('bikes');
            echo json_encode($bikes);
        }
        break;

    case 'POST':
        switch ($method) {
            case 'insert':
                if (isset($data['model'], $data['price_per_day'], $data['is_available'])) {
                    $bikeData = [
                        'model' => $data['model'],
                        'price_per_day' => $data['price_per_day'],
                        'is_available' => $data['is_available']
                    ];
                    $result = $dbManager->insert_data('bikes', $bikeData);
                    echo json_encode(['result' => $result]);
                } else {
                    echo json_encode(['error' => 'Не все данные указаны']);
                }
                break;

            case 'update':
                if (isset($data['id'])) {
                    $updatedFields = [
                        'model' => $data['model'] ?? null,
                        'price_per_day' => $data['price_per_day'] ?? null,
                        'is_available' => $data['is_available'] ?? null,
                    ];

                    // Удаляем поля с null-значениями
                    $updatedFields = array_filter($updatedFields, function ($value) {
                        return $value !== null;
                    });

                    if (empty($updatedFields)) {
                        echo json_encode(['error' => 'Нет полей для обновления']);
                        break;
                    }

                    $result = $dbManager->update_data('bikes', $updatedFields, $data['id']);
                    echo json_encode(['result' => $result]);
                } else {
                    echo json_encode(['error' => 'ID велосипеда не указан']);
                }
                break;

            case 'delete':
                if (isset($data['id'])) {
                    $result = $dbManager->delete_data('bikes', $data['id']);
                    echo json_encode(['result' => $result]);
                } else {
                    echo json_encode(['error' => 'ID велосипеда не указан']);
                }
                break;

            default:
                echo json_encode(['error' => 'Неверный метод']);
                break;
        }
        break;

    default:
        echo json_encode(['error' => 'Метод не поддерживается']);
        break;
}
