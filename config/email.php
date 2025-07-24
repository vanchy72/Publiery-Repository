<?php
/**
 * Configuración y Clase de Email - Publiery
 * Sistema de emails automáticos para la plataforma
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once 'database.php';

// Configuración de Email
define('EMAIL_FROM_NAME', 'Publiery');
define('EMAIL_FROM_ADDRESS', 'publierycompany@gmail.com');
define('EMAIL_REPLY_TO', 'publierycompany@gmail.com');

// Configuración SMTP (usar las definidas en database.php)
// SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS ya están definidas

class EmailService {
    private $smtp_host;
    private $smtp_port;
    private $smtp_user;
    private $smtp_pass;
    private $from_name;
    private $from_address;
    
    public function __construct() {
        $this->smtp_host = defined('SMTP_HOST') ? SMTP_HOST : 'sandbox.smtp.mailtrap.io';
        $this->smtp_port = defined('SMTP_PORT') ? SMTP_PORT : 2525;
        $this->smtp_user = '3cb91099cfe39c';
        $this->smtp_pass = '10a11701f1a03e';
        $this->from_name = Publiery;
        $this->from_address = EMAIL_FROM_ADDRESS;
    }
    
    /**
     * Enviar email usando PHPMailer o función mail() nativa
     */
    public function sendEmail($to, $subject, $body, $isHTML = true) {
        // Intentar usar PHPMailer si está disponible
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return $this->sendWithPHPMailer($to, $subject, $body, $isHTML);
        } else {
            return $this->sendWithNativeMail($to, $subject, $body, $isHTML);
        }
    }
    
    /**
     * Enviar email usando PHPMailer (recomendado)
     */
    private function sendWithPHPMailer($to, $subject, $body, $isHTML) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Configuración del servidor
            $mail->isSMTP();
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_user;
            $mail->Password = $this->smtp_pass;
            $mail->SMTPSecure = 'tls';
            $mail->Port = $this->smtp_port;
            $mail->CharSet = 'UTF-8';
            
            // Remitente (usar el mismo que el usuario SMTP)
            $mail->setFrom($this->smtp_user, $this->from_name);
            $mail->addReplyTo(EMAIL_REPLY_TO, $this->from_name);
            
            // Destinatario
            $mail->addAddress($to);
            
            // Contenido
            $mail->isHTML($isHTML);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            if (!$isHTML) {
                $mail->AltBody = strip_tags($body);
            }
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Error PHPMailer: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enviar email usando función mail() nativa
     */
    private function sendWithNativeMail($to, $subject, $body, $isHTML) {
        $headers = [];
        $headers[] = 'From: ' . $this->from_name . ' <' . $this->from_address . '>';
        $headers[] = 'Reply-To: ' . EMAIL_REPLY_TO;
        $headers[] = 'MIME-Version: 1.0';
        
        if ($isHTML) {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
        } else {
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        }
        
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        
        return mail($to, $subject, $body, implode("\r\n", $headers));
    }
    
    /**
     * Enviar email de bienvenida tras registro
     */
    public function sendWelcomeEmail($userData) {
        $subject = '¡Bienvenido a Publiery! Tu cuenta ha sido creada exitosamente';
        $body = $this->getWelcomeEmailTemplate($userData);
        
        return $this->sendEmail($userData['email'], $subject, $body);
    }
    
    /**
     * Enviar email de activación de afiliado
     */
    public function sendAffiliateActivationEmail($userData) {
        $subject = '¡Felicidades! Tu cuenta de afiliado ha sido activada';
        $body = $this->getAffiliateActivationTemplate($userData);
        
        return $this->sendEmail($userData['email'], $subject, $body);
    }
    
    /**
     * Enviar email de recordatorio de activación
     */
    public function sendActivationReminderEmail($userData) {
        $subject = 'Activa tu cuenta de afiliado - No pierdas la oportunidad';
        $body = $this->getActivationReminderTemplate($userData);
        
        return $this->sendEmail($userData['email'], $subject, $body);
    }
    
    /**
     * Enviar email de nueva venta (para afiliados)
     */
    public function sendNewSaleEmail($userData, $saleData) {
        $subject = '¡Nueva venta registrada! Comisión generada';
        $body = $this->getNewSaleTemplate($userData, $saleData);
        
        return $this->sendEmail($userData['email'], $subject, $body);
    }
    
    /**
     * Template de email de bienvenida
     */
    public function getWelcomeEmailTemplate($userData) {
        $rolText = $this->getRolText($userData['rol']);
        $activationInfo = '';
        
        if ($userData['rol'] === 'afiliado') {
            $activationInfo = '
                <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 8px; margin: 20px 0;">
                    <h3 style="color: #856404; margin: 0 0 10px 0;">📋 Próximos pasos para activar tu cuenta:</h3>
                    <ul style="color: #856404; margin: 0; padding-left: 20px;">
                        <li>Realiza tu primera compra en nuestra tienda</li>
                        <li>Tu cuenta se activará automáticamente</li>
                        <li>Podrás empezar a generar comisiones</li>
                        <li>Tienes 3 días para activarte</li>
                    </ul>
                </div>';
        }
        
        return '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Bienvenido a Publiery</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 30px;">
                <h1 style="color: white; margin: 0; font-size: 28px;">¡Bienvenido a Publiery!</h1>
                <p style="color: white; margin: 10px 0 0 0; font-size: 16px;">Tu plataforma de libros digitales y afiliados</p>
            </div>
            
            <div style="background-color: #f8f9fa; padding: 25px; border-radius: 8px; margin-bottom: 20px;">
                <h2 style="color: #2c3e50; margin: 0 0 15px 0;">¡Hola ' . htmlspecialchars($userData['nombre']) . '!</h2>
                <p style="margin: 0 0 15px 0;">Nos complace darte la bienvenida a <strong>Publiery</strong>, tu nueva plataforma para descubrir, leer y promocionar libros digitales.</p>
                <p style="margin: 0 0 15px 0;">Tu cuenta ha sido creada exitosamente como <strong>' . $rolText . '</strong>.</p>
            </div>
            
            ' . $activationInfo . '
            
            <div style="background-color: #e8f5e8; border: 1px solid #c3e6c3; padding: 15px; border-radius: 8px; margin: 20px 0;">
                <h3 style="color: #155724; margin: 0 0 10px 0;">🎯 ¿Qué puedes hacer ahora?</h3>
                <ul style="color: #155724; margin: 0; padding-left: 20px;">
                    <li>Explorar nuestra biblioteca de libros digitales</li>
                    <li>Realizar compras seguras con múltiples métodos de pago</li>
                    <li>Acceder a contenido exclusivo y de calidad</li>
                    <li>Conectar con otros miembros de la comunidad</li>
                </ul>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="' . APP_URL . '/tienda.html" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; display: inline-block; font-weight: bold;">Explorar Tienda</a>
            </div>
            
            <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 30px;">
                <h3 style="color: #2c3e50; margin: 0 0 15px 0;">📞 ¿Necesitas ayuda?</h3>
                <p style="margin: 0 0 10px 0;">Nuestro equipo de soporte está aquí para ayudarte:</p>
                <ul style="margin: 0; padding-left: 20px;">
                    <li>Email: soporte@publiery.com</li>
                    <li>Horario: Lunes a Viernes, 9:00 AM - 6:00 PM</li>
                </ul>
            </div>
            
            <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                <p style="color: #666; margin: 0; font-size: 14px;">
                    © 2024 Publiery. Todos los derechos reservados.<br>
                    Este email fue enviado a ' . htmlspecialchars($userData['email']) . '
                </p>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Template de email de activación de afiliado
     */
    public function getAffiliateActivationTemplate($userData) {
        return '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>¡Cuenta Activada!</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%); padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 30px;">
                <h1 style="color: white; margin: 0; font-size: 28px;">🎉 ¡Felicidades!</h1>
                <p style="color: white; margin: 10px 0 0 0; font-size: 16px;">Tu cuenta de afiliado ha sido activada</p>
            </div>
            
            <div style="background-color: #f8f9fa; padding: 25px; border-radius: 8px; margin-bottom: 20px;">
                <h2 style="color: #2c3e50; margin: 0 0 15px 0;">¡Hola ' . htmlspecialchars($userData['nombre']) . '!</h2>
                <p style="margin: 0 0 15px 0;">¡Excelente noticia! Tu cuenta de afiliado ha sido <strong>activada exitosamente</strong> y ya puedes empezar a generar comisiones.</p>
            </div>
            
            <div style="background-color: #e8f5e8; border: 1px solid #c3e6c3; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h3 style="color: #155724; margin: 0 0 15px 0;">🚀 ¡Ya puedes empezar a ganar!</h3>
                <div style="background-color: white; padding: 15px; border-radius: 8px; margin: 10px 0;">
                    <p style="margin: 0 0 10px 0;"><strong>Código de Afiliado:</strong> <span style="background-color: #f8f9fa; padding: 5px 10px; border-radius: 4px; font-family: monospace;">' . htmlspecialchars($userData['codigo_afiliado']) . '</span></p>
                    <p style="margin: 0 0 10px 0;"><strong>Nivel:</strong> ' . htmlspecialchars($userData['nivel']) . '</p>
                    <p style="margin: 0;"><strong>Comisión por venta:</strong> 30%</p>
                </div>
            </div>
            
            <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 8px; margin: 20px 0;">
                <h3 style="color: #856404; margin: 0 0 10px 0;">💡 Próximos pasos:</h3>
                <ul style="color: #856404; margin: 0; padding-left: 20px;">
                    <li>Comparte tu código de afiliado con otros</li>
                    <li>Promociona nuestros libros en tus redes sociales</li>
                    <li>Construye tu red de afiliados</li>
                    <li>Monitorea tus ganancias en tu dashboard</li>
                </ul>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="' . APP_URL . '/dashboard-afiliado.html" style="background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; display: inline-block; font-weight: bold;">Ir al Dashboard</a>
            </div>
            
            <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 30px;">
                <h3 style="color: #2c3e50; margin: 0 0 15px 0;">📊 Herramientas disponibles:</h3>
                <ul style="margin: 0; padding-left: 20px;">
                    <li>Dashboard con estadísticas en tiempo real</li>
                    <li>Enlaces de afiliado personalizados</li>
                    <li>Reportes de comisiones detallados</li>
                    <li>Sistema de retiros automáticos</li>
                </ul>
            </div>
            
            <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                <p style="color: #666; margin: 0; font-size: 14px;">
                    © 2024 Publiery. Todos los derechos reservados.<br>
                    Este email fue enviado a ' . htmlspecialchars($userData['email']) . '
                </p>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Template de recordatorio de activación
     */
    public function getActivationReminderTemplate($userData) {
        return '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Activa tu cuenta - No pierdas la oportunidad</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 30px;">
                <h1 style="color: white; margin: 0; font-size: 28px;">⏰ ¡No pierdas la oportunidad!</h1>
                <p style="color: white; margin: 10px 0 0 0; font-size: 16px;">Activa tu cuenta de afiliado antes de que expire</p>
            </div>
            
            <div style="background-color: #f8f9fa; padding: 25px; border-radius: 8px; margin-bottom: 20px;">
                <h2 style="color: #2c3e50; margin: 0 0 15px 0;">¡Hola ' . htmlspecialchars($userData['nombre']) . '!</h2>
                <p style="margin: 0 0 15px 0;">Notamos que aún no has activado tu cuenta de afiliado. ¡No dejes pasar esta gran oportunidad de empezar a generar ingresos!</p>
            </div>
            
            <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h3 style="color: #856404; margin: 0 0 15px 0;">🎯 ¿Por qué activar tu cuenta?</h3>
                <ul style="color: #856404; margin: 0; padding-left: 20px;">
                    <li>Gana 30% de comisión por cada venta</li>
                    <li>Construye tu red de afiliados</li>
                    <li>Genera ingresos pasivos</li>
                    <li>Accede a herramientas exclusivas</li>
                </ul>
            </div>
            
            <div style="background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 8px; margin: 20px 0;">
                <h3 style="color: #721c24; margin: 0 0 10px 0;">⚠️ Importante:</h3>
                <p style="color: #721c24; margin: 0;">Tu cuenta se inactivará automáticamente si no la activas en los próximos días. ¡No pierdas esta oportunidad!</p>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="' . APP_URL . '/tienda.html" style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; display: inline-block; font-weight: bold;">Activar mi cuenta</a>
            </div>
            
            <div style="background-color: #e8f5e8; border: 1px solid #c3e6c3; padding: 15px; border-radius: 8px; margin: 20px 0;">
                <h3 style="color: #155724; margin: 0 0 10px 0;">💡 ¿Cómo activar?</h3>
                <ol style="color: #155724; margin: 0; padding-left: 20px;">
                    <li>Visita nuestra tienda</li>
                    <li>Selecciona un libro que te interese</li>
                    <li>Completa la compra</li>
                    <li>¡Tu cuenta se activará automáticamente!</li>
                </ol>
            </div>
            
            <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                <p style="color: #666; margin: 0; font-size: 14px;">
                    © 2024 Publiery. Todos los derechos reservados.<br>
                    Este email fue enviado a ' . htmlspecialchars($userData['email']) . '
                </p>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Template de nueva venta
     */
    public function getNewSaleTemplate($userData, $saleData) {
        return '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>¡Nueva venta registrada!</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%); padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 30px;">
                <h1 style="color: white; margin: 0; font-size: 28px;">💰 ¡Nueva venta!</h1>
                <p style="color: white; margin: 10px 0 0 0; font-size: 16px;">Has generado una nueva comisión</p>
            </div>
            
            <div style="background-color: #f8f9fa; padding: 25px; border-radius: 8px; margin-bottom: 20px;">
                <h2 style="color: #2c3e50; margin: 0 0 15px 0;">¡Felicidades ' . htmlspecialchars($userData['nombre']) . '!</h2>
                <p style="margin: 0 0 15px 0;">Se ha registrado una nueva venta a través de tu enlace de afiliado.</p>
            </div>
            
            <div style="background-color: #e8f5e8; border: 1px solid #c3e6c3; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h3 style="color: #155724; margin: 0 0 15px 0;">📊 Detalles de la venta:</h3>
                <div style="background-color: white; padding: 15px; border-radius: 8px; margin: 10px 0;">
                    <p style="margin: 0 0 10px 0;"><strong>Libro:</strong> ' . htmlspecialchars($saleData['libro_nombre']) . '</p>
                    <p style="margin: 0 0 10px 0;"><strong>Valor de venta:</strong> $' . number_format($saleData['valor_venta'], 2) . '</p>
                    <p style="margin: 0 0 10px 0;"><strong>Tu comisión (30%):</strong> $' . number_format($saleData['comision'], 2) . '</p>
                    <p style="margin: 0;"><strong>Fecha:</strong> ' . date('d/m/Y H:i', strtotime($saleData['fecha'])) . '</p>
                </div>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="' . APP_URL . '/dashboard-afiliado.html" style="background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; display: inline-block; font-weight: bold;">Ver mi Dashboard</a>
            </div>
            
            <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 30px;">
                <h3 style="color: #2c3e50; margin: 0 0 15px 0;">💡 Consejos para más ventas:</h3>
                <ul style="margin: 0; padding-left: 20px;">
                    <li>Comparte en tus redes sociales</li>
                    <li>Escribe reseñas de los libros</li>
                    <li>Usa tu enlace de afiliado en todas partes</li>
                    <li>Construye tu red de afiliados</li>
                </ul>
            </div>
            
            <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                <p style="color: #666; margin: 0; font-size: 14px;">
                    © 2024 Publiery. Todos los derechos reservados.<br>
                    Este email fue enviado a ' . htmlspecialchars($userData['email']) . '
                </p>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Obtener texto del rol
     */
    private function getRolText($rol) {
        switch ($rol) {
            case 'afiliado':
                return 'Afiliado';
            case 'escritor':
                return 'Escritor';
            case 'lector':
                return 'Lector';
            default:
                return ucfirst($rol);
        }
    }
}

// Función global para enviar emails
function sendEmail($to, $subject, $body, $isHTML = true) {
    $emailService = new EmailService();
    return $emailService->sendEmail($to, $subject, $body, $isHTML);
}

// Función para enviar email de bienvenida
function sendWelcomeEmail($userData) {
    $emailService = new EmailService();
    return $emailService->sendWelcomeEmail($userData);
}

// Función para enviar email de activación de afiliado
function sendAffiliateActivationEmail($userData) {
    $emailService = new EmailService();
    return $emailService->sendAffiliateActivationEmail($userData);
}

// Función para enviar recordatorio de activación
function sendActivationReminderEmail($userData) {
    $emailService = new EmailService();
    return $emailService->sendActivationReminderEmail($userData);
}

// Función para enviar email de nueva venta
function sendNewSaleEmail($userData, $saleData) {
    $emailService = new EmailService();
    return $emailService->sendNewSaleEmail($userData, $saleData);
}
?> 