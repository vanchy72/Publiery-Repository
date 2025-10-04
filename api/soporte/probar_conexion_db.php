<?php
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../config/database.php';

// Solo permitir solicitudes GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['message' => 'Método no permitido'], 405);
}

// Autenticación de administrador
if (!isAdmin()) {
    jsonResponse(['message' => 'Acceso denegado. Solo administradores pueden probar la conexión a la base de datos.'], 403);
}

try {
    $pdo = getDBConnection();
    // Si llegamos aquí, la conexión fue exitosa
    jsonResponse(['success' => true, 'message' => 'Conexión a la base de datos exitosa.'], 200);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Error al conectar a la base de datos.', 'error' => $e->getMessage()], 500);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Error inesperado al probar la conexión a la base de datos.', 'error' => $e->getMessage()], 500);
}
?>
