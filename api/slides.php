<?php
require_once '../cors-handler.php';
require_once '../config/database.php';

// Auto-create slides table if it doesn't exist
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS slides (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        subtitle TEXT,
        description TEXT,
        image_url VARCHAR(500),
        button_text VARCHAR(100),
        button_link VARCHAR(255),
        order_position INT DEFAULT 1,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
} catch (Exception $e) {
    // Continue if table creation fails
}

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

try {
    switch ($method) {
        case 'GET':
            handleGet($pdo);
            break;
        case 'POST':
            handlePost($pdo);
            break;
        case 'PUT':
            handlePut($pdo, $pathParts);
            break;
        case 'DELETE':
            handleDelete($pdo, $pathParts);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGet($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM slides ORDER BY order_position ASC");
        $stmt->execute();
        $slides = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['slides' => $slides]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handlePost($pdo) {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Authorization required']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        return;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO slides (title, subtitle, description, image_url, button_text, button_link, order_position, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $input['title'] ?? '',
            $input['subtitle'] ?? '',
            $input['description'] ?? '',
            $input['image_url'] ?? '',
            $input['button_text'] ?? '',
            $input['button_link'] ?? '',
            $input['order_position'] ?? 1,
            isset($input['is_active']) ? ($input['is_active'] ? 1 : 0) : 1
        ]);
        
        $slideId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Slide created successfully',
            'slide_id' => $slideId
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handlePut($pdo, $pathParts) {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Authorization required']);
        return;
    }

    // Get slide ID from query parameter or path
    $slideId = $_GET['id'] ?? end($pathParts);
    if (!is_numeric($slideId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid slide ID']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        return;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE slides 
            SET title = ?, subtitle = ?, description = ?, image_url = ?, button_text = ?, button_link = ?, order_position = ?, is_active = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $input['title'] ?? '',
            $input['subtitle'] ?? '',
            $input['description'] ?? '',
            $input['image_url'] ?? '',
            $input['button_text'] ?? '',
            $input['button_link'] ?? '',
            $input['order_position'] ?? 1,
            isset($input['is_active']) ? ($input['is_active'] ? 1 : 0) : 1,
            $slideId
        ]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Slide updated successfully'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Slide not found']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleDelete($pdo, $pathParts) {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Authorization required']);
        return;
    }

    // Get slide ID from query parameter or path
    $slideId = $_GET['id'] ?? end($pathParts);
    if (!is_numeric($slideId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid slide ID']);
        return;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM slides WHERE id = ?");
        $stmt->execute([$slideId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Slide deleted successfully'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Slide not found']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>