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
    jsonResponse(['message' => 'Acceso denegado. Solo administradores pueden actualizar el estado de los libros.'], 403);
}

// Obtener los datos del cuerpo de la solicitud (JSON)
$input = json_decode(file_get_contents('php://input'), true);

// Validar que el ID del libro y el nuevo estado estén presentes
if (!isset($input['id'], $input['estado'])) {
    jsonResponse(['message' => 'Datos incompletos. Se requiere ID del libro y el nuevo estado.'], 400);
}

$libroId = $input['id'];
$nuevoEstado = $input['estado'];
$comentariosEditorial = $input['comentarios_editorial'] ?? null;

// Validar que el nuevo estado sea uno de los valores permitidos en el ENUM de la DB
$allowedStates = ['pendiente_revision', 'en_revision', 'correccion_autor', 'aprobado_autor', 'publicado', 'rechazado'];
if (!in_array($nuevoEstado, $allowedStates)) {
    jsonResponse(['message' => 'Estado de libro inválido.'], 400);
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        UPDATE 
            libros 
        SET 
            estado = ?, 
            comentarios_editorial = ?, 
            fecha_revision = CURRENT_TIMESTAMP 
        WHERE 
            id = ?
    ");
    $stmt->execute([$nuevoEstado, $comentariosEditorial, $libroId]);

    if ($stmt->rowCount() > 0) {
        jsonResponse(['success' => true, 'message' => 'Estado del libro actualizado exitosamente.'], 200);
    } else {
        jsonResponse(['message' => 'No se encontró el libro o no hubo cambios en el estado.'], 404);
    }

} catch (PDOException $e) {
    error_log("Error al actualizar estado del libro: " . $e->getMessage());
    jsonResponse(['message' => 'Error interno del servidor al actualizar el estado del libro', 'error' => $e->getMessage()], 500);
}
?>
