<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../config/database.php';

function authenticate() {
    $headers = getallheaders();
    $token = null;

    if (isset($headers['Authorization'])) {
        $auth_header = $headers['Authorization'];
        if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
            $token = $matches[1];
        }
    }

    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'No token provided']);
        exit();
    }

    $decoded = JWT::decode($token);
    if (!$decoded) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token']);
        exit();
    }

    return $decoded;
}

$database = new Database();
$db = $database->getConnection();

// Create table if it doesn't exist
try {
    $create_table = "CREATE TABLE IF NOT EXISTS loan_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        amount DECIMAL(15,2) NOT NULL,
        purpose TEXT,
        income DECIMAL(15,2),
        employment VARCHAR(100),
        status ENUM('SUBMITTED', 'UNDER_REVIEW', 'APPROVED', 'REJECTED') DEFAULT 'SUBMITTED',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $db->exec($create_table);
} catch (Exception $e) {
    // Table creation failed, continue anyway
}

$method = $_SERVER['REQUEST_METHOD'];
$path_info = $_SERVER['PATH_INFO'] ?? '';
$request = explode('/', trim($path_info, '/'));

switch($method) {
    case 'POST':
        submitForm();
        break;
    case 'GET':
        if (isset($request[0]) && $request[0] === 'mine') {
            getMyForms();
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function submitForm() {
    global $db;
    
    $auth_user = authenticate();
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Handle both direct fields and nested form_data
    $form_fields = isset($data['form_data']) ? $data['form_data'] : $data;
    
    // Map loan types
    $loan_type_map = [
        'personal' => 'PERSONAL',
        'home' => 'HOME', 
        'car' => 'VEHICLE',
        'business' => 'BUSINESS'
    ];
    
    $type = $loan_type_map[$form_fields['loanType']] ?? 'PERSONAL';
    $amount = $form_fields['amount'] ?? 0;
    $purpose = $form_fields['purpose'] ?? '';
    $income = $form_fields['income'] ?? 0;
    $employment = $form_fields['employment'] ?? '';
    
    try {
        $query = "INSERT INTO loan_applications (user_id, type, amount, purpose, income, employment) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        
        if($stmt->execute([$auth_user['user_id'], $type, $amount, $purpose, $income, $employment])) {
            echo json_encode([
                'success' => true,
                'application_id' => $db->lastInsertId(),
                'message' => 'Application submitted successfully'
            ]);
        } else {
            throw new Exception('Failed to insert data');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function getMyForms() {
    global $db;
    
    $auth_user = authenticate();
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    
    try {
        $query = "SELECT * FROM loan_applications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$auth_user['user_id'], $limit, $offset]);
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'applications' => $applications,
            'page' => $page,
            'limit' => $limit
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>