<?php
// Script para establecer sesión correctamente para Santiago
session_start();

// ESTABLECER SESIÓN PARA SANTIAGO
$_SESSION['user_id'] = 85;
$_SESSION['id'] = 85; // Fallback
$_SESSION['user_nombre'] = 'SANTIAGO ORTEGA RAMIREZ';
$_SESSION['user_rol'] = 'afiliado';
$_SESSION['user_estado'] = 'pendiente';

// Headers JSON
header('Content-Type: application/json');

// Respuesta
echo json_encode([
    'success' => true,
    'message' => 'Sesión establecida correctamente',
    'session_data' => [
        'user_id' => $_SESSION['user_id'],
        'nombre' => $_SESSION['user_nombre'],
        'rol' => $_SESSION['user_rol'],
        'estado' => $_SESSION['user_estado']
    ]
]);
?>