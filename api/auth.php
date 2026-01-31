<?php
require_once __DIR__ . '/../middleware/cors_secure.php';
SecureCorsMiddleware::handle();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../models/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'], '/'));

switch($method) {
    case 'POST':
        if ($request[0] === 'register') {
            register();
        } elseif ($request[0] === 'login') {
            login();
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function register() {
    global $user;
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['name']) || !isset($data['email']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }

    if ($user->emailExists($data['email'])) {
        http_response_code(409);
        echo json_encode(['error' => 'Email already exists']);
        return;
    }

    $user_id = $user->create($data['name'], $data['email'], $data['password']);
    
    if ($user_id) {
        $payload = [
            'user_id' => $user_id,
            'email' => $data['email'],
            'role' => 'USER',
            'exp' => time() + (24 * 60 * 60) // 24 hours
        ];
        
        $token = JWT::encode($payload);
        
        echo json_encode([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user_id,
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => 'USER'
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Registration failed']);
    }
}

function login() {
    global $user;
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['email']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Email and password required']);
        return;
    }

    $user_data = $user->findByEmail($data['email']);
    
    if ($user_data && $user->verifyPassword($data['password'], $user_data['password_hash'])) {
        $payload = [
            'user_id' => $user_data['id'],
            'email' => $user_data['email'],
            'role' => $user_data['role'],
            'exp' => time() + (24 * 60 * 60) // 24 hours
        ];
        
        $token = JWT::encode($payload);
        
        echo json_encode([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user_data['id'],
                'name' => $user_data['name'],
                'email' => $user_data['email'],
                'role' => $user_data['role']
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
    }
}
?>