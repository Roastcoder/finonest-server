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
require_once __DIR__ . '/../models/Application.php';

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
$application = new Application($db);

$method = $_SERVER['REQUEST_METHOD'];
$path_info = $_SERVER['PATH_INFO'] ?? '';
$request = explode('/', trim($path_info, '/'));

switch($method) {
    case 'POST':
        submitForm();
        break;
    case 'PUT':
        updateApplicationStatus();
        break;
    case 'GET':
        // Check if it's a request for user's own applications
        if (strpos($_SERVER['REQUEST_URI'], '/mine') !== false) {
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
    global $application;
    
    $auth_user = authenticate();
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Handle both direct fields and nested form_data
    $form_fields = isset($data['form_data']) ? $data['form_data'] : $data;
    
    // Create form_data object from the submitted data
    $form_data = [
        'loan_type' => $form_fields['loanType'] ?? $form_fields['loan_type'] ?? 'General Inquiry',
        'amount' => $form_fields['amount'] ?? 0,
        'full_name' => $form_fields['full_name'] ?? '',
        'email' => $form_fields['email'] ?? '',
        'phone' => $form_fields['phone'] ?? '',
        'employment_type' => $form_fields['employment'] ?? $form_fields['employment_type'] ?? '',
        'monthly_income' => $form_fields['income'] ?? $form_fields['monthly_income'] ?? 0,
        'notes' => $form_fields['purpose'] ?? $form_fields['notes'] ?? ''
    ];

    $application_id = $application->create($auth_user['user_id'], $form_data);
    
    if ($application_id) {
        echo json_encode([
            'success' => true,
            'application_id' => $application_id,
            'message' => 'Application submitted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to submit application']);
    }
}

function getMyForms() {
    global $application;
    
    $auth_user = authenticate();
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    
    $applications = $application->getByUserId($auth_user['user_id'], $limit, $offset);
    
    echo json_encode([
        'success' => true,
        'applications' => $applications,
        'page' => $page,
        'limit' => $limit,
        'user_id' => $auth_user['user_id'] // Add for debugging
    ]);
}

function updateApplicationStatus() {
    global $application;
    
    $auth_user = authenticate();
    
    // Check if user is admin
    if ($auth_user['role'] !== 'ADMIN') {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. Admin role required.']);
        return;
    }
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['id']) || !isset($data['status'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: id and status']);
        return;
    }
    
    $result = $application->updateStatus($data['id'], $data['status']);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Application status updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update application status']);
    }
}
?>