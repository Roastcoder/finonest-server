<?php
require_once __DIR__ . '/../../middleware/cors_secure.php';
SecureCorsMiddleware::handle();

require_once __DIR__ . '/../../config/jwt.php';
require_once __DIR__ . '/../../config/database.php';

function requireAdmin() {
    $headers = getallheaders();
    $token = null;

    if (isset($headers['Authorization'])) {
        $auth_header = $headers['Authorization'];
        if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
            $token = $matches[1];
        }
    }

    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'No token provided']);
        exit();
    }

    $decoded = JWT::decode($token);
    if (!$decoded || $decoded['role'] !== 'ADMIN') {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        exit();
    }

    return $decoded;
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));

switch($method) {
    case 'GET':
        getLeads();
        break;
    case 'PUT':
        if (isset($request[0]) && isset($request[1]) && $request[1] === 'status') {
            updateLeadStatus($request[0]);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function getLeads() {
    global $db;
    
    requireAdmin();
    
    try {
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

function updateLeadStatus($leadId) {
    global $db;
    
    requireAdmin();
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['status'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Status is required']);
        return;
    }
    
    try {
        $query = "UPDATE leads SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$data['status'], $leadId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Lead status updated successfully'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Lead not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update lead status']);
    }
}
?>