<?php
// Headers CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN');

// Manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Preflight request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Función para respuesta JSON
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Verificar autenticación simple
function isAdminAuth() {
    return true; // Temporal para testing
}

try {
    // Verificar autenticación
    if (!isAdminAuth()) {
        sendResponse(['success' => false, 'error' => 'Acceso denegado'], 403);
    }

    // Conectar a base de datos
    require_once __DIR__ . '/../../config/database.php';
    $conn = getDBConnection();
    
    // Obtener filtro si existe
    $filtro = $_GET['filtro'] ?? '';
    
    // Primero verificamos qué columnas existen en la tabla comisiones
    $stmt = $conn->prepare("SHOW COLUMNS FROM comisiones");
    $stmt->execute();
    $columnas_comisiones = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Construir query dinámicamente basado en las columnas que existen
    $select_fields = ['c.id'];
    
    // Campos comunes que verificamos
    $campos_posibles = [
        'fecha_creacion', 'fecha_pago', 'fecha',
        'monto', 'cantidad', 'total',
        'tipo', 'categoria',
        'estado', 'status',
        'porcentaje', 'nivel'
    ];
    
    foreach ($campos_posibles as $campo) {
        if (in_array($campo, $columnas_comisiones)) {
            $select_fields[] = "c.$campo";
        }
    }
    
    $sql = "SELECT " . implode(', ', $select_fields) . "
            FROM comisiones c 
            WHERE 1=1";
    $params = [];

    if (!empty($filtro)) {
        $where_conditions = ["c.id LIKE ?"];
        $params = ['%' . $filtro . '%'];
        
        // Agregar condiciones de filtro solo para campos que existen
        if (in_array('estado', $columnas_comisiones)) {
            $where_conditions[] = "c.estado LIKE ?";
            $params[] = '%' . $filtro . '%';
        }
        if (in_array('tipo', $columnas_comisiones)) {
            $where_conditions[] = "c.tipo LIKE ?";
            $params[] = '%' . $filtro . '%';
        }
        
        $sql .= " AND (" . implode(' OR ', $where_conditions) . ")";
    }

    $sql .= " ORDER BY c.id DESC";

    // Ejecutar query
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $comisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Transformar los datos para que coincidan con la estructura esperada por el frontend
    $pagos = array_map(function($comision) {
        return [
            'id' => $comision['id'],
            'destinatario_nombre' => $comision['destinatario_nombre'] ?? 'N/A',
            'monto' => $comision['monto'] ?? '0.00',
            'tipo_pago' => ($comision['tipo'] ?? 'comision') . 
                          (isset($comision['porcentaje']) ? ' (' . $comision['porcentaje'] . '%)' : '') .
                          (isset($comision['nivel']) ? ' - Nivel ' . $comision['nivel'] : ''),
            'fecha_pago' => $comision['fecha_pago'] ?? $comision['fecha_creacion'] ?? $comision['fecha'] ?? 'N/A',
            'estado' => $comision['estado'] ?? $comision['status'] ?? 'pendiente',
            'libro_titulo' => $comision['libro_titulo'] ?? 'N/A',
            'codigo_afiliado' => $comision['codigo_afiliado'] ?? 'N/A',
            'fecha_venta' => $comision['fecha_venta'] ?? 'N/A',
            'precio_venta' => $comision['precio_venta'] ?? 'N/A'
        ];
    }, $comisiones);
    
    sendResponse([
        'success' => true,
        'pagos' => $pagos
    ]);

} catch (Exception $e) {
    error_log('Error listando pagos: ' . $e->getMessage());
    sendResponse([
        'success' => false, 
        'error' => 'Error al obtener pagos: ' . $e->getMessage()
    ], 500);
}
?>