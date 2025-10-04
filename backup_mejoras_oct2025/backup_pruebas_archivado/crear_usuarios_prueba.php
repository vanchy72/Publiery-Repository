<?php
/**
 * SCRIPT PARA CREAR USUARIOS DE PRUEBA
 * Proyecto: Publiery
 * Propósito: Crear usuarios de prueba para testing completo del sistema
 */

require_once 'config/database.php';

class CreadorUsuariosPrueba {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    /**
     * Crear usuario escritor de prueba
     */
    public function crearEscritorPrueba() {
        echo "<h3>📝 Creando Escritor de Prueba</h3>\n";
        
        try {
            // Verificar si ya existe
            $stmt = $this->pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute(['juan.escritor@prueba.com']);
            
            if ($stmt->rowCount() > 0) {
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "⚠️ El escritor de prueba ya existe - ID: {$usuario['id']}<br>\n";
                return $usuario['id'];
            }
            
            // 1. Crear usuario en tabla usuarios
            $stmt = $this->pdo->prepare("
                INSERT INTO usuarios (nombre, email, password, documento, rol, estado, fecha_registro)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $password_hash = password_hash('123456', PASSWORD_DEFAULT);
            $stmt->execute([
                'Juan Pérez Escritor',
                'juan.escritor@prueba.com',
                $password_hash,
                '123456789', // Documento del escritor
                'escritor',
                'activo',
                date('Y-m-d H:i:s')
            ]);
            
            $usuario_id = $this->pdo->lastInsertId();
            
            // 2. Crear entrada en tabla escritores
            $stmt = $this->pdo->prepare("
                INSERT INTO escritores (usuario_id, estado_activacion, estado, fecha_activacion)
                VALUES (?, 'activo', 'activo', ?)
            ");
            
            $stmt->execute([
                $usuario_id,
                date('Y-m-d H:i:s')
            ]);
            
            echo "✅ Escritor creado exitosamente<br>\n";
            echo "👤 Usuario ID: {$usuario_id}<br>\n";
            echo "📧 Email: juan.escritor@prueba.com<br>\n";
            echo "🔑 Contraseña: 123456<br>\n";
            echo "📝 Rol: escritor<br>\n";
            
            return $usuario_id;
            
        } catch (Exception $e) {
            echo "❌ Error al crear escritor: " . $e->getMessage() . "<br>\n";
            return false;
        }
    }
    
    /**
     * Crear afiliados de prueba (estructura multinivel)
     */
    public function crearAfiliadosPrueba() {
        echo "<h3>🌐 Creando Afiliados de Prueba</h3>\n";
        
        $afiliados_data = [
            [
                'nombre' => 'María García',
                'email' => 'maria.afiliado@prueba.com',
                'nivel' => 1,
                'patrocinador_id' => null
            ],
            [
                'nombre' => 'Carlos López', 
                'email' => 'carlos.afiliado@prueba.com',
                'nivel' => 2,
                'patrocinador_id' => null // Se asignará después
            ],
            [
                'nombre' => 'Ana Martín',
                'email' => 'ana.afiliado@prueba.com',
                'nivel' => 3,
                'patrocinador_id' => null // Se asignará después
            ]
        ];
        
        $ids_creados = [];
        
        foreach ($afiliados_data as $index => $datos_afiliado) {
            try {
                // Verificar si ya existe
                $stmt = $this->pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
                $stmt->execute([$datos_afiliado['email']]);
                
                if ($stmt->rowCount() > 0) {
                    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo "⚠️ Afiliado {$datos_afiliado['email']} ya existe - ID: {$usuario['id']}<br>\n";
                    $ids_creados[] = $usuario['id'];
                    continue;
                }
                
                // 1. Crear usuario en tabla usuarios
                $stmt = $this->pdo->prepare("
                    INSERT INTO usuarios (nombre, email, password, documento, rol, estado, fecha_registro)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                $password_hash = password_hash('123456', PASSWORD_DEFAULT);
                $documento_unico = '12345678' . ($index + 1); // Documento único para cada afiliado
                
                $stmt->execute([
                    $datos_afiliado['nombre'],
                    $datos_afiliado['email'],
                    $password_hash,
                    $documento_unico,
                    'afiliado',
                    'activo',
                    date('Y-m-d H:i:s')
                ]);
                
                $usuario_id = $this->pdo->lastInsertId();
                $ids_creados[] = $usuario_id;
                
                // 2. Crear registro en tabla afiliados
                $codigo_afiliado = 'AF' . str_pad($usuario_id, 6, '0', STR_PAD_LEFT);
                
                // Obtener el ID del afiliado patrocinador (no usuario_id)
                $patrocinador_id = null;
                if ($index > 0) {
                    $stmt_patrocinador = $this->pdo->prepare("SELECT id FROM afiliados WHERE usuario_id = ?");
                    $stmt_patrocinador->execute([$ids_creados[$index - 1]]);
                    $patrocinador = $stmt_patrocinador->fetch(PDO::FETCH_ASSOC);
                    $patrocinador_id = $patrocinador ? $patrocinador['id'] : null;
                }
                
                $stmt = $this->pdo->prepare("
                    INSERT INTO afiliados (usuario_id, codigo_afiliado, patrocinador_id, nivel, fecha_activacion)
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $usuario_id,
                    $codigo_afiliado,
                    $patrocinador_id,
                    $datos_afiliado['nivel'],
                    date('Y-m-d H:i:s')
                ]);
                
                echo "✅ Afiliado L{$datos_afiliado['nivel']} creado<br>\n";
                echo "👤 ID: {$usuario_id}<br>\n";
                echo "📧 Email: {$datos_afiliado['email']}<br>\n";
                echo "🔗 Código: {$codigo_afiliado}<br>\n";
                echo "👥 Patrocinador: " . ($patrocinador_id ? $patrocinador_id : 'Ninguno') . "<br>\n";
                echo "<br>\n";
                
            } catch (Exception $e) {
                echo "❌ Error al crear afiliado {$datos_afiliado['email']}: " . $e->getMessage() . "<br>\n";
            }
        }
        
        return $ids_creados;
    }
    
    /**
     * Crear libro de prueba
     */
    public function crearLibroPrueba($escritor_id) {
        echo "<h3>📚 Creando Libro de Prueba</h3>\n";
        
        $datos_libro = [
            'titulo' => 'Mi Primer Libro - Prueba',
            'descripcion' => 'Este es un libro de prueba para verificar el funcionamiento del sistema Publiery. Contiene información valiosa sobre emprendimiento y marketing digital.',
            'precio' => 25000,
            'precio_afiliado' => 25000,
            'autor_id' => $escritor_id,
            'imagen_portada' => 'portada_prueba.jpg',
            'archivo_original' => 'libro_prueba.pdf',
            'categoria' => 'Emprendimiento',
            'fecha_registro' => date('Y-m-d H:i:s'),
            'estado' => 'publicado'
        ];
        
        try {
            // Verificar si ya existe un libro de prueba para este escritor
            $stmt = $this->pdo->prepare("
                SELECT id FROM libros 
                WHERE autor_id = ? AND titulo LIKE '%Prueba%'
            ");
            $stmt->execute([$escritor_id]);
            
            if ($stmt->rowCount() > 0) {
                $libro_id = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
                echo "⚠️ Ya existe un libro de prueba para este escritor - ID: {$libro_id}<br>\n";
                return $libro_id;
            }
            
            // Crear libro
            $stmt = $this->pdo->prepare("
                INSERT INTO libros (titulo, descripcion, precio, precio_afiliado, autor_id, imagen_portada, archivo_original, categoria, fecha_registro, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $datos_libro['titulo'],
                $datos_libro['descripcion'],
                $datos_libro['precio'],
                $datos_libro['precio_afiliado'],
                $datos_libro['autor_id'],
                $datos_libro['imagen_portada'],
                $datos_libro['archivo_original'],
                $datos_libro['categoria'],
                $datos_libro['fecha_registro'],
                $datos_libro['estado']
            ]);
            
            $libro_id = $this->pdo->lastInsertId();
            
            echo "✅ Libro creado - ID: {$libro_id}<br>\n";
            echo "📖 Título: {$datos_libro['titulo']}<br>\n";
            echo "💰 Precio: $" . number_format($datos_libro['precio']) . "<br>\n";
            echo "👤 Escritor ID: {$escritor_id}<br>\n";
            
            return $libro_id;
            
        } catch (Exception $e) {
            echo "❌ Error al crear libro: " . $e->getMessage() . "<br>\n";
            return false;
        }
    }
    
    /**
     * Crear comprador de prueba
     */
    private function crearCompradorPrueba() {
        try {
            // Verificar si ya existe
            $stmt = $this->pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute(['comprador.prueba@test.com']);
            
            if ($stmt->rowCount() > 0) {
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                return $usuario['id'];
            }
            
            // Crear comprador
            $stmt = $this->pdo->prepare("
                INSERT INTO usuarios (nombre, email, password, documento, rol, estado, fecha_registro)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $password_hash = password_hash('123456', PASSWORD_DEFAULT);
            $stmt->execute([
                'Cliente Comprador',
                'comprador.prueba@test.com',
                $password_hash,
                '987654321',
                'lector',
                'activo',
                date('Y-m-d H:i:s')
            ]);
            
            return $this->pdo->lastInsertId();
            
        } catch (Exception $e) {
            // Si falla, usar el ID del escritor como comprador
            return 55; // ID del escritor creado
        }
    }
    
    /**
     * Crear venta de prueba
     */
    public function crearVentaPrueba($libro_id, $afiliado_id = null) {
        echo "<h3>💰 Creando Venta de Prueba</h3>\n";
        
        try {
            // Obtener datos del libro
            $stmt = $this->pdo->prepare("SELECT precio, autor_id FROM libros WHERE id = ?");
            $stmt->execute([$libro_id]);
            $libro = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$libro) {
                echo "❌ Libro no encontrado<br>\n";
                return false;
            }
            
            // Calcular montos
            $precio_venta = $libro['precio'];
            $porcentaje_autor = 70.00;
            $porcentaje_empresa = 30.00;
            $monto_autor = $precio_venta * ($porcentaje_autor / 100);
            $monto_empresa = $precio_venta * ($porcentaje_empresa / 100);
            
            // Crear comprador si no existe
            $comprador_id = $this->crearCompradorPrueba();
            
            // Crear venta
            $stmt = $this->pdo->prepare("
                INSERT INTO ventas (
                    libro_id, id_escritor, comprador_id, afiliado_id, fecha_venta, 
                    cantidad, total, tipo, estado, monto_autor, monto_empresa, 
                    porcentaje_autor, porcentaje_empresa, precio_venta
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $libro_id,
                $libro['autor_id'],
                $comprador_id,
                $afiliado_id,
                date('Y-m-d H:i:s'),
                1, // cantidad
                $precio_venta,
                $afiliado_id ? 'tienda' : 'directa',
                'completada',
                $monto_autor,
                $monto_empresa,
                $porcentaje_autor,
                $porcentaje_empresa,
                $precio_venta
            ]);
            
            $venta_id = $this->pdo->lastInsertId();
            
            echo "✅ Venta creada - ID: {$venta_id}<br>\n";
            echo "📖 Libro ID: {$libro_id}<br>\n";
            echo "💰 Precio: $" . number_format($precio_venta) . "<br>\n";
            echo "👤 Autor: $" . number_format($monto_autor) . " ({$porcentaje_autor}%)<br>\n";
            echo "🏢 Empresa: $" . number_format($monto_empresa) . " ({$porcentaje_empresa}%)<br>\n";
            echo "🔗 Afiliado: " . ($afiliado_id ? $afiliado_id : 'Venta directa') . "<br>\n";
            
            // Calcular comisiones si hay afiliado
            if ($afiliado_id) {
                $this->calcularComisionesPrueba($venta_id, $precio_venta, $afiliado_id);
            }
            
            return $venta_id;
            
        } catch (Exception $e) {
            echo "❌ Error al crear venta: " . $e->getMessage() . "<br>\n";
            return false;
        }
    }
    
    /**
     * Calcular comisiones para venta de prueba
     */
    private function calcularComisionesPrueba($venta_id, $precio_venta, $afiliado_id) {
        echo "<h4>💳 Calculando Comisiones</h4>\n";
        
        try {
            // Obtener afiliado
            $stmt = $this->pdo->prepare("
                SELECT usuario_id, patrocinador_id, nivel 
                FROM afiliados 
                WHERE usuario_id = ?
            ");
            $stmt->execute([$afiliado_id]);
            $afiliado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$afiliado) {
                echo "⚠️ Afiliado no encontrado<br>\n";
                return;
            }
            
            // Comisiones según nivel (usando la estructura real de comisiones)
            $porcentajes = [
                1 => 15, // Nivel 1: 15%
                2 => 3,  // Nivel 2: 3%
                3 => 3   // Nivel 3: 3%
            ];
            
            $afiliado_actual = $afiliado;
            $nivel_comision = 1;
            
            while ($afiliado_actual && $nivel_comision <= 3) {
                $porcentaje = $porcentajes[$nivel_comision];
                $monto_comision = $precio_venta * ($porcentaje / 100);
                
                // Insertar comisión usando la estructura real
                $stmt = $this->pdo->prepare("
                    INSERT INTO comisiones (venta_id, afiliado_id, nivel, porcentaje, monto, estado, fecha_generacion)
                    VALUES (?, ?, ?, ?, ?, 'pendiente', ?)
                ");
                
                $stmt->execute([
                    $venta_id,
                    $afiliado_actual['usuario_id'],
                    $nivel_comision,
                    $porcentaje,
                    $monto_comision,
                    date('Y-m-d H:i:s')
                ]);
                
                echo "💰 Comisión L{$nivel_comision} - Afiliado {$afiliado_actual['usuario_id']}: $" . number_format($monto_comision) . " ({$porcentaje}%)<br>\n";
                
                // Buscar patrocinador para siguiente nivel
                if ($afiliado_actual['patrocinador_id']) {
                    $stmt = $this->pdo->prepare("
                        SELECT usuario_id, patrocinador_id, nivel 
                        FROM afiliados 
                        WHERE usuario_id = ?
                    ");
                    $stmt->execute([$afiliado_actual['patrocinador_id']]);
                    $afiliado_actual = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $afiliado_actual = null;
                }
                
                $nivel_comision++;
            }
            
        } catch (Exception $e) {
            echo "❌ Error al calcular comisiones: " . $e->getMessage() . "<br>\n";
        }
    }
    
    /**
     * Crear entorno completo de prueba
     */
    public function crearEntornoCompleto() {
        echo "<h1>🚀 CREANDO ENTORNO COMPLETO DE PRUEBA</h1>\n";
        echo "<hr>\n";
        
        // 1. Crear escritor
        $escritor_id = $this->crearEscritorPrueba();
        if (!$escritor_id) {
            echo "❌ Error crítico: No se pudo crear el escritor<br>\n";
            return false;
        }
        
        echo "<hr>\n";
        
        // 2. Crear afiliados
        $afiliados_ids = $this->crearAfiliadosPrueba();
        if (empty($afiliados_ids)) {
            echo "❌ Error crítico: No se pudieron crear los afiliados<br>\n";
            return false;
        }
        
        echo "<hr>\n";
        
        // 3. Crear libro
        $libro_id = $this->crearLibroPrueba($escritor_id);
        if (!$libro_id) {
            echo "❌ Error crítico: No se pudo crear el libro<br>\n";
            return false;
        }
        
        echo "<hr>\n";
        
        // 4. Crear ventas de prueba
        // Venta directa
        $this->crearVentaPrueba($libro_id);
        echo "<br>\n";
        
        // Venta con afiliado (usar el primer afiliado creado)
        if (!empty($afiliados_ids)) {
            $this->crearVentaPrueba($libro_id, $afiliados_ids[0]);
        }
        
        echo "<hr>\n";
        echo "<h2>🎉 ENTORNO DE PRUEBA CREADO EXITOSAMENTE</h2>\n";
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>\n";
        echo "<h3>📋 CREDENCIALES DE ACCESO:</h3>\n";
        echo "<strong>✍️ Escritor:</strong><br>\n";
        echo "Email: juan.escritor@prueba.com<br>\n";
        echo "Contraseña: 123456<br><br>\n";
        echo "<strong>🌐 Afiliados:</strong><br>\n";
        echo "Email: maria.afiliado@prueba.com (Nivel 1)<br>\n";
        echo "Email: carlos.afiliado@prueba.com (Nivel 2)<br>\n";
        echo "Email: ana.afiliado@prueba.com (Nivel 3)<br>\n";
        echo "Contraseña para todos: 123456<br><br>\n";
        echo "<strong>📖 Libro creado:</strong> Mi Primer Libro - Prueba<br>\n";
        echo "<strong>💰 Ventas creadas:</strong> 2 (1 directa, 1 con afiliado)<br>\n";
        echo "</div>\n";
        
        return true;
    }
}

// Si se ejecuta directamente
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Crear Usuarios de Prueba - Publiery</title>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            hr { margin: 20px 0; }
            .success { background: #e8f5e8; padding: 15px; border-radius: 5px; }
        </style>
    </head>
    <body>";
    
    $creador = new CreadorUsuariosPrueba();
    $creador->crearEntornoCompleto();
    
    echo "<br><a href='scripts_verificacion_pruebas.php'>🔍 Verificar entorno creado</a>";
    echo "</body></html>";
}
?>
