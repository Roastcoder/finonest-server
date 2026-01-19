<?php
// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// Debug
error_log("Request URI: $uri, Path: $path");

if ($path === '/api/auth/login' || $path === '/api/auth/register') {
    $_SERVER['PATH_INFO'] = str_replace('/api/auth', '', $path);
    include 'api/auth.php';
} elseif (strpos($path, '/api/admin/') === 0) {
    $_SERVER['PATH_INFO'] = str_replace('/api/admin', '', $path);
    include 'api/admin.php';
} elseif ($path === '/api/forms' || $path === '/api/forms/mine' || strpos($path, '/api/forms/') === 0) {
    $_SERVER['PATH_INFO'] = str_replace('/api/forms', '', $path);
    include 'api/forms.php';
} elseif ($path === '/api/contact') {
    include 'api/contact.php';
} elseif (strpos($path, '/api/branches') === 0) {
    $_SERVER['PATH_INFO'] = str_replace('/api/branches', '', $path);
    include 'api/branches.php';
} elseif (strpos($path, '/api/bankers') === 0) {
    $_SERVER['PATH_INFO'] = str_replace('/api/bankers', '', $path);
    include 'api/bankers.php';
} elseif ($path === '/api/validate') {
    include 'api/validate.php';
} elseif ($path === '/') {
    echo json_encode(['message' => 'Finonest API Server', 'status' => 'running']);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Not found', 'path' => $path]);
}
?>