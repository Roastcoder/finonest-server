<?php
require_once '../cors-handler.php';
require_once '../config/database.php';

// Public endpoint - no authentication required
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Fetch only AI-related settings
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('gemini_api_key', 'gemini_model', 'ai_enabled')");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $config = [
        'apiKey' => '',
        'model' => 'gemini-1.5-flash',
        'enabled' => false
    ];
    
    foreach ($settings as $setting) {
        switch ($setting['setting_key']) {
            case 'gemini_api_key':
                $config['apiKey'] = $setting['setting_value'];
                break;
            case 'gemini_model':
                $config['model'] = $setting['setting_value'] ?: 'gemini-1.5-flash';
                break;
            case 'ai_enabled':
                $config['enabled'] = $setting['setting_value'] === 'enabled';
                break;
        }
    }
    
    echo json_encode($config);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'apiKey' => '',
        'model' => 'gemini-1.5-flash',
        'enabled' => false
    ]);
}
?>