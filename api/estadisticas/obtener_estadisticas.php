<?php
ini_set('display_errors', 'On'); // Activar la visualización de errores
error_reporting(E_ALL); // Reportar todos los errores

error_log("DEBUG: api/estadisticas/obtener_estadisticas.php iniciado."); // Log de depuración

// Incluir dependencias.
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/response.php';

// Establecer cabeceras después de iniciar la sesión.
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar que es admin
if (!isAdmin()) {
    jsonResponse(['success' => false, 'error' => 'Acceso denegado. Solo los administradores pueden ver las estadísticas'], 403);
}

try {
    $db = getDBConnection();

    // Función auxiliar para realizar llamadas internas a las APIs de estadísticas
    function callInternalApi($apiPath, $method = 'GET', $data = []) {
        // Simular una solicitud interna para ejecutar el script PHP
        ob_start(); // Iniciar buffer de salida

        // Crear un contexto de solicitud simulado
        $_SERVER['REQUEST_METHOD'] = $method;
        $_GET = ($method === 'GET') ? $data : [];
        $_POST = ($method === 'POST') ? $data : [];

        // Incluir el archivo de la API. Asumimos que los archivos están en la misma carpeta o ruta relativa similar.
        $filePath = __DIR__ . '/' . $apiPath;
        if (!file_exists($filePath)) {
            error_log("API interna no encontrada: " . $filePath);
            return ['success' => false, 'error' => "API interna no encontrada: " . $apiPath];
        }

        include $filePath; // Ejecutar el script PHP
        $output = ob_get_clean(); // Obtener el contenido del buffer y limpiarlo

        $result = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Error al decodificar JSON de la API interna " . $apiPath . ": " . json_last_error_msg() . " - Output: " . $output);
            return ['success' => false, 'error' => "Error en API interna: " . $apiPath . ". Output: " . $output];
        }
        return $result;
    }

    $stats = [];

    // Total de usuarios
    $totalUsuarios = callInternalApi('total_usuarios.php');
    if ($totalUsuarios['success']) {
        $stats[] = [
            'id' => 1,
            'titulo' => 'Total de Usuarios',
            'valor' => $totalUsuarios['total_usuarios'],
            'sufijo' => '',
            'icono' => '👥',
            'activo' => true,
            'chart_type' => 'number',
            'id_html_canvas' => null
        ];
    }

    // Total de Libros
    $totalLibros = callInternalApi('total_libros.php');
    if ($totalLibros['success']) {
        $stats[] = [
            'id' => 4,
            'titulo' => 'Total de Libros',
            'valor' => $totalLibros['total_libros'],
            'sufijo' => '',
            'icono' => '📚',
            'activo' => true,
            'chart_type' => 'number',
            'id_html_canvas' => null
        ];
    }

    // Total de Ventas
    $totalVentas = callInternalApi('total_ventas.php');
    if ($totalVentas['success']) {
        $stats[] = [
            'id' => 5,
            'titulo' => 'Total de Ventas',
            'valor' => $totalVentas['total_ventas'],
            'sufijo' => '',
            'icono' => '🛒',
            'activo' => true,
            'chart_type' => 'number',
            'id_html_canvas' => null
        ];
    }

    // Ganancias Totales
    $gananciasTotales = callInternalApi('ganancias_totales.php');
    if ($gananciasTotales['success']) {
        $stats[] = [
            'id' => 6,
            'titulo' => 'Ganancias Totales',
            'valor' => number_format($gananciasTotales['ganancias_totales'], 2, '.', ''),
            'sufijo' => '$ ',
            'icono' => '💲',
            'activo' => true,
            'chart_type' => 'number',
            'id_html_canvas' => null
        ];
    }

    // Total de Pagos (y comisiones)
    $totalPagos = callInternalApi('total_pagos.php');
    if ($totalPagos['success']) {
        $stats[] = [
            'id' => 7,
            'titulo' => 'Total de Pagos y Comisiones',
            'valor' => $totalPagos['total_pagos'],
            'sufijo' => '',
            'icono' => '💳',
            'activo' => true,
            'chart_type' => 'number',
            'id_html_canvas' => null
        ];
    }

    // Libros más Vendidos
    $librosMasVendidos = callInternalApi('libros_mas_vendidos.php');
    if ($librosMasVendidos['success']) {
        $stats[] = [
            'id' => 8,
            'titulo' => 'Libros más Vendidos',
            'valor' => $librosMasVendidos['libros_mas_vendidos'],
            'sufijo' => '',
            'icono' => '📈',
            'activo' => true,
            'chart_type' => 'list'
        ];
    }

    // Usuarios por rol (para un gráfico de pastel/donut)
    $usuariosPorRol = callInternalApi('usuarios_por_rol.php');
    if ($usuariosPorRol['success']) {
        $stats[] = [
            'id' => 2,
            'titulo' => 'Usuarios por Rol',
            'valor' => $usuariosPorRol['usuarios_por_rol'],
            'sufijo' => '',
            'icono' => '👥',
            'activo' => true,
            'chart_type' => 'doughnut',
            'id_html_canvas' => 'graficoUsuariosPorRol'
        ];
    }

    // Ventas Mensuales (para un gráfico de líneas)
    $ventasMensuales = callInternalApi('ventas_mensuales.php');
    if ($ventasMensuales['success']) {
        $stats[] = [
            'id' => 3,
            'titulo' => 'Ventas Mensuales',
            'valor' => $ventasMensuales['ventas_mensuales'],
            'sufijo' => '',
            'icono' => '💰',
            'activo' => true,
            'chart_type' => 'line',
            'id_html_canvas' => 'graficoVentasMensuales'
        ];
    }

    // Pagos por estado (para un gráfico de pastel/donut)
    $pagosPorEstado = callInternalApi('pagos_por_estado.php');
    if ($pagosPorEstado['success']) {
        $stats[] = [
            'id' => 9,
            'titulo' => 'Pagos por Estado',
            'valor' => $pagosPorEstado['pagos_por_estado'],
            'sufijo' => '',
            'icono' => '💲',
            'activo' => true,
            'chart_type' => 'doughnut',
            'id_html_canvas' => 'graficoPagosPorEstado'
        ];
    }

    jsonResponse([
        'success' => true,
        'data' => $stats
    ]);

} catch (Exception $e) {
    error_log('Error al obtener estadísticas generales: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener estadísticas generales: ' . $e->getMessage()
    ], 500);
}
?> 