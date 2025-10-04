<?php
class CSRF {
    private static $tokenName = 'csrf_token';
    private static $headerName = 'X-CSRF-TOKEN';
    
    public static function generateToken() {
        if (!isset($_SESSION[self::$tokenName])) {
            $_SESSION[self::$tokenName] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::$tokenName];
    }
    
    public static function getToken() {
        return $_SESSION[self::$tokenName] ?? null;
    }
    
    public static function validateToken($token = null) {
        if (!$token) {
            // Obtener headers de manera compatible
            $headers = [];
            if (function_exists('getallheaders')) {
                $headers = getallheaders();
            } else {
                foreach ($_SERVER as $key => $value) {
                    if (substr($key, 0, 5) == 'HTTP_') {
                        $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                        $headers[$header] = $value;
                    }
                }
            }
            $token = $headers[self::$headerName] ?? null;
        }
        
        if (!$token || !isset($_SESSION[self::$tokenName])) {
            return false;
        }
        
        return hash_equals($_SESSION[self::$tokenName], $token);
    }
    
    public static function removeToken() {
        unset($_SESSION[self::$tokenName]);
    }
    
    public static function getHeaderName() {
        return self::$headerName;
    }
}