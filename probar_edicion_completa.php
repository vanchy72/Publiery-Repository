<?php
require_once 'config/database.php';

try {
    $conn = getDBConnection();
    
    echo "🧪 PROBANDO EDICIÓN COMPLETA DE USUARIO\n";
    echo "======================================\n\n";
    
    // Buscar un usuario para probar
    $stmt = $conn->query("SELECT id, nombre, email, documento, rol, estado FROM usuarios WHERE rol != 'admin' LIMIT 1");
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        echo "❌ No se encontró usuario de prueba\n";
        exit;
    }
    
    echo "👤 USUARIO ANTES DE LA EDICIÓN:\n";
    echo "-------------------------------\n";
    foreach ($usuario as $campo => $valor) {
        echo sprintf("%-12s: %s\n", $campo, $valor);
    }
    
    echo "\n🔧 SIMULANDO EDICIÓN COMPLETA...\n";
    echo "--------------------------------\n";
    
    // Datos de prueba para actualización completa
    $datos_nuevos = [
        'id' => $usuario['id'],
        'nombre' => $usuario['nombre'] . ' (Editado)',
        'email' => 'editado.' . time() . '@example.com',
        'documento' => 'EDIT-' . rand(100000, 999999),
        'rol' => $usuario['rol'] === 'lector' ? 'afiliado' : 'lector',
        'estado' => $usuario['estado'] === 'activo' ? 'pendiente' : 'activo'
    ];
    
    echo "📤 DATOS A ENVIAR:\n";
    foreach ($datos_nuevos as $campo => $valor) {
        echo sprintf("%-12s: %s\n", $campo, $valor);
    }
    
    // Simular petición PUT como lo hace el modal
    echo "\n🔄 EJECUTANDO UPDATE...\n";
    
    // Contar campos (excluyendo id)
    $input_fields = array_filter($datos_nuevos, function($value, $key) {
        return $key !== 'id' && $value !== null && $value !== '';
    }, ARRAY_FILTER_USE_BOTH);
    
    echo "📊 Campos detectados: " . count($input_fields) . "\n";
    echo "🔍 Contiene 'nombre': " . (isset($datos_nuevos['nombre']) ? 'SÍ' : 'NO') . "\n";
    
    // Lógica que debería ejecutar la API
    if (count($input_fields) === 1 && isset($datos_nuevos['estado']) && !isset($datos_nuevos['nombre'])) {
        echo "🎯 RUTA: Solo actualizar estado\n";
    } else {
        echo "🎯 RUTA: Actualización completa\n";
        
        // Simular la actualización
        $updates = [];
        $params = [];
        $allowed_fields = ['nombre', 'email', 'documento', 'rol', 'estado'];
        
        foreach ($allowed_fields as $field) {
            if (isset($datos_nuevos[$field]) && $datos_nuevos[$field] !== '') {
                $updates[] = "$field = ?";
                $params[] = trim($datos_nuevos[$field]);
            }
        }
        
        if (!empty($updates)) {
            $params[] = $usuario['id'];
            $sql = "UPDATE usuarios SET " . implode(', ', $updates) . " WHERE id = ?";
            
            echo "📝 SQL: $sql\n";
            echo "📋 Parámetros: " . implode(', ', $params) . "\n";
            
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                echo "✅ UPDATE ejecutado correctamente\n";
                
                // Verificar los cambios
                $stmt = $conn->prepare("SELECT id, nombre, email, documento, rol, estado FROM usuarios WHERE id = ?");
                $stmt->execute([$usuario['id']]);
                $usuario_actualizado = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo "\n👤 USUARIO DESPUÉS DE LA EDICIÓN:\n";
                echo "---------------------------------\n";
                foreach ($usuario_actualizado as $campo => $valor) {
                    $cambio = $valor !== $usuario[$campo] ? ' 🔄 CAMBIÓ' : '';
                    echo sprintf("%-12s: %s%s\n", $campo, $valor, $cambio);
                }
            } else {
                echo "❌ Error en UPDATE\n";
            }
        }
    }
    
    echo "\n💡 AHORA PUEDES PROBAR EN EL PANEL:\n";
    echo "1. Ve al panel de administración\n";
    echo "2. Busca el usuario ID {$usuario['id']}\n";
    echo "3. Haz clic en 'Editar'\n";
    echo "4. Modifica varios campos (nombre, email, documento, rol, estado)\n";
    echo "5. Haz clic en 'Guardar Cambios'\n";
    echo "6. Deberían actualizarse TODOS los campos, no solo el estado\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>