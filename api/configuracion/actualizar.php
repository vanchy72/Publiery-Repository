<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/response.php';

// Solo permitir solicitudes POST o PUT
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    jsonResponse(['message' => 'Método no permitido'], 405);
}

// Autenticación de administrador
if (!isAdmin()) {
    jsonResponse(['message' => 'Acceso denegado. Solo administradores pueden actualizar la configuración.'], 403);
}

// Obtener los datos del cuerpo de la solicitud (JSON)
$input = json_decode(file_get_contents('php://input'), true);

// Validar que se reciba un array de configuraciones
if (!isset($input['configuraciones']) || !is_array($input['configuraciones'])) {
    jsonResponse(['message' => 'Datos incompletos. Se espera un array de configuraciones.'], 400);
}

$configuraciones = $input['configuraciones'];
$updatedCount = 0;

try {
    $pdo = getDBConnection();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE configuracion SET valor = ?, fecha_actualizacion = CURRENT_TIMESTAMP WHERE clave = ?");

    foreach ($configuraciones as $configItem) {
        if (isset($configItem['clave']) && isset($configItem['valor'])) {
            // Convertir booleanos a string 'true' o 'false' para guardar en TEXT
            if (is_bool($configItem['valor'])) {
                $configItem['valor'] = $configItem['valor'] ? 'true' : 'false';
            } elseif (is_array($configItem['valor']) || is_object($configItem['valor'])) {
                $configItem['valor'] = json_encode($configItem['valor']);
            }
            
            $stmt->execute([$configItem['valor'], $configItem['clave']]);
            $updatedCount += $stmt->rowCount();
        }
    }

    $pdo->commit();
    jsonResponse(['success' => true, 'message' => 'Configuración actualizada exitosamente', 'updated_items' => $updatedCount], 200);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error al actualizar configuración: " . $e->getMessage());
    jsonResponse(['message' => 'Error interno del servidor al actualizar configuración', 'error' => $e->getMessage()], 500);
}
?>
