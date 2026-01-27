<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../middleware/cors.php';
require_once '../config/jwt.php';

// Require admin authentication
$headers = getallheaders() ?: [];
$token = null;

foreach ($headers as $key => $value) {
    if (strtolower($key) === 'authorization') {
        if (preg_match('/Bearer\s(\S+)/', $value, $matches)) {
            $token = $matches[1];
        }
        break;
    }
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['error' => 'No token provided']);
    exit();
}

try {
    $decoded = JWT::decode($token);
    if (!$decoded || !isset($decoded['role']) || $decoded['role'] !== 'ADMIN') {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        exit();
    }
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        error_log('Database connection failed in admin-loan-onboarding.php');
        throw new Exception('Database connection failed');
    }
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = ($page - 1) * $limit;
    
    // Check if table exists, create if not
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'loan_onboarding'");
    if ($tableCheck->rowCount() == 0) {
        // Create table if it doesn't exist
        $createTable = "CREATE TABLE loan_onboarding (
            id INT AUTO_INCREMENT PRIMARY KEY,
            mobile VARCHAR(15) NOT NULL,
            pan VARCHAR(10),
            pan_name VARCHAR(255),
            pan_response JSON,
            dob VARCHAR(20),
            gender VARCHAR(10),
            credit_score INT,
            credit_response JSON,
            vehicle_rc VARCHAR(20),
            vehicle_model VARCHAR(255),
            vehicle_year INT,
            vehicle_make VARCHAR(255),
            owner_name VARCHAR(255),
            fuel_type VARCHAR(50),
            vehicle_color VARCHAR(50),
            vehicle_response JSON,
            vehicle_value INT,
            income INT,
            employment VARCHAR(100),
            application_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $pdo->exec($createTable);
    }
    
    // Get total count
    $countStmt = $pdo->query("SELECT COUNT(*) FROM loan_onboarding");
    $totalRecords = $countStmt->fetchColumn();
    
    // Get applications with pagination
    $stmt = $pdo->prepare("SELECT * FROM loan_onboarding ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$limit, $offset]);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Parse JSON responses for display
    foreach ($applications as &$app) {
        $app['pan_response'] = json_decode($app['pan_response'], true);
        $app['credit_response'] = json_decode($app['credit_response'], true);
        $app['vehicle_response'] = json_decode($app['vehicle_response'], true);
    }
    
    echo json_encode([
        'success' => true,
        'applications' => $applications,
        'total_records' => $totalRecords,
        'current_page' => $page,
        'total_pages' => ceil($totalRecords / $limit)
    ]);
    
} catch (Exception $e) {
    error_log('Admin Loan Onboarding Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch applications', 'error' => $e->getMessage()]);
}
?>