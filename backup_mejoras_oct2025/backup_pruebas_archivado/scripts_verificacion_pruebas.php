<?php
/**
 * SCRIPT DE VERIFICACIÓN AUTOMÁTICA PARA PRUEBAS
 * Proyecto: Publiery
 * Propósito: Verificar automáticamente el estado del sistema durante las pruebas
 */

require_once 'config/database.php';

class VerificadorPruebas {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    /**
     * Verificar estado general del sistema
     */
    public function verificarSistema() {
        echo "<h2>🔍 VERIFICACIÓN GENERAL DEL SISTEMA</h2>\n";
        
        // Verificar conexión a BD
        if ($this->verificarConexionBD()) {
            echo "✅ Conexión a base de datos: OK<br>\n";
        } else {
            echo "❌ Conexión a base de datos: ERROR<br>\n";
            return false;
        }
        
        // Verificar tablas principales
        $tablas = ['usuarios', 'libros', 'ventas', 'comisiones', 'afiliados'];
        foreach ($tablas as $tabla) {
            if ($this->verificarTabla($tabla)) {
                echo "✅ Tabla '$tabla': OK<br>\n";
            } else {
                echo "❌ Tabla '$tabla': ERROR<br>\n";
            }
        }
        
        return true;
    }
    
    /**
     * Verificar usuarios de prueba
     */
    public function verificarUsuariosPrueba() {
        echo "<h2>👥 VERIFICACIÓN DE USUARIOS DE PRUEBA</h2>\n";
        
        try {
            $stmt = $this->pdo->query("
                SELECT id, nombre, email, rol, estado, fecha_registro 
                FROM usuarios 
                WHERE email LIKE '%prueba.com%' 
                ORDER BY fecha_registro DESC
            ");
            
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($usuarios)) {
                echo "⚠️ No se encontraron usuarios de prueba<br>\n";
                return false;
            }
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
            echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Estado</th><th>Fecha</th></tr>\n";
            
            foreach ($usuarios as $usuario) {
                $estado_color = $usuario['estado'] === 'activo' ? 'green' : 'orange';
                echo "<tr>";
                echo "<td>{$usuario['id']}</td>";
                echo "<td>{$usuario['nombre']}</td>";
                echo "<td>{$usuario['email']}</td>";
                echo "<td>{$usuario['rol']}</td>";
                echo "<td style='color: {$estado_color}'>{$usuario['estado']}</td>";
                echo "<td>{$usuario['fecha_registro']}</td>";
                echo "</tr>\n";
            }
            echo "</table><br>\n";
            
            echo "✅ Usuarios de prueba encontrados: " . count($usuarios) . "<br>\n";
            return true;
            
        } catch (Exception $e) {
            echo "❌ Error al verificar usuarios: " . $e->getMessage() . "<br>\n";
            return false;
        }
    }
    
    /**
     * Verificar libros subidos
     */
    public function verificarLibros() {
        echo "<h2>📚 VERIFICACIÓN DE LIBROS</h2>\n";
        
        try {
            $stmt = $this->pdo->query("
                SELECT l.id, l.titulo, l.precio, u.nombre as autor, l.fecha_registro
                FROM libros l
                INNER JOIN usuarios u ON l.autor_id = u.id
                WHERE l.fecha_registro >= CURDATE()
                ORDER BY l.fecha_registro DESC
            ");
            
            $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($libros)) {
                echo "⚠️ No se encontraron libros subidos hoy<br>\n";
                return false;
            }
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
            echo "<tr><th>ID</th><th>Título</th><th>Autor</th><th>Precio</th><th>Fecha</th></tr>\n";
            
            foreach ($libros as $libro) {
                echo "<tr>";
                echo "<td>{$libro['id']}</td>";
                echo "<td>{$libro['titulo']}</td>";
                echo "<td>{$libro['autor']}</td>";
                echo "<td>$" . number_format($libro['precio']) . "</td>";
                echo "<td>{$libro['fecha_registro']}</td>";
                echo "</tr>\n";
            }
            echo "</table><br>\n";
            
            echo "✅ Libros encontrados: " . count($libros) . "<br>\n";
            return true;
            
        } catch (Exception $e) {
            echo "❌ Error al verificar libros: " . $e->getMessage() . "<br>\n";
            return false;
        }
    }
    
