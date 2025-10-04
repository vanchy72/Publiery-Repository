<?php
/**
 * Verificación final del estado de la tienda
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Verificación Final de la Tienda Publiery</h1>";
echo "<hr>";

// 1. Verificar conexión a base de datos
echo "<h2>1. Base de Datos</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    $conn = getDBConnection();
    echo "✅ Conexión a BD: OK<br>";
    
    // Verificar tabla libros
    $stmt = $conn->query("SELECT COUNT(*) as total FROM libros WHERE estado = 'publicado'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "📚 Libros publicados: " . $result['total'] . "<br>";
    
} catch (Exception $e) {
    echo "❌ Error BD: " . $e->getMessage() . "<br>";
}

echo "<br>";

// 2. Verificar archivos clave
echo "<h2>2. Archivos del Sistema</h2>";

$archivos = [
    'api/libros/disponibles.php' => 'API de libros',
    'js/tienda.js' => 'JavaScript de tienda',
    'tienda-lectores.html' => 'Página principal de tienda',
    'config/auth_functions.php' => 'Funciones de autenticación'
];

foreach ($archivos as $archivo => $descripcion) {
    if (file_exists($archivo)) {
        echo "✅ $descripcion: Existe<br>";
    } else {
        echo "❌ $descripcion: NO EXISTE<br>";
    }
}

echo "<br>";

// 3. Probar API directamente
echo "<h2>3. Prueba de API</h2>";

try {
    $apiUrl = 'http://localhost/publiery/api/libros/disponibles.php';
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ]);
    
    $response = file_get_contents($apiUrl, false, $context);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && isset($data['success'])) {
            if ($data['success']) {
                echo "✅ API funcionando correctamente<br>";
                echo "📊 Total de libros retornados: " . (isset($data['total']) ? $data['total'] : 0) . "<br>";
            } else {
                echo "❌ API retorna error: " . ($data['error'] ?? 'Error desconocido') . "<br>";
            }
        } else {
            echo "❌ Respuesta de API inválida<br>";
            echo "📄 Respuesta: " . htmlspecialchars(substr($response, 0, 200)) . "<br>";
        }
    } else {
        echo "❌ No se pudo conectar a la API<br>";
    }
} catch (Exception $e) {
    echo "❌ Error probando API: " . $e->getMessage() . "<br>";
}

echo "<br>";

// 4. Verificar directorios de imágenes
echo "<h2>4. Directorios de Imágenes</h2>";

$directorios = [
    'images/' => 'Imágenes por defecto',
    'uploads/portadas/' => 'Portadas subidas por usuarios'
];

foreach ($directorios as $dir => $descripcion) {
    if (is_dir($dir)) {
        $archivos = glob($dir . '*');
        echo "✅ $descripcion: " . count($archivos) . " archivos<br>";
    } else {
        echo "❌ $descripcion: Directorio no existe<br>";
    }
}

echo "<br>";

// 5. Enlaces de prueba
echo "<h2>5. Enlaces de Prueba</h2>";
echo '<a href="tienda-lectores.html" target="_blank">🛒 Abrir Tienda Principal</a><br>';
echo '<a href="test_tienda_completo.html" target="_blank">🧪 Test Completo de Tienda</a><br>';
echo '<a href="api/libros/disponibles.php" target="_blank">🔗 API de Libros Directa</a><br>';

echo "<br><hr>";
echo "<p><strong>Verificación completada el:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>