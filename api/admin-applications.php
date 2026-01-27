<?php
header('Content-Type: application/json');
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
    $pdo = getDBConnection();
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = ($page - 1) * $limit;
    
    // Get total count
    $countStmt = $pdo->query("SELECT COUNT(*) FROM loan_applications");
    $totalRecords = $countStmt->fetchColumn();
    
    // Get applications with pagination
    $stmt = $pdo->prepare("SELECT * FROM loan_applications ORDER BY created_at DESC LIMIT ? OFFSET ?");
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
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch applications']);
}
?>