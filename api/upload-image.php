<?php
require_once __DIR__ . '/../middleware/cors_secure.php';
SecureCorsMiddleware::handle();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Verify admin authentication
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

    if ($decoded['role'] !== 'ADMIN') {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        exit();
    }

    return $decoded;
}

// Debug logging
error_log('Upload request received');
error_log('FILES: ' . print_r($_FILES, true));
error_log('Headers: ' . print_r(getallheaders(), true));

$user = authenticate();

// Check if file was uploaded
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    error_log('File upload error: ' . ($_FILES['image']['error'] ?? 'No file'));
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded or upload error', 'debug' => $_FILES]);
    exit;
}

$file = $_FILES['image'];
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB

// Validate file type
if (!in_array($file['type'], $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed']);
    exit;
}

// Validate file size
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['error' => 'File too large. Maximum size is 5MB']);
    exit;
}

// Create uploads directory if it doesn't exist
$uploadDir = __DIR__ . '/../uploads/blog-images/';
error_log('Upload directory: ' . $uploadDir);
if (!is_dir($uploadDir)) {
    error_log('Creating upload directory');
    mkdir($uploadDir, 0755, true);
}

if (!is_writable($uploadDir)) {
    error_log('Upload directory not writable');
    http_response_code(500);
    echo json_encode(['error' => 'Upload directory not writable']);
    exit;
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('blog_') . '_' . time() . '.' . $extension;
$filepath = $uploadDir . $filename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $filepath)) {
    error_log('File uploaded successfully: ' . $filepath);
    // Return the URL path for the uploaded image
    $imageUrl = '/uploads/blog-images/' . $filename;
    
    echo json_encode([
        'success' => true,
        'image_url' => $imageUrl,
        'filename' => $filename
    ]);
} else {
    error_log('Failed to move uploaded file from ' . $file['tmp_name'] . ' to ' . $filepath);
    http_response_code(500);
    echo json_encode(['error' => 'Failed to upload file']);
}
?>