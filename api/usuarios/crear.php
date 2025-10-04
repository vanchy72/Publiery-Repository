<?php
// Headers CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN');

// Preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/response.php';

// Verificar que es admin
requireAdmin();

function crearEscritor($conn, $usuario_id) {
    try {
        // Crear registro en tabla escritores
        $stmt = $conn->prepare("INSERT INTO escritores (usuario_id, estado_activacion, estado, fecha_activacion) VALUES (?, 'activo', 'activo', NOW())");
        $stmt->execute([$usuario_id]);
    } catch (Exception $e) {
        error_log("Error creando escritor: " . $e->getMessage());
        throw new Exception("Error al crear registro de escritor: " . $e->getMessage());
    }
}

function asignarAfiliadoDerrame($conn, $usuario_id) {
    try {
        // Buscar patrocinador con menos frontales (nivel 1) y asignar
        $patrocinador = $conn->query("SELECT id, usuario_id, frontal, nivel FROM afiliados WHERE frontal < 3 ORDER BY nivel ASC, frontal ASC, id ASC LIMIT 1")->fetch();
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
        $conn->prepare("INSERT INTO afiliados (usuario_id, codigo_afiliado, patrocinador_id, nivel, frontal, fecha_activacion, comision_total, ventas_totales) VALUES (?, ?, ?, ?, ?, NOW(), 0, 0)")
            ->execute([$usuario_id, $codigo, $patrocinador_id, $nivel, $frontal]);
    } catch (Exception $e) {
        error_log("Error asignando afiliado: " . $e->getMessage());
        throw new Exception("Error al crear registro de afiliado: " . $e->getMessage());
    }
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) throw new Exception('Datos no recibidos');
    $nombre = trim($input['nombre'] ?? '');
    $email = trim($input['email'] ?? '');
    $documento = trim($input['documento'] ?? '');
    $rol = trim($input['rol'] ?? 'afiliado');
    $password = $input['password'] ?? '';
    $estado = trim($input['estado'] ?? 'pendiente');
    
    if (!$nombre || !$email || !$documento || !$rol || !$password) throw new Exception('Faltan datos obligatorios');
    
    $conn = getDBConnection();
    
    // Verificar email único
    $stmt = $conn->prepare('SELECT id FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) throw new Exception('El email ya está registrado');
    
    // Verificar documento único (si no está vacío)
    if (!empty($documento)) {
        $stmt = $conn->prepare('SELECT id FROM usuarios WHERE documento = ?');
        $stmt->execute([$documento]);
        if ($stmt->fetch()) throw new Exception('El documento ya está registrado');
    }
    
    // Crear usuario
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO usuarios (nombre, email, documento, rol, password, estado, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, NOW())');
    $stmt->execute([$nombre, $email, $documento, $rol, $hash, $estado]);
    $usuario_id = $conn->lastInsertId();
    // Si es afiliado, asignar en la red
    if ($rol === 'afiliado') {
        asignarAfiliadoDerrame($conn, $usuario_id);
    }
    
    // Si es escritor, crear registro en tabla escritores
    if ($rol === 'escritor') {
        crearEscritor($conn, $usuario_id);
    }
    
    echo json_encode(['success' => true, 'message' => 'Usuario creado correctamente']);
} catch (Exception $e) {
    error_log("Error en crear usuario: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 