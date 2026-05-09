<?php
require_once '../config/database.php';
require_once '../config/session.php';
requireLogin();

require_once '../vendor/autoload.php';

$db = getDB();

$categoria = $_GET['categoria'] ?? '';
$estado    = $_GET['estado']    ?? '';

$where = "WHERE p.activo=1";
if ($categoria) $where .= " AND p.categoria_id=$categoria";
if ($estado === 'stock_bajo') $where .= " AND p.stock_actual <= p.stock_minimo";
if ($estado === 'por_vencer') $where .= " AND p.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND p.fecha_vencimiento >= CURDATE()";

$productos = $db->query("SELECT p.*, c.nombre AS categoria, pr.nombre AS proveedor, (p.stock_actual * p.precio_venta) AS valor_inventario FROM productos p JOIN categorias c ON p.categoria_id=c.id JOIN proveedores pr ON p.proveedor_id=pr.id $where ORDER BY p.nombre");
$resumen   = $db->query("SELECT COUNT(*) AS total, IFNULL(SUM(stock_actual * precio_venta),0) AS valor, SUM(CASE WHEN stock_actual<=stock_minimo THEN 1 ELSE 0 END) AS stock_bajo FROM productos WHERE activo=1")->fetch_assoc();

$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('MiniMarket G2');
$pdf->SetTitle('Reporte de Inventario');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();

// Título
$pdf->SetFont('helvetica', 'B', 18);
$pdf->SetTextColor(31, 78, 121);
$pdf->Cell(0, 10, 'MiniMarket G2 — Reporte de Inventario', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 6, 'Generado: ' . date('d/m/Y H:i'), 0, 1, 'C');
$pdf->Ln(4);

// Resumen
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(31, 78, 121);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(87, 8, 'Total productos: ' . $resumen['total'], 0, 0, 'L', true);
$pdf->Cell(87, 8, 'Valor inventario: RD$ ' . number_format($resumen['valor'], 0), 0, 0, 'L', true);
$pdf->Cell(87, 8, 'Stock bajo: ' . $resumen['stock_bajo'], 0, 1, 'L', true);
$pdf->Ln(4);

// Encabezado tabla
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetFillColor(46, 117, 182);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(65, 7, 'Producto', 1, 0, 'C', true);
$pdf->Cell(35, 7, 'Categoría', 1, 0, 'C', true);
$pdf->Cell(35, 7, 'Proveedor', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'P.Compra', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'P.Venta', 1, 0, 'C', true);
$pdf->Cell(20, 7, 'Stock', 1, 0, 'C', true);
$pdf->Cell(15, 7, 'Mín.', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Vencimiento', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Valor', 1, 0, 'C', true);
$pdf->Cell(21, 7, 'Estado', 1, 1, 'C', true);

// Filas
$pdf->SetFont('helvetica', '', 7);
$fill = false;
while ($p = $productos->fetch_assoc()) {
    $sb = $p['stock_actual'] <= $p['stock_minimo'];
    $pv = $p['fecha_vencimiento'] && $p['fecha_vencimiento'] <= date('Y-m-d', strtotime('+7 days'));
    $estado_txt = $sb ? 'Stock Bajo' : ($pv ? 'Por Vencer' : 'OK');

    $pdf->SetFillColor($fill ? 240 : 255, $fill ? 244 : 255, $fill ? 248 : 255);
    $pdf->SetTextColor(30, 30, 30);
    $pdf->Cell(65, 6, $p['nombre'], 1, 0, 'L', $fill);
    $pdf->Cell(35, 6, $p['categoria'], 1, 0, 'L', $fill);
    $pdf->Cell(35, 6, $p['proveedor'], 1, 0, 'L', $fill);
    $pdf->Cell(30, 6, 'RD$ ' . number_format($p['precio_compra'], 2), 1, 0, 'R', $fill);
    $pdf->Cell(30, 6, 'RD$ ' . number_format($p['precio_venta'], 2), 1, 0, 'R', $fill);
    $pdf->Cell(20, 6, $p['stock_actual'], 1, 0, 'C', $fill);
    $pdf->Cell(15, 6, $p['stock_minimo'], 1, 0, 'C', $fill);
    $pdf->Cell(30, 6, $p['fecha_vencimiento'] ? date('d/m/Y', strtotime($p['fecha_vencimiento'])) : '—', 1, 0, 'C', $fill);
    $pdf->Cell(30, 6, 'RD$ ' . number_format($p['valor_inventario'], 0), 1, 0, 'R', $fill);
    $pdf->Cell(21, 6, $estado_txt, 1, 1, 'C', $fill);
    $fill = !$fill;
}

$pdf->Output('reporte_inventario_' . date('Ymd') . '.pdf', 'D');
