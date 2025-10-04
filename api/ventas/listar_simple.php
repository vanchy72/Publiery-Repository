<?php
// Headers CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN');

// Manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Preflight request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Función para respuesta JSON
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Verificar autenticación simple
function isAdminAuth() {
    return true; // Temporal para testing
}

try {
    // Verificar autenticación
    if (!isAdminAuth()) {
        sendResponse(['success' => false, 'error' => 'Acceso denegado'], 403);
    }

    // Conectar a base de datos
    require_once __DIR__ . '/../../config/database.php';
    $conn = getDBConnection();
    
    // Obtener filtro si existe
    $filtro = $_GET['filtro'] ?? '';
    
    // Primero verificamos qué columnas existen en la tabla ventas
    $stmt = $conn->prepare("SHOW COLUMNS FROM ventas");
    $stmt->execute();
    $columnas_ventas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Construir query dinámicamente basado en las columnas que existen
    $select_fields = ['v.id'];
    
    // Campos comunes que verificamos
    $campos_posibles = [
        'fecha_venta', 'fecha_creacion', 'fecha',
        'estado', 'status',
        'precio_pagado', 'precio_total', 'total', 'precio',
        'comprador_nombre', 'nombre_comprador', 'comprador',
        'comprador_email', 'email_comprador', 'email'
    ];
    
    foreach ($campos_posibles as $campo) {
        if (in_array($campo, $columnas_ventas)) {
            $select_fields[] = "v.$campo";
        }
    }
    
    $sql = "SELECT " . implode(', ', $select_fields) . "
            FROM ventas v 
            WHERE 1=1";
    $params = [];

    if (!empty($filtro)) {
        $where_conditions = ["v.id LIKE ?"];
        $params = ['%' . $filtro . '%'];
        
        // Agregar condiciones de filtro solo para campos que existen
        if (in_array('estado', $columnas_ventas)) {
            $where_conditions[] = "v.estado LIKE ?";
            $params[] = '%' . $filtro . '%';
        }
        
        $sql .= " AND (" . implode(' OR ', $where_conditions) . ")";
    }

    $sql .= " ORDER BY v.id DESC";

    // Ejecutar query
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendResponse([
        'success' => true,
        'ventas' => $ventas
    ]);

} catch (Exception $e) {
    error_log('Error listando ventas: ' . $e->getMessage());
    sendResponse([
        'success' => false, 
        'error' => 'Error al obtener ventas: ' . $e->getMessage()
    ], 500);
}
?>