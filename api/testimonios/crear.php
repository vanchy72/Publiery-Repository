<?php
// Incluir el archivo de conexión a la base de datos
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/response.php';

// Solo permitir solicitudes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['message' => 'Método no permitido'], 405);
}

// Autenticación de administrador
if (!isAdmin()) {
    jsonResponse(['message' => 'Acceso denegado. Solo administradores pueden realizar esta acción.'], 403);
}

// Obtener los datos del cuerpo de la solicitud (JSON)
$input = json_decode(file_get_contents('php://input'), true);

// Validar que los datos requeridos estén presentes
if (!isset($input['nombre'], $input['email'], $input['testimonio'])) {
    jsonResponse(['message' => 'Datos incompletos. Se requieren nombre, email y testimonio.'], 400);
}

$nombre = $input['nombre'];
$email = $input['email'];
$testimonio = $input['testimonio'];

try {
    $pdo = connectDB();
    $stmt = $pdo->prepare("INSERT INTO testimonios (nombre, email, testimonio) VALUES (?, ?, ?)");
    $stmt->execute([$nombre, $email, $testimonio]);

    if ($stmt->rowCount() > 0) {
        jsonResponse(['message' => 'Testimonio creado exitosamente', 'id' => $pdo->lastInsertId()], 201);
    } else {
        jsonResponse(['message' => 'No se pudo crear el testimonio'], 500);
    }
} catch (PDOException $e) {
    error_log("Error al crear testimonio: " . $e->getMessage());
    jsonResponse(['message' => 'Error interno del servidor'], 500);
}
?>
