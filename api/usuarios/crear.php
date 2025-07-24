<?php
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json; charset=utf-8');

function asignarAfiliadoDerrame($conn, $usuario_id) {
    // Buscar patrocinador con menos frontales (nivel 1) y asignar
    $patrocinador = $conn->query("SELECT id, usuario_id, frontal FROM afiliados WHERE frontal < 3 ORDER BY nivel ASC, frontal ASC, id ASC LIMIT 1")->fetch();
    if ($patrocinador) {
        $nivel = $patrocinador['nivel'] + 1;
        $frontal = 0;
        $patrocinador_id = $patrocinador['id'];
        // Actualizar frontal del patrocinador
        $conn->prepare("UPDATE afiliados SET frontal = frontal + 1 WHERE id = ?")->execute([$patrocinador_id]);
    } else {
        // Si no hay patrocinador, es el primer afiliado
        $nivel = 1;
        $frontal = 0;
        $patrocinador_id = null;
    }
    // Crear afiliado
    $codigo = 'AF' . str_pad($usuario_id, 6, '0', STR_PAD_LEFT);
    $conn->prepare("INSERT INTO afiliados (usuario_id, codigo_afiliado, patrocinador_id, nivel, frontal, fecha_activacion, comision_total, ventas_totales, nombre, email, estado_usuario, fecha_registro) VALUES (?, ?, ?, ?, ?, NOW(), 0, 0, '', '', 'activo', NOW())")
        ->execute([$usuario_id, $codigo, $patrocinador_id, $nivel, $frontal]);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) throw new Exception('Datos no recibidos');
    $nombre = trim($input['nombre'] ?? '');
    $email = trim($input['email'] ?? '');
    $rol = trim($input['rol'] ?? 'afiliado');
    $password = $input['password'] ?? '';
    $estado = trim($input['estado'] ?? 'pendiente');
    if (!$nombre || !$email || !$rol || !$password) throw new Exception('Faltan datos obligatorios');
    $conn = getDBConnection();
    // Verificar email único
    $stmt = $conn->prepare('SELECT id FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) throw new Exception('El email ya está registrado');
    // Crear usuario
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO usuarios (nombre, email, rol, password, estado, fecha_registro) VALUES (?, ?, ?, ?, ?, NOW())');
    $stmt->execute([$nombre, $email, $rol, $hash, $estado]);
    $usuario_id = $conn->lastInsertId();
    // Si es afiliado, asignar en la red
    if ($rol === 'afiliado') {
        asignarAfiliadoDerrame($conn, $usuario_id);
    }
    echo json_encode(['success' => true, 'message' => 'Usuario creado correctamente']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 