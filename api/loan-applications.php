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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    $stmt = $pdo->prepare("SELECT * FROM loan_applications ORDER BY created_at DESC");
    $stmt->execute();
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data
    foreach ($applications as &$app) {
        // Decode JSON fields
        if ($app['pan_response']) {
            $app['pan_response'] = json_decode($app['pan_response'], true);
        }
        if ($app['credit_response']) {
            $app['credit_response'] = json_decode($app['credit_response'], true);
        }
        if ($app['vehicle_response']) {
            $app['vehicle_response'] = json_decode($app['vehicle_response'], true);
        }
        
        // Add application_id if not exists
        if (!isset($app['application_id'])) {
            $app['application_id'] = 'APP' . str_pad($app['id'], 6, '0', STR_PAD_LEFT);
        }
        
        // Map status field
        if (isset($app['application_status'])) {
            $app['status'] = $app['application_status'];
        }
        
        // Add step_completed if not exists
        if (!isset($app['step_completed'])) {
            $app['step_completed'] = 6; // Assume complete if all data exists
        }
        
        // Add vehicle_value if not exists
        if (!isset($app['vehicle_value'])) {
            $app['vehicle_value'] = 0;
        }
    }
    
    echo json_encode([
        'success' => true,
        'applications' => $applications
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to fetch applications',
        'error' => $e->getMessage()
    ]);
}
?>