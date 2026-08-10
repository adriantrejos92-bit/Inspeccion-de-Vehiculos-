<?php
// ============================================================
//  API: VEHÍCULOS
//  GET    → lista todos los vehículos
//  POST   → crea un vehículo nuevo
//  DELETE → elimina un vehículo por placa (?placa=XXX)
// ============================================================

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

$metodo = getMetodo();
$pdo = getConexion();

// ---------------------------------------------------------
//  GET — Listar vehículos
// ---------------------------------------------------------
if ($metodo === 'GET') {
    $stmt = $pdo->query('SELECT placa, tipo, marca, linea, anio, zona FROM vehiculos ORDER BY placa ASC');
    $vehiculos = $stmt->fetchAll();
    responderJSON($vehiculos);
}

// ---------------------------------------------------------
//  POST — Crear vehículo
// ---------------------------------------------------------
if ($metodo === 'POST') {
    $data = obtenerBodyJSON();

    $faltantes = validarCampos($data, ['placa', 'tipo', 'marca', 'linea', 'anio', 'zona']);
    if ($faltantes) {
        responderJSON(['error' => 'Campos requeridos faltantes: ' . implode(', ', $faltantes)], 400);
    }

    $placa = strtoupper(sanitizar($data['placa']));
    $tipo  = sanitizar($data['tipo']);
    $marca = sanitizar($data['marca']);
    $linea = sanitizar($data['linea']);
    $anio  = (int) $data['anio'];
    $zona  = sanitizar($data['zona']);

    // Verificar duplicado
    $check = $pdo->prepare('SELECT COUNT(*) FROM vehiculos WHERE placa = ?');
    $check->execute([$placa]);
    if ($check->fetchColumn() > 0) {
        responderJSON(['error' => "Ya existe un vehículo con placa $placa"], 409);
    }

    $stmt = $pdo->prepare('INSERT INTO vehiculos (placa, tipo, marca, linea, anio, zona) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$placa, $tipo, $marca, $linea, $anio, $zona]);

    responderJSON(['ok' => true, 'placa' => $placa], 201);
}

// ---------------------------------------------------------
//  DELETE — Eliminar vehículo por placa
// ---------------------------------------------------------
if ($metodo === 'DELETE') {
    $placa = isset($_GET['placa']) ? strtoupper(trim($_GET['placa'])) : '';

    if (!$placa) {
        responderJSON(['error' => 'Parámetro placa requerido'], 400);
    }

    // Verificar si tiene inspecciones asociadas
    $check = $pdo->prepare('SELECT COUNT(*) FROM inspecciones WHERE placa = ?');
    $check->execute([$placa]);
    if ($check->fetchColumn() > 0) {
        responderJSON(['error' => "No se puede eliminar: el vehículo $placa tiene inspecciones registradas"], 409);
    }

    $stmt = $pdo->prepare('DELETE FROM vehiculos WHERE placa = ?');
    $stmt->execute([$placa]);

    if ($stmt->rowCount() === 0) {
        responderJSON(['error' => "No se encontró vehículo con placa $placa"], 404);
    }

    responderJSON(['ok' => true, 'placa' => $placa]);
}

// Método no soportado
responderJSON(['error' => 'Método no permitido'], 405);
