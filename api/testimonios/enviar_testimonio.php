<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

try {
    // Verificar si el usuario está autenticado
    session_start();
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Usuario no autenticado');
    }
    
    // Obtener información del usuario
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, nombre, email, rol FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        throw new Exception('Usuario no encontrado');
    }
    
    // Recibir datos del formulario
    $testimonio = trim($_POST['testimonio'] ?? '');
    $imagen = $_FILES['imagen'] ?? null;
    
    // Validar testimonio
    if (empty($testimonio)) {
        throw new Exception('El testimonio es requerido');
    }
    
    if (strlen($testimonio) < 50) {
        throw new Exception('El testimonio debe tener al menos 50 caracteres');
    }
    
    if (strlen($testimonio) > 500) {
        throw new Exception('El testimonio no puede exceder 500 caracteres');
    }
    
    // Procesar imagen si se subió
    $imagenPath = null;
    if ($imagen && $imagen['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($imagen['type'], $allowedTypes)) {
            throw new Exception('Solo se permiten imágenes JPG y PNG');
        }
        
        if ($imagen['size'] > $maxSize) {
            throw new Exception('La imagen no puede exceder 2MB');
        }
        
        // Crear directorio si no existe
        $uploadDir = '../../uploads/testimonios/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generar nombre único para la imagen
        $extension = pathinfo($imagen['name'], PATHINFO_EXTENSION);
        $imagenName = 'testimonio_' . $usuario['id'] . '_' . time() . '.' . $extension;
        $imagenPath = 'uploads/testimonios/' . $imagenName;
        
        // Mover imagen
        if (!move_uploaded_file($imagen['tmp_name'], $uploadDir . $imagenName)) {
            throw new Exception('Error al subir la imagen');
        }
    }
    
    // Cargar testimonios existentes
    $jsonFile = __DIR__ . '/testimonios.json';
    if (!file_exists($jsonFile)) {
        // Crear archivo si no existe
        $testimoniosData = ['testimonios' => []];
    } else {
        $jsonContent = file_get_contents($jsonFile);
        $testimoniosData = json_decode($jsonContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error al leer testimonios existentes');
        }
    }
    
    // Generar nuevo ID
    $nuevoId = 1;
    if (!empty($testimoniosData['testimonios'])) {
        $nuevoId = max(array_column($testimoniosData['testimonios'], 'id')) + 1;
    }
    
    // Crear nuevo testimonio
    $nuevoTestimonio = [
        'id' => $nuevoId,
        'nombre' => $usuario['nombre'],
        'rol' => ucfirst($usuario['rol']), // Primera letra mayúscula
        'texto' => $testimonio,
        'imagen' => $imagenPath,
        'activo' => false, // Pendiente de aprobación
        'fecha_creacion' => date('Y-m-d H:i:s'),
        'usuario_id' => $usuario['id']
    ];
    
    // Agregar a la lista
    $testimoniosData['testimonios'][] = $nuevoTestimonio;
    
    // Guardar en archivo JSON
    $resultado = file_put_contents($jsonFile, json_encode($testimoniosData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    if ($resultado === false) {
        throw new Exception('Error al guardar el testimonio');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Testimonio enviado correctamente. Será revisado por el administrador antes de ser publicado.',
        'testimonio' => [
            'id' => $nuevoId,
            'nombre' => $usuario['nombre'],
            'rol' => ucfirst($usuario['rol'])
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?> 