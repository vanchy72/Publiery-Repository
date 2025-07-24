<?php
require_once 'config/database.php';
header('Content-Type: text/html; charset=utf-8');

echo "<h2>🔍 Diagnóstico de Token de Sesión</h2>";

// Obtener token desde GET o header
$token = $_GET['token'] ?? null;
if (!$token) {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $token = str_replace('Bearer ', '', $headers['Authorization']);
    }
}

if (!$token) {
    echo "<p style='color: red;'>❌ No se proporcionó token. Usa ?token=VALOR o envía Authorization: Bearer VALOR</p>";
    echo "<script>if (localStorage.getItem('session_token')) { document.write('<b>Token en localStorage:</b> ' + localStorage.getItem('session_token')); }</script>";
    exit;
}

echo "<b>Token recibido:</b> <code>$token</code><br>";

try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM sesiones WHERE token = ? LIMIT 1");
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if ($row) {
        echo "<ul>";
        echo "<li><b>ID sesión:</b> " . $row['id'] . "</li>";
        echo "<li><b>Usuario ID:</b> " . $row['usuario_id'] . "</li>";
        echo "<li><b>Fecha creación:</b> " . $row['fecha_creacion'] . "</li>";
        echo "<li><b>Fecha expiración:</b> " . $row['fecha_expiracion'] . "</li>";
        echo "<li><b>Activa:</b> " . ($row['activa'] ? 'Sí' : 'No') . "</li>";
        echo "<li><b>IP:</b> " . $row['ip_address'] . "</li>";
        echo "<li><b>User Agent:</b> " . $row['user_agent'] . "</li>";
        echo "</ul>";
        $expirada = (strtotime($row['fecha_expiracion']) < time());
        if (!$row['activa']) {
            echo "<p style='color: orange;'>⚠️ La sesión está INACTIVA.</p>";
        } elseif ($expirada) {
            echo "<p style='color: orange;'>⚠️ La sesión está EXPIRADA.</p>";
        } else {
            echo "<p style='color: green;'>✅ La sesión está ACTIVA y VÁLIDA.</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Token NO encontrado en la base de datos.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr><b>Token en localStorage (si existe):</b> <script>document.write(localStorage.getItem('session_token')||'No hay token');</script>";
?> 