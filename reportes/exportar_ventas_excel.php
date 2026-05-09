<?php
require_once '../config/database.php';
require_once '../config/session.php';
requireLogin();

require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

$db = getDB();

$desde  = $_GET['desde']  ?? date('Y-m-01');
$hasta  = $_GET['hasta']  ?? date('Y-m-d');
$metodo = $_GET['metodo'] ?? '';

$where = "WHERE v.estado='completada' AND DATE(v.fecha) BETWEEN '$desde' AND '$hasta'";
if ($metodo) $where .= " AND v.metodo_pago='$metodo'";

$resumen = $db->query("SELECT COUNT(*) AS total_ventas, IFNULL(SUM(v.total),0) AS ingresos FROM ventas v $where")->fetch_assoc();
$ventas  = $db->query("SELECT v.*, u.nombre AS cajero, c.nombre AS cliente FROM ventas v JOIN usuarios u ON v.usuario_id=u.id LEFT JOIN clientes c ON v.cliente_id=c.id $where ORDER BY v.fecha DESC");

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Reporte de Ventas');

// Título
$sheet->mergeCells('A1:G1');
$sheet->setCellValue('A1', 'MiniMarket G2 — Reporte de Ventas');
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E79']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->getRowDimension(1)->setRowHeight(25);

// Período
$sheet->mergeCells('A2:G2');
$sheet->setCellValue('A2', 'Período: ' . date('d/m/Y', strtotime($desde)) . ' al ' . date('d/m/Y', strtotime($hasta)) . ' | Generado: ' . date('d/m/Y H:i'));
$sheet->getStyle('A2')->applyFromArray([
    'font' => ['size' => 10, 'color' => ['rgb' => '666666']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);

// Resumen
$sheet->setCellValue('A3', 'Total Ventas:');
$sheet->setCellValue('B3', $resumen['total_ventas']);
$sheet->setCellValue('C3', 'Ingresos:');
$sheet->setCellValue('D3', 'RD$ ' . number_format($resumen['ingresos'], 2));
$sheet->getStyle('A3:D3')->getFont()->setBold(true);

// Encabezados
$headers = ['Factura', 'Cajero', 'Cliente', 'Total (RD$)', 'Método', 'Fecha', 'Estado'];
$cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
foreach ($headers as $i => $h) {
    $sheet->setCellValue($cols[$i] . '5', $h);
}
$sheet->getStyle('A5:G5')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E75B6']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
]);

// Datos
$row = 6;
$fill = false;
while ($v = $ventas->fetch_assoc()) {
    $color = $fill ? 'F0F4F8' : 'FFFFFF';
    $sheet->setCellValue('A' . $row, $v['num_factura']);
    $sheet->setCellValue('B' . $row, $v['cajero']);
    $sheet->setCellValue('C' . $row, $v['cliente'] ?? 'General');
    $sheet->setCellValue('D' . $row, number_format($v['total'], 2));
    $sheet->setCellValue('E' . $row, ucfirst($v['metodo_pago']));
    $sheet->setCellValue('F' . $row, date('d/m/Y H:i', strtotime($v['fecha'])));
    $sheet->setCellValue('G' . $row, ucfirst($v['estado']));
    $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
    ]);
    $row++;
    $fill = !$fill;
}

// Ancho de columnas
$sheet->getColumnDimension('A')->setWidth(25);
$sheet->getColumnDimension('B')->setWidth(20);
$sheet->getColumnDimension('C')->setWidth(25);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(15);
$sheet->getColumnDimension('F')->setWidth(20);
$sheet->getColumnDimension('G')->setWidth(15);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="reporte_ventas_' . date('Ymd') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
