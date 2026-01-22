<?php
class SecureCorsMiddleware {
    private static $allowedOrigins = [
        'https://finonest.com',
        'https://www.finonest.com'
    ];
    
    public static function handle() {
        // Force clear any existing headers by setting them to empty first
        @header('Access-Control-Allow-Origin:', true);
        @header('Access-Control-Allow-Methods:', true);
        @header('Access-Control-Allow-Headers:', true);
        @header('Access-Control-Allow-Credentials:', true);
        
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // Always set our secure headers
        if (in_array($origin, self::$allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin", true);
            header('Access-Control-Allow-Credentials: true', true);
        } else {
            // Don't set any origin header for unauthorized origins
            header('Access-Control-Allow-Origin: null', true);
        }
        
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS', true);
        header('Access-Control-Allow-Headers: Content-Type, Authorization', true);
        header('Access-Control-Max-Age: 86400', true);
        
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit();
        }
        
        header('Content-Type: application/json', true);
    }
}
?>