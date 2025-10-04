# 📧 Sistema de Emails Automáticos - Publiery

## 📋 Descripción General

El sistema de emails automáticos de Publiery permite enviar notificaciones automáticas a los usuarios en diferentes momentos del flujo de la aplicación. El sistema está diseñado para ser robusto, escalable y fácil de mantener.

## 🏗️ Arquitectura

### Archivos Principales

- **`config/email.php`** - Clase principal y configuración del sistema de emails
- **`config/database.php`** - Configuración SMTP (líneas 50-54)
- **`api/auth/register.php`** - Envío de email de bienvenida tras registro
- **`api/ventas/registrar_venta.php`** - Envío de email de activación tras primera compra
- **`api/afiliados/enviar_recordatorios.php`** - Script para recordatorios automáticos

### Dependencias

- **PHPMailer** (opcional, recomendado para producción)
- **Función mail() nativa de PHP** (fallback)

## ⚙️ Configuración

### 1. Configuración SMTP

Edita `config/database.php` y configura las siguientes constantes:

```php
// Configuración de email
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'publierycompany@gmail.com');
define('SMTP_PASS', 'mkmlqfblxsruozxj');
```

### 2. Configuración de Remitente

En `config/email.php`:

```php
define('EMAIL_FROM_NAME', 'Publiery');
define('EMAIL_FROM_ADDRESS', 'noreply@publiery.com');
define('EMAIL_REPLY_TO', 'soporte@publiery.com');
```

### 3. Configuración para Gmail

Si usas Gmail:

1. Habilita la verificación en 2 pasos
2. Genera una contraseña de aplicación
3. Usa esa contraseña en `SMTP_PASS`

## 📧 Tipos de Emails

### 1. Email de Bienvenida
- **Cuándo se envía:** Tras registro exitoso
- **Destinatarios:** Todos los usuarios nuevos
- **Contenido:** Bienvenida, información del rol, próximos pasos
- **Función:** `sendWelcomeEmail($userData)`

### 2. Email de Activación de Afiliado
- **Cuándo se envía:** Tras primera compra de un afiliado
- **Destinatarios:** Afiliados que se activan
- **Contenido:** Felicitaciones, código de afiliado, herramientas disponibles
- **Función:** `sendAffiliateActivationEmail($userData)`

### 3. Email de Recordatorio de Activación
- **Cuándo se envía:** A afiliados pendientes (configurable)
- **Destinatarios:** Afiliados que no se han activado
- **Contenido:** Recordatorio, incentivos, instrucciones de activación
- **Función:** `sendActivationReminderEmail($userData)`

### 4. Email de Nueva Venta
- **Cuándo se envía:** Tras cada venta generada por un afiliado
- **Destinatarios:** Afiliados que generan comisiones
- **Contenido:** Detalles de la venta, comisión generada
- **Función:** `sendNewSaleEmail($userData, $saleData)`

## 🧪 Pruebas

### Script de Prueba

Ejecuta `test_emails.php` para probar todos los tipos de emails:

```bash
http://localhost/publiery/test_emails.php
```

### Configuración de Prueba

1. Edita `test_emails.php`
2. Cambia `$testEmail = 'tu_email@ejemplo.com'`
3. Ejecuta el script
4. Revisa tu email

## 🔄 Automatización

### Recordatorios Automáticos

Para enviar recordatorios automáticamente, configura un cron job:

```bash
# Enviar recordatorios diariamente a las 9:00 AM
0 9 * * * curl http://localhost/publiery/api/afiliados/enviar_recordatorios.php
```

### Inactivación Automática

Para inactivar afiliados que no se activan:

```bash
# Ejecutar cada 3 días a las 6:00 AM
0 6 */3 * * curl http://localhost/publiery/api/afiliados/inactivar_pendientes.php
```

## 📊 Monitoreo y Logs

### Logs de Actividad

Todos los envíos de emails se registran en la tabla `log_actividad`:

```sql
SELECT * FROM log_actividad 
WHERE accion LIKE '%email%' 
ORDER BY fecha_creacion DESC;
```

