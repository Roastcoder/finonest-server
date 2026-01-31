<?php
require_once __DIR__ . '/../cors-handler.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Create table if not exists
    $createTable = "CREATE TABLE IF NOT EXISTS dsa_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(255) NOT NULL,
        mobile_number VARCHAR(20) NOT NULL,
        email VARCHAR(255) NOT NULL,
        pan_number VARCHAR(10) NULL,
        aadhar_number VARCHAR(12) NULL,
        date_of_birth DATE NOT NULL,
        address TEXT NOT NULL,
        city VARCHAR(100) NOT NULL,
        state VARCHAR(100) NOT NULL,
        pincode VARCHAR(6) NOT NULL,
        current_profession VARCHAR(100) NOT NULL,
        experience_years INT NOT NULL,
        monthly_income VARCHAR(50),
        business_type ENUM('individual', 'firm') NOT NULL,
        gst_number VARCHAR(15),
        firm_name VARCHAR(255),
        bank_name VARCHAR(255) NULL,
        account_number VARCHAR(50) NULL,
        ifsc_code VARCHAR(11) NULL,
        preferred_products JSON,
        target_monthly_cases VARCHAR(50),
        coverage_area VARCHAR(255),
        remarks TEXT,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $db->exec($createTable);
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    $required = ['full_name', 'mobile_number', 'email', 'date_of_birth', 
                'address', 'city', 'state', 'pincode', 
                'current_profession', 'experience_years', 'business_type'];
    
    foreach ($required as $field) {
        if (empty($input[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    // Validate preferred_products is not empty
    if (empty($input['preferred_products']) || !is_array($input['preferred_products'])) {
        throw new Exception('At least one preferred product must be selected');
    }
    
    // Check if email or mobile already exists
    $checkQuery = "SELECT id FROM dsa_applications WHERE email = ? OR mobile_number = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$input['email'], $input['mobile_number']]);
    
    if ($checkStmt->fetch()) {
        throw new Exception('Application already exists with this email or mobile number');
    }
    
    $query = "INSERT INTO dsa_applications (
        full_name, mobile_number, email, pan_number, aadhar_number, date_of_birth,
        address, city, state, pincode, current_profession, experience_years,
        monthly_income, business_type, gst_number, firm_name, bank_name,
        account_number, ifsc_code, preferred_products, target_monthly_cases,
        coverage_area, remarks, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
    
    $stmt = $db->prepare($query);
    $result = $stmt->execute([
        $input['full_name'],
        $input['mobile_number'],
        $input['email'],
        $input['pan_number'] ?? null,
        $input['aadhar_number'] ?? null,
        $input['date_of_birth'],
        $input['address'],
        $input['city'],
        $input['state'],
        $input['pincode'],
        $input['current_profession'],
        (int)$input['experience_years'],
        $input['monthly_income'] ?? null,
        $input['business_type'],
        $input['gst_number'] ?? null,
        $input['firm_name'] ?? null,
        $input['bank_name'] ?? null,
        $input['account_number'] ?? null,
        $input['ifsc_code'] ?? null,
        json_encode($input['preferred_products']),
        $input['target_monthly_cases'] ?? null,
        $input['coverage_area'] ?? null,
        $input['remarks'] ?? null
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'DSA application submitted successfully',
            'application_id' => $db->lastInsertId()
        ]);
    } else {
        throw new Exception('Failed to submit application');
    }
    
} catch (Exception $e) {
    error_log("DSA Registration Error: " . $e->getMessage());
    error_log("Input data: " . json_encode($input ?? []));
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>