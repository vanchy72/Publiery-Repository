<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Limpiar cualquier salida previa
if (ob_get_level()) {
    ob_clean();
}
require_once __DIR__ . '/../../config/database.php';

// Verificar autenticación de admin (permitir localhost para testing)
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost']);
$isAuthenticated = isset($_SESSION['email']) && $_SESSION['rol'] === 'admin';

if (!$isAuthenticated && !$isLocalhost) {
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado']);
    exit;
}

$libroId = $_POST['libro_id'] ?? '';
$comentarios = $_POST['comentarios'] ?? '';

if (empty($libroId)) {
    echo json_encode(['success' => false, 'error' => 'ID de libro requerido']);
    exit;
}

try {
    $pdo = getDBConnection();

    // Verificar que el libro existe
    $stmt = $pdo->prepare("SELECT titulo FROM libros WHERE id = ?");
    $stmt->execute([$libroId]);
    $libro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$libro) {
        throw new Exception('Libro no encontrado');
    }

    $uploadDir = '../../uploads/correcciones/';
    $maxFileSize = 50 * 1024 * 1024; // 50MB
    $allowedTypes = ['doc', 'docx', 'pdf', 'txt'];

    // Crear directorio si no existe
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $response = ['success' => false, 'message' => '', 'file' => null];

    if (isset($_FILES['archivo_correcciones']) && $_FILES['archivo_correcciones']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['archivo_correcciones'];

        // Validar tamaño
        if ($file['size'] > $maxFileSize) {
            throw new Exception('El archivo es demasiado grande. Máximo 50MB permitido.');
        }

        // Validar tipo
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExt, $allowedTypes)) {
            throw new Exception('Tipo de archivo no permitido. Solo: ' . implode(', ', $allowedTypes));
        }

        // Generar nombre único
        $version = time();
        $newFileName = "correcciones_libro_{$libroId}_v{$version}.{$fileExt}";
        $filePath = $uploadDir . $newFileName;

        // Mover archivo
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Registrar en base de datos
            $stmt = $pdo->prepare("
                INSERT INTO correcciones_libros (
                    libro_id,
                    archivo_original,
                    archivo_correccion,
                    comentarios,
                    admin_id,
                    fecha_subida
                ) VALUES (?, ?, ?, ?, ?, NOW())
            ");

            $adminId = $_SESSION['user_id'] ?? 1;
            $stmt->execute([
                $libroId,
                $libro['titulo'],
                $newFileName,
                $comentarios,
                $adminId
            ]);

            $response = [
                'success' => true,
                'message' => 'Correcciones subidas exitosamente',
                'file' => [
                    'name' => $newFileName,
                    'size' => $file['size'],
                    'type' => $fileExt,
                    'path' => $filePath,
                    'url' => 'uploads/correcciones/' . $newFileName
                ]
            ];

            // Notificar al autor (opcional)
            notificarCorreccionesAutor($libroId, $comentarios, $pdo);

        } else {
            throw new Exception('Error al subir el archivo');
        }
    } else {
        $error = $_FILES['archivo_correcciones']['error'] ?? 'Archivo no recibido';
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el límite del servidor',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el límite del formulario',
            UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
            UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta directorio temporal',
            UPLOAD_ERR_CANT_WRITE => 'Error al escribir archivo',
            UPLOAD_ERR_EXTENSION => 'Extensión de archivo no permitida'
        ];

        $message = $errorMessages[$error] ?? 'Error desconocido en la subida';
        throw new Exception($message);
    }

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function notificarCorreccionesAutor($libroId, $comentarios, $pdo) {
    try {
        // Obtener información del libro y autor
        $stmt = $pdo->prepare("
            SELECT l.titulo, u.id as autor_id, u.email as autor_email
            FROM libros l
            LEFT JOIN usuarios u ON l.autor_id = u.id
            WHERE l.id = ?
        ");
        $stmt->execute([$libroId]);
        $info = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($info) {
            // Insertar notificación
            $mensaje = "Se han subido correcciones para tu libro '{$info['titulo']}'. ";
            if (!empty($comentarios)) {
                $mensaje .= "Comentarios: {$comentarios}";
            }

            $notifStmt = $pdo->prepare("
                INSERT INTO notificaciones (
                    usuario_id,
                    tipo,
                    titulo,
                    mensaje,
                    fecha_creacion
                ) VALUES (?, ?, ?, ?, NOW())
            ");

            $notifStmt->execute([
                $info['autor_id'],
                'correcciones_editorial',
                'Correcciones disponibles',
                $mensaje
            ]);
        }
    } catch (Exception $e) {
        // No fallar si la notificación falla
        error_log("Error notificando correcciones: " . $e->getMessage());
    }
}
?>
