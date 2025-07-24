<?php
// Script para restaurar afiliados de prueba según los usuarios existentes
require_once __DIR__ . '/config/database.php';

try {
    $conn = getDBConnection();
    $usuarios = $conn->query("SELECT id, nombre, email FROM usuarios WHERE rol = 'afiliado' AND estado = 'activo'")->fetchAll();
    $creados = 0;
    foreach ($usuarios as $usuario) {
        // Verificar si ya existe afiliado para este usuario
        $stmt = $conn->prepare("SELECT id FROM afiliados WHERE usuario_id = ?");
        $stmt->execute([$usuario['id']]);
        if (!$stmt->fetch()) {
            // Crear afiliado de prueba
            $codigo = 'AF' . str_pad($usuario['id'], 6, '0', STR_PAD_LEFT);
            $conn->prepare("INSERT INTO afiliados (usuario_id, codigo_afiliado, patrocinador_id, nivel, frontal, fecha_activacion, comision_total, ventas_totales, nombre, email, estado_usuario, fecha_registro) VALUES (?, ?, NULL, 1, 0, NOW(), 0, 0, ?, ?, 'activo', NOW())")
                ->execute([$usuario['id'], $codigo, $usuario['nombre'], $usuario['email']]);
            $creados++;
        }
    }
    echo "Afiliados de prueba creados: $creados\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} 