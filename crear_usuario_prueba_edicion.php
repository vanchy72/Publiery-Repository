<?php
require_once 'config/database.php';

try {
    $conn = getDBConnection();
    
    echo "🧪 CREANDO USUARIO DE PRUEBA PARA EDICIÓN\n";
    echo "========================================\n\n";
    
    // Crear un usuario de prueba para editar
    $nombre = 'Usuario Para Editar';
    $email = 'editar.test.' . time() . '@example.com';
    $documento = 'EDIT-' . rand(100000, 999999);
    $password = password_hash('123456', PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, documento, rol, password, estado, fecha_registro) VALUES (?, ?, ?, 'lector', ?, 'activo', NOW())");
    $stmt->execute([$nombre, $email, $documento, $password]);
    
    $usuario_id = $conn->lastInsertId();
    echo "✅ Usuario creado con ID: $usuario_id\n";
    echo "   - Nombre: $nombre\n";
    echo "   - Email: $email\n";
    echo "   - Documento: $documento\n";
    echo "   - Rol: lector\n";
    echo "   - Estado: activo\n\n";
    
    echo "🧪 AHORA PUEDES PROBAR LA EDICIÓN CON MODAL:\n";
    echo "===========================================\n";
    echo "1. Ve a tu panel de administración\n";
    echo "2. En la pestaña 'Usuarios', busca '$nombre'\n";
    echo "3. Haz clic en el botón azul 'Editar' (✏️)\n";
    echo "4. Se abrirá un modal con todos los campos del usuario\n";
    echo "5. Modifica los campos que desees:\n";
    echo "   - Cambiar nombre\n";
    echo "   - Cambiar email\n";
    echo "   - Cambiar documento\n";
    echo "   - Cambiar rol (ej: de 'lector' a 'afiliado')\n";
    echo "   - Cambiar estado\n";
    echo "   - Cambiar contraseña (opcional)\n";
    echo "6. Haz clic en 'Guardar Cambios'\n";
    echo "7. Los cambios deberían aplicarse inmediatamente\n\n";
    
    echo "✅ FUNCIONALIDADES DEL NUEVO MODAL DE EDICIÓN:\n";
    echo "- ✅ Formulario completo con todos los campos\n";
    echo "- ✅ Campos pre-rellenados con datos actuales\n";
    echo "- ✅ Validaciones de email y documento únicos\n";
    echo "- ✅ Contraseña opcional (solo cambia si se llena)\n";
    echo "- ✅ Interfaz moderna y fácil de usar\n";
    echo "- ✅ Validaciones en tiempo real\n\n";
    
    echo "⚠️ DIFERENCIAS CON LA FUNCIONALIDAD ANTERIOR:\n";
    echo "- ANTES: Solo permitía cambiar el estado con prompt\n";
    echo "- AHORA: Modal completo para editar todos los campos\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>