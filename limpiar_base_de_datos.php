<?php
/**
 * SCRIPT PARA LIMPIAR LA BASE DE DATOS
 * Propósito: Eliminar todos los datos de prueba (ventas, comisiones, usuarios, etc.)
 * excepto el usuario administrador.
 */

require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><title>Limpieza de Base de Datos</title>";
echo "<style>body { font-family: monospace; margin: 20px; } .success { color: green; } .error { color: red; }</style>";
echo "</head><body>";
echo "<h1>Iniciando limpieza de la base de datos...</h1>";

try {
    $pdo = getDBConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Desactivar temporalmente la comprobación de claves foráneas para evitar errores de orden
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0;');
    echo "<p>Se desactivó la comprobación de claves foráneas.</p>";

    // Obtener una lista de todas las tablas existentes en la base de datos
    $stmt = $pdo->query("SHOW TABLES");
    $tablas_existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Lista de tablas a vaciar
    $tablas_a_vaciar = [
        'campana_envios',
        'notificaciones',
        'notificaciones_escritores',
        'correcciones_libros',
        'descargas',
        'comisiones',
        'pagos',
        'ventas',
        'testimonios',
        'libros',
        'afiliados',
        'escritores',
        'lectores',
        'campanas',
        'activaciones',
        'cambios_contrasena',
        'config_comisiones',
        'email_logs',
        'log_actividad'
    ];

    echo "<h2>Vaciando tablas...</h2>";
    foreach ($tablas_a_vaciar as $tabla) {
        // Comprobar si la tabla existe en la lista de tablas de la BD
        if (in_array($tabla, $tablas_existentes)) {
            try {
                $pdo->exec("TRUNCATE TABLE `{$tabla}`");
                echo "<p class='success'>- Tabla `{$tabla}` vaciada exitosamente.</p>";
            } catch (PDOException $e) {
                echo "<p class='error'>- Error al vaciar la tabla `{$tabla}`: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p>- Tabla `{$tabla}` no encontrada, se omite.</p>";
        }
    }

    // Eliminar usuarios que no son administradores
    echo "<h2>Limpiando tabla de usuarios...</h2>";
    $stmt = $pdo->prepare("DELETE FROM `usuarios` WHERE `rol` != 'admin'");
    $stmt->execute(); // Ejecutar la sentencia DELETE
    $num_usuarios_eliminados = $stmt->rowCount(); // Obtener el número de filas afectadas después de la ejecución
    
    if ($num_usuarios_eliminados > 0) {
        echo "<p class='success'>- Se eliminaron {$num_usuarios_eliminados} usuarios (no administradores).</p>";
    } else {
        echo "<p>- No se encontraron usuarios no administradores para eliminar.</p>";
    }

    // Reactivar la comprobación de claves foráneas
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
    echo "<p>Se reactivó la comprobación de claves foráneas.</p>";

    echo "<h2 class='success'>¡Limpieza completada exitosamente!</h2>";
    echo "<p>La base de datos está lista para producción.</p>";

} catch (Exception $e) {
    echo "<h2 class='error'>Ocurrió un error durante la limpieza:</h2>";
    echo "<p class='error'>" . $e->getMessage() . "</p>";
    // Asegurarse de reactivar las claves foráneas en caso de error
    if (isset($pdo)) {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
        echo "<p>Se intentó reactivar la comprobación de claves foráneas.</p>";
    }
}

echo "</body></html>";
?>
