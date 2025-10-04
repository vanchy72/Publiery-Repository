<?php
require_once 'config/database.php';

try {
    $conn = getDBConnection();
    echo "=== VERIFICANDO CAMPO COMPARTIDA_RED ===\n\n";
    
    // Ver el estado actual
    $stmt = $conn->query("SELECT id, nombre, estado, compartida_red FROM campanas");
    while($row = $stmt->fetch()) {
        echo "ID: {$row['id']} - {$row['nombre']}\n";
        echo "  Estado: '{$row['estado']}'\n";
        echo "  Compartida_red: {$row['compartida_red']}\n\n";
    }
    
    // Actualizar la campaña 21 para que sea compartida
    echo "=== ACTUALIZANDO CAMPAÑA 21 ===\n";
    $stmt = $conn->prepare("UPDATE campanas SET compartida_red = 1 WHERE id = 21");
    $resultado = $stmt->execute();
    
    if ($resultado) {
        echo "✅ Campaña 21 marcada como compartida\n";
    } else {
        echo "❌ Error al actualizar\n";
    }
    
    // Verificar después de actualizar
    echo "\n=== ESTADO DESPUÉS DE ACTUALIZAR ===\n";
    $stmt = $conn->prepare("SELECT id, nombre, estado, compartida_red FROM campanas WHERE id = 21");
    $stmt->execute();
    $row = $stmt->fetch();
    
    echo "ID: {$row['id']} - {$row['nombre']}\n";
    echo "Estado: '{$row['estado']}'\n";
    echo "Compartida_red: {$row['compartida_red']}\n";
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>