    /**
     * Verificar ventas realizadas
     */
    public function verificarVentas() {
        echo "<h2>💰 VERIFICACIÓN DE VENTAS</h2>\n";
        
        try {
            $stmt = $this->pdo->query("
                SELECT v.id, l.titulo, v.precio_venta, v.afiliado_id, v.fecha_venta
                FROM ventas v
                INNER JOIN libros l ON v.libro_id = l.id
                WHERE v.fecha_venta >= CURDATE()
                ORDER BY v.fecha_venta DESC
            ");
            
            $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($ventas)) {
                echo "⚠️ No se encontraron ventas realizadas hoy<br>\n";
                return false;
            }
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
            echo "<tr><th>ID</th><th>Libro</th><th>Precio</th><th>Afiliado</th><th>Fecha</th></tr>\n";
            
            $total_ventas = 0;
            foreach ($ventas as $venta) {
                $afiliado = $venta['afiliado_id'] ? $venta['afiliado_id'] : 'Directa';
                echo "<tr>";
                echo "<td>{$venta['id']}</td>";
                echo "<td>{$venta['titulo']}</td>";
                echo "<td>$" . number_format($venta['precio_venta']) . "</td>";
                echo "<td>{$afiliado}</td>";
                echo "<td>{$venta['fecha_venta']}</td>";
                echo "</tr>\n";
                $total_ventas += $venta['precio_venta'];
            }
            echo "</table><br>\n";
            
            echo "✅ Ventas encontradas: " . count($ventas) . "<br>\n";
            echo "💵 Total vendido: $" . number_format($total_ventas) . "<br>\n";
            return true;
            
        } catch (Exception $e) {
            echo "❌ Error al verificar ventas: " . $e->getMessage() . "<br>\n";
            return false;
        }
    }
    
    /**
     * Verificar comisiones calculadas
     */
    public function verificarComisiones() {
        echo "<h2>💳 VERIFICACIÓN DE COMISIONES</h2>\n";
        
        try {
            $stmt = $this->pdo->query("
                SELECT c.id, c.afiliado_id, u.nombre, c.nivel, c.monto, c.porcentaje, c.fecha_generacion
                FROM comisiones c
                INNER JOIN usuarios u ON c.afiliado_id = u.id
                WHERE c.fecha_generacion >= CURDATE()
                ORDER BY c.fecha_generacion DESC
            ");
            
            $comisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($comisiones)) {
                echo "⚠️ No se encontraron comisiones calculadas hoy<br>\n";
                return false;
            }
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
            echo "<tr><th>ID</th><th>Usuario</th><th>Nivel</th><th>Monto</th><th>%</th><th>Fecha</th></tr>\n";
            
            $total_comisiones = 0;
            foreach ($comisiones as $comision) {
                echo "<tr>";
                echo "<td>{$comision['id']}</td>";
                echo "<td>{$comision['nombre']}</td>";
                echo "<td>Nivel {$comision['nivel']}</td>";
                echo "<td>$" . number_format($comision['monto']) . "</td>";
                echo "<td>{$comision['porcentaje']}%</td>";
                echo "<td>{$comision['fecha_generacion']}</td>";
                echo "</tr>\n";
                $total_comisiones += $comision['monto'];
            }
            echo "</table><br>\n";
            
            echo "✅ Comisiones encontradas: " . count($comisiones) . "<br>\n";
            echo "💰 Total comisiones: $" . number_format($total_comisiones) . "<br>\n";
            return true;
            
        } catch (Exception $e) {
            echo "❌ Error al verificar comisiones: " . $e->getMessage() . "<br>\n";
            return false;
        }
    }
    
