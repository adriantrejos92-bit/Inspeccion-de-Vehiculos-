<?php
/**
 * Agregar columna fotos a la tabla inspecciones en Neon
 * Ejecutar: http://localhost/control-seguridad/database/add_fotos.php
 */

$neonUrl = 'postgresql://neondb_owner:npg_EAc8prYX2MVZ@ep-raspy-bread-aytziauu-pooler.c-5.us-east-2.aws.neon.tech/neondb?sslmode=require';
$parsed = parse_url($neonUrl);

try {
    $dsn = "pgsql:host={$parsed['host']};port=" . ($parsed['port'] ?? 5432) . ";dbname=" . ltrim($parsed['path'], '/') . ";sslmode=require";
    $pdo = new PDO($dsn, $parsed['user'], $parsed['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "<p style='color:green;'>✅ Conectado a Neon</p>";

    $pdo->exec("ALTER TABLE inspecciones ADD COLUMN IF NOT EXISTS fotos JSONB DEFAULT '[]'::jsonb");
    echo "<p style='color:green;'>✅ Columna 'fotos' agregada exitosamente!</p>";

    // Verificar
    $stmt = $pdo->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'inspecciones' ORDER BY ordinal_position");
    echo "<h3>Columnas de inspecciones:</h3><ul>";
    foreach ($stmt->fetchAll() as $col) {
        echo "<li><strong>{$col['column_name']}</strong> — {$col['data_type']}</li>";
    }
    echo "</ul>";
} catch (PDOException $e) {
    die("<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>");
}
