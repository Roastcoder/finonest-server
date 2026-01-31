<?php
require_once __DIR__ . '/../middleware/cors_secure.php';
SecureCorsMiddleware::handle();

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'POST':
        submitContactForm();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function submitContactForm() {
    global $db;
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Validate required fields
    if (!isset($data['name']) || !isset($data['phone']) || !isset($data['email'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }
    
    try {
        // Create contact_forms table if it doesn't exist
        $create_table = "CREATE TABLE IF NOT EXISTS contact_forms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            email VARCHAR(255) NOT NULL,
            loan_type VARCHAR(100),
            amount VARCHAR(50),
            consent_terms BOOLEAN DEFAULT FALSE,
            consent_data_processing BOOLEAN DEFAULT FALSE,
            consent_communication BOOLEAN DEFAULT FALSE,
            consent_marketing BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $db->exec($create_table);
        
        // Insert contact form data
        $query = "INSERT INTO contact_forms 
                  (name, phone, email, loan_type, amount, consent_terms, consent_data_processing, consent_communication, consent_marketing) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            $data['name'],
            $data['phone'],
            $data['email'],
            $data['loan_type'] ?? '',
            $data['amount'] ?? '',
            isset($data['consent_terms']) ? ($data['consent_terms'] ? 1 : 0) : 0,
            isset($data['consent_data_processing']) ? ($data['consent_data_processing'] ? 1 : 0) : 0,
            isset($data['consent_communication']) ? ($data['consent_communication'] ? 1 : 0) : 0,
            isset($data['consent_marketing']) ? ($data['consent_marketing'] ? 1 : 0) : 0
        ]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Contact form submitted successfully',
                'id' => $db->lastInsertId()
            ]);
        } else {
            throw new Exception('Failed to insert data');
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>