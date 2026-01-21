<?php
// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Simple router for PHP development server
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Route API requests
if (strpos($path, '/api/auth/') === 0) {
    $_SERVER['PATH_INFO'] = substr($path, 9); // Remove '/api/auth'
    include 'api/auth.php';
} elseif (strpos($path, '/api/admin/') === 0) {
    $_SERVER['PATH_INFO'] = substr($path, 10); // Remove '/api/admin'
    include 'api/admin.php';
} elseif (strpos($path, '/api/forms') === 0) {
    $_SERVER['PATH_INFO'] = substr($path, 10); // Remove '/api/forms'
    include 'api/forms.php';
} elseif (strpos($path, '/api/contact') === 0) {
    include 'api/contact.php';
} elseif (strpos($path, '/api/branches') === 0) {
    $_SERVER['PATH_INFO'] = substr($path, 13); // Remove '/api/branches'
    include 'api/branches.php';
} elseif (strpos($path, '/api/bankers') === 0) {
    $_SERVER['PATH_INFO'] = substr($path, 12); // Remove '/api/bankers'
    include 'api/bankers.php';
} elseif (strpos($path, '/api/blogs') === 0) {
    $_SERVER['PATH_INFO'] = substr($path, 10); // Remove '/api/blogs'
    include 'api/blogs.php';
} elseif (strpos($path, '/api/courses') === 0) {
    $_SERVER['PATH_INFO'] = substr($path, 12); // Remove '/api/courses'
    include 'api/courses.php';
} elseif (strpos($path, '/api/validate') === 0) {
    include 'api/validate.php';
} elseif ($path === '/') {
    echo json_encode(['message' => 'Finonest API Server', 'status' => 'running']);
} else {
    // Return 404 for other requests
    http_response_code(404);
    echo json_encode(['error' => 'Not found', 'path' => $path, 'uri' => $request_uri]);
}
?>