### Logs de Error

Los errores se registran en el log de PHP:

```bash
tail -f /var/log/apache2/error.log
```

## 🛠️ Mantenimiento

### Verificar Estado del Sistema

```php
// Verificar configuración SMTP
$emailService = new EmailService();
$config = [
    'host' => $emailService->smtp_host,
    'port' => $emailService->smtp_port,
    'user' => $emailService->smtp_user
];
```

### Limpiar Emails Fallidos

```sql
-- Ver emails que fallaron en las últimas 24 horas
SELECT * FROM log_actividad 
WHERE accion LIKE '%email%' 
AND detalles LIKE '%error%'
AND fecha_creacion > DATE_SUB(NOW(), INTERVAL 1 DAY);
```

## 🔧 Personalización

### Modificar Templates

Los templates están en `config/email.php` en las funciones:

- `getWelcomeEmailTemplate()`
- `getAffiliateActivationTemplate()`
- `getActivationReminderTemplate()`
- `getNewSaleTemplate()`

### Agregar Nuevos Tipos de Email

1. Crear nueva función en `EmailService`
2. Crear template correspondiente
3. Crear función global
4. Integrar en el flujo correspondiente

### Ejemplo de Nuevo Email

```php
public function sendPasswordResetEmail($userData) {
    $subject = 'Restablecer contraseña - Publiery';
    $body = $this->getPasswordResetTemplate($userData);
    return $this->sendEmail($userData['email'], $subject, $body);
}

private function getPasswordResetTemplate($userData) {
    return '<html>...</html>';
}
```

## 🚨 Solución de Problemas

### Error: "SMTP connection failed"

**Causas posibles:**
- Credenciales SMTP incorrectas
- Puerto bloqueado por firewall
- Servidor SMTP no disponible

**Solución:**
1. Verificar credenciales en `config/database.php`
2. Probar conexión manual
3. Verificar configuración de firewall

### Error: "Authentication failed"

**Causas posibles:**
- Usuario/contraseña incorrectos
- Verificación en 2 pasos no configurada (Gmail)
- Contraseña de aplicación no generada

**Solución:**
1. Verificar credenciales
2. Configurar verificación en 2 pasos
3. Generar contraseña de aplicación

### Emails no llegan

**Causas posibles:**
- Emails en carpeta de spam
- Configuración DNS incorrecta
- Servidor SMTP en lista negra

**Solución:**
1. Revisar carpeta de spam
2. Verificar configuración DNS
3. Usar servicio SMTP confiable

## 📈 Métricas y Analytics

### Estadísticas de Envío

```sql
-- Emails enviados por tipo
SELECT 
    accion,
    COUNT(*) as total,
    DATE(fecha_creacion) as fecha
FROM log_actividad 
WHERE accion LIKE '%email%'
GROUP BY accion, DATE(fecha_creacion)
ORDER BY fecha DESC;
```

### Tasa de Apertura (Futuro)

Para implementar tracking de apertura:

1. Agregar pixel de tracking en templates
2. Crear endpoint para registrar aperturas
3. Almacenar métricas en base de datos

## 🔒 Seguridad

### Buenas Prácticas

1. **No almacenar contraseñas en texto plano**
2. **Usar SMTP con TLS/SSL**
3. **Validar emails antes de enviar**
4. **Limitar frecuencia de envío**
5. **Implementar rate limiting**

### Rate Limiting

```php
// Ejemplo de rate limiting
function checkEmailRateLimit($email) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM log_actividad 
        WHERE accion LIKE '%email%' 
        AND detalles LIKE ? 
        AND fecha_creacion > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute(['%' . $email . '%']);
    $result = $stmt->fetch();
    
    return $result['count'] < 10; // Máximo 10 emails por hora
}
```

## 📞 Soporte

Para problemas con el sistema de emails:

1. Revisar logs de error
2. Ejecutar script de prueba
3. Verificar configuración SMTP
4. Contactar al equipo de desarrollo

---

**Última actualización:** Diciembre 2024  
**Versión:** 1.0.0  
**Mantenido por:** Equipo Publiery 