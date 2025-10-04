<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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

$test = $_GET['test'] ?? '';

try {
    switch ($test) {
        case 'database':
            echo json_encode(diagnosticarBaseDatos());
            break;
        case 'files':
            echo json_encode(diagnosticarArchivos());
            break;
        case 'security':
            echo json_encode(diagnosticarSeguridad());
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Tipo de test no válido']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function diagnosticarBaseDatos() {
    try {
        $pdo = getDBConnection();
        
        // Test conexión básica
        $stmt = $pdo->query("SELECT 1");
        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'No se pudo conectar a la base de datos',
                'details' => 'Error en consulta de prueba'
            ];
        }
        
        // Verificar tablas principales
        $tablas = ['usuarios', 'afiliados', 'libros', 'ventas', 'comisiones'];
        $tablasExistentes = [];
        $totalRegistros = 0;
        
        foreach ($tablas as $tabla) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM `{$tabla}`");
                if ($stmt) {
                    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    $tablasExistentes[] = "{$tabla} ({$count} registros)";
                    $totalRegistros += $count;
                }
            } catch (Exception $e) {
                $tablasExistentes[] = "{$tabla} (ERROR: {$e->getMessage()})";
            }
        }
        
        // Test de velocidad
        $start = microtime(true);
        $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
        $tiempoRespuesta = round((microtime(true) - $start) * 1000, 2);
        
        // Verificar conexiones activas
        try {
            $stmt = $pdo->query("SHOW STATUS LIKE 'Threads_connected'");
            $conexiones = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC)['Value'] : 'N/A';
        } catch (Exception $e) {
            $conexiones = 'N/A';
        }
        
        return [
            'success' => true,
            'message' => 'Base de datos funcionando correctamente',
            'details' => "Tablas verificadas:\n" . implode("\n", $tablasExistentes) . 
                        "\n\nTotal registros: {$totalRegistros}" .
                        "\nTiempo de respuesta: {$tiempoRespuesta}ms" .
                        "\nConexiones activas: {$conexiones}"
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error en diagnóstico de base de datos',
            'details' => $e->getMessage()
        ];
    }
}

function diagnosticarArchivos() {
    try {
        $directorios = [
            '../../uploads' => 'Uploads',
            '../../config' => 'Config',
            '../../api' => 'API',
            '../../css' => 'CSS',
            '../../js' => 'JavaScript'
        ];
        
        $resultados = [];
        $problemasPermisos = [];
        
        foreach ($directorios as $ruta => $nombre) {
            if (file_exists($ruta)) {
                $permisos = substr(sprintf('%o', fileperms($ruta)), -4);
                $escribible = is_writable($ruta) ? 'SÍ' : 'NO';
                $legible = is_readable($ruta) ? 'SÍ' : 'NO';
                
                $resultados[] = "{$nombre}: Permisos {$permisos}, Lectura: {$legible}, Escritura: {$escribible}";
                
                if (!is_readable($ruta)) {
                    $problemasPermisos[] = "{$nombre}: Sin permisos de lectura";
                }
                
                if ($nombre === 'Uploads' && !is_writable($ruta)) {
                    $problemasPermisos[] = "{$nombre}: Sin permisos de escritura (requerido)";
                }
            } else {
                $resultados[] = "{$nombre}: DIRECTORIO NO EXISTE";
                $problemasPermisos[] = "{$nombre}: Directorio faltante";
            }
        }
        
        // Verificar espacio en disco
        $espacioLibre = disk_free_space('.');
        $espacioTotal = disk_total_space('.');
        $porcentajeUso = round((($espacioTotal - $espacioLibre) / $espacioTotal) * 100, 2);
        
        $espacioInfo = "Espacio en disco:\n" .
                       "Total: " . formatBytes($espacioTotal) . "\n" .
                       "Libre: " . formatBytes($espacioLibre) . "\n" .
                       "Uso: {$porcentajeUso}%";
        
        $success = empty($problemasPermisos) && $porcentajeUso < 90;
        
        return [
            'success' => $success,
            'message' => $success ? 'Sistema de archivos en buen estado' : 'Problemas detectados en archivos',
            'details' => implode("\n", $resultados) . "\n\n" . $espacioInfo . 
                        (empty($problemasPermisos) ? '' : "\n\nProblemas:\n" . implode("\n", $problemasPermisos))
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error verificando sistema de archivos',
            'details' => $e->getMessage()
        ];
    }
}

function diagnosticarSeguridad() {
    try {
        $problemas = [];
        $recomendaciones = [];
        
        // Verificar configuración PHP
        $displayErrors = ini_get('display_errors');
        if ($displayErrors) {
            $problemas[] = 'display_errors está habilitado (riesgo de seguridad)';
        }
        
        $exposePhp = ini_get('expose_php');
        if ($exposePhp) {
            $recomendaciones[] = 'Desactivar expose_php en php.ini';
        }
        
        // Verificar archivos sensibles
        $archivosSensibles = [
            '../../config/database.php' => 'Configuración de BD',
            '../../.htaccess' => 'Configuración Apache',
            '../../config/email.php' => 'Configuración Email'
        ];
        
        $archivosExpuestos = [];
        foreach ($archivosSensibles as $archivo => $descripcion) {
            if (file_exists($archivo)) {
                $permisos = substr(sprintf('%o', fileperms($archivo)), -4);
                if ($permisos !== '0644' && $permisos !== '0640') {
                    $archivosExpuestos[] = "{$descripcion}: Permisos {$permisos} (revisar)";
                }
            }
        }
        
        // Verificar HTTPS
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                  || $_SERVER['SERVER_PORT'] == 443
                  || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        
        if (!$isHttps) {
            $recomendaciones[] = 'Configurar HTTPS para mayor seguridad';
        }
        
        // Verificar versión de PHP
        $phpVersion = PHP_VERSION;
        $phpMajor = (int)explode('.', $phpVersion)[0];
        $phpMinor = (int)explode('.', $phpVersion)[1];
        
        if ($phpMajor < 8 || ($phpMajor == 8 && $phpMinor < 0)) {
            $recomendaciones[] = 'Actualizar PHP a una versión más reciente';
        }
        
        $todoOk = empty($problemas) && empty($archivosExpuestos);
        
        $detalles = "Verificación de seguridad:\n";
        $detalles .= "- PHP Version: {$phpVersion}\n";
        $detalles .= "- HTTPS: " . ($isHttps ? 'SÍ' : 'NO') . "\n";
        $detalles .= "- Display Errors: " . ($displayErrors ? 'SÍ (PROBLEMA)' : 'NO (OK)') . "\n";
        $detalles .= "- Expose PHP: " . ($exposePhp ? 'SÍ' : 'NO') . "\n";
        
        if (!empty($problemas)) {
            $detalles .= "\nProblemas críticos:\n" . implode("\n", $problemas);
        }
        
        if (!empty($archivosExpuestos)) {
            $detalles .= "\nArchivos a revisar:\n" . implode("\n", $archivosExpuestos);
        }
        
        if (!empty($recomendaciones)) {
            $detalles .= "\nRecomendaciones:\n" . implode("\n", $recomendaciones);
        }
        
        return [
            'success' => $todoOk,
            'message' => $todoOk ? 'Configuración de seguridad adecuada' : 'Se encontraron problemas de seguridad',
            'details' => $detalles
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error verificando seguridad',
            'details' => $e->getMessage()
        ];
    }
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>
