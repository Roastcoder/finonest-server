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

if (!isset($input['id_number']) || empty($input['id_number'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'RC number is required']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Get SurePass API credentials from settings
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    
    $stmt->execute(['surepass_api_url']);
    $apiUrl = $stmt->fetchColumn() ?: 'https://kyc-api.surepass.app/api/v1/rc/rc-full';
    
    $stmt->execute(['surepass_token']);
    $token = $stmt->fetchColumn();
    
    if (empty($token)) {
        // Return mock data for testing if token not configured
        echo json_encode([
            'success' => true,
            'data' => [
                'owner_name' => 'Test Owner',
                'maker_description' => 'MARUTI SUZUKI',
                'maker_model' => 'SWIFT',
                'manufacturing_date_formatted' => '2020-01-01',
                'fuel_type' => 'PETROL',
                'color' => 'WHITE',
                'rc_number' => $input['id_number']
            ],
            'message' => 'Mock RC data (token not configured)'
        ]);
        exit;
    }
    
    // Call SurePass RC API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($input));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
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
    error_log('RC Verification Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'RC verification failed', 'error' => $e->getMessage()]);
}
?>