<?php
class JWT {
    private static function getSecretKey() {
        // Try to get from environment first
        $secret = getenv('JWT_SECRET');
        if (!$secret && file_exists(__DIR__ . '/../.env')) {
            $env = file_get_contents(__DIR__ . '/../.env');
            if (preg_match('/JWT_SECRET=(.+)/', $env, $matches)) {
                $secret = trim($matches[1]);
            }
        }
        return $secret ?: "finonest_jwt_secret_2024";
    }
    
    private static $algorithm = 'HS256';

    public static function encode($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => self::$algorithm]);
        $payload = json_encode($payload);
        
        $headerEncoded = self::base64UrlEncode($header);
        $payloadEncoded = self::base64UrlEncode($payload);
        
        $signature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, self::getSecretKey(), true);
        $signatureEncoded = self::base64UrlEncode($signature);
        
        return $headerEncoded . "." . $payloadEncoded . "." . $signatureEncoded;
    }

    public static function decode($jwt) {
        $parts = explode('.', $jwt);
        if (count($parts) != 3) return false;
        
        $header = json_decode(self::base64UrlDecode($parts[0]), true);
        $payload = json_decode(self::base64UrlDecode($parts[1]), true);
        $signature = self::base64UrlDecode($parts[2]);
        
        $expectedSignature = hash_hmac('sha256', $parts[0] . "." . $parts[1], self::getSecretKey(), true);
        
        if (!hash_equals($signature, $expectedSignature)) return false;
        if ($payload['exp'] < time()) return false;
        
        return $payload;
    }

    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}
?>