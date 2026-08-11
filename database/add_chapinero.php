<?php
/**
 * Agregar vehículos de Chapinero a Neon
 * Ejecutar: php add_chapinero.php
 */

$neonUrl = 'postgresql://neondb_owner:npg_EAc8prYX2MVZ@ep-raspy-bread-aytziauu-pooler.c-5.us-east-2.aws.neon.tech/neondb?sslmode=require';
$parsed = parse_url($neonUrl);

try {
    $dsn = "pgsql:host={$parsed['host']};port=" . ($parsed['port'] ?? 5432) . ";dbname=" . ltrim($parsed['path'], '/') . ";sslmode=require";
    $pdo = new PDO($dsn, $parsed['user'], $parsed['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "Conectado a Neon\n";

    $placas = ['LCL743','LGU451','LCL748','LGU432','LGU482','LJT716','LJT778','LJT779','LJT836','LJT780','LJT843','LJT840','LJT681'];

    $stmt = $pdo->prepare("INSERT INTO vehiculos (placa, tipo, marca, linea, anio, zona) VALUES (?, 'Sencillo', 'POR DEFINIR', 'POR DEFINIR', 2024, 'Chapinero') ON CONFLICT (placa) DO NOTHING");

    $insertados = 0;
    foreach ($placas as $placa) {
        $stmt->execute([$placa]);
        if ($stmt->rowCount() > 0) $insertados++;
    }

    echo "Insertados: $insertados de " . count($placas) . "\n";

    $total = $pdo->query("SELECT COUNT(*) FROM vehiculos")->fetchColumn();
    echo "Total vehículos en BD: $total\n";
} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
