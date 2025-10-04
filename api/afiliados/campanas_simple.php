<?php
// API Campañas - Versión Ultra Simplificada para Debug
// Solo devuelve JSON básico sin dependencias

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Limpiar cualquier salida previa
if (ob_get_level()) {
    ob_clean();
}

try {
    // Configuración directa sin archivos externos
    $host = 'localhost';
    $dbname = 'publiery_db';
    $username = 'root';
    $password = '';
    
    // Conectar directamente
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Consulta simple
        $stmt = $pdo->prepare("SELECT * FROM campanas ORDER BY fecha_creacion DESC");
        $stmt->execute();
        $campanas = $stmt->fetchAll();
        
        // Formatear para respuesta
        $campanasFormateadas = [];
        foreach ($campanas as $campana) {
            $campanasFormateadas[] = [
                'id' => (int)$campana['id'],
                'nombre' => $campana['nombre'] ?? '',
                'descripcion' => $campana['descripcion'] ?? '',
                'tipo' => $campana['tipo'] ?? 'promocion',
                'estado' => $campana['estado'] ?? 'borrador',
                'fecha_creacion' => $campana['fecha_creacion'] ?? ''
            ];
        }
        
        echo json_encode([
            'success' => true,
            'campanas' => $campanasFormateadas,
            'total' => count($campanasFormateadas),
            'debug' => 'API simplificada funcionando'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Método no permitido'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
?>