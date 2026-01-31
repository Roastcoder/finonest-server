<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON data']);
    exit;
}

// Validate required fields
$required_fields = ['lender_id', 'banker_name', 'mobile_number', 'official_email', 'profile'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Field '$field' is required"]);
        exit;
    }
}

try {
    $db->beginTransaction();
    
    // Insert banker
    $query = "INSERT INTO bankers (lender_id, banker_name, mobile_number, official_email, profile, reporting_to, status) 
              VALUES (?, ?, ?, ?, ?, ?, 'active')";
    $stmt = $db->prepare($query);
    $stmt->execute([
        $data['lender_id'],
        $data['banker_name'],
        $data['mobile_number'],
        $data['official_email'],
        $data['profile'],
        $data['reporting_to'] === 'new' ? null : ($data['reporting_to'] ?: null)
    ]);
    
    $banker_id = $db->lastInsertId();
    
    // Insert territories
    if (isset($data['territories']) && is_array($data['territories'])) {
        foreach ($data['territories'] as $territory) {
            if (empty($territory['name']) || empty($territory['distance'])) continue;
            
            $territory_query = "INSERT INTO territories (banker_id, name, distance, latitude, longitude, address) 
                               VALUES (?, ?, ?, ?, ?, ?)";
            $territory_stmt = $db->prepare($territory_query);
            $territory_stmt->execute([
                $banker_id,
                $territory['name'],
                $territory['distance'],
                $territory['latitude'] ?: null,
                $territory['longitude'] ?: null,
                $territory['address'] ?: null
            ]);
            
            $territory_id = $db->lastInsertId();
            
            // Insert case types
            if (isset($territory['caseTypes']) && is_array($territory['caseTypes'])) {
                foreach ($territory['caseTypes'] as $caseType) {
                    if (!$caseType['enabled']) continue;
                    
                    $case_query = "INSERT INTO case_types (territory_id, type, remarks, loan_capping) 
                                  VALUES (?, ?, ?, ?)";
                    $case_stmt = $db->prepare($case_query);
                    $case_stmt->execute([
                        $territory_id,
                        $caseType['type'],
                        $caseType['remarks'] ?: null,
                        $caseType['loan_capping'] ?: null
                    ]);
                }
            }
        }
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Banker information submitted successfully',
        'banker_id' => $banker_id
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log('Banker form error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to submit banker information']);
}
?>