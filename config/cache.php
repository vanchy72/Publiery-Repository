<?php
/**
 * CONFIGURACIÓN DE CACHÉ PARA DESARROLLO
 * 
 * Este archivo controla los headers de caché para evitar problemas
 * durante el desarrollo. Se puede incluir en cualquier archivo PHP
 * que necesite control de caché.
 */

// Función para desactivar caché
function disableCache() {
    // Headers para desactivar caché
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: 0");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
}

// Función para configurar caché para archivos estáticos
function setStaticCache($seconds = 3600) {
    header("Cache-Control: public, max-age=" . $seconds);
    header("Expires: " . gmdate("D, d M Y H:i:s", time() + $seconds) . " GMT");
}

// Función para configurar caché para contenido dinámico
function setDynamicCache($seconds = 300) {
    header("Cache-Control: private, max-age=" . $seconds);
    header("Expires: " . gmdate("D, d M Y H:i:s", time() + $seconds) . " GMT");
}

// Función para verificar si es una petición de desarrollo
function isDevelopment() {
    return (
        $_SERVER['SERVER_NAME'] === 'localhost' || 
        $_SERVER['SERVER_NAME'] === '127.0.0.1' ||
        strpos($_SERVER['SERVER_NAME'], '.local') !== false ||
        strpos($_SERVER['SERVER_NAME'], '.test') !== false
    );
}

// Función para configurar caché automáticamente según el entorno
function autoCacheControl() {
    if (isDevelopment()) {
        // En desarrollo: desactivar caché
        disableCache();
    } else {
        // En producción: configurar caché apropiado
        setDynamicCache(300); // 5 minutos para contenido dinámico
    }
}

// Función para agregar parámetro de versión a URLs
function addVersionToUrl($url, $version = null) {
    if ($version === null) {
        $version = time(); // Usar timestamp como versión
    }
    
    $separator = (strpos($url, '?') !== false) ? '&' : '?';
    return $url . $separator . 'v=' . $version;
}

// Función para limpiar caché del navegador (JavaScript)
function getCacheBusterScript() {
    return "
    <script>
    // Script para forzar recarga sin caché
    function forceReload() {
        window.location.reload(true);
    }
    
    // Agregar parámetro de versión a todos los recursos
    function addVersionToResources() {
        var links = document.querySelectorAll('link[rel=\"stylesheet\"]');
        var scripts = document.querySelectorAll('script[src]');
        var timestamp = new Date().getTime();
        
        links.forEach(function(link) {
            if (link.href.indexOf('?') === -1) {
                link.href += '?v=' + timestamp;
            }
        });
        
        scripts.forEach(function(script) {
            if (script.src.indexOf('?') === -1) {
                script.src += '?v=' + timestamp;
            }
        });
    }
    
    // Ejecutar al cargar la página
    window.addEventListener('load', addVersionToResources);
    </script>
    ";
}

// Función para generar meta tags de caché
function getCacheMetaTags() {
    return "
    <meta http-equiv=\"Cache-Control\" content=\"no-cache, no-store, must-revalidate\">
    <meta http-equiv=\"Pragma\" content=\"no-cache\">
    <meta http-equiv=\"Expires\" content=\"0\">
    ";
}

// Configuración automática si se incluye este archivo
if (!defined('CACHE_CONFIG_LOADED')) {
    define('CACHE_CONFIG_LOADED', true);
    
    // Aplicar configuración automática en desarrollo
    if (isDevelopment()) {
        disableCache();
    }
}
?> 