<?php
// Limpiar cualquier output previo y suprimir warnings
if (ob_get_level()) {
    ob_clean();
}
ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);

require_once __DIR__ . '/../../config/database.php';
require_once '../../config/email_avanzado.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Iniciar sesión y verificar que es admin
if (session_status() === PHP_SESSION_NONE) {
}

if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'error' => 'No autorizado'], 401);
    exit;
}

$db = getDBConnection();
$stmt = $db->prepare("SELECT rol FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['rol'] !== 'admin') {
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden gestionar emails'], 403);
    exit;
}

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? 'listar';
    $emailService = new EmailAvanzado();
    
    switch ($action) {
        case 'listar':
            // Listar logs de emails con filtros
            $filtro = $_GET['filtro'] ?? '';
            $plantilla = $_GET['plantilla'] ?? '';
            $exitoso = $_GET['exitoso'] ?? '';
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = min(100, max(10, intval($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;
            
            $where_conditions = [];
            $params = [];
            
            if (!empty($filtro)) {
                $where_conditions[] = "(email_destinatario LIKE ? OR asunto LIKE ?)";
                $params[] = "%$filtro%";
                $params[] = "%$filtro%";
            }
            
            if (!empty($plantilla)) {
                $where_conditions[] = "plantilla = ?";
                $params[] = $plantilla;
            }
            
            if ($exitoso !== '') {
                $where_conditions[] = "exitoso = ?";
                $params[] = intval($exitoso);
            }
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            // Consulta principal
            $sql = "
                SELECT 
                    id, email_destinatario, asunto, plantilla, exitoso,
                    error_mensaje, fecha_envio, fecha_apertura, fecha_click
                FROM email_logs
                $where_clause
                ORDER BY fecha_envio DESC
                LIMIT $limit OFFSET $offset
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar total
            $count_sql = "SELECT COUNT(*) as total FROM email_logs $where_clause";
            $stmt = $db->prepare($count_sql);
            $stmt->execute($params);
            $total = $stmt->fetch()['total'];
            
            // Estadísticas
            $stats_sql = "
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN exitoso = 1 THEN 1 ELSE 0 END) as enviados_exitosos,
                    SUM(CASE WHEN exitoso = 0 THEN 1 ELSE 0 END) as enviados_fallidos,
                    COUNT(CASE WHEN fecha_apertura IS NOT NULL THEN 1 END) as abiertos,
                    COUNT(CASE WHEN fecha_click IS NOT NULL THEN 1 END) as clicks
                FROM email_logs
                $where_clause
            ";
            
            $stmt = $db->prepare($stats_sql);
            $stmt->execute($params);
            $estadisticas = $stmt->fetch();
            
            jsonResponse([
                'success' => true,
                'emails' => $emails,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ],
                'estadisticas' => [
                    'total' => (int)$estadisticas['total'],
                    'enviados_exitosos' => (int)$estadisticas['enviados_exitosos'],
                    'enviados_fallidos' => (int)$estadisticas['enviados_fallidos'],
                    'abiertos' => (int)$estadisticas['abiertos'],
                    'clicks' => (int)$estadisticas['clicks'],
                    'tasa_apertura' => $estadisticas['total'] > 0 ? round(($estadisticas['abiertos'] / $estadisticas['total']) * 100, 2) : 0,
                    'tasa_click' => $estadisticas['total'] > 0 ? round(($estadisticas['clicks'] / $estadisticas['total']) * 100, 2) : 0
                ]
            ]);
            break;
            
        case 'enviar_prueba':
            // Enviar email de prueba
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || empty($input['email']) || empty($input['plantilla'])) {
                jsonResponse(['success' => false, 'error' => 'Email y plantilla son requeridos'], 400);
                exit;
            }
            
            // Datos de prueba
            $datos = [
                'NOMBRE' => 'Usuario de Prueba',
                'EMAIL' => $input['email'],
                'ROL' => 'admin',
                'LIBRO_TITULO' => 'Libro de Ejemplo',
                'PRECIO' => '25.00',
                'MONTO' => '7.50',
                'FECHA' => date('d/m/Y H:i'),
                'LOGIN_URL' => (APP_URL ?? 'http://localhost/publiery') . '/login.html',
                'DASHBOARD_URL' => (APP_URL ?? 'http://localhost/publiery') . '/admin-panel.html'
            ];
            
            $resultado = $emailService->enviarConPlantilla(
                $input['email'],
                'Email de prueba - ' . ucfirst($input['plantilla']),
                $input['plantilla'],
                $datos
            );
            
            jsonResponse($resultado);
            break;
            
        case 'estadisticas':
            // Obtener estadísticas detalladas
            $fechaDesde = $_GET['fecha_desde'] ?? null;
            $fechaHasta = $_GET['fecha_hasta'] ?? null;
            
            $estadisticas = $emailService->obtenerEstadisticas($fechaDesde, $fechaHasta);
            
            if ($estadisticas) {
                jsonResponse([
                    'success' => true,
                    'estadisticas' => $estadisticas
                ]);
            } else {
                jsonResponse(['success' => false, 'error' => 'Error obteniendo estadísticas'], 500);
            }
            break;
            
        case 'plantillas':
            // Listar plantillas disponibles
            $dirPlantillas = __DIR__ . '/../../config/email_templates/';
            $plantillas = [];
            
            if (is_dir($dirPlantillas)) {
                $archivos = scandir($dirPlantillas);
                foreach ($archivos as $archivo) {
                    if (pathinfo($archivo, PATHINFO_EXTENSION) === 'html') {
                        $nombre = pathinfo($archivo, PATHINFO_FILENAME);
                        $plantillas[] = [
                            'nombre' => $nombre,
                            'archivo' => $archivo,
                            'titulo' => ucfirst(str_replace('_', ' ', $nombre))
                        ];
                    }
                }
            }
            
            jsonResponse([
                'success' => true,
                'plantillas' => $plantillas
            ]);
            break;
            
        case 'configuracion':
            // Obtener configuración de email
            jsonResponse([
                'success' => true,
                'configuracion' => [
                    'smtp_configurado' => true, // En un caso real, verificar la configuración
                    'plantillas_disponibles' => 4,
                    'emails_hoy' => 0 // Calcular emails enviados hoy
                ]
            ]);
            break;
            
        default:
            jsonResponse(['success' => false, 'error' => 'Acción no válida'], 400);
    }

} catch (Exception $e) {
    error_log('Error en gestión de emails: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ], 500);
}
?>
