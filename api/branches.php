<?php
require_once __DIR__ . '/../middleware/cors_secure.php';
SecureCorsMiddleware::handle();

require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../config/database.php';

// Error handling
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

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

$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Extract ID from URL like /api/branches/5
if (preg_match('/\/api\/branches\/(\d+)/', $path, $matches)) {
    $branch_id = $matches[1];
} else {
    $branch_id = null;
}

// Debug: log the request
error_log('Request method: ' . $method);
error_log('Request URI: ' . $request_uri);
error_log('Extracted branch ID: ' . ($branch_id ?? 'none'));

switch($method) {
    case 'GET':
        if (strpos($path, '/admin') !== false) {
            getAllBranchesAdmin();
        } else {
            getAllBranches();
        }
        break;
    case 'POST':
        createBranch();
        break;
    case 'PUT':
        if (strpos($path, '/position') !== false && $branch_id) {
            updateBranchPosition($branch_id);
        } elseif ($branch_id) {
            updateBranch($branch_id);
        }
        break;
    case 'DELETE':
        if ($branch_id) {
            deleteBranch($branch_id);
        } else {
            http_response_code(400);
            echo json_encode([
                'error' => 'Branch ID required for deletion',
                'path' => $path,
                'uri' => $request_uri
            ]);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function getAllBranches() {
    global $db;
    
    // Add cache-busting headers
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    try {
        // Check if branches table exists
        $checkTable = $db->query("SHOW TABLES LIKE 'branches'");
        if ($checkTable->rowCount() === 0) {
            http_response_code(500);
            echo json_encode(['error' => 'Branches table not found']);
            return;
        }
        
        // Check if priority column exists, if not add it
        try {
            $checkColumn = $db->query("SHOW COLUMNS FROM branches LIKE 'priority'");
            if ($checkColumn->rowCount() === 0) {
                $db->exec("ALTER TABLE branches ADD COLUMN priority INT DEFAULT 0");
            }
        } catch (PDOException $e) {
            error_log('Priority column check/add error: ' . $e->getMessage());
        }
        
        // Use COALESCE to handle missing priority column gracefully
        $query = "SELECT *, COALESCE(priority, 0) as priority FROM branches WHERE status = 'active' ORDER BY COALESCE(priority, 0) DESC, city, name";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'branches' => $branches
        ]);
    } catch (PDOException $e) {
        error_log('Database error in getAllBranches: ' . $e->getMessage());
        
        // Try without priority column if it doesn't exist
        try {
            $query = "SELECT * FROM branches WHERE status = 'active' ORDER BY city, name";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add default priority to each branch
            foreach ($branches as &$branch) {
                if (!isset($branch['priority'])) {
                    $branch['priority'] = 0;
                }
            }
            
            echo json_encode([
                'success' => true,
                'branches' => $branches
            ]);
            return;
        } catch (PDOException $e2) {
            error_log('Fallback query also failed: ' . $e2->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch branches: ' . $e2->getMessage()]);
        }
    } catch (Exception $e) {
        error_log('General error in getAllBranches: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch branches']);
    }
}

function getAllBranchesAdmin() {
    global $db;
    
    requireAdmin();
    
    // Add cache-busting headers
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    try {
        // Ensure priority column exists for admin queries too
        try {
            $checkColumn = $db->query("SHOW COLUMNS FROM branches LIKE 'priority'");
            if ($checkColumn->rowCount() === 0) {
                $db->exec("ALTER TABLE branches ADD COLUMN priority INT DEFAULT 0");
            }
        } catch (PDOException $e) {
            error_log('Priority column check/add error in admin: ' . $e->getMessage());
        }
        
        $query = "SELECT *, COALESCE(priority, 0) as priority FROM branches ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'branches' => $branches
        ]);
    } catch (Exception $e) {
        error_log('Error in getAllBranchesAdmin: ' . $e->getMessage());
        
        // Fallback without priority
        try {
            $query = "SELECT * FROM branches ORDER BY created_at DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add default priority
            foreach ($branches as &$branch) {
                if (!isset($branch['priority'])) {
                    $branch['priority'] = 0;
                }
            }
            
            echo json_encode([
                'success' => true,
                'branches' => $branches
            ]);
            return;
        } catch (Exception $e2) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch branches']);
        }
    }
}

function createBranch() {
    global $db;
    
    try {
        requireAdmin();
    } catch (Exception $e) {
        error_log('Admin auth error in createBranch: ' . $e->getMessage());
        http_response_code(401);
        echo json_encode(['error' => 'Authentication failed']);
        return;
    }
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
        return;
    }
    
    $required_fields = ['name', 'address', 'city', 'state', 'pincode', 'latitude', 'longitude'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Field '$field' is required"]);
            return;
        }
    }
    
    // Validate latitude and longitude ranges
    $latitude = floatval($data['latitude']);
    $longitude = floatval($data['longitude']);
    
    try {
        // Add position and priority columns if they don't exist
        try {
            $db->exec("ALTER TABLE branches ADD COLUMN x_position DECIMAL(5,2) NULL");
        } catch (PDOException $e) {}
        
        try {
            $db->exec("ALTER TABLE branches ADD COLUMN y_position DECIMAL(5,2) NULL");
        } catch (PDOException $e) {}
        
        try {
            $db->exec("ALTER TABLE branches ADD COLUMN priority INT DEFAULT 0");
        } catch (PDOException $e) {}
        
        $query = "INSERT INTO branches (name, address, city, state, pincode, phone, email, latitude, longitude, x_position, y_position, manager_name, working_hours, status, priority) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            $data['name'],
            $data['address'],
            $data['city'],
            $data['state'],
            $data['pincode'],
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $latitude,
            $longitude,
            isset($data['x_position']) && $data['x_position'] !== '' ? floatval($data['x_position']) : null,
            isset($data['y_position']) && $data['y_position'] !== '' ? floatval($data['y_position']) : null,
            $data['manager_name'] ?? null,
            $data['working_hours'] ?? '9:00 AM - 6:00 PM',
            $data['status'] ?? 'active',
            isset($data['priority']) ? intval($data['priority']) : 0
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Branch created successfully',
            'id' => $db->lastInsertId()
        ]);
    } catch (PDOException $e) {
        error_log('Database error in createBranch: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log('General error in createBranch: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create branch']);
    }
}

