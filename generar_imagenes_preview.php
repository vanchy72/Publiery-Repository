<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config/database.php';

function generarImagenLibro($titulo, $id, $ancho = 300, $alto = 400) {
    // Crear imagen
    $imagen = imagecreate($ancho, $alto);
    
    // Colores variados basados en el ID
    $colores_fondo = [
        [64, 81, 181],   // Indigo
        [156, 39, 176],  // Purple  
        [63, 81, 181],   // Deep Purple
        [33, 150, 243],  // Blue
        [0, 150, 136],   // Teal
        [76, 175, 80],   // Green
        [255, 152, 0],   // Orange
        [244, 67, 54],   // Red
        [121, 85, 72],   // Brown
        [96, 125, 139]   // Blue Grey
    ];
    
    $color_index = $id % count($colores_fondo);
    $color_fondo = $colores_fondo[$color_index];
    
    // Crear colores
    $fondo = imagecolorallocate($imagen, $color_fondo[0], $color_fondo[1], $color_fondo[2]);
    $blanco = imagecolorallocate($imagen, 255, 255, 255);
    $gris_claro = imagecolorallocate($imagen, 230, 230, 230);
    
    // Rellenar fondo
    imagefill($imagen, 0, 0, $fondo);
    
    // Crear degradado sutil
    for ($i = 0; $i < 40; $i++) {
        $alpha_color = imagecolorallocatealpha($imagen, 255, 255, 255, 100 + ($i * 2));
        imagefilledrectangle($imagen, 0, $i * 3, $ancho, ($i * 3) + 3, $alpha_color);
    }
    
    // Marco decorativo
    imagerectangle($imagen, 8, 8, $ancho - 9, $alto - 9, $blanco);
    imagerectangle($imagen, 12, 12, $ancho - 13, $alto - 13, $gris_claro);
    
    // Preparar título para múltiples líneas
    $titulo_corto = strlen($titulo) > 40 ? substr($titulo, 0, 37) . '...' : $titulo;
    $lineas = explode(' ', $titulo_corto);
    $lineas_texto = [];
    $linea_actual = '';
    
    foreach ($lineas as $palabra) {
        if (strlen($linea_actual . ' ' . $palabra) > 15) {
            if (!empty($linea_actual)) {
                $lineas_texto[] = $linea_actual;
                $linea_actual = $palabra;
            } else {
                $lineas_texto[] = $palabra;
            }
        } else {
            $linea_actual = empty($linea_actual) ? $palabra : $linea_actual . ' ' . $palabra;
        }
    }
    if (!empty($linea_actual)) {
        $lineas_texto[] = $linea_actual;
    }
    
    // Escribir título
    $y_start = 60;
    foreach ($lineas_texto as $i => $linea) {
        $x = max(20, ($ancho - (strlen($linea) * 10)) / 2);
        imagestring($imagen, 4, $x, $y_start + ($i * 25), $linea, $blanco);
    }
    
    // ID del libro
    imagestring($imagen, 3, 20, 25, "ID: $id", $blanco);
    
    // Marca
    imagestring($imagen, 2, $ancho - 80, $alto - 25, "PUBLIERY", $gris_claro);
    
    // Decoración adicional
    imagefilledrectangle($imagen, 20, $alto - 60, $ancho - 20, $alto - 55, $gris_claro);
    
    return $imagen;
}

try {
    $pdo = getDBConnection();
    
    // Verificar GD
    if (!extension_loaded('gd')) {
        echo json_encode(['success' => false, 'error' => 'Extensión GD no disponible']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'preview':
            // Generar previsualizaciones
            $query = "SELECT id, titulo, imagen_portada FROM libros WHERE estado = 'publicado' ORDER BY id";
            $stmt = $pdo->query($query);
            $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $imagenes_preview = [];
            
            foreach ($libros as $libro) {
                // Generar imagen temporal
                $imagen = generarImagenLibro($libro['titulo'], $libro['id']);
                
                // Crear nombre temporal único
                $temp_name = "temp_preview_" . $libro['id'] . "_" . time() . ".png";
                $temp_path = "images/" . $temp_name;
                
                // Guardar imagen temporal
                if (imagepng($imagen, $temp_path)) {
                    $imagenes_preview[] = [
                        'id' => (int)$libro['id'],
                        'titulo' => $libro['titulo'],
                        'archivo' => $temp_name,
                        'url' => 'images/' . $temp_name,
                        'temporal' => true
                    ];
                }
                
                imagedestroy($imagen);
            }
            
            echo json_encode([
                'success' => true,
                'libros' => $libros,
                'imagenes' => $imagenes_preview
            ]);
            break;
            
        case 'aplicar':
            // Aplicar imágenes seleccionadas a la BD
            $imagenes = $input['imagenes'] ?? [];
            
            if (empty($imagenes)) {
                echo json_encode(['success' => false, 'error' => 'No hay imágenes para aplicar']);
                exit;
            }
            
            $pdo->beginTransaction();
            $update_stmt = $pdo->prepare("UPDATE libros SET imagen_portada = ? WHERE id = ?");
            $actualizados = 0;
            
            foreach ($imagenes as $img) {
                // Crear nombre permanente
                $nombre_permanente = "libro_" . $img['id'] . "_" . time() . ".png";
                $ruta_temporal = $img['url'];
                $ruta_permanente = "images/" . $nombre_permanente;
                
                // Mover de temporal a permanente
                if (file_exists($ruta_temporal)) {
                    if (rename($ruta_temporal, $ruta_permanente)) {
                        // Actualizar BD
                        $update_stmt->execute([$nombre_permanente, $img['id']]);
                        $actualizados++;
                    }
                }
            }
            
            // Limpiar archivos temporales restantes
            $temp_files = glob("images/temp_preview_*.png");
            foreach ($temp_files as $temp_file) {
                if (file_exists($temp_file)) {
                    unlink($temp_file);
                }
            }
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'actualizados' => $actualizados,
                'total' => count($imagenes)
            ]);
            break;
            
        case 'upload':
            // Subir imagen personalizada
            $libro_id = $_POST['libro_id'] ?? 0;
            
            if (!$libro_id || !isset($_FILES['image'])) {
                echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
                exit;
            }
            
            $file = $_FILES['image'];
            
            // Validar archivo
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file['type'], $allowed_types)) {
                echo json_encode(['success' => false, 'error' => 'Tipo de archivo no permitido']);
                exit;
            }
            
            if ($file['size'] > 5 * 1024 * 1024) { // 5MB máximo
                echo json_encode(['success' => false, 'error' => 'Archivo muy grande (máximo 5MB)']);
                exit;
            }
            
            // Generar nombre único
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $nombre_archivo = "libro_custom_" . $libro_id . "_" . time() . "." . $extension;
            $ruta_destino = "images/" . $nombre_archivo;
            
            // Mover archivo
            if (move_uploaded_file($file['tmp_name'], $ruta_destino)) {
                // Actualizar BD
                $stmt = $pdo->prepare("UPDATE libros SET imagen_portada = ? WHERE id = ?");
                $stmt->execute([$nombre_archivo, $libro_id]);
                
                echo json_encode([
                    'success' => true,
                    'archivo' => $nombre_archivo,
                    'libro_id' => $libro_id
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error al subir archivo']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Acción no válida']);
    }
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>