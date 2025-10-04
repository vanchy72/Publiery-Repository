<?php
/**
 * Sistema Avanzado de Emails - Publiery
 * Gestión completa de envío de emails con plantillas y seguimiento
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/database.php';

class EmailAvanzado {
    private $mailer;
    private $db;
    private $config;
    
    public function __construct() {
        $this->db = getDBConnection();
        $this->config = $this->cargarConfiguracion();
        $this->configurarMailer();
    }
    
    /**
     * Cargar configuración de email desde base de datos o archivo
     */
    private function cargarConfiguracion() {
        // Configuración por defecto - En producción, estos datos vendrían de la DB o variables de entorno
        return [
            'smtp_host' => 'smtp.gmail.com', // Cambiar por tu servidor SMTP
            'smtp_port' => 587,
            'smtp_username' => 'tu-email@gmail.com', // Cambiar por tu email
            'smtp_password' => 'tu-app-password', // Cambiar por tu password de app
            'from_email' => 'noreply@publiery.com',
            'from_name' => 'Publiery - Plataforma de Afiliados',
            'reply_to' => 'soporte@publiery.com'
        ];
    }
    
    /**
     * Configurar PHPMailer
     */
    private function configurarMailer() {
        $this->mailer = new PHPMailer(true);
        
        try {
            // Configuración del servidor
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['smtp_host'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->config['smtp_username'];
            $this->mailer->Password = $this->config['smtp_password'];
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = $this->config['smtp_port'];
            
            // Configuración general
            $this->mailer->setFrom($this->config['from_email'], $this->config['from_name']);
            $this->mailer->addReplyTo($this->config['reply_to']);
            $this->mailer->isHTML(true);
            $this->mailer->CharSet = 'UTF-8';
            
        } catch (Exception $e) {
            error_log("Error configurando mailer: " . $e->getMessage());
        }
    }
    
    /**
     * Enviar email con plantilla
     */
    public function enviarConPlantilla($destinatario, $asunto, $plantilla, $datos = [], $adjuntos = []) {
        try {
            // Limpiar destinatarios previos
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Configurar destinatario
            if (is_array($destinatario)) {
                $this->mailer->addAddress($destinatario['email'], $destinatario['nombre'] ?? '');
            } else {
                $this->mailer->addAddress($destinatario);
            }
            
            // Configurar asunto
            $this->mailer->Subject = $asunto;
            
            // Generar contenido de la plantilla
            $contenidoHtml = $this->generarPlantilla($plantilla, $datos);
            $contenidoTexto = $this->generarTextoPlano($contenidoHtml);
            
            $this->mailer->Body = $contenidoHtml;
            $this->mailer->AltBody = $contenidoTexto;
            
            // Agregar adjuntos
            foreach ($adjuntos as $adjunto) {
                if (is_array($adjunto)) {
                    $this->mailer->addAttachment($adjunto['path'], $adjunto['name'] ?? '');
                } else {
                    $this->mailer->addAttachment($adjunto);
                }
            }
            
            // Enviar email
            $resultado = $this->mailer->send();
            
            // Registrar envío en la base de datos
            $this->registrarEnvio($destinatario, $asunto, $plantilla, $resultado);
            
            return [
                'success' => true,
                'message' => 'Email enviado correctamente'
            ];
            
        } catch (Exception $e) {
            error_log("Error enviando email: " . $e->getMessage());
            
            // Registrar error en la base de datos
            $this->registrarEnvio($destinatario, $asunto, $plantilla, false, $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generar contenido HTML de la plantilla
     */
    private function generarPlantilla($plantilla, $datos) {
        $rutaPlantilla = __DIR__ . "/email_templates/{$plantilla}.html";
        
        if (!file_exists($rutaPlantilla)) {
            throw new Exception("Plantilla no encontrada: {$plantilla}");
        }
        
        $contenido = file_get_contents($rutaPlantilla);
        
        // Reemplazar variables en la plantilla
        foreach ($datos as $clave => $valor) {
            $contenido = str_replace("{{" . $clave . "}}", $valor, $contenido);
        }
        
        // Reemplazar variables del sistema
        $contenido = str_replace("{{CURRENT_YEAR}}", date('Y'), $contenido);
        $contenido = str_replace("{{SITE_URL}}", APP_URL ?? 'http://localhost/publiery', $contenido);
        $contenido = str_replace("{{UNSUBSCRIBE_URL}}", (APP_URL ?? 'http://localhost/publiery') . '/unsubscribe', $contenido);
        
        return $contenido;
    }
    
    /**
     * Generar versión de texto plano
     */
    private function generarTextoPlano($html) {
        // Convertir HTML básico a texto plano
        $texto = strip_tags($html);
        $texto = html_entity_decode($texto, ENT_QUOTES, 'UTF-8');
        $texto = preg_replace('/\s+/', ' ', $texto);
        $texto = trim($texto);
        
        return $texto;
    }
    
    /**
     * Registrar envío en base de datos
     */
    private function registrarEnvio($destinatario, $asunto, $plantilla, $exitoso, $error = null) {
        try {
            $email = is_array($destinatario) ? $destinatario['email'] : $destinatario;
            
            $stmt = $this->db->prepare("
                INSERT INTO email_logs (
                    email_destinatario, asunto, plantilla, 
                    exitoso, error_mensaje, fecha_envio
                ) VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $email,
                $asunto,
                $plantilla,
                $exitoso ? 1 : 0,
                $error
            ]);
            
        } catch (Exception $e) {
            error_log("Error registrando envío de email: " . $e->getMessage());
        }
    }
    
    /**
     * Enviar email de bienvenida a nuevo usuario
     */
    public function enviarBienvenida($usuario) {
        $datos = [
            'NOMBRE' => $usuario['nombre'],
            'EMAIL' => $usuario['email'],
            'ROL' => $usuario['rol'],
            'LOGIN_URL' => (APP_URL ?? 'http://localhost/publiery') . '/login.html'
        ];
        
        return $this->enviarConPlantilla(
            ['email' => $usuario['email'], 'nombre' => $usuario['nombre']],
            '¡Bienvenido a Publiery!',
            'bienvenida',
            $datos
        );
    }
    
    /**
     * Enviar notificación de nueva venta
     */
    public function enviarNotificacionVenta($destinatario, $venta) {
        $datos = [
            'NOMBRE' => $destinatario['nombre'],
            'LIBRO_TITULO' => $venta['libro_titulo'],
            'PRECIO' => number_format($venta['precio'], 2),
            'FECHA' => date('d/m/Y H:i'),
            'DASHBOARD_URL' => (APP_URL ?? 'http://localhost/publiery') . '/dashboard-' . $destinatario['rol'] . '.html'
        ];
        
        return $this->enviarConPlantilla(
            ['email' => $destinatario['email'], 'nombre' => $destinatario['nombre']],
            '¡Nueva venta registrada!',
            'nueva_venta',
            $datos
        );
    }
    
    /**
     * Enviar notificación de comisión
     */
    public function enviarNotificacionComision($afiliado, $comision) {
        $datos = [
            'NOMBRE' => $afiliado['nombre'],
            'MONTO' => number_format($comision['monto'], 2),
            'LIBRO_TITULO' => $comision['libro_titulo'],
            'FECHA' => date('d/m/Y H:i'),
            'DASHBOARD_URL' => (APP_URL ?? 'http://localhost/publiery') . '/dashboard-afiliado.html'
        ];
        
        return $this->enviarConPlantilla(
            ['email' => $afiliado['email'], 'nombre' => $afiliado['nombre']],
            '¡Nueva comisión disponible!',
            'nueva_comision',
            $datos
        );
    }
    
    /**
     * Enviar campaña de marketing
     */
    public function enviarCampana($campana, $destinatarios) {
        $resultados = [
            'enviados' => 0,
            'errores' => 0,
            'detalles' => []
        ];
        
        foreach ($destinatarios as $destinatario) {
            $datos = [
                'NOMBRE' => $destinatario['nombre'],
                'EMAIL' => $destinatario['email']
            ];
            
            // Agregar datos adicionales de la campaña si existen
            if (!empty($campana['datos_adicionales'])) {
                $datosAdicionales = json_decode($campana['datos_adicionales'], true);
                if ($datosAdicionales) {
                    $datos = array_merge($datos, $datosAdicionales);
                }
            }
            
            $resultado = $this->enviarConPlantilla(
                ['email' => $destinatario['email'], 'nombre' => $destinatario['nombre']],
                $campana['contenido_asunto'],
                'campana_custom',
                $datos
            );
            
            if ($resultado['success']) {
                $resultados['enviados']++;
            } else {
                $resultados['errores']++;
                $resultados['detalles'][] = [
                    'email' => $destinatario['email'],
                    'error' => $resultado['error']
                ];
            }
            
            // Pequeña pausa para no sobrecargar el servidor SMTP
            usleep(100000); // 0.1 segundos
        }
        
        return $resultados;
    }
    
    /**
     * Obtener estadísticas de emails enviados
     */
    public function obtenerEstadisticas($fechaDesde = null, $fechaHasta = null) {
        try {
            $where = "";
            $params = [];
            
            if ($fechaDesde && $fechaHasta) {
                $where = "WHERE fecha_envio BETWEEN ? AND ?";
                $params = [$fechaDesde, $fechaHasta];
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN exitoso = 1 THEN 1 ELSE 0 END) as enviados_exitosos,
                    SUM(CASE WHEN exitoso = 0 THEN 1 ELSE 0 END) as enviados_fallidos,
                    plantilla,
                    COUNT(*) as por_plantilla
                FROM email_logs 
                {$where}
                GROUP BY plantilla
                ORDER BY por_plantilla DESC
            ");
            
            $stmt->execute($params);
            $estadisticas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Estadísticas generales
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN exitoso = 1 THEN 1 ELSE 0 END) as exitosos,
                    SUM(CASE WHEN exitoso = 0 THEN 1 ELSE 0 END) as fallidos
                FROM email_logs 
                {$where}
            ");
            
            $stmt->execute($params);
            $general = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'general' => $general,
                'por_plantilla' => $estadisticas
            ];
            
        } catch (Exception $e) {
            error_log("Error obteniendo estadísticas de email: " . $e->getMessage());
            return null;
        }
    }
}

// Funciones de utilidad para usar en otros archivos
function enviarEmailBienvenida($usuario) {
    $emailService = new EmailAvanzado();
    return $emailService->enviarBienvenida($usuario);
}

function enviarEmailVenta($destinatario, $venta) {
    $emailService = new EmailAvanzado();
    return $emailService->enviarNotificacionVenta($destinatario, $venta);
}

function enviarEmailComision($afiliado, $comision) {
    $emailService = new EmailAvanzado();
    return $emailService->enviarNotificacionComision($afiliado, $comision);
}
?>
