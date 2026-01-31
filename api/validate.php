<?php
require_once __DIR__ . '/../cors-handler.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../models/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    validateToken();
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

function validateToken() {
    global $user;
    
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(['error' => 'No token provided']);
        return;
    }
    
    $token = $matches[1];
    $payload = JWT::decode($token);
    
    if (!$payload) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token']);
        return;
    }
    
    $user_data = $user->findById($payload['user_id']);
    
    if (!$user_data) {
        http_response_code(401);
        echo json_encode(['error' => 'User not found']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user_data['id'],
            'name' => $user_data['name'],
            'email' => $user_data['email'],
            'role' => $user_data['role']
        ]
    ]);
}
?>