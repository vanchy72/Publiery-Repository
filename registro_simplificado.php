<?php
/**
 * ENDPOINT DE REGISTRO SIMPLIFICADO PARA DEBUG
 * Versión paso a paso para identificar el problema exacto
 */

// Mostrar errores para debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config/database.php';

// Función de sanitización
function sanitizeInput($data) {
    if ($data === null) return '';
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Método no permitido'], 405);
}

try {
    echo "DEBUG: Iniciando proceso de registro\n";
    
    // Paso 1: Obtener datos del POST
    echo "DEBUG: Obteniendo datos del POST\n";
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    echo "DEBUG: Datos recibidos: " . json_encode($input) . "\n";
    
    // Paso 2: Validar y sanitizar datos
    echo "DEBUG: Sanitizando datos\n";
    $nombre = sanitizeInput($input['nombre'] ?? '');
    $email = trim(strtolower($input['email'] ?? ''));
    $documento = sanitizeInput($input['documento'] ?? '');
    $password = $input['password'] ?? '';
    $rol = sanitizeInput($input['rol'] ?? 'lector');
    
    echo "DEBUG: Datos sanitizados - Nombre: $nombre, Email: $email, Rol: $rol\n";
    
    // Paso 3: Validaciones básicas
    echo "DEBUG: Validando datos\n";
    if (empty($nombre)) {
        jsonResponse(['error' => 'El nombre es requerido'], 400);
    }
    if (empty($email)) {
        jsonResponse(['error' => 'El email es requerido'], 400);
    }
    if (empty($password)) {
        jsonResponse(['error' => 'La contraseña es requerida'], 400);
    }
    if (empty($documento)) {
        jsonResponse(['error' => 'El documento es requerido'], 400);
    }
    
    echo "DEBUG: Validaciones básicas OK\n";
    
    // Paso 4: Validar email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['error' => 'Email inválido'], 400);
    }
    
    echo "DEBUG: Email válido\n";
    
    // Paso 5: Conectar a BD
    echo "DEBUG: Conectando a BD\n";
    $conn = getDBConnection();
    
    // Paso 6: Verificar si el email ya existe
    echo "DEBUG: Verificando email existente\n";
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        jsonResponse(['error' => 'El email ya está registrado'], 400);
    }
    
    echo "DEBUG: Email disponible\n";
    
    // Paso 7: Verificar si el documento ya existe
    echo "DEBUG: Verificando documento existente\n";
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE documento = ?");
    $stmt->execute([$documento]);
    if ($stmt->rowCount() > 0) {
        jsonResponse(['error' => 'El documento ya está registrado'], 400);
    }
    
    echo "DEBUG: Documento disponible\n";
    
    // Paso 8: Crear usuario
    echo "DEBUG: Creando usuario\n";
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("
        INSERT INTO usuarios (nombre, email, password, documento, rol, estado, fecha_registro)
        VALUES (?, ?, ?, ?, ?, 'activo', NOW())
    ");
    
    echo "DEBUG: Ejecutando INSERT\n";
    $stmt->execute([$nombre, $email, $hashedPassword, $documento, $rol]);
    
    $userId = $conn->lastInsertId();
    echo "DEBUG: Usuario creado con ID: $userId\n";
    
    // Paso 9: Respuesta exitosa
    echo "DEBUG: Preparando respuesta exitosa\n";
    $response = [
        'success' => true,
        'message' => 'Usuario registrado exitosamente',
        'user' => [
            'id' => $userId,
            'nombre' => $nombre,
            'email' => $email,
            'rol' => $rol,
            'estado' => 'activo'
        ]
    ];
    
    // Si llegamos aquí, todo está bien
    jsonResponse($response, 201);
    
} catch (Exception $e) {
    echo "DEBUG: Error capturado - " . $e->getMessage() . "\n";
    echo "DEBUG: Archivo - " . $e->getFile() . "\n";
    echo "DEBUG: Línea - " . $e->getLine() . "\n";
    
    jsonResponse([
        'error' => 'Error en registro: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ], 500);
}
?>
