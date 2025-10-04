<?php
// Incluir el archivo de conexión a la base de datos
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/response.php';

// Solo permitir solicitudes DELETE o POST con un campo _method=DELETE (para compatibilidad)
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && !($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'DELETE')) {
    jsonResponse(['message' => 'Método no permitido'], 405);
}

// Autenticación de administrador
if (!isAdmin()) {
    jsonResponse(['message' => 'Acceso denegado. Solo administradores pueden realizar esta acción.'], 403);
}

// Obtener el ID del testimonio de la URL o del cuerpo de la solicitud
$id = null;
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Para DELETE, el ID se suele pasar en la URL (ej: /api/testimonios/eliminar.php?id=123)
    if (isset($_GET['id'])) {
        $id = $_GET['id'];
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'DELETE') {
    // Para POST con _method=DELETE, el ID podría venir en el cuerpo (JSON o form-data)
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['id'])) {
        $id = $input['id'];
    } else if (isset($_POST['id'])) { // Fallback para form-data
        $id = $_POST['id'];
    }
}

if (empty($id)) {
    jsonResponse(['message' => 'ID de testimonio no proporcionado.'], 400);
}

try {
    $pdo = connectDB();
    $stmt = $pdo->prepare("DELETE FROM testimonios WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        jsonResponse(['message' => 'Testimonio eliminado exitosamente'], 200);
    } else {
        jsonResponse(['message' => 'No se encontró el testimonio o ya fue eliminado'], 404);
    }
} catch (PDOException $e) {
    error_log("Error al eliminar testimonio: " . $e->getMessage());
    jsonResponse(['message' => 'Error interno del servidor'], 500);
}
?>
