<?php
session_start();

// Establecer sesión de admin para pruebas
$_SESSION['user_id'] = 80; // ID del admin

echo "Sesión establecida para usuario ID: {$_SESSION['user_id']}\n";
echo "Puedes probar el formulario del panel de administración ahora.";
?>