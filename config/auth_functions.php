<?php
/**
 * FUNCIONES DE AUTENTICACIÓN Y UTILIDADES
 * Funciones necesarias para el sistema de registro y login
 */

/**
 * Limpiar y sanitizar entrada de usuario
 */
function sanitizeInput($input) {
    return trim(htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8'));
}

/**
 * Validar formato de email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validar contraseña segura
 */
function validatePassword($password) {
    // Al menos 8 caracteres, una mayúscula, una minúscula y un número
    return strlen($password) >= 8 && 
           preg_match('/[A-Z]/', $password) && 
           preg_match('/[a-z]/', $password) && 
           preg_match('/[0-9]/', $password);
}

/**
 * Responder con JSON
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Generar token seguro
 */
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Registrar actividad de usuario
 */
function logActivity($userId, $action, $description = '') {
    try {
        $conn = getDBConnection();
        
        // Verificar si existe la tabla de logs
        $stmt = $conn->query("SHOW TABLES LIKE 'log_actividad'");
        if ($stmt->rowCount() == 0) {
            // Crear tabla si no existe
            $conn->exec("
                CREATE TABLE log_actividad (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario_id INT,
                    accion VARCHAR(100),
                    descripcion TEXT,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
        }
        
        $stmt = $conn->prepare("
            INSERT INTO log_actividad (usuario_id, accion, descripcion, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Error logging activity: " . $e->getMessage());
        return false;
    }
}

// La función sendWelcomeEmail() ya está definida en config/email.php

// Las funciones hashPassword() y verifyPassword() ya están definidas en database.php
?>