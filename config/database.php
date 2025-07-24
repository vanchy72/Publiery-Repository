<?php
/**
 * Configuración de Base de Datos
 * Archivo de configuración para conexión MySQL
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'publiery_db';
    private $username = 'root';
    private $password = '';
    private $conn;

    /**
     * Obtener conexión a la base de datos
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $exception) {
            error_log("Error de conexión: " . $exception->getMessage());
            throw new Exception("Error de conexión a la base de datos");
        }

        return $this->conn;
    }

    /**
     * Cerrar conexión
     */
    public function closeConnection() {
        $this->conn = null;
    }
}

// Configuración global
define('DB_HOST', 'localhost');
define('DB_NAME', 'publiery_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuración de la aplicación
define('APP_NAME', 'Publiery');
// Detectar automáticamente la URL base
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$path = dirname($_SERVER['SCRIPT_NAME'] ?? '');
// Eliminada la definición de APP_URL para evitar redefinición
define('APP_VERSION', '1.0.0');

// Configuración de seguridad
define('JWT_SECRET', 'tu_clave_secreta_muy_segura_aqui_2024');
define('SESSION_TIMEOUT', 3600); // 1 hora

// Configuración de archivos
define('UPLOAD_PATH', '../uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

// Configuración de email (Sistema de Emails Automáticos)
// Para Gmail: Habilitar verificación en 2 pasos y usar contraseña de aplicación
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
// Credenciales reales de Publiery
define('SMTP_USER', 'publierycompany@gmail.com');
define('SMTP_PASS', 'mkmlqfblxsruozxj');

// Para otros proveedores:
// Outlook/Hotmail: smtp-mail.outlook.com:587
// Yahoo: smtp.mail.yahoo.com:587
// Servidor propio: tu_servidor.com:587

// Función para obtener conexión global
function getDBConnection() {
    static $db = null;
    if ($db === null) {
        $db = new Database();
    }
    return $db->getConnection();
}

// Función para manejo de errores
function handleError($error, $context = '') {
    error_log("Error en $context: " . $error);
    
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        return ['error' => $error, 'context' => $context];
    } else {
        return ['error' => 'Ha ocurrido un error interno'];
    }
}

// Función para validar datos de entrada
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Función para generar tokens seguros
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Función para validar email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Función para validar contraseña
function validatePassword($password) {
    // Mínimo 8 caracteres, al menos una mayúscula, una minúscula y un número
    return strlen($password) >= 8 && 
           preg_match('/[A-Z]/', $password) && 
           preg_match('/[a-z]/', $password) && 
           preg_match('/[0-9]/', $password);
}

// Función para hashear contraseñas
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Función para verificar contraseñas
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Función para generar respuesta JSON
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Función para verificar si el usuario está autenticado
function isAuthenticated() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Función para obtener información del usuario actual
function getCurrentUser() {
    if (!isAuthenticated()) {
        return null;
    }
    
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

// Función para verificar permisos de rol
function hasRole($requiredRole) {
    $user = getCurrentUser();
    if (!$user) return false;
    
    return $user['rol'] === $requiredRole || $user['rol'] === 'admin';
}

// Función para registrar actividad
function logActivity($userId, $action, $details = '') {
    try {
        $conn = getDBConnection();
        
        // Verificar si la tabla log_actividad existe
        $stmt = $conn->prepare("SHOW TABLES LIKE 'log_actividad'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            // Crear la tabla si no existe
            $conn->exec("
                CREATE TABLE log_actividad (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario_id INT NOT NULL,
                    accion VARCHAR(100) NOT NULL,
                    detalles TEXT NULL,
                    ip_address VARCHAR(45) NULL,
                    user_agent TEXT NULL,
                    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                    INDEX idx_usuario (usuario_id),
                    INDEX idx_fecha (fecha_creacion)
                )
            ");
        }
        
        $stmt = $conn->prepare("
            INSERT INTO log_actividad (usuario_id, accion, detalles, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $e) {
        // Solo log en desarrollo
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("Error registrando actividad: " . $e->getMessage());
        }
    }
}
?> 