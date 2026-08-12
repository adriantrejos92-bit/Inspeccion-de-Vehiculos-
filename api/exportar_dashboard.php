<?php
// ============================================================
//  API: EXPORTAR DASHBOARD — Excel con una hoja por tipo de inspección
//  Cada hoja muestra los vehículos que NO cumplen con ese tipo
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

// Tipos de inspección
$tipos = [
    'botiquin'            => 'Botiquín',
    'carretilla'          => 'Carretilla',
    'extintor'            => 'Extintor',
    'caja_fuerte'         => 'Caja Fuerte',
    'boton_panico'        => 'Botón Pánico',
    'inspeccion_vehiculo' => 'Insp. Vehículo',
];

// Obtener todos los vehículos
$vehiculos = $pdo->query('SELECT * FROM vehiculos ORDER BY placa')->fetchAll();

// Obtener la última inspección de cada tipo por placa
function getUltimaInspeccion($pdo, $placa, $tipo) {
    $stmt = $pdo->prepare(
        'SELECT * FROM inspecciones WHERE placa = ? AND tipo = ? ORDER BY fecha DESC, hora DESC LIMIT 1'
    );
    $stmt->execute([$placa, $tipo]);
    $row = $stmt->fetch();
    if ($row) {
        $row['items'] = is_string($row['items']) ? json_decode($row['items'], true) : $row['items'];
    }
    return $row;
}

// Evaluar cumplimiento de una inspección
function evaluar($items) {
    if (!is_array($items) || empty($items)) return ['estado' => 'Sin inspección', 'pct' => 0, 'alertas' => [], 'vencidos' => [], 'noOk' => []];

    $hoy = new DateTime();
    $total = 0; $cumple = 0;
    $alertas = []; $vencidos = []; $noOk = [];

    foreach ($items as $key => $val) {
        $label = str_replace('_', ' ', $key);
        if (is_bool($val)) {
            $total++;
            if ($val) $cumple++;
            else $noOk[] = $label;
        } elseif (is_string($val) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
            $total++;
            $d = new DateTime($val);
            $dias = (int) $hoy->diff($d)->format('%r%a');
            if ($dias < 0) {
                $vencidos[] = $label . ' (vencido ' . abs($dias) . 'd)';
            } elseif ($dias <= 30) {
                $alertas[] = $label . ' (' . $dias . 'd restantes)';
                $cumple++;
            } else {
                $cumple++;
            }
        }
    }

    if ($total === 0) return ['estado' => 'N/A', 'pct' => 100, 'alertas' => [], 'vencidos' => [], 'noOk' => []];

    $pct = round($cumple / $total * 100);
    $hasFail = count($vencidos) > 0 || count($noOk) > 0;
    $hasWarn = count($alertas) > 0;

    if ($hasFail) $estado = 'No cumple';
    elseif ($hasWarn) $estado = 'Alerta';
    else $estado = 'Cumple';

    return ['estado' => $estado, 'pct' => $pct, 'alertas' => $alertas, 'vencidos' => $vencidos, 'noOk' => $noOk];
}

// Colores para encabezados por tipo
$coloresTipo = [
    'botiquin'            => '6D5ACE',
    'carretilla'          => 'C26A18',
    'extintor'            => 'C43131',
    'caja_fuerte'         => '2E86AB',
    'boton_panico'        => '8B5E3C',
    'inspeccion_vehiculo' => '2D8659',
];

// ── Crear Excel ──
$spreadsheet = new Spreadsheet();
$spreadsheet->removeSheetByIndex(0); // quitar hoja por defecto

$sheetIndex = 0;
foreach ($tipos as $tipoKey => $tipoLabel) {
    $sheet = $spreadsheet->createSheet($sheetIndex);
    $sheet->setTitle($tipoLabel);

    // Encabezados
    $headers = ['Placa', 'Tipo Vehículo', 'Zona', 'Estado', '% Cumplimiento', 'Última Inspección', 'Inspector', 'Observaciones'];
    $col = 'A';
    foreach ($headers as $h) {
        $sheet->setCellValue($col . '1', $h);
        $col++;
    }

    // Estilo encabezados
    $headerColor = $coloresTipo[$tipoKey] ?? '1B2A4A';
    $sheet->getStyle('A1:H1')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $headerColor]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
    ]);
    $sheet->getRowDimension(1)->setRowHeight(28);

    // Datos — solo vehículos que NO cumplen o tienen alertas o sin inspección
    $row = 2;
    foreach ($vehiculos as $v) {
        $insp = getUltimaInspeccion($pdo, $v['placa'], $tipoKey);

        if (!$insp) {
            // Sin inspección
            $sheet->setCellValue("A{$row}", $v['placa']);
            $sheet->setCellValue("B{$row}", $v['tipo'] ?? '');
            $sheet->setCellValue("C{$row}", $v['zona'] ?? '');
            $sheet->setCellValue("D{$row}", 'Sin inspección');
            $sheet->setCellValue("E{$row}", '0%');
            $sheet->setCellValue("F{$row}", '—');
            $sheet->setCellValue("G{$row}", '—');
            $sheet->setCellValue("H{$row}", '');

            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F5F5']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E0E0E0']]],
            ]);
            $row++;
            continue;
        }

        $ev = evaluar($insp['items']);

        // Solo incluir los que NO cumplen, tienen alertas o sin inspección
        if ($ev['estado'] === 'Cumple') continue;

        // Armar observaciones
        $obsItems = [];
        foreach ($ev['vencidos'] as $x) $obsItems[] = '❌ ' . $x;
        foreach ($ev['noOk'] as $x) $obsItems[] = '❌ ' . $x . ' (no cumple)';
        foreach ($ev['alertas'] as $x) $obsItems[] = '⚠ ' . $x;
        if (!empty($insp['observaciones'])) $obsItems[] = $insp['observaciones'];

        $sheet->setCellValue("A{$row}", $v['placa']);
        $sheet->setCellValue("B{$row}", $v['tipo'] ?? '');
        $sheet->setCellValue("C{$row}", $v['zona'] ?? '');
        $sheet->setCellValue("D{$row}", $ev['estado']);
        $sheet->setCellValue("E{$row}", $ev['pct'] . '%');
        $sheet->setCellValue("F{$row}", $insp['fecha']);
        $sheet->setCellValue("G{$row}", $insp['inspector']);
        $sheet->setCellValue("H{$row}", implode(', ', $obsItems));

        // Color según estado
        $color = 'FFFFFF';
        if ($ev['estado'] === 'No cumple') $color = 'FCEAEA';
        elseif ($ev['estado'] === 'Alerta') $color = 'FEF7E8';

        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E0E0E0']]],
        ]);
        $row++;
    }

    // Si no hay datos, agregar mensaje
    if ($row === 2) {
        $sheet->setCellValue('A2', 'Todos los vehículos cumplen con ' . $tipoLabel);
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'color' => ['rgb' => '1A7D4B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
    }

    // Autoajustar columnas
    foreach (range('A', 'H') as $c) {
        $sheet->getColumnDimension($c)->setAutoSize(true);
    }

    // Filtros y congelar fila
    if ($row > 2) {
        $sheet->setAutoFilter('A1:H' . ($row - 1));
    }
    $sheet->freezePane('A2');

    $sheetIndex++;
}

// Activar primera hoja
$spreadsheet->setActiveSheetIndex(0);

// ── Enviar archivo ──
$filename = 'Dashboard_CONALCA_' . date('Y-m-d') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
