<?php
// Headers CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN');

// Preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir dependencias.
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/response.php';

// Verificar que es admin
requireAdmin();

// Recibir datos del usuario (no aplica directamente para GET, pero se mantiene la estructura por si se añade filtro)
$filtro = $_GET['filtro'] ?? ''; // Asume que el filtro viene por GET

try {
    $db = getDBConnection();
    $sql = "SELECT id, nombre, email, rol, estado, fecha_registro FROM usuarios";
    $params = [];

    if (!empty($filtro)) {
        $sql .= " WHERE nombre LIKE ? OR email LIKE ? OR rol LIKE ?";
        $search = '%' . $filtro . '%';
        $params = [$search, $search, $search];
    }

    $sql .= " ORDER BY id DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    jsonResponse([
        'success' => true,
        'usuarios' => $usuarios
    ]);
} catch (Exception $e) {
    error_log('Error listando usuarios: ' . $e->getMessage());
    jsonResponse([
        'success' => false, 
        'error' => 'Error al obtener usuarios: ' . $e->getMessage()
    ], 500);
}
?> 