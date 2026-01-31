<?php
require_once __DIR__ . '/../middleware/cors_secure.php';
SecureCorsMiddleware::handle();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Banker.php';

$database = new Database();
$db = $database->getConnection();
$banker = new Banker($db);

$method = $_SERVER['REQUEST_METHOD'];
$path_info = $_SERVER['PATH_INFO'] ?? '';
$request = explode('/', trim($path_info, '/'));

switch($method) {
    case 'GET':
        if (empty($request[0])) {
            getBankers();
        } elseif ($request[0] === 'lenders') {
            getLenders();
        } elseif (is_numeric($request[0])) {
            getBanker($request[0]);
        }
        break;
    case 'POST':
        if ($request[0] === 'lenders') {
            createLender();
        } else {
            createBanker();
        }
        break;
    case 'PUT':
        if (is_numeric($request[0])) {
            updateBanker($request[0]);
        }
        break;
    case 'DELETE':
        if (is_numeric($request[0])) {
            deleteBanker($request[0]);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function getBankers() {
    global $banker;
    $bankers = $banker->getAll();
    echo json_encode(['success' => true, 'data' => $bankers]);
}

function getBanker($id) {
    global $banker;
    $data = $banker->getById($id);
    if ($data) {
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Banker not found']);
    }
}

function createBanker() {
    global $banker;
    $data = json_decode(file_get_contents("php://input"), true);
    
    $id = $banker->create($data);
    if ($id) {
        echo json_encode(['success' => true, 'id' => $id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create banker']);
    }
}

function updateBanker($id) {
    global $banker;
    $data = json_decode(file_get_contents("php://input"), true);
    
    if ($banker->update($id, $data)) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update banker']);
    }
}

function deleteBanker($id) {
    global $banker;
    if ($banker->delete($id)) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete banker']);
    }
}

function getLenders() {
    global $banker;
    $lenders = $banker->getLenders();
    echo json_encode(['success' => true, 'data' => $lenders]);
}

function createLender() {
    global $banker;
    $data = json_decode(file_get_contents("php://input"), true);
    
    $id = $banker->createLender($data);
    if ($id) {
        echo json_encode(['success' => true, 'id' => $id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create lender']);
    }
}
?>