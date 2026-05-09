<?php
require_once '../config/database.php';
require_once '../config/session.php';
requireLogin();

require_once '../vendor/autoload.php';

$db = getDB();

$desde  = $_GET['desde']  ?? date('Y-m-01');
$hasta  = $_GET['hasta']  ?? date('Y-m-d');
$metodo = $_GET['metodo'] ?? '';

$where = "WHERE v.estado='completada' AND DATE(v.fecha) BETWEEN '$desde' AND '$hasta'";
if ($metodo) $where .= " AND v.metodo_pago='$metodo'";

$resumen = $db->query("SELECT COUNT(*) AS total_ventas, IFNULL(SUM(v.total),0) AS ingresos FROM ventas v $where")->fetch_assoc();
$ventas  = $db->query("SELECT v.*, u.nombre AS cajero, c.nombre AS cliente FROM ventas v JOIN usuarios u ON v.usuario_id=u.id LEFT JOIN clientes c ON v.cliente_id=c.id $where ORDER BY v.fecha DESC");

// Crear PDF
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('MiniMarket G2');
$pdf->SetAuthor('MiniMarket G2');
$pdf->SetTitle('Reporte de Ventas');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();

// Título
$pdf->SetFont('helvetica', 'B', 18);
$pdf->SetTextColor(31, 78, 121);
$pdf->Cell(0, 10, 'MiniMarket G2 — Reporte de Ventas', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 6, 'Período: ' . date('d/m/Y', strtotime($desde)) . ' al ' . date('d/m/Y', strtotime($hasta)), 0, 1, 'C');
$pdf->Cell(0, 6, 'Generado: ' . date('d/m/Y H:i'), 0, 1, 'C');
$pdf->Ln(4);

// Resumen
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetFillColor(31, 78, 121);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(130, 8, 'Total de Ventas: ' . $resumen['total_ventas'], 0, 0, 'L', true);
$pdf->Cell(130, 8, 'Ingresos Totales: RD$ ' . number_format($resumen['ingresos'], 2), 0, 1, 'L', true);
$pdf->Ln(4);

// Encabezado tabla
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(46, 117, 182);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(50, 7, 'Factura', 1, 0, 'C', true);
$pdf->Cell(50, 7, 'Cajero', 1, 0, 'C', true);
$pdf->Cell(60, 7, 'Cliente', 1, 0, 'C', true);
$pdf->Cell(40, 7, 'Total', 1, 0, 'C', true);
$pdf->Cell(35, 7, 'Método', 1, 0, 'C', true);
$pdf->Cell(45, 7, 'Fecha', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Estado', 1, 1, 'C', true);

// Filas
$pdf->SetFont('helvetica', '', 8);
$fill = false;
while ($v = $ventas->fetch_assoc()) {
    $pdf->SetFillColor($fill ? 240 : 255, $fill ? 244 : 255, $fill ? 248 : 255);
    $pdf->SetTextColor(30, 30, 30);
    $pdf->Cell(50, 6, $v['num_factura'], 1, 0, 'L', $fill);
    $pdf->Cell(50, 6, $v['cajero'], 1, 0, 'L', $fill);
    $pdf->Cell(60, 6, $v['cliente'] ?? 'General', 1, 0, 'L', $fill);
    $pdf->Cell(40, 6, 'RD$ ' . number_format($v['total'], 2), 1, 0, 'R', $fill);
    $pdf->Cell(35, 6, ucfirst($v['metodo_pago']), 1, 0, 'C', $fill);
    $pdf->Cell(45, 6, date('d/m/Y H:i', strtotime($v['fecha'])), 1, 0, 'C', $fill);
    $pdf->Cell(30, 6, ucfirst($v['estado']), 1, 1, 'C', $fill);
    $fill = !$fill;
}

$pdf->Output('reporte_ventas_' . date('Ymd') . '.pdf', 'D');
