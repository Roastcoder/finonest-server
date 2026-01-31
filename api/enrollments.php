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

$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Extract ID from URL
if (preg_match('/\/api\/enrollments\/(\d+)/', $path, $matches)) {
    $enrollment_id = $matches[1];
} else {
    $enrollment_id = null;
}

switch($method) {
    case 'GET':
        getAllEnrollments();
        break;
    case 'PUT':
        if ($enrollment_id) {
            updateEnrollmentStatus($enrollment_id);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function getAllEnrollments() {
    global $db;
    
    requireAdmin();
    
    try {
        // First check if table exists
        $tableCheck = $db->query("SHOW TABLES LIKE 'course_enrollments'");
        if ($tableCheck->rowCount() === 0) {
            echo json_encode([
                'success' => true,
                'enrollments' => [],
                'message' => 'No enrollments table found - will be created when first enrollment is made'
            ]);
            return;
        }
        
        $query = "SELECT 
                    ce.*,
                    u.name as user_name,
                    u.email as user_email
                  FROM course_enrollments ce
                  LEFT JOIN users u ON ce.user_id = u.id
                  ORDER BY ce.enrollment_date DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse JSON fields for each enrollment
        foreach ($enrollments as &$enrollment) {
            if ($enrollment['student_info']) {
                $enrollment['student_info'] = json_decode($enrollment['student_info'], true);
            }
            if (isset($enrollment['payment_details']) && $enrollment['payment_details']) {
                $enrollment['payment_details'] = json_decode($enrollment['payment_details'], true);
            } else {
                $enrollment['payment_details'] = null;
            }
        }
        
        echo json_encode([
            'success' => true,
            'enrollments' => $enrollments
        ]);
    } catch (PDOException $e) {
        error_log('Database error in getAllEnrollments: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log('Error in getAllEnrollments: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch enrollments: ' . $e->getMessage()]);
    }
}

function updateEnrollmentStatus($id) {
    global $db;
    
    requireAdmin();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $status = $input['status'] ?? '';
    
    if (!in_array($status, ['active', 'completed', 'cancelled'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid status']);
        return;
    }
    
    try {
        $query = "UPDATE course_enrollments SET status = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$status, $id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Enrollment status updated successfully'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Enrollment not found']);
        }
    } catch (Exception $e) {
        error_log('Error in updateEnrollmentStatus: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update enrollment status']);
    }
}
?>