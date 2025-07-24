<?php
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json; charset=utf-8');

// Validar que el usuario sea admin (opcional: puedes mejorar esto con autenticación real)
// Por ahora, solo ejecuta la consulta

try {
    $conn = getDBConnection();
    $stmt = $conn->query("SELECT id, nombre, email, rol, estado, fecha_registro FROM usuarios ORDER BY id DESC");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
        'success' => true,
        'usuarios' => $usuarios
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener usuarios: ' . $e->getMessage()
    ]);
} 