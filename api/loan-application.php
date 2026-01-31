<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../middleware/cors.php';

// Call verification APIs
function callPanAPI($pan) {
    $url = 'https://api.finonest.com/api/pan-verify.php';
    $data = json_encode(['pan' => $pan]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

function callCreditAPI($pan, $mobile, $firstName, $lastName, $gender, $dob, $email = null) {
    $url = 'https://api.finonest.com/api/credit-report.php';
    
    // Ensure required fields have default values
    $email = $email ?: $mobile . '@example.com'; // Use provided email or generate from mobile
    $firstName = $firstName ?: 'User';
    $lastName = $lastName ?: 'Name';
    $gender = $gender ? strtolower($gender) : 'male';
    $dob = $dob ?: '1990-01-01';
    
    $data = json_encode([
        'phone' => $mobile,
        'email' => $email,
        'pan' => $pan,
        'firstName' => $firstName,
        'lastName' => $lastName,
        'gender' => $gender,
        'dateOfBirth' => $dob,
        'pincode' => '110001'
    ]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Log the response for debugging
    error_log('Credit API Response: ' . $response);
    
    return json_decode($response, true);
}

function callRcAPI($rcNumber) {
    $url = 'https://api.finonest.com/api/rc-surepass.php';
    $data = json_encode(['id_number' => $rcNumber]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(['success' => true, 'message' => 'Enhanced API is working', 'method' => 'GET']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// Handle both direct API calls and frontend application data
if (!$input || !isset($input['mobile'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required field: mobile']);
    exit;
}

// Check if this is a complete application from frontend or individual API call
if (isset($input['applicationId']) || isset($input['submittedAt'])) {
    // This is a complete application from frontend - save directly
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
            email VARCHAR(255),
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
            application_id VARCHAR(20),
            step_completed INT DEFAULT 6,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $pdo->exec($createTable);
        
        // Insert application data directly
        $stmt = $pdo->prepare("INSERT INTO loan_onboarding (
            mobile, email, pan, pan_name, pan_response, dob, gender, credit_score, credit_response,
            vehicle_rc, vehicle_model, vehicle_year, vehicle_make, owner_name, fuel_type, 
            vehicle_color, vehicle_response, vehicle_value, income, employment, application_id, step_completed
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $input['mobile'],
            $input['email'] ?? null,
            $input['pan'] ?? null,
            $input['panName'] ?? null,
            json_encode($input['panResponse'] ?? null),
            $input['dob'] ?? null,
            $input['gender'] ?? null,
            $input['creditScore'] ?? null,
            json_encode($input['creditResponse'] ?? null),
            $input['vehicleRC'] ?? null,
            $input['vehicleModel'] ?? null,
            $input['vehicleYear'] ?? null,
            $input['vehicleMake'] ?? null,
            $input['ownerName'] ?? null,
            $input['fuelType'] ?? null,
            $input['vehicleColor'] ?? null,
            json_encode($input['vehicleResponse'] ?? null),
            $input['vehicleValue'] ?? null,
            $input['income'] ?? null,
            $input['employment'] ?? null,
            $input['applicationId'] ?? ('APP' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT)),
            6
        ]);
        
        $id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'application_id' => $id,
            'message' => 'Application saved successfully'
        ]);
        exit;
        
    } catch (Exception $e) {
        error_log('Direct Application Save Error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save application', 'error' => $e->getMessage()]);
        exit;
    }
}

// Original API verification flow for individual calls
if (!isset($input['pan']) || !isset($input['vehicle_rc'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields: pan, vehicle_rc']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Auto-alter loan_onboarding table to add missing columns
    $alterQueries = [
        "ALTER TABLE loan_onboarding ADD COLUMN email VARCHAR(255)",
        "ALTER TABLE loan_onboarding ADD COLUMN pan_response JSON",
        "ALTER TABLE loan_onboarding ADD COLUMN credit_response JSON", 
        "ALTER TABLE loan_onboarding ADD COLUMN vehicle_response JSON",
        "ALTER TABLE loan_onboarding ADD COLUMN application_id VARCHAR(20)",
        "ALTER TABLE loan_onboarding ADD COLUMN step_completed INT DEFAULT 6",
        "ALTER TABLE loan_onboarding ADD COLUMN vehicle_value INT"
    ];
    
    foreach ($alterQueries as $query) {
        try {
            $pdo->exec($query);
        } catch(PDOException $e) {
            // Column might already exist, continue
        }
    }
    
    // Create table if not exists (fallback)
    $createTable = "CREATE TABLE IF NOT EXISTS loan_onboarding (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mobile VARCHAR(15) NOT NULL,
        email VARCHAR(255),
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
        application_id VARCHAR(20),
        step_completed INT DEFAULT 6,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($createTable);
    
    // Call verification APIs
    $panResponse = callPanAPI($input['pan']);
    $rcResponse = callRcAPI($input['vehicle_rc']);
    
    // Extract data from PAN response
    $panName = $panResponse['data']['full_name'] ?? '';
    $dob = $panResponse['data']['dob'] ?? '';
    $gender = $panResponse['data']['gender'] ?? '';
    
    // Call credit API with PAN data
    $firstName = explode(' ', $panName)[0] ?? '';
    $lastName = explode(' ', $panName)[1] ?? '';
    $creditResponse = callCreditAPI($input['pan'], $input['mobile'], $firstName, $lastName, $gender, $dob, $input['email'] ?? null);
    
    // Extract data from responses
    $creditScore = $creditResponse['data']['SCORE']['FCIREXScore'] ?? null;
    $vehicleMake = $rcResponse['data']['maker_description'] ?? '';
    $vehicleModel = $rcResponse['data']['maker_model'] ?? '';
    $vehicleYear = date('Y', strtotime($rcResponse['data']['manufacturing_date_formatted'] ?? ''));
    $fuelType = $rcResponse['data']['fuel_type'] ?? '';
    $vehicleColor = $rcResponse['data']['color'] ?? '';
    $ownerName = $rcResponse['data']['owner_name'] ?? '';
    $vehicleValue = 800000; // Default estimation
    
    // Generate application ID
    $applicationId = 'APP' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
    
    // Insert loan application with verification data
    $stmt = $pdo->prepare("INSERT INTO loan_onboarding (
        mobile, email, pan, pan_name, pan_response, dob, gender, credit_score, credit_response,
        vehicle_rc, vehicle_model, vehicle_year, vehicle_make, owner_name, fuel_type, 
        vehicle_color, vehicle_response, vehicle_value, income, employment, application_id, step_completed
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $input['mobile'],
        $input['email'] ?? ($input['mobile'] . '@example.com'),
        $input['pan'],
        $panName,
        json_encode($panResponse),
        $dob,
        $gender,
        $creditScore,
        json_encode($creditResponse),
        $input['vehicle_rc'],
        $vehicleModel,
        $vehicleYear ?: null,
        $vehicleMake,
        $ownerName,
        $fuelType,
        $vehicleColor,
        json_encode($rcResponse),
        $vehicleValue,
        18000, // Default income
        'salaried', // Default employment
        $applicationId,
        6
    ]);
    
    $id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'application_id' => $id,
        'message' => 'Application saved successfully with verification data',
        'verification_data' => [
            'pan_verified' => !empty($panResponse['success']),
            'credit_score' => $creditScore,
            'vehicle_verified' => !empty($rcResponse['success']),
            'pan_name' => $panName,
            'vehicle_details' => $vehicleMake . ' ' . $vehicleModel
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Loan Application Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save application', 'error' => $e->getMessage()]);
}
?>