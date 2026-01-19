<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/database.php';

function validateApiKey() {
    $headers = getallheaders();
    $apiKey = $headers['X-API-Key'] ?? null;
    
    if (!$apiKey || $apiKey !== 'lms_8188272ffd90118df860b5e768fe6681') {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid API key']);
        exit();
    }
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'POST':
        submitLead();
        break;
    case 'GET':
        getLeads();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function submitLead() {
    global $db;
    
    validateApiKey();
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    $required_fields = ['name', 'mobile', 'email', 'product_id', 'channel_code'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Field '$field' is required"]);
            return;
        }
    }
    
    try {
        // Create leads table if not exists
        $createTable = "CREATE TABLE IF NOT EXISTS leads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            external_id INT,
            name VARCHAR(255) NOT NULL,
            mobile VARCHAR(15) NOT NULL,
            email VARCHAR(255) NOT NULL,
            product_id INT NOT NULL,
            product_name VARCHAR(255),
            product_variant VARCHAR(255),
            product_highlights TEXT,
            bank_redirect_url VARCHAR(500),
            channel_code VARCHAR(50) NOT NULL,
            status ENUM('new', 'contacted', 'qualified', 'converted', 'rejected') DEFAULT 'new',
            source VARCHAR(100) DEFAULT 'API',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_mobile (mobile),
            INDEX idx_email (email),
            INDEX idx_channel (channel_code),
            INDEX idx_external (external_id)
        )";
        $db->exec($createTable);
        
        // Get product details for saving with lead
        $productQuery = "SELECT name, variant, product_highlights, bank_redirect_url FROM products WHERE id = ?";
        $productStmt = $db->prepare($productQuery);
        $productStmt->execute([$data['product_id']]);
        $product = $productStmt->fetch(PDO::FETCH_ASSOC);
        
        // If product not found in local DB, fetch from external API and save
        if (!$product) {
            try {
                $apiResponse = file_get_contents('https://api.finonest.com/api/products', false, stream_context_create([
                    'http' => [
                        'header' => "X-API-Key: " . (getenv('API_KEY') ?: 'lms_8188272ffd90118df860b5e768fe6681')
                    ]
                ]));
                $apiData = json_decode($apiResponse, true);
                
                if ($apiData && $apiData['status'] === 200) {
                    // Save all products to local DB
                    $insertProduct = "INSERT IGNORE INTO products (id, name, category, variant, commission_rate, card_image, variant_image, product_highlights, bank_redirect_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $insertStmt = $db->prepare($insertProduct);
                    
                    foreach ($apiData['data'] as $apiProduct) {
                        $insertStmt->execute([
                            $apiProduct['id'],
                            $apiProduct['name'],
                            $apiProduct['category'],
                            $apiProduct['variant'],
                            $apiProduct['commission_rate'],
                            $apiProduct['card_image'],
                            $apiProduct['variant_image'],
                            $apiProduct['product_highlights'],
                            $apiProduct['bank_redirect_url']
                        ]);
                    }
                    
                    // Get the specific product we need
                    $productStmt->execute([$data['product_id']]);
                    $product = $productStmt->fetch(PDO::FETCH_ASSOC);
                }
            } catch (Exception $e) {
                // Continue without product details if API fails
                $product = null;
            }
        }
        
        // Check for duplicate lead
        $checkDuplicate = "SELECT id FROM leads WHERE mobile = ? OR email = ?";
        $stmt = $db->prepare($checkDuplicate);
        $stmt->execute([$data['mobile'], $data['email']]);
        
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Lead already exists with this mobile or email']);
            return;
        }
        
        $query = "INSERT INTO leads (name, mobile, email, product_id, product_name, product_variant, product_highlights, bank_redirect_url, channel_code, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            $data['name'],
            $data['mobile'],
            $data['email'],
            $data['product_id'],
            $product['name'] ?? null,
            $product['variant'] ?? null,
            $product['product_highlights'] ?? null,
            $product['bank_redirect_url'] ?? null,
            $data['channel_code'],
            $data['notes'] ?? null
        ]);
        
        $leadId = $db->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Lead submitted successfully',
            'lead_id' => $leadId
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to submit lead']);
    }
}

function getLeads() {
    global $db;
    
    validateApiKey();
    
    // Only allow admin access for leads
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
}
?>