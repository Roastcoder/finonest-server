<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../middleware/cors.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$required = ['phone', 'email', 'pan', 'firstName', 'lastName', 'gender', 'dateOfBirth', 'pincode'];
foreach ($required as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => ucfirst($field) . ' is required']);
        exit;
    }
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Get API credentials from settings
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    
    $stmt->execute(['base_url']);
    $baseUrl = $stmt->fetchColumn() ?: 'https://profilex-api.neokred.tech/core-svc/api/v2/';
    
    $apiUrl = $baseUrl . 'exp/user-profiling/credit-report';
    
    $stmt->execute(['client_user_id']);
    $clientUserId = $stmt->fetchColumn() ?: '';
    
    $stmt->execute(['secret_key']);
    $secretKey = $stmt->fetchColumn() ?: '';
    
    $stmt->execute(['access_key']);
    $accessKey = $stmt->fetchColumn() ?: '';
    
    $stmt->execute(['service_id']);
    $serviceId = $stmt->fetchColumn() ?: '';
    
    // Call external Credit Report API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($input));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'client-user-id: ' . $clientUserId,
        'secret-key: ' . $secretKey,
        'access-key: ' . $accessKey,
        'service-id: ' . $serviceId,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response === false || $httpCode !== 200) {
        throw new Exception('API call failed');
    }
    
    $data = json_decode($response, true);
    echo json_encode($data);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Credit report failed']);
}
?>