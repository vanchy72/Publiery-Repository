<?php
// Configurar manejo de errores para evitar que se muestren en la respuesta
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Capturar errores fatales
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("Error fatal en register.php: " . print_r($error, true));
        jsonResponse(['error' => 'Error interno del servidor'], 500);
    }
});

/**
 * API de Autenticación - Registro
 * Maneja el registro de nuevos usuarios (afiliado, escritor, lector)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';
require_once '../../config/email.php';

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Método no permitido'], 405);
}

// Envolver todo en try-catch global
try {
    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    // Validar datos de entrada
    $nombre = sanitizeInput($input['nombre'] ?? '');
    $email = trim(strtolower($input['email'] ?? '')); // Limpiar y normalizar email
    $documento = sanitizeInput($input['documento'] ?? '');
    $password = $input['password'] ?? '';
    $rol = sanitizeInput($input['rol'] ?? 'lector');
    $codigoReferido = sanitizeInput($input['codigo_referido'] ?? '');

    // Validaciones básicas
    if (empty($nombre) || empty($email) || empty($documento) || empty($password)) {
        jsonResponse(['error' => 'Todos los campos son requeridos'], 400);
    }
    if (!validateEmail($email)) {
        jsonResponse(['error' => 'Formato de email inválido'], 400);
    }
    if (!validatePassword($password)) {
        jsonResponse(['error' => 'La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número'], 400);
    }
    if (strlen($documento) < 5) {
        jsonResponse(['error' => 'El documento debe tener al menos 5 caracteres'], 400);
    }
    $rolesPermitidos = ['escritor', 'afiliado', 'lector'];
    if (!in_array($rol, $rolesPermitidos)) {
        jsonResponse(['error' => 'Rol inválido'], 400);
    }

    $conn = getDBConnection();

    // Verificar si el email ya existe (con mejor manejo de errores)
    try {
        $stmt = $conn->prepare("SELECT id, email FROM usuarios WHERE LOWER(TRIM(email)) = ?");
        $stmt->execute([$email]);
        $existingUser = $stmt->fetch();
        if ($existingUser) {
            jsonResponse(['error' => 'El email ya está registrado', 'debug' => 'Email encontrado: ' . $existingUser['email']], 409);
        }
    } catch (Exception $e) {
        error_log("Error verificando email: " . $e->getMessage());
        jsonResponse(['error' => 'Error verificando email'], 500);
    }
    
    // Verificar si el documento ya existe
    try {
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE documento = ?");
        $stmt->execute([$documento]);
        if ($stmt->fetch()) {
            jsonResponse(['error' => 'El documento ya está registrado'], 409);
        }
    } catch (Exception $e) {
        error_log("Error verificando documento: " . $e->getMessage());
        jsonResponse(['error' => 'Error verificando documento'], 500);
    }

    // Buscar patrocinador si se proporciona código de referido (solo para afiliados)
    $patrocinadorId = null;
    $nivel = 1;
    if ($rol === 'afiliado') {
        if (!empty($codigoReferido)) {
            // Si se proporciona código, buscar el patrocinador
            $stmt = $conn->prepare("SELECT id, nivel FROM afiliados WHERE codigo_afiliado = ?");
            $stmt->execute([$codigoReferido]);
            $patrocinador = $stmt->fetch();
            if ($patrocinador) {
                $patrocinadorId = $patrocinador['id'];
                $nivel = $patrocinador['nivel'] + 1;
            } else {
                jsonResponse(['error' => 'Código de referido inválido'], 400);
            }
        } else {
            // Lógica de derrame: buscar patrocinador disponible
            // Buscar el afiliado con menos referidos directos (más espacio disponible)
            $stmt = $conn->prepare("
                SELECT a.id, a.nivel, COUNT(b.id) AS referidos_directos
                FROM afiliados a
                LEFT JOIN afiliados b ON b.patrocinador_id = a.id
                GROUP BY a.id, a.nivel
                ORDER BY referidos_directos ASC, a.id ASC
                LIMIT 1
            ");
            $stmt->execute();
            $patrocinador = $stmt->fetch();
            
            if ($patrocinador) {
                // Asignar al afiliado con menos referidos
                $patrocinadorId = $patrocinador['id'];
                $nivel = $patrocinador['nivel'] + 1;
            } else {
                // Si no hay ningún afiliado, será el primer afiliado (frontal)
                $patrocinadorId = null;
                $nivel = 1;
            }
        }
    }

    // Iniciar transacción
    $conn->beginTransaction();
    try {
        // Insertar usuario
        $hashedPassword = hashPassword($password);
        $tokenActivacion = generateSecureToken();
        $fechaExpiracion = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $estado = 'pendiente';
        if ($rol === 'lector') {
            $estado = 'activo'; // Los lectores pueden comprar inmediatamente
        }
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password, documento, rol, estado, token_activacion, fecha_expiracion_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $email, $hashedPassword, $documento, $rol, $estado, $tokenActivacion, $fechaExpiracion]);
        $userId = $conn->lastInsertId();

        // Si es afiliado, crear registro en tabla afiliados
        if ($rol === 'afiliado') {
            $codigoAfiliado = 'AF' . str_pad($userId, 6, '0', STR_PAD_LEFT);
            $stmt = $conn->prepare("INSERT INTO afiliados (usuario_id, codigo_afiliado, patrocinador_id, nivel, frontal) VALUES (?, ?, ?, ?, ?)");
            $frontal = $patrocinadorId ? 0 : 1;
            $stmt->execute([$userId, $codigoAfiliado, $patrocinadorId, $nivel, $frontal]);
        }
        // Si es escritor, crear registro en tabla escritores
        if ($rol === 'escritor') {
            $stmt = $conn->prepare("INSERT INTO escritores (usuario_id, estado) VALUES (?, 'pendiente_revision')");
            $stmt->execute([$userId]);
        }
        // Si es lector, crear registro en tabla lectores
        if ($rol === 'lector') {
            $stmt = $conn->prepare("INSERT INTO lectores (usuario_id, estado) VALUES (?, 'activo')");
            $stmt->execute([$userId]);
        }
        $conn->commit();
        logActivity($userId, 'user_registered', "Usuario registrado con rol: $rol");

        // Enviar email de bienvenida (opcional - no fallar si hay error)
        $emailSent = false;
        try {
            $userDataForEmail = [
                'nombre' => $nombre,
                'email' => $email,
                'rol' => $rol,
                'estado' => $estado
            ];
            
            if ($rol === 'afiliado') {
                $userDataForEmail['codigo_afiliado'] = $codigoAfiliado;
                $userDataForEmail['nivel'] = $nivel;
            }
            
            $emailSent = sendWelcomeEmail($userDataForEmail);
            if ($emailSent) {
                logActivity($userId, 'welcome_email_sent', 'Email de bienvenida enviado');
            }
        } catch (Exception $emailError) {
            error_log("Error enviando email de bienvenida: " . $emailError->getMessage());
            // No fallar el registro si el email falla
            $emailSent = false;
        }

        // Preparar respuesta y redirección según el rol
        $response = [
            'success' => true,
            'message' => 'Registro exitoso.',
            'user' => [
                'id' => $userId,
                'nombre' => $nombre,
                'email' => $email,
                'rol' => $rol,
                'estado' => $estado
            ]
        ];
        // Restaurar la lógica de redirección según rol en el registro
        if ($rol === 'afiliado') {
            $response['redirect'] = 'dashboard-afiliado.html';
            $response['activation_token'] = $tokenActivacion;
            $response['activation_deadline'] = date('Y-m-d H:i:s', strtotime('+3 days'));
        } elseif ($rol === 'escritor') {
            $response['redirect'] = 'dashboard-escritor.html';
            $response['activation_token'] = $tokenActivacion;
        } elseif ($rol === 'lector') {
            $response['redirect'] = 'tienda-lectores.html';
        }
        jsonResponse($response, 201);
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
} catch (Exception $e) {
    error_log("Error en registro: " . $e->getMessage());
    jsonResponse(['error' => 'Error interno del servidor: ' . $e->getMessage()], 500);
}
?> 