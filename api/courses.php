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

// Extract ID from URL
if (preg_match('/\/api\/courses\/(\d+)/', $path, $matches)) {
    $course_id = $matches[1];
} else {
    $course_id = null;
}

// Create courses table if it doesn't exist
try {
    $createTable = "CREATE TABLE IF NOT EXISTS courses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        duration VARCHAR(100),
        lessons INT DEFAULT 0,
        level ENUM('Beginner', 'Intermediate', 'Advanced') DEFAULT 'Beginner',
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $db->exec($createTable);
} catch (PDOException $e) {
    error_log('Table creation error: ' . $e->getMessage());
}

switch($method) {
    case 'GET':
        getAllCourses();
        break;
    case 'POST':
        createCourse();
        break;
    case 'PUT':
        if ($course_id) {
            updateCourse($course_id);
        }
        break;
    case 'DELETE':
        if ($course_id) {
            deleteCourse($course_id);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function getAllCourses() {
    global $db;
    
    requireAdmin();
    
    try {
        $query = "SELECT * FROM courses ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'courses' => $courses
        ]);
    } catch (Exception $e) {
        error_log('Error in getAllCourses: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch courses']);
    }
}

function createCourse() {
    global $db;
    
    requireAdmin();
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
        return;
    }
    
    $required_fields = ['title', 'description'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Field '$field' is required"]);
            return;
        }
    }
    
    try {
        $query = "INSERT INTO courses (title, description, duration, lessons, level, status) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            $data['title'],
            $data['description'],
            $data['duration'] ?? '',
            $data['lessons'] ?? 0,
            $data['level'] ?? 'Beginner',
            $data['status'] ?? 'active'
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Course created successfully',
            'id' => $db->lastInsertId()
        ]);
    } catch (Exception $e) {
        error_log('Error in createCourse: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create course']);
    }
}

function updateCourse($id) {
    global $db;
    
    requireAdmin();
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
        return;
    }
    
    try {
        $query = "UPDATE courses SET title = ?, description = ?, duration = ?, lessons = ?, 
                  level = ?, status = ?, updated_at = CURRENT_TIMESTAMP 
                  WHERE id = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            $data['title'],
            $data['description'],
            $data['duration'] ?? '',
            $data['lessons'] ?? 0,
            $data['level'] ?? 'Beginner',
            $data['status'] ?? 'active',
            $id
        ]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Course updated successfully'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Course not found']);
        }
    } catch (Exception $e) {
        error_log('Error in updateCourse: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update course']);
    }
}

function deleteCourse($id) {
    global $db;
    
    requireAdmin();
    
    try {
        $query = "DELETE FROM courses WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Course deleted successfully'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Course not found']);
        }
    } catch (Exception $e) {
        error_log('Error in deleteCourse: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete course']);
    }
}
?>