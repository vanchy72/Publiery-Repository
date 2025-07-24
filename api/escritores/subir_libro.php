<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (!isAuthenticated()) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$user = getCurrentUser();
if (!$user || ($user['rol'] !== 'escritor' && $user['rol'] !== 'admin')) {
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

// Los escritores pueden subir libros sin estar activados
// La activación se hace automáticamente al subir el primer libro

try {
    // Log de inicio
    error_log("Subir libro - Iniciando proceso");
    error_log("Subir libro - Usuario ID: " . $user['id']);
    error_log("Subir libro - Datos recibidos: " . print_r($_POST, true));
    error_log("Subir libro - Archivos recibidos: " . print_r($_FILES, true));
    
    $conn = getDBConnection();
    
    // Obtener datos del formulario
    $autor_id = $user['id']; // Usar el ID del usuario autenticado
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $precio = floatval($_POST['precio']);
    $precio_afiliado = $precio * 0.7; // 30% de descuento para afiliados
    $comision_porcentaje = 30; // 30% de comisión
    
    // Validaciones
    if (empty($titulo) || empty($descripcion)) {
        echo json_encode(['success' => false, 'error' => 'Título y descripción son obligatorios']);
        exit;
    }
    
    if ($precio <= 0) {
        echo json_encode(['success' => false, 'error' => 'El precio debe ser mayor a 0']);
        exit;
    }
    
    // Procesar archivo PDF
    $archivo_pdf = null;
    if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['archivo_pdf'];
        
        // Validar tipo de archivo
        $allowed_types = ['application/pdf'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            echo json_encode(['success' => false, 'error' => 'Solo se permiten archivos PDF']);
            exit;
        }
        
        // Validar tamaño (10MB máximo)
        if ($file['size'] > 10 * 1024 * 1024) {
            echo json_encode(['success' => false, 'error' => 'El archivo no puede superar 10MB']);
            exit;
        }
        
        // Generar nombre único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $nombre_archivo = uniqid() . '_' . time() . '.' . $extension;
        $ruta_destino = '../../uploads/libros/' . $nombre_archivo;
        
        // Crear directorio si no existe
        if (!is_dir('../../uploads/libros/')) {
            mkdir('../../uploads/libros/', 0777, true);
        }
        
        // Mover archivo
        if (move_uploaded_file($file['tmp_name'], $ruta_destino)) {
            $archivo_pdf = $nombre_archivo;
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al subir el archivo PDF']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Archivo PDF es obligatorio']);
        exit;
    }
    
    // Procesar portada (opcional)
    $imagen_portada = null;
    if (isset($_FILES['portada']) && $_FILES['portada']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['portada'];
        
        // Validar tipo de archivo
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            echo json_encode(['success' => false, 'error' => 'Solo se permiten imágenes JPG o PNG']);
            exit;
        }
        
        // Validar tamaño (2MB máximo)
        if ($file['size'] > 2 * 1024 * 1024) {
            echo json_encode(['success' => false, 'error' => 'La portada no puede superar 2MB']);
            exit;
        }
        
        // Generar nombre único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $nombre_portada = 'portada_' . uniqid() . '_' . time() . '.' . $extension;
        $ruta_portada = '../../uploads/portadas/' . $nombre_portada;
        
        // Crear directorio si no existe
        if (!is_dir('../../uploads/portadas/')) {
            mkdir('../../uploads/portadas/', 0777, true);
        }
        
        // Mover archivo
        if (move_uploaded_file($file['tmp_name'], $ruta_portada)) {
            $imagen_portada = $nombre_portada;
        }
    }
    
    // Insertar libro en la base de datos
    $query = "
        INSERT INTO libros (
            autor_id, titulo, descripcion, precio, precio_afiliado, comision_porcentaje,
            imagen_portada, archivo_original, estado, fecha_registro
        ) VALUES (
            :autor_id, :titulo, :descripcion, :precio, :precio_afiliado, :comision_porcentaje,
            :imagen_portada, :archivo_pdf, 'pendiente_revision', NOW()
        )
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        'autor_id' => $autor_id,
        'titulo' => $titulo,
        'descripcion' => $descripcion,
        'precio' => $precio,
        'precio_afiliado' => $precio_afiliado,
        'comision_porcentaje' => $comision_porcentaje,
        'imagen_portada' => $imagen_portada,
        'archivo_pdf' => $archivo_pdf
    ]);
    
    $libro_id = $conn->lastInsertId();
    
    error_log("Subir libro - Libro insertado exitosamente. ID: $libro_id");
    
    // Activar automáticamente al escritor si no está activado
    if ($user['estado'] !== 'activo') {
        $stmt = $conn->prepare("UPDATE usuarios SET estado = 'activo' WHERE id = ?");
        $stmt->execute([$autor_id]);
        
        if ($stmt->rowCount() > 0) {
            error_log("Subir libro - Escritor activado automáticamente. ID: $autor_id");
            
            // Actualizar la sesión
            $_SESSION['user_role'] = 'escritor';
            $_SESSION['user_id'] = $autor_id;
        }
    }
    
    // Registrar actividad si la función existe
    if (function_exists('logActivity')) {
        logActivity($autor_id, 'libro_subido', "Libro subido: $titulo (ID: $libro_id)");
    }
    
    error_log("Subir libro - Proceso completado exitosamente");
    
    $mensaje = 'Libro subido exitosamente. Será revisado por nuestro equipo.';
    
    // Agregar mensaje de activación si corresponde
    if ($user['estado'] !== 'activo') {
        $mensaje .= ' ¡Tu cuenta ha sido activada automáticamente!';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $mensaje,
        'libro_id' => $libro_id,
        'cuenta_activada' => ($user['estado'] !== 'activo')
    ]);
    
} catch (PDOException $e) {
    error_log("Subir libro - Error de base de datos: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Subir libro - Error general: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
?> 