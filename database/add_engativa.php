<?php
/**
 * Agregar vehículos de Engativá a Neon
 * Ejecutar: php add_engativa.php
 */

$neonUrl = 'postgresql://neondb_owner:npg_EAc8prYX2MVZ@ep-raspy-bread-aytziauu.c-5.us-east-2.aws.neon.tech/neondb?sslmode=require';
$parsed = parse_url($neonUrl);

try {
    $dsn = "pgsql:host={$parsed['host']};port=" . ($parsed['port'] ?? 5432) . ";dbname=" . ltrim($parsed['path'], '/') . ";sslmode=require";
    $pdo = new PDO($dsn, $parsed['user'], $parsed['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "Conectado a Neon\n";

    $placas = ['LCL736','LCL737','LCL742','LCL760','LJT684','LJT686','LJT687','LJT690','LJT791','LJT792','LJT838','LJT841'];

    $stmt = $pdo->prepare("INSERT INTO vehiculos (placa, tipo, marca, linea, anio, zona) VALUES (?, 'Sencillo', 'POR DEFINIR', 'POR DEFINIR', 2024, 'Engativa') ON CONFLICT (placa) DO NOTHING");

    $insertados = 0;
    foreach ($placas as $placa) {
        $stmt->execute([$placa]);
        if ($stmt->rowCount() > 0) $insertados++;
    }

    echo "Insertados: $insertados de " . count($placas) . "\n";

    $total = $pdo->query("SELECT COUNT(*) FROM vehiculos")->fetchColumn();
    echo "Total vehículos en BD: $total\n";

    // Desglose por zona
    $zonas = $pdo->query("SELECT zona, COUNT(*) as c FROM vehiculos GROUP BY zona ORDER BY zona")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($zonas as $z) {
        echo "  {$z['zona']}: {$z['c']}\n";
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
