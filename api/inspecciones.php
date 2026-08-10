<?php
// ============================================================
//  API: INSPECCIONES
//  GET    → lista inspecciones (filtros opcionales)
//  POST   → guarda una inspección nueva
//  DELETE → elimina una inspección por id (?id=X)
// ============================================================

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

$metodo = getMetodo();
$pdo = getConexion();

// ---------------------------------------------------------
//  GET — Listar inspecciones con filtros opcionales
//  ?placa=XXX      → filtra por placa
//  ?tipo=botiquin   → filtra por tipo
//  ?desde=2026-01-01&hasta=2026-12-31  → rango de fechas
//  ?ultimo=1&placa=XXX  → solo la última de cada tipo para esa placa
// ---------------------------------------------------------
if ($metodo === 'GET') {

    // Caso especial: últimas inspecciones por placa
    if (isset($_GET['ultimo']) && isset($_GET['placa'])) {
        $placa = strtoupper(trim($_GET['placa']));
        $stmt = $pdo->prepare(
            'SELECT i1.* FROM inspecciones i1
             INNER JOIN (
               SELECT tipo, MAX(id) AS max_id
               FROM inspecciones WHERE placa = ?
               GROUP BY tipo
             ) i2 ON i1.id = i2.max_id
             ORDER BY i1.tipo'
        );
        $stmt->execute([$placa]);
        $rows = $stmt->fetchAll();

        // Decodificar items JSON
        foreach ($rows as &$row) {
            $row['items'] = json_decode($row['items'], true);
        }
        responderJSON($rows);
    }

    // Construcción dinámica de query con filtros
    $where = [];
    $params = [];

    if (isset($_GET['placa']) && trim($_GET['placa']) !== '') {
        $where[] = 'placa = ?';
        $params[] = strtoupper(trim($_GET['placa']));
    }

    if (isset($_GET['tipo']) && trim($_GET['tipo']) !== '') {
        $where[] = 'tipo = ?';
        $params[] = trim($_GET['tipo']);
    }

    if (isset($_GET['desde']) && trim($_GET['desde']) !== '') {
        $where[] = 'fecha >= ?';
        $params[] = trim($_GET['desde']);
    }

    if (isset($_GET['hasta']) && trim($_GET['hasta']) !== '') {
        $where[] = 'fecha <= ?';
        $params[] = trim($_GET['hasta']);
    }

    $sql = 'SELECT * FROM inspecciones';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY fecha DESC, hora DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Decodificar items JSON
    foreach ($rows as &$row) {
        $row['items'] = json_decode($row['items'], true);
    }

    responderJSON($rows);
}

// ---------------------------------------------------------
//  POST — Guardar inspección
// ---------------------------------------------------------
if ($metodo === 'POST') {
    $data = obtenerBodyJSON();

    $faltantes = validarCampos($data, ['tipo', 'placa', 'inspector', 'fecha', 'hora', 'items']);
    if ($faltantes) {
        responderJSON(['error' => 'Campos requeridos faltantes: ' . implode(', ', $faltantes)], 400);
    }

    $tipo      = trim($data['tipo']);
    $placa     = strtoupper(sanitizar($data['placa']));
    $inspector = sanitizar($data['inspector']);
    $fecha     = trim($data['fecha']);
    $hora      = trim($data['hora']);
    $obs       = isset($data['observaciones']) ? sanitizar($data['observaciones']) : '';
    $items     = $data['items'];

    // Validar tipo
    $tiposValidos = ['botiquin', 'carretilla', 'extintor', 'caja_fuerte', 'boton_panico', 'inspeccion_vehiculo'];
    if (!in_array($tipo, $tiposValidos)) {
        responderJSON(['error' => 'Tipo inválido. Debe ser: ' . implode(', ', $tiposValidos)], 400);
    }

    // Validar que el vehículo existe
    $check = $pdo->prepare('SELECT COUNT(*) FROM vehiculos WHERE placa = ?');
    $check->execute([$placa]);
    if ($check->fetchColumn() == 0) {
        responderJSON(['error' => "No existe vehículo con placa $placa"], 404);
    }

    // Validar formato fecha
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        responderJSON(['error' => 'Formato de fecha inválido. Use YYYY-MM-DD'], 400);
    }

    // Validar formato hora
    if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora)) {
        responderJSON(['error' => 'Formato de hora inválido. Use HH:MM o HH:MM:SS'], 400);
    }

    // Validar items es objeto/array
    if (!is_array($items) || empty($items)) {
        responderJSON(['error' => 'El campo items debe ser un objeto con los campos de la inspección'], 400);
    }

    $itemsJSON = json_encode($items, JSON_UNESCAPED_UNICODE);

    $stmt = $pdo->prepare(
        'INSERT INTO inspecciones (tipo, placa, inspector, fecha, hora, observaciones, items)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$tipo, $placa, $inspector, $fecha, $hora, $obs, $itemsJSON]);

    $id = (int) $pdo->lastInsertId();

    responderJSON([
        'ok'    => true,
        'id'    => $id,
        'tipo'  => $tipo,
        'placa' => $placa
    ], 201);
}

// ---------------------------------------------------------
//  DELETE — Eliminar inspección por id
// ---------------------------------------------------------
if ($metodo === 'DELETE') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if (!$id) {
        responderJSON(['error' => 'Parámetro id requerido'], 400);
    }

    $stmt = $pdo->prepare('DELETE FROM inspecciones WHERE id = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        responderJSON(['error' => 'Inspección no encontrada'], 404);
    }

    responderJSON(['ok' => true, 'id' => $id]);
}

// Método no soportado
responderJSON(['error' => 'Método no permitido'], 405);
