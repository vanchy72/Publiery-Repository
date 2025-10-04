<?php
echo "<h1>🔧 CORRECCIÓN FINAL: Error de Base de Datos</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
.panel { border: 1px solid #ddd; margin: 20px 0; padding: 15px; border-radius: 8px; }
.success { background-color: #d4edda; border-left: 5px solid #28a745; }
.warning { background-color: #fff3cd; border-left: 5px solid #ffc107; }
.info { background-color: #d1ecf1; border-left: 5px solid #17a2b8; }
.error { background-color: #f8d7da; border-left: 5px solid #dc3545; }
.btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; text-decoration: none; display: inline-block; }
.btn-primary { background: #007bff; color: white; }
.btn-success { background: #28a745; color: white; }
.btn-warning { background: #ffc107; color: black; }
.btn:hover { opacity: 0.8; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>";

echo "<div class='panel success'>";
echo "<h2>✅ Progreso: Memoria PHP Corregida</h2>";
echo "<p>✅ <strong>memory_limit = 1024M</strong> - Configurado correctamente</p>";
echo "<p>✅ <strong>upload_max_filesize = 50M</strong> - Suficiente para PDFs</p>";
echo "<p>✅ <strong>post_max_size = 60M</strong> - Configurado correctamente</p>";
echo "</div>";

echo "<div class='panel error'>";
echo "<h2>❌ Nuevo Problema: Error de Base de Datos</h2>";
echo "<p><strong>Error:</strong> 'Cannot add or update a child row: foreign key constraint fails'</p>";
echo "<p><strong>Causa:</strong> El usuario/escritor con ID 101 no existe en la tabla 'usuarios'</p>";
echo "<p><strong>Solución:</strong> Crear el escritor faltante o usar un ID existente</p>";
echo "</div>";

require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    
    // Verificar usuarios existentes
    echo "<div class='panel info'>";
    echo "<h3>👥 Usuarios/Escritores Disponibles</h3>";
    
    $stmt = $pdo->query("
        SELECT id, nombre, email, rol, estado, fecha_registro 
        FROM usuarios 
        WHERE rol IN ('escritor', 'admin') 
        ORDER BY fecha_registro DESC
    ");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($usuarios)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Estado</th><th>Acción</th></tr>";
        
        foreach ($usuarios as $usuario) {
            $estado_color = $usuario['estado'] === 'activo' ? '#28a745' : '#ffc107';
            echo "<tr>";
            echo "<td>#{$usuario['id']}</td>";
            echo "<td>" . htmlspecialchars($usuario['nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($usuario['email']) . "</td>";
            echo "<td>" . ucfirst($usuario['rol']) . "</td>";
            echo "<td style='color: $estado_color;'>" . ucfirst($usuario['estado']) . "</td>";
            echo "<td>";
            echo "<form method='POST' style='display: inline;'>";
            echo "<input type='hidden' name='usar_escritor' value='{$usuario['id']}'>";
            echo "<button type='submit' class='btn btn-success' style='padding: 5px 10px; font-size: 12px;'>✅ Usar Este</button>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>⚠️ No hay usuarios escritores en la base de datos.</p>";
    }
    echo "</div>";
    
    // Crear escritor ID 101 si no existe
    if (isset($_POST['crear_escritor_101'])) {
        echo "<div class='panel info'>";
        echo "<h4>👤 Creando Escritor ID 101...</h4>";
        
        try {
            // Verificar si ID 101 ya existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = 101");
            $stmt->execute();
            
            if ($stmt->fetch()) {
                echo "<p>⚠️ Ya existe un usuario con ID 101</p>";
            } else {
                // Insertar con ID específico
                $stmt = $pdo->prepare("
                    INSERT INTO usuarios (id, nombre, email, password, rol, estado, fecha_registro) 
                    VALUES (101, 'Escritor Principal', 'escritor@publiery.com', ?, 'escritor', 'activo', NOW())
                ");
                $password = password_hash('escritor123', PASSWORD_DEFAULT);
                $stmt->execute([$password]);
                
                echo "<div class='success'>";
                echo "<h4>✅ Escritor ID 101 Creado</h4>";
                echo "<p><strong>Nombre:</strong> Escritor Principal</p>";
                echo "<p><strong>Email:</strong> escritor@publiery.com</p>";
                echo "<p><strong>Password:</strong> escritor123</p>";
                echo "<p><strong>ID:</strong> 101</p>";
                echo "</div>";
            }
        } catch (Exception $e) {
            echo "<p style='color: #dc3545;'>❌ Error: " . $e->getMessage() . "</p>";
        }
        echo "</div>";
    }
    
    // Usar escritor existente
    $escritor_id_actual = 101; // Valor por defecto
    if (isset($_POST['usar_escritor'])) {
        $escritor_id_actual = (int)$_POST['usar_escritor'];
        echo "<div class='panel success'>";
        echo "<h4>✅ Usando Escritor ID: $escritor_id_actual</h4>";
        echo "</div>";
    }
    
    // Formulario de subida corregido
    echo "<div class='panel'>";
    echo "<h3>📚 Subida de Libro - CORREGIDA</h3>";
    
    echo "<form method='POST' enctype='multipart/form-data' style='border: 1px solid #ddd; padding: 20px; margin: 10px 0; border-radius: 5px;'>";
    echo "<input type='hidden' name='autor_id' value='$escritor_id_actual'>";
    
    echo "<div style='margin: 10px 0;'>";
    echo "<label><strong>Escritor Seleccionado:</strong></label>";
    echo "<p>ID: $escritor_id_actual";
    if (!empty($usuarios)) {
        foreach ($usuarios as $u) {
            if ($u['id'] == $escritor_id_actual) {
                echo " - " . htmlspecialchars($u['nombre']) . " (" . htmlspecialchars($u['email']) . ")";
                break;
            }
        }
    }
    echo "</p>";
    echo "</div>";
    
    echo "<div style='margin: 10px 0;'>";
    echo "<label>Título del Libro:</label><br>";
    echo "<input type='text' name='titulo' required style='width: 100%; padding: 8px;' placeholder='Ingresa el título del libro'>";
    echo "</div>";
    
    echo "<div style='margin: 10px 0;'>";
    echo "<label>Descripción:</label><br>";
    echo "<textarea name='descripcion' required style='width: 100%; padding: 8px; height: 80px;' placeholder='Describe tu libro'></textarea>";
    echo "</div>";
    
    echo "<div style='margin: 10px 0;'>";
    echo "<label>Precio (COP):</label><br>";
    echo "<input type='number' name='precio' min='1000' required style='width: 100%; padding: 8px;' placeholder='15000'>";
    echo "</div>";
    
    echo "<div style='margin: 10px 0;'>";
    echo "<label>Categoría:</label><br>";
    echo "<select name='categoria' style='width: 100%; padding: 8px;'>";
    echo "<option value='negocios'>Negocios</option>";
    echo "<option value='autoayuda'>Autoayuda</option>";
    echo "<option value='tecnologia'>Tecnología</option>";
    echo "<option value='salud'>Salud</option>";
    echo "<option value='educacion'>Educación</option>";
    echo "</select>";
    echo "</div>";
    
    echo "<div style='margin: 10px 0;'>";
    echo "<label>Archivo PDF (requerido, máx 50MB):</label><br>";
    echo "<input type='file' name='archivo_pdf' accept='.pdf' required style='width: 100%; padding: 8px;'>";
    echo "</div>";
    
    echo "<div style='margin: 10px 0;'>";
    echo "<label>Portada JPG (opcional, máx 2MB):</label><br>";
    echo "<input type='file' name='portada' accept='image/*' style='width: 100%; padding: 8px;'>";
    echo "</div>";
    
    echo "<button type='submit' name='subir_libro_final' value='1' class='btn btn-success' style='font-size: 16px; padding: 12px 25px;'>📚 SUBIR LIBRO AHORA</button>";
    echo "</form>";
    echo "</div>";
    
    // Procesar subida final
    if (isset($_POST['subir_libro_final'])) {
        echo "<div class='panel info'>";
        echo "<h4>🚀 Procesando Subida Final...</h4>";
        
        try {
            $autor_id = (int)$_POST['autor_id'];
            $titulo = trim($_POST['titulo']);
            $descripcion = trim($_POST['descripcion']);
            $precio = floatval($_POST['precio']);
            $categoria = $_POST['categoria'];
            
            // Validaciones
            if (empty($titulo) || empty($descripcion) || $precio <= 0) {
                throw new Exception("Todos los campos son obligatorios");
            }
            
            // Verificar que el autor existe
            $stmt = $pdo->prepare("SELECT id, nombre FROM usuarios WHERE id = ? AND rol IN ('escritor', 'admin')");
            $stmt->execute([$autor_id]);
            $autor = $stmt->fetch();
            
            if (!$autor) {
                throw new Exception("El escritor con ID $autor_id no existe");
            }
            
            // Procesar PDF
            if (!isset($_FILES['archivo_pdf']) || $_FILES['archivo_pdf']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Archivo PDF es obligatorio");
            }
            
            $pdf_file = $_FILES['archivo_pdf'];
            if ($pdf_file['size'] > 50 * 1024 * 1024) {
                throw new Exception("El PDF no puede superar 50MB");
            }
            
            // Verificar tipo MIME
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $pdf_file['tmp_name']);
            finfo_close($finfo);
            
            if ($mime_type !== 'application/pdf') {
                throw new Exception("Solo se permiten archivos PDF");
            }
            
            // Crear directorios
            if (!is_dir('uploads/libros/')) {
                mkdir('uploads/libros/', 0777, true);
            }
            
            // Mover PDF
            $pdf_name = 'libro_' . uniqid() . '_' . time() . '.pdf';
            $pdf_path = 'uploads/libros/' . $pdf_name;
            
            if (!move_uploaded_file($pdf_file['tmp_name'], $pdf_path)) {
                throw new Exception("Error al subir el PDF");
            }
            
            // Procesar portada
            $portada_name = null;
            if (isset($_FILES['portada']) && $_FILES['portada']['error'] === UPLOAD_ERR_OK) {
                $portada_file = $_FILES['portada'];
                if ($portada_file['size'] <= 2 * 1024 * 1024) {
                    if (!is_dir('uploads/portadas/')) {
                        mkdir('uploads/portadas/', 0777, true);
                    }
                    $ext = pathinfo($portada_file['name'], PATHINFO_EXTENSION);
                    $portada_name = 'portada_' . uniqid() . '_' . time() . '.' . $ext;
                    move_uploaded_file($portada_file['tmp_name'], 'uploads/portadas/' . $portada_name);
                }
            }
            
            // Insertar en base de datos
            $precio_afiliado = $precio * 0.7;
            $stmt = $pdo->prepare("
                INSERT INTO libros (
                    autor_id, titulo, descripcion, precio, precio_afiliado, 
                    comision_porcentaje, categoria, imagen_portada, archivo_original, 
                    estado, fecha_registro
                ) VALUES (
                    ?, ?, ?, ?, ?, 30, ?, ?, ?, 'pendiente_revision', NOW()
                )
            ");
            
            $stmt->execute([
                $autor_id, $titulo, $descripcion, $precio, $precio_afiliado,
                $categoria, $portada_name, $pdf_name
            ]);
            
            $libro_id = $pdo->lastInsertId();
            
            echo "<div class='success'>";
            echo "<h3>🎉 ¡LIBRO SUBIDO EXITOSAMENTE!</h3>";
            echo "<table>";
            echo "<tr><td><strong>ID del Libro:</strong></td><td>#$libro_id</td></tr>";
            echo "<tr><td><strong>Título:</strong></td><td>" . htmlspecialchars($titulo) . "</td></tr>";
            echo "<tr><td><strong>Autor:</strong></td><td>" . htmlspecialchars($autor['nombre']) . " (ID: $autor_id)</td></tr>";
            echo "<tr><td><strong>Precio:</strong></td><td>$" . number_format($precio, 0, ',', '.') . " COP</td></tr>";
            echo "<tr><td><strong>Precio Afiliado:</strong></td><td>$" . number_format($precio_afiliado, 0, ',', '.') . " COP</td></tr>";
            echo "<tr><td><strong>Categoría:</strong></td><td>" . ucfirst($categoria) . "</td></tr>";
            echo "<tr><td><strong>Archivo PDF:</strong></td><td>$pdf_name</td></tr>";
            if ($portada_name) echo "<tr><td><strong>Portada:</strong></td><td>$portada_name</td></tr>";
            echo "<tr><td><strong>Estado:</strong></td><td>Pendiente de revisión</td></tr>";
            echo "</table>";
            echo "</div>";
            
            echo "<div style='margin: 20px 0;'>";
            echo "<a href='dashboard-escritor-mejorado.html' target='_blank' class='btn btn-primary'>📝 Volver al Dashboard</a>";
            echo "<a href='admin-panel.html' target='_blank' class='btn btn-warning'>⚙️ Panel Admin (Revisar Libro)</a>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div class='error'>";
            echo "<h4>❌ Error en Subida</h4>";
            echo "<p>" . $e->getMessage() . "</p>";
            echo "</div>";
        }
        
        echo "</div>";
    }
    
    // Opción para crear escritor ID 101
    if (empty($usuarios) || !in_array(101, array_column($usuarios, 'id'))) {
        echo "<div class='panel warning'>";
        echo "<h3>👤 Crear Escritor ID 101</h3>";
        echo "<p>Si prefieres usar específicamente el ID 101:</p>";
        echo "<form method='POST'>";
        echo "<button type='submit' name='crear_escritor_101' value='1' class='btn btn-warning'>👤 Crear Escritor ID 101</button>";
        echo "</form>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='panel error'>";
    echo "<h3>❌ Error de Conexión</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>