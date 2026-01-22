<?php
require_once __DIR__ . '/../middleware/cors_secure.php';
SecureCorsMiddleware::handle();

require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Application.php';
require_once __DIR__ . '/../models/User.php';

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
$application = new Application($db);
$user_model = new User($db);

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'], '/'));

switch($method) {
    case 'GET':
        if ($request[0] === 'forms') {
            getAllForms();
        } elseif ($request[0] === 'contact-forms') {
            getContactForms();
        } elseif ($request[0] === 'users') {
            getAllUsers();
        } elseif ($request[0] === 'branches') {
            getBranches();
        }
        break;
    case 'PUT':
        if ($request[0] === 'forms' && isset($request[1])) {
            updateFormStatus($request[1]);
        } elseif ($request[0] === 'users' && isset($request[1])) {
            updateUserRole($request[1]);
        } elseif ($request[0] === 'branches' && isset($request[1])) {
            updateBranch($request[1]);
        }
        break;
    case 'DELETE':
        if ($request[0] === 'users' && isset($request[1])) {
            deleteUser($request[1]);
        } elseif ($request[0] === 'branches' && isset($request[1])) {
            deleteBranch($request[1]);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function getAllForms() {
    global $application;
    
    requireAdmin();
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $offset = ($page - 1) * $limit;
    
    $applications = $application->getAll($limit, $offset, $status);
    
    echo json_encode([
        'success' => true,
        'applications' => $applications,
        'page' => $page,
        'limit' => $limit,
        'status' => $status
    ]);
}

function updateFormStatus($id) {
    global $application;
    
    requireAdmin();
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['status']) || !in_array($data['status'], ['SUBMITTED', 'UNDER_REVIEW', 'APPROVED', 'REJECTED'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid status required']);
        return;
    }

    if ($application->updateStatus($id, $data['status'])) {
        echo json_encode([
            'success' => true,
            'message' => 'Status updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update status']);
    }
}

function getContactForms() {
    global $db;
    
    requireAdmin();
    
    try {
        $query = "SELECT * FROM contact_forms ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $forms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'forms' => $forms
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch contact forms']);
    }
}

function getAllUsers() {
    global $user_model;
    
    requireAdmin();
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    
    $users = $user_model->getAll($limit, $offset);
    $total = $user_model->getTotalCount();
    
    echo json_encode([
        'success' => true,
        'users' => $users,
        'total' => $total,
        'page' => $page,
        'limit' => $limit
    ]);
}

function updateUserRole($id) {
    global $user_model;
    
    requireAdmin();
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['role']) || !in_array($data['role'], ['USER', 'ADMIN'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid role required (USER or ADMIN)']);
        return;
    }

    if ($user_model->updateRole($id, $data['role'])) {
        echo json_encode([
            'success' => true,
            'message' => 'User role updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update user role']);
    }
}

function deleteUser($id) {
    global $user_model;
    
    requireAdmin();
    
    if ($user_model->delete($id)) {
        echo json_encode([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete user']);
    }
}

function getBranches() {
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
?>