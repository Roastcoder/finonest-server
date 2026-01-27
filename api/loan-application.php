<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Debug: Log the request method
error_log('Request method: ' . $_SERVER['REQUEST_METHOD']);

require_once '../config/database.php';
require_once '../middleware/cors.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(['success' => true, 'message' => 'API is working', 'method' => 'GET']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed', 'received_method' => $_SERVER['REQUEST_METHOD']]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Create table if not exists
    $createTable = "CREATE TABLE IF NOT EXISTS loan_onboarding (
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
    
    // Insert loan application
    $stmt = $pdo->prepare("INSERT INTO loan_onboarding (
        mobile, pan, pan_name, pan_response, dob, gender, credit_score, credit_response,
        vehicle_rc, vehicle_model, vehicle_year, vehicle_make, owner_name, fuel_type, 
        vehicle_color, vehicle_response, vehicle_value, income, employment
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $input['mobile'] ?? '',
        $input['pan'] ?? '',
        $input['panName'] ?? '',
        json_encode($input['panResponse'] ?? null),
        $input['dob'] ?? '',
        $input['gender'] ?? '',
        $input['creditScore'] ?? 0,
        json_encode($input['creditResponse'] ?? null),
        $input['vehicleRC'] ?? '',
        $input['vehicleModel'] ?? '',
        $input['vehicleYear'] ?? 0,
        $input['vehicleMake'] ?? '',
        $input['ownerName'] ?? '',
        $input['fuelType'] ?? '',
        $input['vehicleColor'] ?? '',
        json_encode($input['vehicleResponse'] ?? null),
        $input['vehicleValue'] ?? 0,
        $input['income'] ?? 0,
        $input['employment'] ?? ''
    ]);
    
    $applicationId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'application_id' => $applicationId,
        'message' => 'Application saved successfully'
    ]);
    
} catch (Exception $e) {
    error_log('Loan Application Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save application', 'error' => $e->getMessage()]);
}
?>