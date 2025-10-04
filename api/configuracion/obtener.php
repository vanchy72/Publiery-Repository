<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/response.php';

// Solo permitir solicitudes GET si se está ejecutando a través de un servidor web
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['message' => 'Método no permitido'], 405);
}

// Autenticación de administrador
if (!isAdmin()) {
    jsonResponse(['message' => 'Acceso denegado. Solo administradores pueden ver la configuración.'], 403);
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT clave, valor, descripcion, tipo FROM configuracion");
    $configuracion = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convertir valores booleanos y numéricos si el 'tipo' lo indica
    foreach ($configuracion as &$item) {
        switch ($item['tipo']) {
            case 'boolean':
                $item['valor'] = filter_var($item['valor'], FILTER_VALIDATE_BOOLEAN);
                break;
            case 'number':
                $item['valor'] = is_numeric($item['valor']) ? (float)$item['valor'] : $item['valor'];
                break;
            case 'json':
                $item['valor'] = json_decode($item['valor'], true);
                break;
        }
    }

    jsonResponse(['success' => true, 'configuracion' => $configuracion], 200);

} catch (PDOException $e) {
    error_log("Error al obtener configuración: " . $e->getMessage());
    jsonResponse(['message' => 'Error interno del servidor al obtener configuración', 'error' => $e->getMessage()], 500);
}
?>
