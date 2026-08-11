<?php
// ============================================================
//  API: INSPECTORES
//  GET    → lista todos los inspectores activos
//  POST   → crea un inspector nuevo
//  DELETE → elimina (desactiva) un inspector por id (?id=X)
// ============================================================

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

$metodo = getMetodo();
$pdo = getConexion();

// ---------------------------------------------------------
//  GET — Listar inspectores activos
// ---------------------------------------------------------
if ($metodo === 'GET') {
    $stmt = $pdo->query('SELECT id, nombre FROM inspectores WHERE activo = 1 ORDER BY nombre ASC');
    $inspectores = $stmt->fetchAll();
    responderJSON($inspectores);
}

// ---------------------------------------------------------
//  POST — Crear inspector
// ---------------------------------------------------------
if ($metodo === 'POST') {
    $data = obtenerBodyJSON();

    $faltantes = validarCampos($data, ['nombre']);
    if ($faltantes) {
        responderJSON(['error' => 'El campo nombre es requerido'], 400);
    }

    $nombre = strtoupper(sanitizar($data['nombre']));

    if (strlen($nombre) < 3) {
        responderJSON(['error' => 'El nombre debe tener al menos 3 caracteres'], 400);
    }

    // Verificar duplicado (incluyendo inactivos)
    $check = $pdo->prepare('SELECT id, activo FROM inspectores WHERE nombre = ?');
    $check->execute([$nombre]);
    $existente = $check->fetch();

    if ($existente) {
        // Si existe pero inactivo, reactivar
        if ($existente['activo'] == 0) {
            $stmt = $pdo->prepare('UPDATE inspectores SET activo = 1 WHERE id = ?');
            $stmt->execute([$existente['id']]);
            responderJSON(['ok' => true, 'id' => $existente['id'], 'nombre' => $nombre, 'reactivado' => true]);
        }
        responderJSON(['error' => "Ya existe un inspector con nombre $nombre"], 409);
    }

    if (DB_DRIVER === 'pgsql') {
        $stmt = $pdo->prepare('INSERT INTO inspectores (nombre) VALUES (?) RETURNING id');
        $stmt->execute([$nombre]);
        $newId = (int) $stmt->fetchColumn();
    } else {
        $stmt = $pdo->prepare('INSERT INTO inspectores (nombre) VALUES (?)');
        $stmt->execute([$nombre]);
        $newId = (int) $pdo->lastInsertId();
    }

    responderJSON(['ok' => true, 'id' => $newId, 'nombre' => $nombre], 201);
}

// ---------------------------------------------------------
//  DELETE — Desactivar inspector por id
// ---------------------------------------------------------
if ($metodo === 'DELETE') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if (!$id) {
        responderJSON(['error' => 'Parámetro id requerido'], 400);
    }

    $stmt = $pdo->prepare('UPDATE inspectores SET activo = 0 WHERE id = ? AND activo = 1');
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        responderJSON(['error' => 'Inspector no encontrado o ya inactivo'], 404);
    }

    responderJSON(['ok' => true, 'id' => $id]);
}

// Método no soportado
responderJSON(['error' => 'Método no permitido'], 405);
