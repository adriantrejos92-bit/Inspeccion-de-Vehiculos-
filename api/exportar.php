<?php
// ============================================================
//  API: EXPORTAR INSPECCIONES A EXCEL (.xlsx)
//  GET → descarga archivo Excel con todas las inspecciones
//  Filtros opcionales: ?desde=YYYY-MM-DD&hasta=YYYY-MM-DD&tipo=botiquin
// ============================================================

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

$metodo = getMetodo();

if ($metodo !== 'GET') {
    responderJSON(['error' => 'Método no permitido'], 405);
}

$pdo = getConexion();

// ── Construir query con filtros ──
$where = [];
$params = [];

if (isset($_GET['tipo']) && trim($_GET['tipo']) !== '') {
    $where[] = 'i.tipo = ?';
    $params[] = trim($_GET['tipo']);
}
if (isset($_GET['desde']) && trim($_GET['desde']) !== '') {
    $where[] = 'i.fecha >= ?';
    $params[] = trim($_GET['desde']);
}
if (isset($_GET['hasta']) && trim($_GET['hasta']) !== '') {
    $where[] = 'i.fecha <= ?';
    $params[] = trim($_GET['hasta']);
}

$sql = 'SELECT i.*, v.tipo AS tipo_vehiculo, v.marca, v.zona
        FROM inspecciones i
        LEFT JOIN vehiculos v ON i.placa = v.placa';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY i.fecha DESC, i.hora DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// ── Nombres legibles de tipos ──
$tipoLabels = [
    'botiquin'            => 'Botiquín',
    'carretilla'          => 'Carretilla',
    'extintor'            => 'Extintor',
    'caja_fuerte'         => 'Caja Fuerte',
    'boton_panico'        => 'Botón de Pánico',
    'inspeccion_vehiculo' => 'Inspección Vehículo',
];

// ── Evaluar cumplimiento (misma lógica que el frontend) ──
function evalCumplimiento($items) {
    if (!is_array($items) || empty($items)) return ['estado' => 'Sin datos', 'pct' => 0];

    $total = 0;
    $cumple = 0;

    foreach ($items as $key => $val) {
        if (is_bool($val)) {
            $total++;
            if ($val) $cumple++;
        } elseif (is_string($val) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
            $total++;
            $d = new DateTime($val);
            $hoy = new DateTime();
            $dias = (int) $hoy->diff($d)->format('%r%a');
            if ($dias >= 0) $cumple++;
        }
        // strings no-fecha (como "Número carretilla") no se evalúan
    }

    if ($total === 0) return ['estado' => 'N/A', 'pct' => 100];

    $pct = round($cumple / $total * 100);
    if ($pct === 100) return ['estado' => 'Cumple', 'pct' => 100];
    if ($pct >= 70) return ['estado' => 'Alerta', 'pct' => $pct];
    return ['estado' => 'No cumple', 'pct' => $pct];
}

// ── Crear Excel ──
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Inspecciones');

// Encabezados
$headers = ['Placa', 'Tipo Vehículo', 'Zona', 'Tipo Inspección', 'Fecha', 'Hora', 'Inspector', 'Estado', '% Cumplimiento', 'Observaciones'];
$col = 'A';
foreach ($headers as $h) {
    $sheet->setCellValue($col . '1', $h);
    $col++;
}

// Estilo encabezados
$headerRange = 'A1:J1';
$sheet->getStyle($headerRange)->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1B2A4A']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
]);
$sheet->getRowDimension(1)->setRowHeight(28);

// Datos
$row = 2;
foreach ($rows as $r) {
    $items = is_string($r['items']) ? json_decode($r['items'], true) : $r['items'];
    $ev = evalCumplimiento($items);

    $sheet->setCellValue("A{$row}", $r['placa']);
    $sheet->setCellValue("B{$row}", $r['tipo_vehiculo'] ?? '');
    $sheet->setCellValue("C{$row}", $r['zona'] ?? '');
    $sheet->setCellValue("D{$row}", $tipoLabels[$r['tipo']] ?? $r['tipo']);
    $sheet->setCellValue("E{$row}", $r['fecha']);
    $sheet->setCellValue("F{$row}", $r['hora']);
    $sheet->setCellValue("G{$row}", $r['inspector']);
    $sheet->setCellValue("H{$row}", $ev['estado']);
    $sheet->setCellValue("I{$row}", $ev['pct'] . '%');
    $sheet->setCellValue("J{$row}", $r['observaciones'] ?? '');

    // Color según estado
    $color = 'FFFFFF';
    if ($ev['estado'] === 'Cumple') $color = 'E6F4ED';
    elseif ($ev['estado'] === 'Alerta') $color = 'FEF7E8';
    elseif ($ev['estado'] === 'No cumple') $color = 'FCEAEA';

    $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E0E0E0']]],
    ]);

    $row++;
}

// Autoajustar ancho de columnas
foreach (range('A', 'J') as $c) {
    $sheet->getColumnDimension($c)->setAutoSize(true);
}

// Filtros automáticos
$sheet->setAutoFilter("A1:J" . ($row - 1));

// Congelar primera fila
$sheet->freezePane('A2');

// ── Enviar archivo ──
$filename = 'Inspecciones_CONALCA_' . date('Y-m-d') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
