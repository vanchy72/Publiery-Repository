<?php
// Incluir el archivo de conexión a la base de datos
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/response.php';

// Solo permitir solicitudes PUT o POST con un campo _method=PUT (para compatibilidad)
if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && !($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'PUT')) {
    jsonResponse(['message' => 'Método no permitido'], 405);
}

// Autenticación de administrador
if (!isAdmin()) {
    jsonResponse(['message' => 'Acceso denegado. Solo administradores pueden realizar esta acción.'], 403);
}

// Obtener los datos del cuerpo de la solicitud (JSON)
$input = json_decode(file_get_contents('php://input'), true);

// Validar que el ID del testimonio y al menos un campo a actualizar estén presentes
if (!isset($input['id'])) {
    jsonResponse(['message' => 'ID de testimonio no proporcionado.'], 400);
}

$id = $input['id'];
$updates = [];
$params = [];

if (isset($input['nombre'])) {
    $updates[] = 'nombre = ?';
    $params[] = $input['nombre'];
}
if (isset($input['email'])) {
    $updates[] = 'email = ?';
    $params[] = $input['email'];
}
if (isset($input['testimonio'])) {
    $updates[] = 'testimonio = ?';
    $params[] = $input['testimonio'];
}

// Si no hay campos para actualizar, retornar un error
if (empty($updates)) {
    jsonResponse(['message' => 'No hay campos para actualizar.'], 400);
}

$params[] = $id; // El ID siempre es el último parámetro para la cláusula WHERE

try {
    $pdo = connectDB();
    $sql = "UPDATE testimonios SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() > 0) {
        jsonResponse(['message' => 'Testimonio actualizado exitosamente'], 200);
    } else {
        jsonResponse(['message' => 'No se encontró el testimonio o no hubo cambios'], 404);
    }
} catch (PDOException $e) {
    error_log("Error al actualizar testimonio: " . $e->getMessage());
    jsonResponse(['message' => 'Error interno del servidor'], 500);
}
?>
