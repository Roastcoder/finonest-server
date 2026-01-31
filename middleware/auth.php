<?php
require_once '../config/jwt.php';
require_once '../models/User.php';

class AuthMiddleware {
    public static function authenticate() {
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

    public static function requireAdmin() {
        $user = self::authenticate();
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Admin access required']);
            exit();
        }
        return $user;
    }

    public static function requireCustomer() {
        $user = self::authenticate();
        if ($user['role'] !== 'customer') {
            http_response_code(403);
            echo json_encode(['error' => 'Customer access required']);
            exit();
        }
        return $user;
    }
}
?>