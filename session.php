<?php
/**
 * Configuración y arranque de sesión unificado.
 * Este archivo debe ser incluido al principio de cualquier script que necesite sesiones.
 */

// Configurar los parámetros de la cookie de sesión para que sea válida en todo el sitio.
session_set_cookie_params([
    'lifetime' => 0, // La cookie expira cuando se cierra el navegador.
    'path' => '/',   // La cookie está disponible en todo el dominio.
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => isset($_SERVER['HTTPS']), // Solo enviar sobre HTTPS si está disponible.
    'httponly' => true, // Prevenir acceso desde JavaScript.
    'samesite' => 'Lax' // Protección contra CSRF.
]);

// Iniciar la sesión si no está ya activa.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Función para destruir una sesión de forma segura.
function destroy_session() {
    $_SESSION = []; // Limpiar el array de sesión.
    setcookie(session_name(), '', time() - 42000, '/'); // Invalidar la cookie.
    session_destroy(); // Destruir la sesión en el servidor.
}