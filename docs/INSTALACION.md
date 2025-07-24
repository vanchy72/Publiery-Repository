# 🚀 GUÍA DE INSTALACIÓN - TuPlataforma con MySQL

## 📋 Requisitos Previos

### Software Necesario:
- **XAMPP** (versión 8.0 o superior)
- **Navegador web** (Chrome, Firefox, Safari, Edge)
- **Editor de código** (VS Code, Sublime Text, etc.)

### Requisitos del Sistema:
- **Windows 10/11** (recomendado)
- **4GB RAM** mínimo
- **2GB espacio libre** en disco

---

## 🔧 PASO 1: Instalar XAMPP

### 1.1 Descargar XAMPP
1. Ve a [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Descarga la versión para Windows
3. Ejecuta el instalador como administrador

### 1.2 Instalar XAMPP
1. **Ejecutar instalador** como administrador
2. **Seleccionar componentes:**
   - ✅ Apache
   - ✅ MySQL
   - ✅ PHP
   - ✅ phpMyAdmin
3. **Elegir directorio de instalación:** `C:\xampp`
4. **Completar instalación**

### 1.3 Verificar Instalación
1. Abrir **XAMPP Control Panel**
2. Iniciar **Apache** y **MySQL**
3. Verificar que ambos muestren **Status: Running**

---

## 📁 PASO 2: Configurar el Proyecto

### 2.1 Copiar Archivos
1. Copia todo el contenido del proyecto a:
   ```
   C:\xampp\htdocs\tuplataforma\
   ```

### 2.2 Estructura de Carpetas
Tu proyecto debe quedar así:
```
C:\xampp\htdocs\tuplataforma\
├── api/
├── config/
├── css/
├── database/
├── images/
├── js/
├── index.html
├── login.html
├── registro.html
└── ... (otros archivos)
```

---

## 🗄️ PASO 3: Configurar Base de Datos

### 3.1 Acceder a phpMyAdmin
1. Abre tu navegador
2. Ve a: `http://localhost/phpmyadmin`
3. Usuario: `root`
4. Contraseña: (dejar vacío)

### 3.2 Crear Base de Datos
1. **Crear nueva base de datos:**
   - Nombre: `tuplataforma_db`
   - Cotejamiento: `utf8mb4_unicode_ci`
   - Clic en "Crear"

### 3.3 Importar Esquema
1. **Seleccionar** la base de datos `tuplataforma_db`
2. **Ir a pestaña** "Importar"
3. **Seleccionar archivo:** `database/schema.sql`
4. **Clic en** "Continuar"

### 3.4 Verificar Importación
Deberías ver estas tablas creadas:
- ✅ `usuarios`
- ✅ `afiliados`
- ✅ `libros`
- ✅ `ventas`
- ✅ `comisiones`
- ✅ `retiros`
- ✅ `campanas`
- ✅ `activaciones`
- ✅ `sesiones`

---

## ⚙️ PASO 4: Configurar PHP

### 4.1 Verificar Configuración
1. Abrir: `C:\xampp\php\php.ini`
2. Verificar estas configuraciones:
   ```ini
   extension=pdo_mysql
   extension=mysqli
   extension=openssl
   extension=mbstring
   ```

### 4.2 Configurar Límites
En el mismo archivo `php.ini`:
```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
memory_limit = 256M
```

### 4.3 Reiniciar Apache
1. En **XAMPP Control Panel**
2. **Stop** Apache
3. **Start** Apache

---

## 🔐 PASO 5: Configurar Seguridad

### 5.1 Configurar Base de Datos
1. Abrir: `config/database.php`
2. Verificar configuración:
   ```php
   private $host = 'localhost';
   private $db_name = 'tuplataforma_db';
   private $username = 'root';
   private $password = '';
   ```

### 5.2 Configurar URL de la Aplicación
En `config/database.php`, cambiar:
```php
define('APP_URL', 'http://localhost/tuplataforma');
```

### 5.3 Configurar Clave Secreta
Cambiar la clave JWT_SECRET por una más segura:
```php
define('JWT_SECRET', 'tu_clave_secreta_muy_segura_aqui_2024');
```

---

## 🧪 PASO 6: Probar la Instalación

### 6.1 Acceder a la Aplicación
1. Abrir navegador
2. Ir a: `http://localhost/tuplataforma`
3. Deberías ver la página principal

### 6.2 Probar Registro
1. Ir a: `http://localhost/tuplataforma/registro.html`
2. Crear una cuenta de prueba
3. Verificar que se guarde en la base de datos

### 6.3 Probar Login
1. Ir a: `http://localhost/tuplataforma/login.html`
2. Iniciar sesión con la cuenta creada
3. Verificar redirección al dashboard

### 6.4 Verificar Dashboard
1. Deberías ver el dashboard del afiliado
2. Verificar que carguen los datos desde la base de datos

---

## 🔧 PASO 7: Solución de Problemas

### 7.1 Error de Conexión a Base de Datos
**Síntoma:** Error "Error de conexión a la base de datos"

**Solución:**
1. Verificar que MySQL esté corriendo en XAMPP
2. Verificar configuración en `config/database.php`
3. Verificar que la base de datos existe

### 7.2 Error 404 - Página no encontrada
**Síntoma:** Error 404 al acceder a la aplicación

**Solución:**
1. Verificar que Apache esté corriendo
2. Verificar ruta del proyecto: `C:\xampp\htdocs\tuplataforma\`
3. Verificar URL: `http://localhost/tuplataforma`

### 7.3 Error de Permisos
**Síntoma:** Error al subir archivos o crear directorios

**Solución:**
1. Dar permisos de escritura a la carpeta del proyecto
2. Verificar configuración de PHP para uploads

### 7.4 Error de Sesión
**Síntoma:** No se mantiene la sesión

**Solución:**
1. Verificar configuración de sesiones en PHP
2. Verificar que las cookies estén habilitadas
3. Verificar configuración de JWT_SECRET

---

## 📊 PASO 8: Datos de Prueba

### 8.1 Usuarios de Prueba
La base de datos incluye estos usuarios de prueba:

| Email | Contraseña | Rol |
|-------|------------|-----|
| `admin@tuplataforma.com` | `password` | Admin |
| `laura@email.com` | `password` | Afiliado |
| `carlos@email.com` | `password` | Afiliado |
| `ana@email.com` | `password` | Escritor |
| `luis@email.com` | `password` | Lector |

### 8.2 Libros de Prueba
- "El Camino del Éxito" - $29.99
- "Marketing Digital Avanzado" - $39.99
- "Inversiones Inteligentes" - $49.99

---

## 🚀 PASO 9: Próximos Pasos

### 9.1 Configuración de Email
Para activar el envío de emails:
1. Configurar SMTP en `config/database.php`
2. Crear cuenta de email para la aplicación
3. Configurar credenciales SMTP

### 9.2 Configuración de Pagos
Para integrar pasarelas de pago:
1. Crear cuentas en pasarelas (PayPal, Stripe, etc.)
2. Configurar webhooks
3. Implementar endpoints de pago

### 9.3 Configuración de SSL
Para producción:
1. Obtener certificado SSL
2. Configurar HTTPS
3. Actualizar URLs en la configuración

---

## 📞 Soporte

### Si tienes problemas:
1. **Verificar logs** de Apache: `C:\xampp\apache\logs\error.log`
2. **Verificar logs** de PHP: `C:\xampp\php\logs\php_error_log`
3. **Verificar logs** de MySQL: `C:\xampp\mysql\data\mysql_error.log`

### Comandos útiles:
```bash
# Verificar estado de servicios
netstat -an | findstr :80
netstat -an | findstr :3306

# Reiniciar servicios
net stop apache2
net start apache2
```

---

## ✅ Verificación Final

### Checklist de Instalación:
- [ ] XAMPP instalado y funcionando
- [ ] Apache y MySQL corriendo
- [ ] Proyecto copiado a htdocs
- [ ] Base de datos creada e importada
- [ ] Configuración PHP verificada
- [ ] Aplicación accesible en navegador
- [ ] Registro y login funcionando
- [ ] Dashboard cargando datos
- [ ] No hay errores en consola

### ¡Felicidades! 🎉
Tu plataforma está lista para usar. Puedes comenzar a:
- Registrar nuevos usuarios
- Crear campañas
- Subir libros
- Probar el sistema de afiliados

---

## 🔄 Actualizaciones

Para actualizar el proyecto:
1. Hacer backup de la base de datos
2. Reemplazar archivos del proyecto
3. Ejecutar scripts de migración si los hay
4. Verificar que todo funcione correctamente

---

**¡Disfruta usando TuPlataforma! 🚀** 