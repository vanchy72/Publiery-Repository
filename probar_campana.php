<?php
session_start();

// Simular sesión de admin para prueba
$_SESSION['user_id'] = 1; // Asumiendo que ID 1 es admin

require_once 'config/database.php';

try {
    $conn = getDBConnection();
    
    echo "🧪 PRUEBA DE CREACIÓN DE CAMPAÑA\n\n";
    
    // Verificar si el usuario 1 es admin
    $stmt = $conn->prepare("SELECT id, nombre, rol FROM usuarios WHERE id = 1");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if (!$admin) {
        echo "❌ No existe usuario con ID 1\n";
        // Buscar cualquier admin
        $stmt = $conn->prepare("SELECT id, nombre, rol FROM usuarios WHERE rol = 'admin' LIMIT 1");
        $stmt->execute();
        $admin = $stmt->fetch();
        
        if ($admin) {
            $_SESSION['user_id'] = $admin['id'];
            echo "✅ Usando admin encontrado: {$admin['nombre']} (ID: {$admin['id']})\n";
        } else {
            echo "❌ No hay administradores en el sistema\n";
            exit;
        }
    } else {
        echo "✅ Usuario admin encontrado: {$admin['nombre']} ({$admin['rol']})\n";
    }
    
    // Simular datos de campaña
    $testData = [
        'nombre' => 'Campaña de Prueba ' . date('Y-m-d H:i:s'),
        'descripcion' => 'Esta es una campaña de prueba para verificar el sistema',
        'tipo' => 'promocion',
        'audiencia_tipo' => 'afiliados',
        'estado' => 'borrador'
    ];
    
    // Crear FormData simulado (como POST)
    $_POST = $testData;
    
    echo "\n📝 Datos de prueba:\n";
    foreach ($testData as $key => $value) {
        echo "- {$key}: {$value}\n";
    }
    
    // Llamar al API directamente
    echo "\n🚀 Llamando al API de creación...\n";
    
    // Capturar la salida del API
    ob_start();
    include 'api/campanas/crear.php';
    $apiOutput = ob_get_clean();
    
    echo "📄 Respuesta del API:\n";
    echo $apiOutput . "\n";
    
    // Verificar si se creó la campaña
    $stmt = $conn->prepare("
        SELECT id, nombre, tipo, estado, fecha_creacion 
        FROM campanas 
        WHERE nombre LIKE 'Campaña de Prueba%' 
        ORDER BY id DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $campanaNueva = $stmt->fetch();
    
    if ($campanaNueva) {
        echo "\n✅ CAMPAÑA CREADA EXITOSAMENTE:\n";
        echo "- ID: {$campanaNueva['id']}\n";
        echo "- Nombre: {$campanaNueva['nombre']}\n";
        echo "- Tipo: {$campanaNueva['tipo']}\n";
        echo "- Estado: {$campanaNueva['estado']}\n";
        echo "- Fecha: {$campanaNueva['fecha_creacion']}\n";
        
        // Probar compartir con red
        echo "\n🌐 Probando compartir con red...\n";
        
        $shareData = json_encode(['campana_id' => $campanaNueva['id']]);
        
        // Simular input para compartir
        $GLOBALS['HTTP_RAW_POST_DATA'] = $shareData;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        ob_start();
        include 'api/campanas/compartir_red.php';
        $shareOutput = ob_get_clean();
        
        echo "📄 Respuesta de compartir:\n";
        echo $shareOutput . "\n";
        
    } else {
        echo "\n❌ No se encontró la campaña creada\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>