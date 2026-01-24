<?php
require_once __DIR__ . '/../middleware/cors_secure.php';
SecureCorsMiddleware::handle();

ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';

function authenticate() {
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
    if (!$decoded) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token']);
        exit();
    }

    return $decoded;
}

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
        // Check if slug column exists
        $result = $pdo->query("SHOW COLUMNS FROM blogs LIKE 'slug'");
        if ($result->rowCount() == 0) {
            $pdo->exec("ALTER TABLE blogs ADD COLUMN slug VARCHAR(255) UNIQUE AFTER title");
        }
        
        // Check if meta_tags column exists
        $result = $pdo->query("SHOW COLUMNS FROM blogs LIKE 'meta_tags'");
        if ($result->rowCount() == 0) {
            $pdo->exec("ALTER TABLE blogs ADD COLUMN meta_tags TEXT AFTER video_url");
        }
        
        // Add new blog section columns
        $newColumns = [
            'meta_title' => 'VARCHAR(255)',
            'meta_description' => 'TEXT',
            'table_of_contents' => 'TEXT',
            'introduction' => 'TEXT',
            'quick_info_box' => 'TEXT',
            'emi_example' => 'TEXT',
            'what_is_loan' => 'TEXT',
            'benefits' => 'TEXT',
            'who_should_apply' => 'TEXT',
            'eligibility_criteria' => 'TEXT',
            'documents_required' => 'TEXT',
            'interest_rates' => 'TEXT',
            'finonest_process' => 'TEXT',
            'why_choose_finonest' => 'TEXT',
            'customer_testimonials' => 'TEXT',
            'common_mistakes' => 'TEXT',
            'mid_blog_cta' => 'TEXT',
            'faqs' => 'TEXT',
            'service_areas' => 'TEXT',
            'related_blogs' => 'TEXT',
            'final_cta' => 'TEXT',
            'final_cta_text' => 'TEXT',
            'disclaimer' => 'TEXT',
            'trust_footer' => 'TEXT'
        ];
        
        foreach ($newColumns as $column => $type) {
            $result = $pdo->query("SHOW COLUMNS FROM blogs LIKE '$column'");
            if ($result->rowCount() == 0) {
                $pdo->exec("ALTER TABLE blogs ADD COLUMN $column $type AFTER meta_tags");
            }
        }
        
        // Create index if not exists
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_slug ON blogs(slug)");
    } catch (Exception $e) {
        error_log('Migration error: ' . $e->getMessage());
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
            $user = authenticate();
            if (!$user || $user['role'] !== 'ADMIN') {
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
                INSERT INTO blogs (title, slug, excerpt, content, category, author, status, image_url, video_url, meta_title, meta_description, meta_tags,
                table_of_contents, introduction, quick_info_box, emi_example, what_is_loan, benefits, who_should_apply,
                eligibility_criteria, documents_required, interest_rates, finonest_process, why_choose_finonest,
                customer_testimonials, common_mistakes, mid_blog_cta, faqs, service_areas, related_blogs,
                final_cta, final_cta_text, disclaimer, trust_footer) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
                $input['meta_title'] ?? null,
                $input['meta_description'] ?? null,
                $input['meta_tags'] ?? null,
                $input['table_of_contents'] ?? null,
                $input['introduction'] ?? null,
                $input['quick_info_box'] ?? null,
                $input['emi_example'] ?? null,
                $input['what_is_loan'] ?? null,
                $input['benefits'] ?? null,
                $input['who_should_apply'] ?? null,
                $input['eligibility_criteria'] ?? null,
                $input['documents_required'] ?? null,
                $input['interest_rates'] ?? null,
                $input['finonest_process'] ?? null,
                $input['why_choose_finonest'] ?? null,
                $input['customer_testimonials'] ?? null,
                $input['common_mistakes'] ?? null,
                $input['mid_blog_cta'] ?? null,
                $input['faqs'] ?? null,
                $input['service_areas'] ?? null,
                $input['related_blogs'] ?? null,
                $input['final_cta'] ?? null,
                $input['final_cta_text'] ?? null,
                $input['disclaimer'] ?? null,
                $input['trust_footer'] ?? null
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
            $user = authenticate();
            if (!$user || $user['role'] !== 'ADMIN') {
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
            
            // Build dynamic query - only update fields that are provided
            $updateFields = [];
            $params = [];
            
            if (isset($input['title']) && !empty($input['title'])) {
                $updateFields[] = "title = ?";
                $params[] = $input['title'];
                
                // Generate slug from title
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $input['title']), '-'));
                $updateFields[] = "slug = ?";
                $params[] = $slug;
            }
            
            if (isset($input['excerpt'])) {
                $updateFields[] = "excerpt = ?";
                $params[] = $input['excerpt'];
            }
            
            if (isset($input['content'])) {
                $updateFields[] = "content = ?";
                $params[] = $input['content'];
            }
            
            if (isset($input['category'])) {
                $updateFields[] = "category = ?";
                $params[] = $input['category'];
            }
            
            if (isset($input['status'])) {
                $updateFields[] = "status = ?";
                $params[] = $input['status'];
            }
            
            if (isset($input['image_url'])) {
                $updateFields[] = "image_url = ?";
                $params[] = $input['image_url'] ?: null;
            }
            
            if (isset($input['video_url'])) {
                $updateFields[] = "video_url = ?";
                $params[] = $input['video_url'] ?: null;
            }
            
            if (isset($input['meta_title'])) {
                $updateFields[] = "meta_title = ?";
                $params[] = $input['meta_title'] ?: null;
            }
            
            if (isset($input['meta_description'])) {
                $updateFields[] = "meta_description = ?";
                $params[] = $input['meta_description'] ?: null;
            }
            
            if (isset($input['meta_tags'])) {
                $updateFields[] = "meta_tags = ?";
                $params[] = $input['meta_tags'] ?: null;
            }
            
            // Blog section fields
            $sectionFields = [
                'table_of_contents', 'introduction', 'quick_info_box', 'emi_example', 'what_is_loan',
                'benefits', 'who_should_apply', 'eligibility_criteria', 'documents_required',
                'interest_rates', 'finonest_process', 'why_choose_finonest', 'customer_testimonials',
                'common_mistakes', 'mid_blog_cta', 'faqs', 'service_areas', 'related_blogs',
                'final_cta', 'final_cta_text', 'disclaimer', 'trust_footer'
            ];
            
            foreach ($sectionFields as $field) {
                if (isset($input[$field])) {
                    $updateFields[] = "$field = ?";
                    $params[] = $input[$field] ?: null;
                }
            }
            
            if (empty($updateFields)) {
                http_response_code(400);
                echo json_encode(['error' => 'No fields to update']);
                exit();
            }
            
            $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
            $query = "UPDATE blogs SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $params[] = $blogId;

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);

            echo json_encode([
                'success' => true,
                'message' => 'Blog updated successfully'
            ]);
            break;

        case 'DELETE':
            // Admin only - delete blog
            $user = authenticate();
            if (!$user || $user['role'] !== 'ADMIN') {
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