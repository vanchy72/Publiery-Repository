<?php
require_once 'config/database.php';

try {
    $conn = getDBConnection();
    
    echo "🧪 AGREGANDO DATOS COMPLETOS A USUARIO DE PRUEBA\n";
    echo "===============================================\n\n";
    
    // Buscar un usuario existente para actualizar
    $stmt = $conn->query("SELECT id, nombre, email FROM usuarios WHERE rol != 'admin' LIMIT 1");
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        echo "❌ No se encontró ningún usuario para actualizar\n";
        exit;
    }
    
    echo "👤 Usuario seleccionado:\n";
    echo "   - ID: {$usuario['id']}\n";
    echo "   - Nombre: {$usuario['nombre']}\n";
    echo "   - Email: {$usuario['email']}\n\n";
    
    // Datos de prueba completos
    $biografia = "Soy un usuario activo de la plataforma Publiery. Me apasiona la lectura y la escritura, especialmente los géneros de ficción y autoayuda. Tengo experiencia en marketing digital y disfruto compartiendo contenido valioso con la comunidad.";
    $cuenta_payu = "payu_" . $usuario['id'] . "_" . date('Y');
    $fecha_ultimo_login = date('Y-m-d H:i:s', strtotime('-2 hours')); // Hace 2 horas
    
    // Actualizar el usuario con datos completos
    $stmt = $conn->prepare("UPDATE usuarios SET 
        biografia = ?, 
        cuenta_payu = ?, 
        fecha_ultimo_login = ?
        WHERE id = ?");
    
    $stmt->execute([$biografia, $cuenta_payu, $fecha_ultimo_login, $usuario['id']]);
    
    echo "✅ DATOS ACTUALIZADOS:\n";
    echo "---------------------\n";
    echo "📝 Biografía: Agregada (texto de muestra)\n";
    echo "💳 Cuenta PayU: $cuenta_payu\n";
    echo "🕒 Último Login: $fecha_ultimo_login\n\n";
    
    echo "🧪 AHORA PUEDES PROBAR EL MODAL VER MEJORADO:\n";
    echo "============================================\n";
    echo "1. Ve a tu panel de administración\n";
    echo "2. En la pestaña 'Usuarios', busca '{$usuario['nombre']}'\n";
    echo "3. Haz clic en el botón verde 'Ver' (👁️)\n";
    echo "4. Verás el nuevo modal completo con:\n";
    echo "   ✅ Foto de perfil (placeholder si no tiene)\n";
    echo "   ✅ Información personal completa\n";
    echo "   ✅ Estado del sistema con badges coloridos\n";
    echo "   ✅ Actividad (registro y último login)\n";
    echo "   ✅ Configuración de pagos PayU\n";
    echo "   ✅ Tokens de seguridad (si los tiene)\n";
    echo "   ✅ Biografía en formato expandible\n\n";
    
    echo "✨ CARACTERÍSTICAS DEL NUEVO MODAL:\n";
    echo "----------------------------------\n";
    echo "- 🎨 Diseño moderno con secciones organizadas\n";
    echo "- 🏷️ Badges coloridos para roles y estados\n";
    echo "- 📧 Email clickeable para contactar\n";
    echo "- 💾 Campos preparados para cuenta PayU\n";
    echo "- 📅 Fechas formateadas correctamente\n";
    echo "- 🖼️ Soporte para foto de perfil\n";
    echo "- 📝 Biografía con scroll si es muy larga\n";
    echo "- ⚠️ Indicadores claros para datos faltantes\n\n";
    
    echo "🔍 DATOS PREPARADOS PARA PAYU:\n";
    echo "-----------------------------\n";
    echo "El modal ya está listo para mostrar:\n";
    echo "- ✅ Cuenta PayU configurada\n";
    echo "- ⚠️ Cuenta PayU pendiente de configuración\n";
    echo "- 🔧 Estado de configuración visual\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>