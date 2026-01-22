<?php
require_once __DIR__ . '/../middleware/cors_secure.php';
SecureCorsMiddleware::handle();

error_reporting(0);

try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/jwt.php';
} catch (Exception $e) {
    echo json_encode(['success' => true, 'leads' => []]);
    exit();
}

function requireAdmin() {
    $headers = apache_request_headers() ?: [];
    $token = null;

    foreach ($headers as $key => $value) {
        if (strtolower($key) === 'authorization') {
            if (preg_match('/Bearer\s(\S+)/', $value, $matches)) {
                $token = $matches[1];
            }
            break;
        }
    }

    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'No token provided']);
        exit();
    }

    try {
        $decoded = JWT::decode($token);
        if (!$decoded || $decoded['role'] !== 'ADMIN') {
            http_response_code(403);
            echo json_encode(['error' => 'Admin access required']);
            exit();
        }
        return $decoded;
    } catch (Exception $e) {
        error_log('JWT decode error: ' . $e->getMessage());
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token']);
        exit();
    }
}

function validateApiKey() {
    $headers = getallheaders();
    $apiKey = $headers['X-API-Key'] ?? null;
    
    if (!$apiKey || $apiKey !== 'lms_8188272ffd90118df860b5e768fe6681') {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid API key']);
        exit();
    }
}

try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    echo json_encode(['success' => true, 'leads' => []]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'POST':
        submitLead();
        break;
    case 'GET':
        getLeads();
        break;
    case 'PUT':
        updateLeadStatus();
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
        
        // Create products table if not exists
        $createProductsTable = "CREATE TABLE IF NOT EXISTS products (
            id INT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            category VARCHAR(100),
            variant VARCHAR(255),
            commission_rate DECIMAL(10,2),
            card_image VARCHAR(500),
            variant_image VARCHAR(500),
            product_highlights TEXT,
            bank_redirect_url VARCHAR(500),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $db->exec($createProductsTable);
        
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
        
        $existingLead = $stmt->fetch();
        if ($existingLead) {
            http_response_code(409);
            echo json_encode([
                'status' => 409,
                'message' => 'Duplicate application found. Existing Lead ID: ' . $existingLead['id'],
                'data' => null,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
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
        
        // Also save to external cards API
        try {
            $externalData = $data;
            $externalData['lead_id'] = $leadId;
            
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n" .
                               "X-API-Key: lms_8188272ffd90118df860b5e768fe6681\r\n",
                    'content' => json_encode($externalData)
                ]
            ]);
            
            file_get_contents('https://cards.finonest.com/api/leads', false, $context);
        } catch (Exception $e) {
            // Continue even if external API fails
        }
        
        echo json_encode([
            'status' => 201,
            'message' => 'Lead created successfully',
            'data' => ['lead_id' => $leadId],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to submit lead']);
    }
}

function getLeads() {
    global $db;
    
    requireAdmin();
    
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
        
        $query = "SELECT * FROM leads ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'leads' => $leads
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch leads']);
    }
}

function updateLeadStatus() {
    global $db;
    
    validateApiKey();
    
    // Get lead ID from URL path
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $leadId = null;
    
    // Look for lead ID in path like /api/leads/123/status
    for ($i = 0; $i < count($pathParts); $i++) {
        if ($pathParts[$i] === 'leads' && isset($pathParts[$i + 1]) && is_numeric($pathParts[$i + 1])) {
            $leadId = (int)$pathParts[$i + 1];
            break;
        }
    }
    
    if (!$leadId) {
        http_response_code(400);
        echo json_encode(['error' => 'Lead ID is required']);
        return;
    }
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['status'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Status is required']);
        return;
    }
    
    $validStatuses = ['new', 'contacted', 'qualified', 'converted', 'rejected'];
    if (!in_array($data['status'], $validStatuses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid status']);
        return;
    }
    
    try {
        $query = "UPDATE leads SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$data['status'], $leadId]);
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Lead not found']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Lead status updated successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update lead status']);
    }
}
?>