function updateBranch($id) {
    global $db;
    
    requireAdmin();
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
        return;
    }
    
    // Validate latitude and longitude if provided
    if (isset($data['latitude'])) {
        $latitude = floatval($data['latitude']);
    }
    
    if (isset($data['longitude'])) {
        $longitude = floatval($data['longitude']);
    }
    
    try {
        // Add position and priority columns if they don't exist
        try {
            $db->exec("ALTER TABLE branches ADD COLUMN x_position DECIMAL(5,2) NULL");
        } catch (PDOException $e) {}
        
        try {
            $db->exec("ALTER TABLE branches ADD COLUMN y_position DECIMAL(5,2) NULL");
        } catch (PDOException $e) {}
        
        try {
            $db->exec("ALTER TABLE branches ADD COLUMN priority INT DEFAULT 0");
        } catch (PDOException $e) {}
        
        $query = "UPDATE branches SET name = ?, address = ?, city = ?, state = ?, pincode = ?, 
                  phone = ?, email = ?, latitude = ?, longitude = ?, x_position = ?, y_position = ?, manager_name = ?, 
                  working_hours = ?, status = ?, priority = ?, updated_at = CURRENT_TIMESTAMP 
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
            isset($latitude) ? $latitude : $data['latitude'],
            isset($longitude) ? $longitude : $data['longitude'],
            isset($data['x_position']) && $data['x_position'] !== '' ? floatval($data['x_position']) : null,
            isset($data['y_position']) && $data['y_position'] !== '' ? floatval($data['y_position']) : null,
            $data['manager_name'] ?? null,
            $data['working_hours'] ?? '9:00 AM - 6:00 PM',
            $data['status'] ?? 'active',
            isset($data['priority']) ? intval($data['priority']) : 0,
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
    } catch (PDOException $e) {
        error_log('Database error in updateBranch: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log('General error in updateBranch: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update branch']);
    }
}

function deleteBranch($id) {
    global $db;
    
    requireAdmin();
    
    // Debug: log the ID being deleted
    error_log("Attempting to delete branch with ID: " . $id);
    
    try {
        // Force delete without checking if exists first
        $query = "DELETE FROM branches WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        
        $rowCount = $stmt->rowCount();
        error_log("Delete query affected $rowCount rows");
        
        // Add cache-busting headers
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        if ($rowCount > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Branch deleted successfully',
                'deleted_id' => $id,
                'rows_affected' => $rowCount
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Branch not found or already deleted',
                'id' => $id,
                'rows_affected' => $rowCount
            ]);
        }
    } catch (Exception $e) {
        error_log('Delete branch error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete branch: ' . $e->getMessage()]);
    }
}

function updateBranchPosition($id) {
    global $db;
    
    requireAdmin();
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
        return;
    }
    
    try {
        // First check if the branch exists
        $checkQuery = "SELECT id, name FROM branches WHERE id = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$id]);
        $branch = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$branch) {
            // Let's also check what branches exist
            $allQuery = "SELECT id, name FROM branches LIMIT 5";
            $allStmt = $db->prepare($allQuery);
            $allStmt->execute();
            $allBranches = $allStmt->fetchAll(PDO::FETCH_ASSOC);
            
            http_response_code(404);
            echo json_encode([
                'error' => 'Branch not found', 
                'requested_id' => $id,
                'available_branches' => $allBranches
            ]);
            return;
        }
        
        // Try to add columns if they don't exist (MySQL will ignore if they exist)
        try {
            $db->exec("ALTER TABLE branches ADD COLUMN x_position DECIMAL(5,2) NULL");
        } catch (PDOException $e) {
            // Column already exists, ignore
        }
        
        try {
            $db->exec("ALTER TABLE branches ADD COLUMN y_position DECIMAL(5,2) NULL");
        } catch (PDOException $e) {
            // Column already exists, ignore
        }
        
        // Now update the position
        $query = "UPDATE branches SET x_position = ?, y_position = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([
            $data['x_position'],
            $data['y_position'],
            $id
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Branch position updated successfully',
            'branch' => $branch['name'],
            'position' => ['x' => $data['x_position'], 'y' => $data['y_position']]
        ]);
        
    } catch (PDOException $e) {
        error_log('Database error in updateBranchPosition: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log('General error in updateBranchPosition: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update branch position']);
    }
}
?>