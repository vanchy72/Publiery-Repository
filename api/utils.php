<?php
class Utils {
    // Validate and sanitize input
    public static function sanitizeInput($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    // Generate a secure random password
    public static function generatePassword($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
        return substr(str_shuffle($chars), 0, $length);
    }

    // Validate email format
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    // Hash password securely
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    // Verify password
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    // Generate JSON response
    public static function sendResponse($success, $data = null, $message = null, $code = 200) {
        http_response_code($code);
        echo json_encode([
            'success' => $success,
            'data' => $data,
            'message' => $message
        ]);
        exit;
    }

    // Validate admin session
    public static function validateAdminSession() {
        if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'admin') {
            self::sendResponse(false, null, 'Acceso no autorizado', 401);
        }
    }

    // Log actions
    public static function logAction($userId, $action, $details) {
        // Implement logging functionality
        // Could write to database or file
    }

    // Format date to standard format
    public static function formatDate($date) {
        return date('Y-m-d H:i:s', strtotime($date));
    }

    // Validate required fields
    public static function validateRequired($data, $fields) {
        foreach ($fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                return false;
            }
        }
        return true;
    }

    // Clean file name for security
    public static function sanitizeFileName($fileName) {
        // Remove any character that isn't a letter, number, dot or dash
        $fileName = preg_replace("/[^a-zA-Z0-9.-]/", "_", $fileName);
        // Remove any dots except the last one
        $fileName = preg_replace("/\.(?=.*\.)/", "_", $fileName);
        return $fileName;
    }

    // Get request method (supports _method override)
    public static function getRequestMethod() {
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method === 'POST' && isset($_POST['_method'])) {
            return strtoupper($_POST['_method']);
        }
        return $method;
    }

    // Get request data based on method
    public static function getRequestData() {
        $method = self::getRequestMethod();
        switch ($method) {
            case 'GET':
                return $_GET;
            case 'POST':
                return json_decode(file_get_contents('php://input'), true) ?? $_POST;
            case 'PUT':
            case 'DELETE':
                if (isset($_POST['_method'])) {
                    return $_POST;
                }
                return json_decode(file_get_contents('php://input'), true);
            default:
                return [];
        }
    }
}