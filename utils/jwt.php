<?php
class JWT {
    private static $secret = 'YOUR_SECURE_SECRET_KEY'; // Change this in production
    private static $algorithm = 'HS256';
    
    public static function encode($payload) {
        $header = json_encode([
            'typ' => 'JWT',
            'alg' => self::$algorithm
        ]);
        
        $payload['iat'] = time(); // Issued at
        $payload['exp'] = time() + (60 * 60 * 24); // Expires in 24 hours
        $payload = json_encode($payload);
        
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', 
            $base64UrlHeader . "." . $base64UrlPayload, 
            self::$secret, 
            true
        );
        
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
    
    public static function decode($jwt) {
        $tokenParts = explode('.', $jwt);
        if (count($tokenParts) != 3) {
            throw new Exception('Invalid token format');
        }
        
        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $tokenParts;
        
        $signature = base64_decode(str_replace(['-', '_'], ['+', '/'], $base64UrlSignature));
        
        $validSignature = hash_hmac('sha256',
            $base64UrlHeader . "." . $base64UrlPayload,
            self::$secret,
            true
        );
        
        if (!hash_equals($signature, $validSignature)) {
            throw new Exception('Invalid signature');
        }
        
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64UrlPayload)), true);
        
        if (!$payload) {
            throw new Exception('Invalid payload');
        }
        
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new Exception('Token expired');
        }
        
        return $payload;
    }
    
    public static function validateToken($token) {
        try {
            return self::decode($token);
        } catch (Exception $e) {
            return false;
        }
    }
}