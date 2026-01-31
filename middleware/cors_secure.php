<?php
class SecureCorsMiddleware {
    private static $allowedOrigins = [
        'https://finonest.com',
        'https://www.finonest.com'
    ];
    
    public static function handle() {
        // Remove any existing headers first
        header_remove('Access-Control-Allow-Origin');
        header_remove('Access-Control-Allow-Methods');
        header_remove('Access-Control-Allow-Headers');
        header_remove('Access-Control-Allow-Credentials');
        
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // Always set CORS headers for allowed origins
        if (in_array($origin, self::$allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
            header('Access-Control-Allow-Credentials: true');
        } else {
            // Set wildcard for development/testing
            header('Access-Control-Allow-Origin: *');
        }
        
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Max-Age: 86400');
        
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit();
        }
        
        header('Content-Type: application/json');
    }
}
?>