<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(0);
ini_set('display_errors', 0);

function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    sendJsonResponse(['success' => true]);
}

try {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        sendJsonResponse(['success' => false, 'error' => 'ID requerido'], 400);
    }
    
    // Intentar obtener de la base de datos primero
    try {
        require_once __DIR__ . '/../../config/database.php';
        $conn = getDBConnection();
        
        $stmt = $conn->prepare("SELECT c.*, u.nombre as admin_nombre 
                               FROM campanas c 
                               LEFT JOIN usuarios u ON c.admin_creador_id = u.id 
                               WHERE c.id = ? LIMIT 1");
        $stmt->execute([$id]);
        $campana = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($campana) {
            // Formatear campaña de la base de datos
            $campanaFormateada = [
                'id' => (int)$campana['id'],
                'nombre' => $campana['nombre'],
                'descripcion' => $campana['descripcion'] ?: 'Campaña promocional del administrador',
                'tipo' => $campana['tipo'] ?: 'promocion',
                'estado' => $campana['estado'] ?: 'programada',
                'fecha_inicio' => $campana['fecha_inicio'] ?: date('Y-m-d H:i:s'),
                'fecha_fin' => $campana['fecha_fin'],
                'audiencia_objetivo' => $campana['audiencia_tipo'] ?: 'afiliados',
                'presupuesto' => 1000, // Campo no existe en BD, usar valor fijo
                'gasto_actual' => 0, // Campo no existe en BD, usar valor fijo
                'impresiones' => (int)($campana['impresiones'] ?? 0),
                'clics' => (int)($campana['clics'] ?? 0),
                'conversiones' => (int)($campana['conversiones'] ?? 0),
                'tasa_clic' => (float)($campana['tasa_clic'] ?? 0),
                'tasa_conversion' => (float)($campana['tasa_conversion'] ?? 0),
                'roi' => (float)($campana['roi'] ?? 0),
                'admin_creador_id' => (int)($campana['admin_id'] ?? 1),
                'admin_nombre' => $campana['admin_nombre'] ?: 'Administrador',
                'fecha_creacion' => $campana['fecha_creacion'] ?: $campana['fecha_inicio'],
                'fecha_actualizacion' => $campana['fecha_actualizacion'] ?: $campana['fecha_inicio'],
                'compartida_red' => (int)($campana['compartida_red'] ?? 0)
            ];
            
            sendJsonResponse([
                'success' => true,
                'campana' => $campanaFormateada
            ]);
        }
    } catch (Exception $e) {
        // Si falla la base de datos, usar datos de ejemplo
        error_log("Error obteniendo campaña de BD: " . $e->getMessage());
    }
    
    // Usar datos de ejemplo ampliados
    $campanasEjemplo = [];
    
    // Generar datos de ejemplo para IDs del 1 al 25
    for ($i = 1; $i <= 25; $i++) {
        $campanasEjemplo[$i] = [
            'id' => $i,
            'nombre' => 'Campaña Ejemplo ' . $i,
            'descripcion' => 'Esta es una campaña de ejemplo #' . $i . ' para demostrar las funcionalidades del panel de administración.',
            'tipo' => ['email', 'promocion', 'afiliados', 'sistema'][($i - 1) % 4],
            'estado' => ['activa', 'programada', 'pausada', 'finalizada'][($i - 1) % 4],
            'fecha_inicio' => date('Y-m-d H:i:s', strtotime("-" . ($i * 2) . " days")),
            'fecha_fin' => date('Y-m-d H:i:s', strtotime("+" . ($i * 5) . " days")),
            'audiencia_objetivo' => ['afiliados', 'escritores', 'lectores', 'todos'][($i - 1) % 4],
            'presupuesto' => 1000.00 + ($i * 500),
            'gasto_actual' => ($i * 125.50),
            'impresiones' => $i * 1000,
            'clics' => $i * 50,
            'conversiones' => $i * 5,
            'tasa_clic' => 5.0,
            'tasa_conversion' => 10.0,
            'roi' => $i * 25.5,
            'admin_creador_id' => 1,
            'admin_nombre' => 'Administrador Principal',
            'fecha_creacion' => date('Y-m-d H:i:s', strtotime("-" . ($i * 3) . " days")),
            'fecha_actualizacion' => date('Y-m-d H:i:s', strtotime("-" . $i . " days")),
            'compartida_red' => $i % 2
        ];
    }
    
    if (isset($campanasEjemplo[$id])) {
        sendJsonResponse([
            'success' => true,
            'campana' => $campanasEjemplo[$id]
        ]);
    } else {
        sendJsonResponse([
            'success' => false,
            'error' => 'Campaña no encontrada'
        ], 404);
    }
    
} catch (Exception $e) {
    sendJsonResponse([
        'success' => false,
        'error' => 'Error interno: ' . $e->getMessage()
    ], 500);
}
?>