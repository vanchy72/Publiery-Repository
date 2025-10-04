<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';

try {
    $pdo = getDBConnection();

    $tableExists = [];

    // Check for 'configuracion' table
    $stmt = $pdo->query("SHOW TABLES LIKE 'configuracion'");
    $tableExists['configuracion'] = ($stmt->rowCount() > 0);

    // Check for 'settings' table
    $stmt = $pdo->query("SHOW TABLES LIKE 'settings'");
    $tableExists['settings'] = ($stmt->rowCount() > 0);

    jsonResponse(['success' => true, 'table_exists' => $tableExists], 200);

} catch (PDOException $e) {
    error_log("Error checking config tables: " . $e->getMessage());
    jsonResponse(['message' => 'Error interno del servidor al verificar tablas de configuración', 'error' => $e->getMessage()], 500);
}
?>
