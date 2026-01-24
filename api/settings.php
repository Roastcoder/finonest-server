<?php
require_once __DIR__ . '/../middleware/cors_secure.php';
SecureCorsMiddleware::handle();

require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
} catch (Exception $e) {
    error_log('Database connection error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
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

// Create settings table if it doesn't exist
try {
    $createTable = "CREATE TABLE IF NOT EXISTS system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(255) UNIQUE NOT NULL,
        setting_value TEXT,
        description TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($createTable);
    
    // Insert default settings if they don't exist
    $defaultSettings = [
        ['razorpay_key', 'rzp_test_default', 'Razorpay API Key for payments'],
        ['razorpay_secret', '', 'Razorpay Secret Key (keep secure)'],
        ['site_name', 'Finonest', 'Website name'],
        ['contact_email', 'info@finonest.com', 'Contact email address'],
        ['gemini_api_key', 'AIzaSyDZ8XZq09tzFqvuTAbcJlQscS_WUNDbkAI', 'Google Gemini API key for AI features'],
        ['gemini_model', 'gemini-2.5-flash-lite', 'Gemini model for AI operations'],
        ['ai_enabled', 'enabled', 'Enable or disable AI features']
    ];
    
    foreach ($defaultSettings as $setting) {
        $checkQuery = "SELECT id FROM system_settings WHERE setting_key = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$setting[0]]);
        
        if ($checkStmt->rowCount() === 0) {
            $insertQuery = "INSERT INTO system_settings (setting_key, setting_value, description) VALUES (?, ?, ?)";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->execute($setting);
        }
    }
} catch (PDOException $e) {
    error_log('Table creation error: ' . $e->getMessage());
}

$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Handle specific setting requests
if (preg_match('/\/api\/settings\/(.+)/', $path, $matches)) {
    $setting_key = $matches[1];
    
    // Normalize key (convert hyphens to underscores)
    $setting_key = str_replace('-', '_', $setting_key);
    
    switch($method) {
        case 'GET':
            getSetting($setting_key);
            break;
        case 'PUT':
            updateSetting($setting_key);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} else {
    switch($method) {
        case 'GET':
            getAllSettings();
            break;
        case 'POST':
            createSetting();
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}

function getSetting($key) {
    global $db;
    
    // Public access for certain settings (no auth required)
    $publicSettings = ['razorpay_key', 'site_name'];
    
    if (!in_array($key, $publicSettings)) {
        requireAdmin();
    }
    
    try {
        $query = "SELECT setting_value FROM system_settings WHERE setting_key = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'key' => $result['setting_value']
            ]);
        } else {
            // Return default for razorpay_key if not found
            if ($key === 'razorpay_key') {
                echo json_encode([
                    'success' => true,
                    'key' => 'rzp_test_default'
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Setting not found']);
            }
        }
    } catch (Exception $e) {
        error_log('Error in getSetting: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get setting']);
    }
}

function getAllSettings() {
    global $db;
    
    requireAdmin();
    
    try {
        $query = "SELECT setting_key, setting_value, description, updated_at FROM system_settings ORDER BY setting_key";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'settings' => $settings
        ]);
    } catch (Exception $e) {
        error_log('Error in getAllSettings: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get settings']);
    }
}

function updateSetting($key) {
    global $db;
    
    requireAdmin();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $value = $input['value'] ?? '';
    
    try {
        // First try to update existing setting
        $query = "UPDATE system_settings SET setting_value = ? WHERE setting_key = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$value, $key]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Setting updated successfully'
            ]);
        } else {
            // Setting doesn't exist, create it
            $insertQuery = "INSERT INTO system_settings (setting_key, setting_value, description) VALUES (?, ?, ?)";
            $insertStmt = $db->prepare($insertQuery);
            $description = $key === 'razorpay_secret' ? 'Razorpay Secret Key (keep secure)' : '';
            $insertStmt->execute([$key, $value, $description]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Setting created successfully'
            ]);
        }
    } catch (Exception $e) {
        error_log('Error in updateSetting: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update setting']);
    }
}

function createSetting() {
    global $db;
    
    requireAdmin();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $key = $input['key'] ?? '';
    $value = $input['value'] ?? '';
    $description = $input['description'] ?? '';
    
    if (empty($key)) {
        http_response_code(400);
        echo json_encode(['error' => 'Setting key is required']);
        return;
    }
    
    try {
        $query = "INSERT INTO system_settings (setting_key, setting_value, description) VALUES (?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$key, $value, $description]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Setting created successfully'
        ]);
    } catch (Exception $e) {
        error_log('Error in createSetting: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create setting']);
    }
}
?>