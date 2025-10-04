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

try {
    $systemInfo = obtenerInformacionSistema();
    echo json_encode(['success' => true, 'info' => $systemInfo]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function obtenerInformacionSistema() {
    $info = [];
    
    // Información de PHP
    $info['PHP Version'] = PHP_VERSION;
    $info['PHP SAPI'] = php_sapi_name();
    $info['Memory Limit'] = ini_get('memory_limit');
    $info['Max Execution Time'] = ini_get('max_execution_time') . 's';
    $info['Max Upload Size'] = ini_get('upload_max_filesize');
    $info['Post Max Size'] = ini_get('post_max_size');
    $info['Display Errors'] = ini_get('display_errors') ? 'ON' : 'OFF';
    $info['Error Reporting'] = ini_get('error_reporting');
    
    // Información del servidor
    $info['Server Software'] = $_SERVER['SERVER_SOFTWARE'] ?? 'N/A';
    $info['Server OS'] = PHP_OS;
    $info['Server Admin'] = $_SERVER['SERVER_ADMIN'] ?? 'N/A';
    $info['Document Root'] = $_SERVER['DOCUMENT_ROOT'] ?? 'N/A';
    $info['Server IP'] = $_SERVER['SERVER_ADDR'] ?? 'N/A';
    $info['Server Port'] = $_SERVER['SERVER_PORT'] ?? 'N/A';
    
    // Información de la aplicación
    $info['Script Path'] = __DIR__;
    $info['Current User'] = get_current_user();
    $info['Process ID'] = getmypid();
    $info['Script Owner'] = fileowner(__FILE__);
    
    // Extensiones importantes
    $extensiones = [
        'PDO', 'mysqli', 'curl', 'openssl', 'gd', 'mbstring', 
        'json', 'xml', 'zip', 'fileinfo', 'hash'
    ];
    
    $extLoaded = [];
    foreach ($extensiones as $ext) {
        $extLoaded[] = $ext . ': ' . (extension_loaded(strtolower($ext)) ? 'SÍ' : 'NO');
    }
    $info['Extensiones'] = implode(', ', $extLoaded);
    
    // Información de base de datos
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->query("SELECT VERSION() as version");
        if ($stmt) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $info['MySQL Version'] = $result['version'];
        }
        
        // Información adicional de MySQL
        $stmt = $pdo->query("SHOW VARIABLES LIKE 'max_connections'");
        if ($stmt) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $info['MySQL Max Connections'] = $result['Value'];
        }
        
        $stmt = $pdo->query("SHOW STATUS LIKE 'Uptime'");
        if ($stmt) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $uptime = (int)$result['Value'];
            $days = floor($uptime / 86400);
            $hours = floor(($uptime % 86400) / 3600);
            $info['MySQL Uptime'] = "{$days} días, {$hours} horas";
        }
        
    } catch (Exception $e) {
        $info['MySQL'] = 'Error: ' . $e->getMessage();
    }
    
    // Información de espacio en disco
    try {
        $bytes = disk_free_space('.');
        $info['Disk Free Space'] = formatBytes($bytes);
        
        $total = disk_total_space('.');
        $info['Disk Total Space'] = formatBytes($total);
        
        $used = $total - $bytes;
        $info['Disk Used Space'] = formatBytes($used);
        
        $percent = round(($used / $total) * 100, 2);
        $info['Disk Usage Percent'] = $percent . '%';
        
    } catch (Exception $e) {
        $info['Disk Info'] = 'Error: ' . $e->getMessage();
    }
    
    // Configuración de sesión
    $info['Session Save Path'] = session_save_path();
    $info['Session Name'] = session_name();
    $info['Session ID'] = session_id();
    
    // Configuración de zona horaria
    $info['Default Timezone'] = date_default_timezone_get();
    $info['Current Date/Time'] = date('Y-m-d H:i:s');
    
    // Límites y configuración
    $info['Max Input Vars'] = ini_get('max_input_vars');
    $info['Max File Uploads'] = ini_get('max_file_uploads');
    $info['Auto Start'] = ini_get('session.auto_start') ? 'ON' : 'OFF';
    
    // Información de seguridad
    $info['HTTPS'] = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'SÍ' : 'NO';
    $info['User Agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'N/A';
    
    return $info;
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>