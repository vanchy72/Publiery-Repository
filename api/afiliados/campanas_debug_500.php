<?php
/**
 * API Campañas - Versión de Debug para Error 500
 */

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en output
ini_set('log_errors', 1);

// Iniciar sesión
session_start();

// Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Función de respuesta segura
function safeResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Verificar método
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    if ($method !== 'GET') {
        safeResponse(['error' => 'Solo GET permitido en debug'], 405);
    }
    
    // Conexión directa a base de datos
    $host = 'localhost';
    $dbname = 'publiery_db';
    $username = 'root';
    $password = '';
    
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Consulta simple primero
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM campanas");
    $stmt->execute();
    $count = $stmt->fetch();
    
    // Consulta básica de campañas
    $stmt = $conn->prepare("
        SELECT 
            id,
            nombre,
            descripcion,
            tipo,
            estado,
            fecha_creacion,
            libro_ids
        FROM campanas 
        WHERE estado IN ('borrador', 'programada', 'enviando', 'completada')
        ORDER BY fecha_creacion DESC
        LIMIT 5
    ");
    
    $stmt->execute();
    $campanas = $stmt->fetchAll();
    
    // Formatear respuesta simple
    $campanasSimples = [];
    foreach ($campanas as $campana) {
        $campanasSimples[] = [
            'id' => (int)$campana['id'],
            'nombre' => $campana['nombre'],
            'descripcion' => substr($campana['descripcion'], 0, 100) . '...',
            'tipo' => $campana['tipo'],
            'estado' => $campana['estado'],
            'fecha_creacion' => $campana['fecha_creacion'],
            'tiene_libros' => !empty($campana['libro_ids'])
        ];
    }
    
    safeResponse([
        'success' => true,
        'debug' => true,
        'total_campanas' => $count['total'],
        'campanas' => $campanasSimples,
        'message' => 'Debug API funcionando correctamente'
    ]);
    
} catch (PDOException $e) {
    safeResponse([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ], 500);
} catch (Exception $e) {
    safeResponse([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ], 500);
}
?>