<?php
/**
 * Script para poblar la base de datos Neon (PostgreSQL)
 * Ejecutar desde el navegador: http://localhost/control-seguridad/database/seed_neon.php
 */

$neonUrl = 'postgresql://neondb_owner:npg_EAc8prYX2MVZ@ep-raspy-bread-aytziauu-pooler.c-5.us-east-2.aws.neon.tech/neondb?sslmode=require';

$parsed = parse_url($neonUrl);
$host   = $parsed['host'];
$port   = $parsed['port'] ?? 5432;
$dbname = ltrim($parsed['path'], '/');
$user   = $parsed['user'];
$pass   = $parsed['pass'];

echo "<h2>Conectando a Neon PostgreSQL...</h2>";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "<p style='color:green;'>✅ Conexión exitosa!</p>";
} catch (PDOException $e) {
    die("<p style='color:red;'>❌ Error de conexión: " . $e->getMessage() . "</p>");
}

// Leer y ejecutar el script SQL
$sqlFile = __DIR__ . '/control_seguridad_pg.sql';
$sql = file_get_contents($sqlFile);

if (!$sql) {
    die("<p style='color:red;'>❌ No se pudo leer el archivo SQL</p>");
}

echo "<h2>Ejecutando script SQL...</h2>";

try {
    // Separar por sentencias (ignorar líneas de comentario y vacías)
    // Ejecutar todo como un bloque
    $pdo->exec($sql);
    echo "<p style='color:green;'>✅ Tablas creadas exitosamente!</p>";
} catch (PDOException $e) {
    die("<p style='color:red;'>❌ Error SQL: " . $e->getMessage() . "</p>");
}

// Verificar
echo "<h2>Verificación:</h2>";

$counts = [
    'vehiculos' => 'SELECT COUNT(*) FROM vehiculos',
    'inspectores' => 'SELECT COUNT(*) FROM inspectores',
    'inspecciones' => 'SELECT COUNT(*) FROM inspecciones',
];

echo "<ul>";
foreach ($counts as $tabla => $query) {
    try {
        $count = $pdo->query($query)->fetchColumn();
        echo "<li><strong>$tabla:</strong> $count registros</li>";
    } catch (PDOException $e) {
        echo "<li style='color:red;'><strong>$tabla:</strong> Error - " . $e->getMessage() . "</li>";
    }
}
echo "</ul>";

echo "<h2 style='color:green;'>🎉 Base de datos lista!</h2>";
echo "<p>Ya puedes eliminar este archivo por seguridad.</p>";
