# Contenido Dinámico - Publiery

## Descripción

Se ha implementado un sistema de contenido dinámico para la página principal (`index.html`) que permite cargar testimonios y estadísticas de la comunidad desde archivos JSON, con la posibilidad de migrar fácilmente a datos reales de la base de datos en el futuro.

## Características Implementadas

### 1. Testimonios Dinámicos
- **Ubicación**: `api/testimonios/`
- **Archivos**:
  - `testimonios.json` - Datos de testimonios
  - `obtener_testimonios.php` - Endpoint para obtener testimonios

**Estructura del JSON de testimonios:**
```json
{
  "testimonios": [
    {
      "id": 1,
      "nombre": "Ana",
      "rol": "Escritora",
      "texto": "Gracias a TuPlataforma, mis libros han llegado a lectores de todo el mundo.",
      "imagen": "images/ana_torres.jpeg",
      "activo": true
    }
  ]
}
```

### 2. Estadísticas Dinámicas
- **Ubicación**: `api/estadisticas/`
- **Archivos**:
  - `estadisticas.json` - Datos de estadísticas
  - `obtener_estadisticas.php` - Endpoint para obtener estadísticas

**Estructura del JSON de estadísticas:**
```json
{
  "estadisticas": [
    {
      "id": 1,
      "titulo": "Autores Publicando",
      "valor": 1500,
      "sufijo": "+",
      "icono": "📚",
      "activo": true
    }
  ]
}
```

### 3. JavaScript Dinámico
- **Archivo**: `js/index.js`
- **Funcionalidades**:
  - Carga automática de testimonios y estadísticas
  - Animaciones de contadores
  - Manejo de errores
  - Compatibilidad con AOS (Animate On Scroll)

## Cómo Funciona

1. **Carga Automática**: Al cargar `index.html`, el JavaScript detecta automáticamente las secciones de testimonios y estadísticas
2. **Llamadas AJAX**: Se realizan peticiones a los endpoints PHP para obtener los datos
3. **Renderizado Dinámico**: Los datos se insertan en el DOM con las animaciones correspondientes
4. **Fallback**: Si hay errores, se mantiene el contenido estático original

## Ventajas del Sistema

### ✅ Flexibilidad
- Fácil modificación de contenido sin tocar el HTML
- Agregar/quitar testimonios o estadísticas sin cambios de código

### ✅ Escalabilidad
- Estructura preparada para migrar a base de datos real
- Endpoints reutilizables para otras páginas

### ✅ Mantenimiento
- Separación clara entre contenido y presentación
- Archivos JSON fáciles de editar

### ✅ Performance
- Carga asíncrona sin bloquear la página
- Animaciones optimizadas

## Cómo Usar

### Para Modificar Testimonios:
1. Edita `api/testimonios/testimonios.json`
2. Cambia el campo `activo` a `false` para ocultar un testimonio
3. Agrega nuevos testimonios siguiendo la estructura

### Para Modificar Estadísticas:
1. Edita `api/estadisticas/estadisticas.json`
2. Cambia valores, títulos o iconos
3. Agrega nuevas estadísticas siguiendo la estructura

### Para Agregar Imágenes:
1. Sube las imágenes a la carpeta `images/`
2. Referencia la ruta en el campo `imagen` del JSON

## Migración Futura a Base de Datos

Cuando se quiera migrar a datos reales:

1. **Modificar endpoints PHP** para consultar la base de datos
2. **Mantener la misma estructura JSON** de respuesta
3. **No cambiar el frontend** - seguirá funcionando igual

### Ejemplo de migración:
```php
// En lugar de cargar JSON, consultar BD
$query = "SELECT * FROM testimonios WHERE activo = 1 LIMIT 3";
$result = $conn->query($query);
$testimonios = $result->fetch_all(MYSQLI_ASSOC);
```

## Archivos Creados/Modificados

### Nuevos Archivos:
- `api/testimonios/testimonios.json`
- `api/testimonios/obtener_testimonios.php`
- `api/estadisticas/estadisticas.json`
- `api/estadisticas/obtener_estadisticas.php`
- `js/index.js`
- `test_contenido_dinamico.php`
- `CONTENIDO_DINAMICO.md`

### Archivos Modificados:
- `index.html` - Removido contenido estático, agregado JavaScript

## Pruebas

Para verificar que todo funciona:

1. **Ejecuta**: `http://localhost/publiery/test_contenido_dinamico.php`
2. **Abre**: `http://localhost/publiery/index.html`
3. **Verifica**: Que los testimonios y estadísticas se cargan dinámicamente

## Notas Técnicas

- **CORS**: Los endpoints incluyen headers para permitir peticiones desde el frontend
- **Error Handling**: Manejo robusto de errores en PHP y JavaScript
- **Performance**: Límite de 3 testimonios y 6 estadísticas para optimizar carga
- **Responsive**: Las animaciones y estilos son completamente responsivos 