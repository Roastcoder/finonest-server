<?php
require_once '../config/database.php';
require_once '../middleware/auth.php';
require_once '../middleware/cors.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Handle CORS preflight
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Create courses table if it doesn't exist
    $createTable = "
        CREATE TABLE IF NOT EXISTS courses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            duration VARCHAR(50) NOT NULL,
            lessons INT NOT NULL,
            level ENUM('Beginner', 'Intermediate', 'Advanced') DEFAULT 'Beginner',
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ";
    $pdo->exec($createTable);

    switch ($method) {
        case 'GET':
            if (isset($pathParts[3]) && $pathParts[3] === 'admin') {
                // Admin route - requires authentication
                $user = authenticateUser($pdo);
                if (!$user || $user['role'] !== 'ADMIN') {
                    http_response_code(403);
                    echo json_encode(['error' => 'Admin access required']);
                    exit();
                }
                
                // Get all courses for admin
                $stmt = $pdo->prepare("SELECT * FROM courses ORDER BY created_at DESC");
                $stmt->execute();
                $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['courses' => $courses]);
            } else {
                // Public route - only active courses
                $stmt = $pdo->prepare("SELECT * FROM courses WHERE status = 'active' ORDER BY created_at DESC");
                $stmt->execute();
                $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['courses' => $courses]);
            }
            break;

        case 'POST':
            // Admin only - create new course
            $user = authenticateUser($pdo);
            if (!$user || $user['role'] !== 'ADMIN') {
                http_response_code(403);
                echo json_encode(['error' => 'Admin access required']);
                exit();
            }

            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input['title'] || !$input['description'] || !$input['duration'] || !$input['lessons']) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                exit();
            }

            $stmt = $pdo->prepare("
                INSERT INTO courses (title, description, duration, lessons, level, status) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $input['title'],
                $input['description'],
                $input['duration'],
                $input['lessons'],
                $input['level'] ?? 'Beginner',
                $input['status'] ?? 'active'
            ]);

            $courseId = $pdo->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Course created successfully',
                'course_id' => $courseId
            ]);
            break;

        case 'PUT':
            // Admin only - update course
            $user = authenticateUser($pdo);
            if (!$user || $user['role'] !== 'ADMIN') {
                http_response_code(403);
                echo json_encode(['error' => 'Admin access required']);
                exit();
            }

            if (!isset($pathParts[4])) {
                http_response_code(400);
                echo json_encode(['error' => 'Course ID required']);
                exit();
            }

            $courseId = $pathParts[4];
            $input = json_decode(file_get_contents('php://input'), true);

            $stmt = $pdo->prepare("
                UPDATE courses 
                SET title = ?, description = ?, duration = ?, lessons = ?, level = ?, status = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $input['title'],
                $input['description'],
                $input['duration'],
                $input['lessons'],
                $input['level'] ?? 'Beginner',
                $input['status'] ?? 'active',
                $courseId
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Course updated successfully'
            ]);
            break;

        case 'DELETE':
            // Admin only - delete course
            $user = authenticateUser($pdo);
            if (!$user || $user['role'] !== 'ADMIN') {
                http_response_code(403);
                echo json_encode(['error' => 'Admin access required']);
                exit();
            }

            if (!isset($pathParts[4])) {
                http_response_code(400);
                echo json_encode(['error' => 'Course ID required']);
                exit();
            }

            $courseId = $pathParts[4];
            
            $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
            $stmt->execute([$courseId]);

            echo json_encode([
                'success' => true,
                'message' => 'Course deleted successfully'
            ]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>