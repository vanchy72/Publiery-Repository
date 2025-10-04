<?php
/**
 * Sistema de Notificaciones Automáticas
 * Funciones para generar notificaciones automáticamente en eventos del sistema
 */

require_once __DIR__ . '/../../config/database.php';

class SistemaNotificaciones {
    private $db;
    
    public function __construct() {
        $this->db = getDBConnection();
    }
    
    /**
     * Crear notificación automática
     */
    private function crearNotificacion($usuario_id, $tipo, $titulo, $mensaje, $datos_adicionales = null, $destacada = false, $enlace = null, $icono = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notificaciones (
                    usuario_id, tipo, titulo, mensaje, datos_adicionales, 
                    destacada, enlace, icono
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $usuario_id,
                $tipo,
                $titulo,
                $mensaje,
                $datos_adicionales ? json_encode($datos_adicionales) : null,
                $destacada ? 1 : 0,
                $enlace,
                $icono
            ]);
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("Error creando notificación automática: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Notificar nueva venta
     */
    public function notificarNuevaVenta($venta_id, $libro_titulo, $precio, $comprador_nombre, $afiliado_id = null, $escritor_id = null) {
        // Notificar al escritor
        if ($escritor_id) {
            $mensaje = "¡Nueva venta! Tu libro '{$libro_titulo}' ha sido vendido por $" . number_format($precio, 2);
            $this->crearNotificacion(
                $escritor_id,
                'venta',
                'Nueva venta de tu libro',
                $mensaje,
                ['venta_id' => $venta_id, 'libro' => $libro_titulo, 'precio' => $precio],
                true,
                "/dashboard-escritor.html#ventas",
                'dollar-sign'
            );
        }
        
        // Notificar al afiliado
        if ($afiliado_id) {
            $mensaje = "¡Comisión generada! Venta del libro '{$libro_titulo}' por $" . number_format($precio, 2);
            $this->crearNotificacion(
                $afiliado_id,
                'comision',
                'Nueva comisión disponible',
                $mensaje,
                ['venta_id' => $venta_id, 'libro' => $libro_titulo, 'precio' => $precio],
                true,
                "/dashboard-afiliado.html#comisiones",
                'percentage'
            );
        }
        
        // Notificar a admins
        $this->notificarAdmins(
            'venta',
            'Nueva venta registrada',
            "Venta del libro '{$libro_titulo}' por $" . number_format($precio, 2) . " a {$comprador_nombre}",
            ['venta_id' => $venta_id],
            false,
            "/admin-panel.html#ventas"
        );
    }
    
    /**
     * Notificar nuevo afiliado
     */
    public function notificarNuevoAfiliado($afiliado_id, $nombre, $email, $patrocinador_id = null) {
        // Notificar al patrocinador
        if ($patrocinador_id) {
            $mensaje = "¡Nuevo afiliado en tu red! {$nombre} se ha registrado como tu afiliado.";
            $this->crearNotificacion(
                $patrocinador_id,
                'afiliado',
                'Nuevo afiliado en tu red',
                $mensaje,
                ['afiliado_id' => $afiliado_id, 'nombre' => $nombre],
                true,
                "/dashboard-afiliado.html#red",
                'users'
            );
        }
        
        // Notificar a admins
        $this->notificarAdmins(
            'afiliado',
            'Nuevo afiliado registrado',
            "Nuevo afiliado: {$nombre} ({$email})",
            ['afiliado_id' => $afiliado_id],
            false,
            "/admin-panel.html#afiliados"
        );
    }
    
    /**
     * Notificar activación de afiliado
     */
    public function notificarActivacionAfiliado($afiliado_id, $nombre, $codigo) {
        $mensaje = "¡Felicidades! Tu cuenta de afiliado ha sido activada. Tu código es: {$codigo}";
        $this->crearNotificacion(
            $afiliado_id,
            'success',
            'Cuenta de afiliado activada',
            $mensaje,
            ['codigo_afiliado' => $codigo],
            true,
            "/dashboard-afiliado.html",
            'check-circle'
        );
    }
    
    /**
     * Notificar nuevo libro publicado
     */
    public function notificarNuevoLibro($libro_id, $titulo, $autor_nombre, $precio) {
        // Notificar a todos los afiliados
        $stmt = $this->db->prepare("SELECT usuario_id FROM afiliados WHERE fecha_activacion IS NOT NULL");
        $stmt->execute();
        $afiliados = $stmt->fetchAll();
        
        foreach ($afiliados as $afiliado) {
            $mensaje = "¡Nuevo libro disponible para promocionar! '{$titulo}' por {$autor_nombre} - $" . number_format($precio, 2);
            $this->crearNotificacion(
                $afiliado['usuario_id'],
                'info',
                'Nuevo libro disponible',
                $mensaje,
                ['libro_id' => $libro_id, 'titulo' => $titulo],
                false,
                "/tienda-lectores.html",
                'book'
            );
        }
    }
    
    /**
     * Notificar pago de comisión
     */
    public function notificarPagoComision($usuario_id, $monto, $concepto = '') {
        $mensaje = "¡Comisión pagada! Se ha procesado tu pago por $" . number_format($monto, 2);
        if ($concepto) {
            $mensaje .= " - {$concepto}";
        }
        
        $this->crearNotificacion(
            $usuario_id,
            'success',
            'Comisión pagada',
            $mensaje,
            ['monto' => $monto, 'concepto' => $concepto],
            true,
            "/dashboard-afiliado.html#comisiones",
            'money-bill'
        );
    }
    
    /**
     * Notificar bajo rendimiento
     */
    public function notificarBajoRendimiento($afiliado_id, $periodo = '30 días') {
        $mensaje = "Parece que tu actividad ha bajado en los últimos {$periodo}. ¡Te ayudamos a reactivar tus ventas!";
        $this->crearNotificacion(
            $afiliado_id,
            'warning',
            'Reactivemos tus ventas',
            $mensaje,
            ['periodo' => $periodo],
            false,
            "/dashboard-afiliado.html#campanas",
            'chart-line'
        );
    }
    
    /**
     * Notificar meta alcanzada
     */
    public function notificarMetaAlcanzada($usuario_id, $tipo_meta, $valor, $premio = null) {
        $mensaje = "¡Felicidades! Has alcanzado tu meta de {$tipo_meta}: {$valor}";
        if ($premio) {
            $mensaje .= ". Tu premio: {$premio}";
        }
        
        $this->crearNotificacion(
            $usuario_id,
            'success',
            '🎉 ¡Meta alcanzada!',
            $mensaje,
            ['tipo_meta' => $tipo_meta, 'valor' => $valor, 'premio' => $premio],
            true,
            "/dashboard-afiliado.html",
            'trophy'
        );
    }
    
    /**
     * Notificar recordatorio de actividad
     */
    public function notificarRecordatorioActividad($usuario_id, $dias_inactivo) {
        $mensaje = "Te extrañamos. Han pasado {$dias_inactivo} días desde tu última actividad. ¡Vuelve y sigue generando ingresos!";
        $this->crearNotificacion(
            $usuario_id,
            'info',
            'Te extrañamos',
            $mensaje,
            ['dias_inactivo' => $dias_inactivo],
            false,
            "/dashboard-afiliado.html",
            'clock'
        );
    }
    
    /**
     * Notificar a todos los administradores
     */
    private function notificarAdmins($tipo, $titulo, $mensaje, $datos_adicionales = null, $destacada = false, $enlace = null, $icono = null) {
        $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE rol = 'admin'");
        $stmt->execute();
        $admins = $stmt->fetchAll();
        
        foreach ($admins as $admin) {
            $this->crearNotificacion(
                $admin['id'],
                $tipo,
                $titulo,
                $mensaje,
                $datos_adicionales,
                $destacada,
                $enlace,
                $icono
            );
        }
    }
    
    /**
     * Notificar mantenimiento del sistema
     */
    public function notificarMantenimiento($fecha_inicio, $fecha_fin, $descripcion = '') {
        $mensaje = "Mantenimiento programado del {$fecha_inicio} al {$fecha_fin}";
        if ($descripcion) {
            $mensaje .= ". {$descripcion}";
        }
        
        // Notificar a todos los usuarios
        $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE estado = 'activo'");
        $stmt->execute();
        $usuarios = $stmt->fetchAll();
        
        foreach ($usuarios as $usuario) {
            $this->crearNotificacion(
                $usuario['id'],
                'warning',
                'Mantenimiento programado',
                $mensaje,
                ['fecha_inicio' => $fecha_inicio, 'fecha_fin' => $fecha_fin],
                true,
                null,
                'tools'
            );
        }
    }
    
    /**
     * Limpiar notificaciones antiguas
     */
    public function limpiarNotificacionesAntiguas($dias = 90) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM notificaciones 
                WHERE fecha_creacion < DATE_SUB(NOW(), INTERVAL ? DAY)
                AND leida = 1
            ");
            $stmt->execute([$dias]);
            
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Error limpiando notificaciones: " . $e->getMessage());
            return false;
        }
    }
}

// Funciones de utilidad para usar en otros archivos
function notificarVenta($venta_id, $libro_titulo, $precio, $comprador_nombre, $afiliado_id = null, $escritor_id = null) {
    $sistema = new SistemaNotificaciones();
    return $sistema->notificarNuevaVenta($venta_id, $libro_titulo, $precio, $comprador_nombre, $afiliado_id, $escritor_id);
}

function notificarNuevoAfiliado($afiliado_id, $nombre, $email, $patrocinador_id = null) {
    $sistema = new SistemaNotificaciones();
    return $sistema->notificarNuevoAfiliado($afiliado_id, $nombre, $email, $patrocinador_id);
}

function notificarActivacionAfiliado($afiliado_id, $nombre, $codigo) {
    $sistema = new SistemaNotificaciones();
    return $sistema->notificarActivacionAfiliado($afiliado_id, $nombre, $codigo);
}

function notificarPagoComision($usuario_id, $monto, $concepto = '') {
    $sistema = new SistemaNotificaciones();
    return $sistema->notificarPagoComision($usuario_id, $monto, $concepto);
}
?>
