<?php
require_once __DIR__ . '/../middleware/cors_secure.php';
SecureCorsMiddleware::handle();

require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../config/database.php';

function requireAdmin() {
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
    if (!$decoded || $decoded['role'] !== 'ADMIN') {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        exit();
    }

    return $decoded;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

requireAdmin();

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
    
    // Get total count with simpler query
    $countStmt = $pdo->prepare("SELECT COUNT(id) FROM loan_onboarding");
    $countStmt->execute();
    $totalRecords = $countStmt->fetchColumn();
    
    // Get applications with pagination and limited fields
    $stmt = $pdo->prepare("SELECT id, mobile, email, pan, pan_name, dob, gender, credit_score, 
                          vehicle_rc, vehicle_model, vehicle_make, vehicle_year, fuel_type, 
                          vehicle_value, income, employment, application_status, 
                          created_at, updated_at 
                          FROM loan_onboarding 
                          ORDER BY id DESC 
                          LIMIT :limit OFFSET :offset");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Parse JSON responses and fix field names for frontend compatibility
    foreach ($applications as &$app) {
        // Only parse JSON if fields exist and are not null
        $app['pan_response'] = !empty($app['pan_response']) ? json_decode($app['pan_response'], true) : null;
        $app['credit_response'] = !empty($app['credit_response']) ? json_decode($app['credit_response'], true) : null;
        $app['vehicle_response'] = !empty($app['vehicle_response']) ? json_decode($app['vehicle_response'], true) : null;
        
        // Map database fields to frontend expected fields
        $app['status'] = $app['application_status'] ?? 'pending';
        $app['step_completed'] = 6; // All onboarding applications are complete
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