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

// Handle method override for FormData uploads
if ($method === 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}

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
        price DECIMAL(10,2) DEFAULT 0.00,
        original_price DECIMAL(10,2) DEFAULT NULL,
        image_path VARCHAR(500),
        video_path VARCHAR(500),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $db->exec($createTable);
    
    // Add price columns if they don't exist (for existing tables)
    try {
        $db->exec("ALTER TABLE courses ADD COLUMN price DECIMAL(10,2) DEFAULT 0.00");
    } catch (PDOException $e) {
        // Column already exists
    }
    try {
        $db->exec("ALTER TABLE courses ADD COLUMN original_price DECIMAL(10,2) DEFAULT NULL");
    } catch (PDOException $e) {
        // Column already exists
    }
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
    
    // Check if this is a public request (no auth required for GET)
    $headers = apache_request_headers() ?: [];
    $hasAuthHeader = false;
    
    foreach ($headers as $key => $value) {
        if (strtolower($key) === 'authorization') {
            $hasAuthHeader = true;
            break;
        }
    }
    
    if ($hasAuthHeader) {
        requireAdmin();
    }
    
    try {
        // Check if courses table exists
        $checkTable = $db->query("SHOW TABLES LIKE 'courses'");
        if ($checkTable->rowCount() === 0) {
            // Create table if it doesn't exist
            $createTable = "CREATE TABLE courses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                duration VARCHAR(100),
                lessons INT DEFAULT 0,
                level ENUM('Beginner', 'Intermediate', 'Advanced') DEFAULT 'Beginner',
                status ENUM('active', 'inactive') DEFAULT 'active',
                price DECIMAL(10,2) DEFAULT 0.00,
                original_price DECIMAL(10,2) DEFAULT NULL,
                image_path VARCHAR(500),
                video_path VARCHAR(500),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $db->exec($createTable);
        }
        
        // For public requests, only return active courses
        if (!$hasAuthHeader) {
            $query = "SELECT * FROM courses WHERE status = 'active' ORDER BY created_at DESC";
        } else {
            $query = "SELECT * FROM courses ORDER BY created_at DESC";
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'courses' => $courses
        ]);
    } catch (PDOException $e) {
        error_log('Database error in getAllCourses: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log('Error in getAllCourses: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch courses: ' . $e->getMessage()]);
    }
}

function createCourse() {
    global $db;
    
    requireAdmin();
    
    // Handle file uploads
    $imagePath = null;
    $videoPath = null;
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imagePath = handleFileUpload($_FILES['image'], 'images');
    }
    
    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        $videoPath = handleFileUpload($_FILES['video'], 'videos');
    }
    
    // Get form data
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $duration = $_POST['duration'] ?? '';
    $lessons = intval($_POST['lessons'] ?? 0);
    $level = $_POST['level'] ?? 'Beginner';
    $status = $_POST['status'] ?? 'active';
    $price = floatval($_POST['price'] ?? 0);
    $original_price = !empty($_POST['original_price']) ? floatval($_POST['original_price']) : null;
    
    if (empty($title) || empty($description)) {
        http_response_code(400);
        echo json_encode(['error' => 'Title and description are required']);
        return;
    }
    
    try {
        $query = "INSERT INTO courses (title, description, duration, lessons, level, status, price, original_price, image_path, video_path) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            $title,
            $description,
            $duration,
            $lessons,
            $level,
            $status,
            $price,
            $original_price,
            $imagePath,
            $videoPath
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

function handleFileUpload($file, $type) {
    $uploadDir = __DIR__ . '/../uploads/' . $type . '/';
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Validate file type
    $allowedTypes = [
        'images' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        'videos' => ['video/mp4', 'video/avi', 'video/mov', 'video/wmv']
    ];
    
    if (!in_array($file['type'], $allowedTypes[$type])) {
        throw new Exception('Invalid file type');
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return 'uploads/' . $type . '/' . $filename;
    }
    
    throw new Exception('Failed to upload file');
}

function updateCourse($id) {
    global $db;
    
    requireAdmin();
    
    // For PUT requests, we need to parse the input differently
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'multipart/form-data') !== false) {
        // Handle multipart form data for PUT requests
        $imagePath = null;
        $videoPath = null;
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imagePath = handleFileUpload($_FILES['image'], 'images');
        }
        
        if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
            $videoPath = handleFileUpload($_FILES['video'], 'videos');
        }
        
        // Get form data from $_POST for multipart
        $title = $_POST['title'] ?? null;
        $description = $_POST['description'] ?? null;
        $duration = $_POST['duration'] ?? null;
        $lessons = isset($_POST['lessons']) ? intval($_POST['lessons']) : null;
        $level = $_POST['level'] ?? null;
        $status = $_POST['status'] ?? null;
        $price = isset($_POST['price']) ? floatval($_POST['price']) : null;
        $original_price = !empty($_POST['original_price']) ? floatval($_POST['original_price']) : null;
    } else {
        // Handle JSON data
        $input = json_decode(file_get_contents('php://input'), true);
        $title = $input['title'] ?? null;
        $description = $input['description'] ?? null;
        $duration = $input['duration'] ?? null;
        $lessons = isset($input['lessons']) ? intval($input['lessons']) : null;
        $level = $input['level'] ?? null;
        $status = $input['status'] ?? null;
        $price = isset($input['price']) ? floatval($input['price']) : null;
        $original_price = !empty($input['original_price']) ? floatval($input['original_price']) : null;
        $imagePath = null;
        $videoPath = null;
    }
    
    try {
        // Build dynamic query - only update fields that are provided
        $updateFields = [];
        $params = [];
        
        if ($title !== null) {
            $updateFields[] = "title = ?";
            $params[] = $title;
        }
        if ($description !== null) {
            $updateFields[] = "description = ?";
            $params[] = $description;
        }
        if ($duration !== null) {
            $updateFields[] = "duration = ?";
            $params[] = $duration;
        }
        if ($lessons !== null) {
            $updateFields[] = "lessons = ?";
            $params[] = $lessons;
        }
        if ($level !== null) {
            $updateFields[] = "level = ?";
            $params[] = $level;
        }
        if ($status !== null) {
            $updateFields[] = "status = ?";
            $params[] = $status;
        }
        if ($price !== null) {
            $updateFields[] = "price = ?";
            $params[] = $price;
        }
        if ($original_price !== null) {
            $updateFields[] = "original_price = ?";
            $params[] = $original_price;
        }
        if ($imagePath) {
            $updateFields[] = "image_path = ?";
            $params[] = $imagePath;
        }
        if ($videoPath) {
            $updateFields[] = "video_path = ?";
            $params[] = $videoPath;
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            return;
        }
        
        $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
        $query = "UPDATE courses SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
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
        echo json_encode(['error' => 'Failed to update course: ' . $e->getMessage()]);
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