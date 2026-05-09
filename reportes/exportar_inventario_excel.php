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

$categoria = $_GET['categoria'] ?? '';
$estado    = $_GET['estado']    ?? '';

$where = "WHERE p.activo=1";
if ($categoria) $where .= " AND p.categoria_id=$categoria";
if ($estado === 'stock_bajo') $where .= " AND p.stock_actual <= p.stock_minimo";
if ($estado === 'por_vencer') $where .= " AND p.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND p.fecha_vencimiento >= CURDATE()";

$productos = $db->query("SELECT p.*, c.nombre AS categoria, pr.nombre AS proveedor, (p.stock_actual * p.precio_venta) AS valor_inventario FROM productos p JOIN categorias c ON p.categoria_id=c.id JOIN proveedores pr ON p.proveedor_id=pr.id $where ORDER BY p.nombre");
$resumen   = $db->query("SELECT COUNT(*) AS total, IFNULL(SUM(stock_actual * precio_venta),0) AS valor, SUM(CASE WHEN stock_actual<=stock_minimo THEN 1 ELSE 0 END) AS stock_bajo FROM productos WHERE activo=1")->fetch_assoc();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Inventario');

// Título
$sheet->mergeCells('A1:J1');
$sheet->setCellValue('A1', 'MiniMarket G2 — Reporte de Inventario');
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E79']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->getRowDimension(1)->setRowHeight(25);

// Resumen
$sheet->setCellValue('A2', 'Total productos: ' . $resumen['total']);
$sheet->setCellValue('D2', 'Valor inventario: RD$ ' . number_format($resumen['valor'], 0));
$sheet->setCellValue('G2', 'Stock bajo: ' . $resumen['stock_bajo']);
$sheet->setCellValue('I2', 'Generado: ' . date('d/m/Y H:i'));
$sheet->getStyle('A2:J2')->getFont()->setBold(true);

// Encabezados
$headers = ['Producto', 'Categoría', 'Proveedor', 'P.Compra', 'P.Venta', 'Stock', 'Mínimo', 'Vencimiento', 'Valor', 'Estado'];
$cols = ['A','B','C','D','E','F','G','H','I','J'];
foreach ($headers as $i => $h) {
    $sheet->setCellValue($cols[$i] . '4', $h);
}
$sheet->getStyle('A4:J4')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E75B6']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
]);

// Datos
$row = 5;
$fill = false;
while ($p = $productos->fetch_assoc()) {
    $sb = $p['stock_actual'] <= $p['stock_minimo'];
    $pv = $p['fecha_vencimiento'] && $p['fecha_vencimiento'] <= date('Y-m-d', strtotime('+7 days'));
    $estado_txt = $sb ? 'Stock Bajo' : ($pv ? 'Por Vencer' : 'OK');
    $color = $fill ? 'F0F4F8' : 'FFFFFF';

    $sheet->setCellValue('A' . $row, $p['nombre']);
    $sheet->setCellValue('B' . $row, $p['categoria']);
    $sheet->setCellValue('C' . $row, $p['proveedor']);
    $sheet->setCellValue('D' . $row, number_format($p['precio_compra'], 2));
    $sheet->setCellValue('E' . $row, number_format($p['precio_venta'], 2));
    $sheet->setCellValue('F' . $row, $p['stock_actual']);
    $sheet->setCellValue('G' . $row, $p['stock_minimo']);
    $sheet->setCellValue('H' . $row, $p['fecha_vencimiento'] ? date('d/m/Y', strtotime($p['fecha_vencimiento'])) : '—');
    $sheet->setCellValue('I' . $row, number_format($p['valor_inventario'], 0));
    $sheet->setCellValue('J' . $row, $estado_txt);

    $rowColor = $sb ? 'FFE0E0' : ($pv ? 'FFF3CD' : $color);
    $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray([
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $rowColor]],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
    ]);
    $row++;
    $fill = !$fill;
}

// Ancho columnas
$sheet->getColumnDimension('A')->setWidth(30);
$sheet->getColumnDimension('B')->setWidth(18);
$sheet->getColumnDimension('C')->setWidth(20);
$sheet->getColumnDimension('D')->setWidth(12);
$sheet->getColumnDimension('E')->setWidth(12);
$sheet->getColumnDimension('F')->setWidth(10);
$sheet->getColumnDimension('G')->setWidth(10);
$sheet->getColumnDimension('H')->setWidth(15);
$sheet->getColumnDimension('I')->setWidth(15);
$sheet->getColumnDimension('J')->setWidth(14);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="reporte_inventario_' . date('Ymd') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
