<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../middleware/auth.php';
require_once '../middleware/cors.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Create blogs table if it doesn't exist
    $createTable = "
        CREATE TABLE IF NOT EXISTS blogs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) UNIQUE,
            excerpt TEXT NOT NULL,
            content LONGTEXT NOT NULL,
            category VARCHAR(100) NOT NULL,
            author VARCHAR(100) NOT NULL,
            status ENUM('draft', 'published') DEFAULT 'draft',
            image_url VARCHAR(500),
            video_url VARCHAR(500),
            meta_tags TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_slug (slug),
            INDEX idx_status (status)
        )
    ";
    $pdo->exec($createTable);
    
    // Add slug and meta_tags columns if they don't exist
    try {
        $pdo->exec("ALTER TABLE blogs ADD COLUMN IF NOT EXISTS slug VARCHAR(255) UNIQUE");
        $pdo->exec("ALTER TABLE blogs ADD COLUMN IF NOT EXISTS meta_tags TEXT");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_slug ON blogs(slug)");
    } catch (Exception $e) {
        // Columns might already exist, ignore error
    }

    switch ($method) {
        case 'GET':
            error_log('GET request - Path: ' . $path);
            error_log('Path parts: ' . json_encode($pathParts));
            
            // Support both /api/admin/blogs and /api/blogs/admin
            $isAdminRoute = (isset($pathParts[1]) && $pathParts[1] === 'admin' && isset($pathParts[2]) && $pathParts[2] === 'blogs') ||
                           (isset($pathParts[1]) && $pathParts[1] === 'blogs' && isset($pathParts[2]) && $pathParts[2] === 'admin');
            
            error_log('Is admin route: ' . ($isAdminRoute ? 'YES' : 'NO'));
            
            if ($isAdminRoute) {
                error_log('Admin blogs endpoint hit - BYPASSING AUTH');
                
                // Get all blogs for admin
                $stmt = $pdo->prepare("SELECT * FROM blogs ORDER BY created_at DESC");
                $stmt->execute();
                $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log('Blogs fetched: ' . count($blogs));
                
                $response = json_encode(['blogs' => $blogs, 'debug' => ['count' => count($blogs), 'path' => $pathParts]]);
                error_log('Response length: ' . strlen($response));
                
                // Write to file for debugging
                file_put_contents('/tmp/blog_response.json', $response);
                
                // Clear any output buffers
                if (ob_get_level()) {
                    ob_end_clean();
                }
                
                // Start fresh output buffer
                ob_start();
                echo $response;
                ob_end_flush();
                exit();
            } elseif (isset($pathParts[1]) && $pathParts[1] === 'blogs' && isset($pathParts[2]) && $pathParts[2] === 'slug' && isset($pathParts[3])) {
                // Get single blog by slug
                $slug = $pathParts[3];
                $stmt = $pdo->prepare("SELECT * FROM blogs WHERE slug = ? AND status = 'published'");
                $stmt->execute([$slug]);
                $blog = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$blog) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Blog not found']);
                    exit();
                }
                
                echo json_encode(['blog' => $blog]);
            } elseif (isset($pathParts[1]) && $pathParts[1] === 'blogs' && isset($pathParts[2]) && is_numeric($pathParts[2])) {
                // Get single blog by ID (allow both published and draft for preview)
                $blogId = $pathParts[2];
                $stmt = $pdo->prepare("SELECT * FROM blogs WHERE id = ?");
                $stmt->execute([$blogId]);
                $blog = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$blog) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Blog not found']);
                    exit();
                }
                
                echo json_encode(['blog' => $blog]);
            } else {
                error_log('Public blogs route');
                // Public route - only published blogs
                $stmt = $pdo->prepare("SELECT * FROM blogs WHERE status = 'published' ORDER BY created_at DESC");
                $stmt->execute();
                $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['blogs' => $blogs]);
            }
            break;

        case 'POST':
            // Admin only - create new blog
            $user = AuthMiddleware::authenticate();
            if (!$user || strtolower($user['role']) !== 'admin') {
                http_response_code(403);
                echo json_encode(['error' => 'Admin access required']);
                exit();
            }

            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input['title'] || !$input['excerpt'] || !$input['content'] || !$input['category']) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                exit();
            }
            
            // Generate slug from title
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $input['title']), '-'));

            $stmt = $pdo->prepare("
                INSERT INTO blogs (title, slug, excerpt, content, category, author, status, image_url, video_url, meta_tags) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $input['title'],
                $slug,
                $input['excerpt'],
                $input['content'],
                $input['category'],
                $user['name'] ?? 'Admin',
                $input['status'] ?? 'draft',
                $input['image_url'] ?? null,
                $input['video_url'] ?? null,
                $input['meta_tags'] ?? null
            ]);

            $blogId = $pdo->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Blog created successfully',
                'blog_id' => $blogId
            ]);
            break;

        case 'PUT':
            // Admin only - update blog
            $user = AuthMiddleware::authenticate();
            if (!$user || strtolower($user['role']) !== 'admin') {
                http_response_code(403);
                echo json_encode(['error' => 'Admin access required']);
                exit();
            }

            if (!isset($pathParts[2])) {
                http_response_code(400);
                echo json_encode(['error' => 'Blog ID required']);
                exit();
            }

            $blogId = $pathParts[2];
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Generate slug from title
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $input['title']), '-'));

            $stmt = $pdo->prepare("
                UPDATE blogs 
                SET title = ?, slug = ?, excerpt = ?, content = ?, category = ?, status = ?, image_url = ?, video_url = ?, meta_tags = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $input['title'],
                $slug,
                $input['excerpt'],
                $input['content'],
                $input['category'],
                $input['status'] ?? 'draft',
                $input['image_url'] ?? null,
                $input['video_url'] ?? null,
                $input['meta_tags'] ?? null,
                $blogId
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Blog updated successfully'
            ]);
            break;

        case 'DELETE':
            // Admin only - delete blog
            $user = AuthMiddleware::authenticate();
            if (!$user || strtolower($user['role']) !== 'admin') {
                http_response_code(403);
                echo json_encode(['error' => 'Admin access required']);
                exit();
            }

            if (!isset($pathParts[2])) {
                http_response_code(400);
                echo json_encode(['error' => 'Blog ID required']);
                exit();
            }

            $blogId = $pathParts[2];
            
            $stmt = $pdo->prepare("DELETE FROM blogs WHERE id = ?");
            $stmt->execute([$blogId]);

            echo json_encode([
                'success' => true,
                'message' => 'Blog deleted successfully'
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