<?php
require_once '../../session.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar que es admin
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'error' => 'No autorizado'], 401);
    exit;
}

$db = getDBConnection();
$stmt = $db->prepare("SELECT rol FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['rol'] !== 'admin') {
    jsonResponse(['success' => false, 'error' => 'Solo los administradores pueden procesar pagos'], 403);
    exit;
}

// Procesar datos
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['comision_ids']) || !is_array($input['comision_ids']) || empty($input['comision_ids'])) {
    jsonResponse(['success' => false, 'error' => 'IDs de comisiones requeridos'], 400);
    exit;
}

$comisionIds = array_map('intval', $input['comision_ids']);
$fechaPago = $input['fecha_pago'] ?? date('Y-m-d');
$metodoPago = $input['metodo_pago'] ?? 'manual';
$referencia = $input['referencia'] ?? '';
$observaciones = $input['observaciones'] ?? '';

// Validar fecha
if (!DateTime::createFromFormat('Y-m-d', $fechaPago)) {
    jsonResponse(['success' => false, 'error' => 'Fecha de pago inválida'], 400);
    exit;
}

try {
    $db->beginTransaction();

    // Verificar que todas las comisiones existen y están pendientes
    $placeholders = implode(',', array_fill(0, count($comisionIds), '?'));
    $stmt = $db->prepare("
        SELECT 
            c.id, 
            c.monto, 
            c.estado,
            ua.nombre as afiliado_nombre,
            af.codigo_afiliado
        FROM comisiones c
        INNER JOIN afiliados af ON c.afiliado_id = af.id
        INNER JOIN usuarios ua ON af.usuario_id = ua.id
        WHERE c.id IN ($placeholders)
    ");
    $stmt->execute($comisionIds);
    $comisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($comisiones) !== count($comisionIds)) {
        $db->rollBack();
        jsonResponse(['success' => false, 'error' => 'Algunas comisiones no existen'], 404);
        exit;
    }

    // Verificar que todas están pendientes
    $comisionesPendientes = array_filter($comisiones, function($c) {
        return $c['estado'] === 'pendiente';
    });

    if (count($comisionesPendientes) !== count($comisiones)) {
        $db->rollBack();
        $comisionesNoPendientes = array_filter($comisiones, function($c) {
            return $c['estado'] !== 'pendiente';
        });
        $nombres = array_map(function($c) { return $c['afiliado_nombre']; }, $comisionesNoPendientes);
        jsonResponse(['success' => false, 'error' => 'Hay comisiones que no están pendientes: ' . implode(', ', $nombres)], 400);
        exit;
    }

    // Actualizar todas las comisiones a pagadas
    $stmt = $db->prepare("
        UPDATE comisiones 
        SET estado = 'pagada', fecha_pago = ? 
        WHERE id IN ($placeholders)
    ");
    $params = array_merge([$fechaPago], $comisionIds);
    $stmt->execute($params);

    // Registrar en tabla de pagos (si existe)
    // Primero verificamos si la tabla existe
    $stmt = $db->query("SHOW TABLES LIKE 'pagos_comisiones'");
    if ($stmt->rowCount() > 0) {
        // La tabla existe, registrar el pago
        $totalMonto = array_sum(array_column($comisiones, 'monto'));
        $stmt = $db->prepare("
            INSERT INTO pagos_comisiones 
            (admin_id, fecha_pago, metodo_pago, referencia, observaciones, monto_total, cantidad_comisiones, comision_ids)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $fechaPago,
            $metodoPago,
            $referencia,
            $observaciones,
            $totalMonto,
            count($comisionIds),
            json_encode($comisionIds)
        ]);
    }

    // Enviar notificaciones por email a los afiliados (opcional)
    // Agrupar comisiones por afiliado
    $comisionesPorAfiliado = [];
    foreach ($comisiones as $comision) {
        $codigo = $comision['codigo_afiliado'];
        if (!isset($comisionesPorAfiliado[$codigo])) {
            $comisionesPorAfiliado[$codigo] = [
                'nombre' => $comision['afiliado_nombre'],
                'codigo' => $codigo,
                'comisiones' => [],
                'total' => 0
            ];
        }
        $comisionesPorAfiliado[$codigo]['comisiones'][] = $comision;
        $comisionesPorAfiliado[$codigo]['total'] += (float)$comision['monto'];
    }

    $db->commit();

    // Log del pago para auditoría
    error_log("Pago masivo procesado por admin {$_SESSION['user_id']}: " . count($comisionIds) . " comisiones, total: $" . array_sum(array_column($comisiones, 'monto')));

    $resumen = [
        'comisiones_procesadas' => count($comisionIds),
        'monto_total' => array_sum(array_column($comisiones, 'monto')),
        'afiliados_beneficiados' => count($comisionesPorAfiliado),
        'fecha_pago' => $fechaPago,
        'metodo_pago' => $metodoPago,
        'referencia' => $referencia
    ];

    jsonResponse([
        'success' => true,
        'message' => 'Pagos procesados correctamente',
        'resumen' => $resumen,
        'afiliados' => array_values($comisionesPorAfiliado)
    ]);

} catch (Exception $e) {
    $db->rollBack();
    error_log('Error procesando pago masivo: ' . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Error interno del servidor'], 500);
}
?>
