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

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Get Gemini API settings from system_settings table (same as blog generation)
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    
    $stmt->execute(['gemini_api_key']);
    $apiKey = $stmt->fetchColumn();
    
    $stmt->execute(['gemini_model']);
    $model = $stmt->fetchColumn() ?: 'gemini-2.5-flash-lite';
    
    $stmt->execute(['ai_enabled']);
    $aiEnabled = $stmt->fetchColumn();
    
    if (empty($apiKey) || $aiEnabled !== 'enabled') {
        // Use fallback calculation if AI is disabled or no API key
        $baseValue = 500000;
        $depreciationRate = 0.15;
        $currentYear = date('Y');
        $vehicleYear = $input['vehicleYear'] ?? $currentYear;
        $age = max(0, $currentYear - $vehicleYear);
        $marketValue = $baseValue * pow(1 - $depreciationRate, $age);
        
        echo json_encode([
            'success' => true,
            'market_value' => round($marketValue),
            'explanation' => 'Calculated using depreciation model (AI disabled)',
            'fallback' => true
        ]);
        exit;
    }
    
    // Prepare vehicle data for valuation
    $vehicleModel = $input['vehicleModel'] ?? '';
    $vehicleMake = $input['vehicleMake'] ?? '';
    $vehicleYear = $input['vehicleYear'] ?? '';
    $fuelType = $input['fuelType'] ?? '';
    $bodyType = $input['bodyType'] ?? '';
    $cubicCapacity = $input['cubicCapacity'] ?? '';
    $seatCapacity = $input['seatCapacity'] ?? '';
    $vehicleCategory = $input['vehicleCategory'] ?? '';
    $registrationDate = $input['registrationDate'] ?? '';
    $city = $input['city'] ?? 'Mumbai';
    $condition = $input['condition'] ?? 'Good';
    
    $prompt = "Estimate the current market value of this vehicle in Indian Rupees:
    
Vehicle Details:
- Make: {$vehicleMake}
- Model: {$vehicleModel}
- Year: {$vehicleYear}
- Registration Date: {$registrationDate}
- Fuel Type: {$fuelType}
- Body Type: {$bodyType}
- Engine Capacity: {$cubicCapacity} CC
- Seat Capacity: {$seatCapacity}
- Vehicle Category: {$vehicleCategory}
- City: {$city}
- Condition: {$condition}

Please provide:
1. Current market value in INR (just the number)
2. Brief explanation of factors considered

Format response as JSON:
{
  \"market_value\": 850000,
  \"explanation\": \"Based on depreciation, market demand, fuel type, and condition\"
}";
    
    // Call Gemini API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ]
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response === false || $httpCode !== 200) {
        throw new Exception('Gemini API call failed');
    }
    
    $data = json_decode($response, true);
    $generatedText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    
    // Extract JSON from response
    preg_match('/\{[^}]*"market_value"[^}]*\}/', $generatedText, $matches);
    if (!empty($matches)) {
        $valuation = json_decode($matches[0], true);
        echo json_encode([
            'success' => true,
            'market_value' => $valuation['market_value'] ?? 0,
            'explanation' => $valuation['explanation'] ?? 'AI-based valuation',
            'raw_response' => $generatedText
        ]);
    } else {
        // Fallback calculation
        $baseValue = 500000; // Base value
        $depreciationRate = 0.15; // 15% per year
        $currentYear = date('Y');
        $age = max(0, $currentYear - $vehicleYear);
        $marketValue = $baseValue * pow(1 - $depreciationRate, $age);
        
        echo json_encode([
            'success' => true,
            'market_value' => round($marketValue),
            'explanation' => 'Calculated using depreciation model',
            'fallback' => true
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Car valuation failed']);
}
?>