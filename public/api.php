<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Database.php';

header('Content-Type: application/json');

$db = new Database();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            if ($action === 'list') {
                echo json_encode(['items' => $db->getItems()]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);

            if ($action === 'add' && isset($input['text']) && trim($input['text']) !== '') {
                $item = $db->addItem(trim($input['text']));
                echo json_encode(['item' => $item]);
            } elseif ($action === 'delete' && isset($input['id'])) {
                $db->deleteItem((int)$input['id']);
                echo json_encode(['success' => true]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action or missing data']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
