<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/database.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    // Obtener datos del JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos JSON inválidos');
    }
    
    // Validar campos requeridos
    $required_fields = ['libro_id', 'descripcion', 'categoria'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            throw new Exception("Campo requerido faltante: $field");
        }
    }
    
    $libro_id = intval($input['libro_id']);
    $descripcion = trim($input['descripcion']);
    $categoria = trim($input['categoria']);
    
    // Validaciones adicionales
    if (strlen($descripcion) < 10) {
        throw new Exception('La descripción debe tener al menos 10 caracteres');
    }
    
    // Conectar a la base de datos
    $pdo = getDBConnection();
    
    // Verificar que el libro existe y pertenece al escritor
    $stmt = $pdo->prepare("
        SELECT l.id, l.autor_id, e.usuario_id 
        FROM libros l 
        JOIN escritores e ON l.autor_id = e.usuario_id 
        WHERE l.id = ? AND e.usuario_id = ?
    ");
    
    // Obtener el usuario_id de la sesión
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Sesión no válida');
    }
    
    $usuario_id = $_SESSION['user_id'];
    $stmt->execute([$libro_id, $usuario_id]);
    $libro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$libro) {
        throw new Exception('Libro no encontrado o no tienes permisos para editarlo');
    }
    
    // Actualizar el libro (solo categoría y descripción)
    $stmt = $pdo->prepare("
        UPDATE libros 
        SET descripcion = ?, categoria = ?
        WHERE id = ?
    ");
    
    $result = $stmt->execute([$descripcion, $categoria, $libro_id]);
    
    if (!$result) {
        throw new Exception('Error al actualizar el libro');
    }
    
    // Verificar si se actualizó alguna fila
    if ($stmt->rowCount() === 0) {
        throw new Exception('No se pudo actualizar el libro');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Libro actualizado correctamente',
        'libro_id' => $libro_id
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?> 