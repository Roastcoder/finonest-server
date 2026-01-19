<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../config/database.php';

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
        if (isset($request[0]) && $request[0] === 'admin') {
            getAllBranchesAdmin();
        } else {
            getAllBranches();
        }
        break;
    case 'POST':
        createBranch();
        break;
    case 'PUT':
        if (isset($request[0]) && isset($request[1]) && $request[1] === 'position') {
            updateBranchPosition($request[0]);
        } elseif (isset($request[0])) {
            updateBranch($request[0]);
        }
        break;
    case 'DELETE':
        if (isset($request[0])) {
            deleteBranch($request[0]);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function getAllBranches() {
    global $db;
    
    try {
        $query = "SELECT * FROM branches WHERE status = 'active' ORDER BY city, name";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'branches' => $branches
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch branches']);
    }
}

function getAllBranchesAdmin() {
    global $db;
    
    requireAdmin();
    
    try {
        $query = "SELECT * FROM branches ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'branches' => $branches
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch branches']);
    }
}

function createBranch() {
    global $db;
    
    requireAdmin();
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    $required_fields = ['name', 'address', 'city', 'state', 'pincode', 'latitude', 'longitude'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Field '$field' is required"]);
            return;
        }
    }
    
    try {
        $query = "INSERT INTO branches (name, address, city, state, pincode, phone, email, latitude, longitude, manager_name, working_hours, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            $data['name'],
            $data['address'],
            $data['city'],
            $data['state'],
            $data['pincode'],
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['latitude'],
            $data['longitude'],
            $data['manager_name'] ?? null,
            $data['working_hours'] ?? '9:00 AM - 6:00 PM',
            $data['status'] ?? 'active'
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Branch created successfully',
            'id' => $db->lastInsertId()
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create branch']);
    }
}

function updateBranch($id) {
    global $db;
    
    requireAdmin();
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    try {
        $query = "UPDATE branches SET name = ?, address = ?, city = ?, state = ?, pincode = ?, 
                  phone = ?, email = ?, latitude = ?, longitude = ?, manager_name = ?, 
                  working_hours = ?, status = ?, updated_at = CURRENT_TIMESTAMP 
                  WHERE id = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            $data['name'],
            $data['address'],
            $data['city'],
            $data['state'],
            $data['pincode'],
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['latitude'],
            $data['longitude'],
            $data['manager_name'] ?? null,
            $data['working_hours'] ?? '9:00 AM - 6:00 PM',
            $data['status'] ?? 'active',
            $id
        ]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Branch updated successfully'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Branch not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update branch']);
    }
}

function deleteBranch($id) {
    global $db;
    
    requireAdmin();
    
    try {
        $query = "DELETE FROM branches WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Branch deleted successfully'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Branch not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete branch']);
    }
}

function updateBranchPosition($id) {
    global $db;
    
    requireAdmin();
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    try {
        $query = "UPDATE branches SET x_position = ?, y_position = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([
            $data['x_position'],
            $data['y_position'],
            $id
        ]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Branch position updated successfully'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Branch not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update branch position']);
    }
}
?>