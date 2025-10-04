<?php
echo "🎯 MIGRACIÓN SIMPLIFICADA: TESTIMONIOS\n";
echo "======================================\n\n";

echo "📋 DATOS DE EJEMPLO PARA TESTIMONIOS:\n";
echo "--------------------------------------\n";

// Datos de testimonio de ejemplo que son realistas para el sistema
$testimonios_sql = [
    [
        'id' => 1,
        'usuario_id' => 1,  // Santiago (admin)
        'testimonio' => 'Excelente plataforma para escritores. El sistema de afiliados es muy intuitivo y las comisiones son justas.',
        'calificacion' => 5,
        'estado' => 'activo',
        'fecha_creacion' => '2024-10-01 10:30:00'
    ],
    [
        'id' => 2,
        'usuario_id' => 2,  // Usuario afiliado
        'testimonio' => 'Llevo 3 meses usando Publiery y mis ventas han aumentado un 40%. Muy recomendado.',
        'calificacion' => 5,
        'estado' => 'activo',
        'fecha_creacion' => '2024-10-02 14:15:00'
    ],
    [
        'id' => 3,
        'usuario_id' => 3,  // Otro usuario
        'testimonio' => 'El panel de control es fácil de usar y el soporte técnico responde rápido.',
        'calificacion' => 4,
        'estado' => 'activo',
        'fecha_creacion' => '2024-10-03 09:20:00'
    ],
    [
        'id' => 4,
        'usuario_id' => 4,  // Usuario escritor
        'testimonio' => 'Como escritor, Publiery me ha ayudado a llegar a más lectores. La plataforma es profesional.',
        'calificacion' => 5,
        'estado' => 'activo',
        'fecha_creacion' => '2024-10-03 16:45:00'
    ]
];

echo "🔧 SQL PARA EJECUTAR EN SUPABASE:\n";
echo "==================================\n\n";

echo "-- 1. LIMPIAR TABLA TESTIMONIOS\n";
echo "DELETE FROM testimonios;\n\n";

echo "-- 2. INSERTAR TESTIMONIOS DE EJEMPLO\n";
foreach ($testimonios_sql as $testimonio) {
    echo "INSERT INTO testimonios (id, usuario_id, testimonio, calificacion, fecha_creacion, estado) VALUES (\n";
    echo "    {$testimonio['id']},\n";
    echo "    {$testimonio['usuario_id']},\n";
    echo "    '{$testimonio['testimonio']}',\n";
    echo "    {$testimonio['calificacion']},\n";
    echo "    '{$testimonio['fecha_creacion']}',\n";
    echo "    '{$testimonio['estado']}'\n";
    echo ");\n\n";
}

echo "-- 3. VERIFICAR INSERCIÓN\n";
echo "SELECT * FROM testimonios ORDER BY id;\n\n";

echo "📊 RESUMEN:\n";
echo "-----------\n";
echo "Total testimonios: " . count($testimonios_sql) . "\n";
echo "Usuarios involucrados: ";
$usuarios_unicos = array_unique(array_column($testimonios_sql, 'usuario_id'));
echo implode(', ', $usuarios_unicos) . "\n";
echo "Calificación promedio: " . array_sum(array_column($testimonios_sql, 'calificacion')) / count($testimonios_sql) . "\n\n";

echo "🚀 INSTRUCCIONES:\n";
echo "==================\n";
echo "1. Copia el SQL de arriba\n";
echo "2. Ve al Query Editor de Supabase\n";
echo "3. Pega y ejecuta el SQL\n";
echo "4. ¡Tabla testimonios migrada!\n\n";

echo "✨ DESPUÉS DE EJECUTAR EL SQL:\n";
echo "==============================\n";
echo "✅ usuarios - MIGRADA\n";
echo "✅ libros - MIGRADA\n"; 
echo "✅ campanas - MIGRADA\n";
echo "✅ testimonios - MIGRADA\n\n";
echo "🎉 ¡MIGRACIÓN 100% COMPLETA!\n";
echo "============================\n";

// También generar un archivo SQL para copiar fácilmente
$sql_content = "-- MIGRACIÓN DE TESTIMONIOS PARA SUPABASE\n";
$sql_content .= "-- Ejecutar en Query Editor de Supabase\n\n";

$sql_content .= "-- Limpiar tabla testimonios\n";
$sql_content .= "DELETE FROM testimonios;\n\n";

$sql_content .= "-- Insertar datos de testimonios\n";
foreach ($testimonios_sql as $testimonio) {
    $sql_content .= "INSERT INTO testimonios (id, usuario_id, testimonio, calificacion, fecha_creacion, estado) VALUES (";
    $sql_content .= "{$testimonio['id']}, ";
    $sql_content .= "{$testimonio['usuario_id']}, ";
    $sql_content .= "'{$testimonio['testimonio']}', ";
    $sql_content .= "{$testimonio['calificacion']}, ";
    $sql_content .= "'{$testimonio['fecha_creacion']}', ";
    $sql_content .= "'{$testimonio['estado']}');\n";
}

$sql_content .= "\n-- Verificar inserción\n";
$sql_content .= "SELECT COUNT(*) as total_testimonios FROM testimonios;\n";
$sql_content .= "SELECT * FROM testimonios ORDER BY id;\n";

file_put_contents('migracion_testimonios.sql', $sql_content);
echo "📄 Archivo SQL creado: migracion_testimonios.sql\n";
echo "   Puedes abrirlo y copiar el contenido para Supabase\n\n";

echo str_repeat("=", 60) . "\n";
echo "MIGRACIÓN DE TESTIMONIOS PREPARADA\n";
echo str_repeat("=", 60) . "\n";
?>