    /**
     * Verificar estructura de afiliados
     */
    public function verificarAfiliados() {
        echo "<h2>🌐 VERIFICACIÓN DE ESTRUCTURA DE AFILIADOS</h2>\n";
        
        try {
            $stmt = $this->pdo->query("
                SELECT u.id, u.nombre, u.email, a.codigo_afiliado, a.patrocinador_id, a.nivel
                FROM usuarios u
                INNER JOIN afiliados a ON u.id = a.usuario_id
                WHERE u.estado = 'activo' AND u.rol = 'afiliado'
                ORDER BY a.nivel, a.codigo_afiliado
            ");
            
            $afiliados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($afiliados)) {
                echo "⚠️ No se encontraron afiliados activos<br>\n";
                return false;
            }
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
            echo "<tr><th>ID</th><th>Nombre</th><th>Código</th><th>Patrocinador</th><th>Nivel</th></tr>\n";
            
            foreach ($afiliados as $afiliado) {
                $patrocinador = $afiliado['patrocinador_id'] ? $afiliado['patrocinador_id'] : 'N/A';
                echo "<tr>";
                echo "<td>{$afiliado['id']}</td>";
                echo "<td>{$afiliado['nombre']}</td>";
                echo "<td>{$afiliado['codigo_afiliado']}</td>";
                echo "<td>{$patrocinador}</td>";
                echo "<td>{$afiliado['nivel']}</td>";
                echo "</tr>\n";
            }
            echo "</table><br>\n";
            
            echo "✅ Afiliados activos: " . count($afiliados) . "<br>\n";
            return true;
            
        } catch (Exception $e) {
            echo "❌ Error al verificar afiliados: " . $e->getMessage() . "<br>\n";
            return false;
        }
    }
    
    /**
     * Resumen completo
     */
    public function resumenCompleto() {
        echo "<h1>📋 RESUMEN COMPLETO DE VERIFICACIÓN</h1>\n";
        echo "<hr>\n";
        
        $resultados = [
            'Sistema' => $this->verificarSistema(),
            'Usuarios' => $this->verificarUsuariosPrueba(),
            'Libros' => $this->verificarLibros(),
            'Ventas' => $this->verificarVentas(),
            'Comisiones' => $this->verificarComisiones(),
            'Afiliados' => $this->verificarAfiliados()
        ];
        
        echo "<h2>📊 RESUMEN FINAL</h2>\n";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr><th>Componente</th><th>Estado</th></tr>\n";
        
        foreach ($resultados as $componente => $estado) {
            $icono = $estado ? '✅' : '❌';
            $color = $estado ? 'green' : 'red';
            echo "<tr>";
            echo "<td>{$componente}</td>";
            echo "<td style='color: {$color}'>{$icono} " . ($estado ? 'OK' : 'ERROR') . "</td>";
            echo "</tr>\n";
        }
        echo "</table><br>\n";
        
        $exitosos = array_sum($resultados);
        $total = count($resultados);
        $porcentaje = round(($exitosos / $total) * 100);
        
        echo "<h3>🎯 RESULTADO FINAL: {$exitosos}/{$total} ({$porcentaje}%)</h3>\n";
        
        if ($porcentaje === 100) {
            echo "<div style='color: green; font-weight: bold;'>🎉 TODAS LAS VERIFICACIONES EXITOSAS</div>\n";
        } elseif ($porcentaje >= 80) {
            echo "<div style='color: orange; font-weight: bold;'>⚠️ SISTEMA MAYORMENTE FUNCIONAL</div>\n";
        } else {
            echo "<div style='color: red; font-weight: bold;'>🚨 REQUIERE ATENCIÓN INMEDIATA</div>\n";
        }
    }
    
    // Métodos auxiliares
    private function verificarConexionBD() {
        try {
            $this->pdo->query("SELECT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function verificarTabla($tabla) {
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$tabla}'");
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Si se ejecuta directamente
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Verificación de Pruebas - Publiery</title>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            table { width: 100%; margin: 10px 0; }
            th, td { padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            hr { margin: 20px 0; }
        </style>
    </head>
    <body>";
    
    $verificador = new VerificadorPruebas();
    $verificador->resumenCompleto();
    
    echo "</body></html>";
}
?>
