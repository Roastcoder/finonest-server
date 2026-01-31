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

if (!isset($input['pan']) || empty($input['pan'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'PAN number is required']);
    exit;
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
    
    $apiUrl = $baseUrl . 'exp/validation-service/pan-premium';
    
    $stmt->execute(['client_user_id']);
    $clientUserId = $stmt->fetchColumn() ?: '';
    
    $stmt->execute(['secret_key']);
    $secretKey = $stmt->fetchColumn() ?: '';
    
    $stmt->execute(['access_key']);
    $accessKey = $stmt->fetchColumn() ?: '';
    
    // Call external PAN API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['pan' => $input['pan']]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'client-user-id: ' . $clientUserId,
        'secret-key: ' . $secretKey,
        'access-key: ' . $accessKey,
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
    echo json_encode(['success' => false, 'message' => 'PAN verification failed']);
}
?>