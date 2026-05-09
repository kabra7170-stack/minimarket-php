<?php
require_once '../config/database.php';
require_once '../config/session.php';
requireLogin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit(); }

$venta   = $db->query("SELECT v.*, u.nombre AS cajero, c.nombre AS cliente, c.cedula, c.telefono FROM ventas v JOIN usuarios u ON v.usuario_id=u.id LEFT JOIN clientes c ON v.cliente_id=c.id WHERE v.id=$id")->fetch_assoc();
$detalle = $db->query("SELECT dv.*, p.nombre AS producto, p.codigo_barras FROM detalle_ventas dv JOIN productos p ON dv.producto_id=p.id WHERE dv.venta_id=$id");

if (!$venta) { echo 'Venta no encontrada'; exit(); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Factura <?= $venta['num_factura'] ?></title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Courier New', monospace; font-size: 12px; width: 300px; margin: 0 auto; padding: 10px; }
  .center { text-align: center; }
  .bold { font-weight: bold; }
  .line { border-top: 1px dashed #000; margin: 6px 0; }
  .row { display: flex; justify-content: space-between; margin: 2px 0; }
  .total-row { display: flex; justify-content: space-between; font-size: 14px; font-weight: bold; margin: 4px 0; }
  table { width: 100%; border-collapse: collapse; }
  td { padding: 2px 0; vertical-align: top; }
  .btn-print { display: block; margin: 16px auto; padding: 8px 24px; background: #1F4E79; color: white; border: none; cursor: pointer; font-size: 14px; border-radius: 4px; }
  @media print { .btn-print { display: none; } body { width: 100%; } }
</style>
</head>
<body>

<div class="center bold" style="font-size:14px;">MINIMARKET GRUPO 2</div>
<div class="center">Santo Domingo, Rep. Dominicana</div>
<div class="center">Tel: 809-555-0000</div>
<div class="line"></div>

<div class="center bold">COMPROBANTE DE VENTA</div>
<div class="line"></div>

<div class="row"><span>Factura:</span><span class="bold"><?= $venta['num_factura'] ?></span></div>
<div class="row"><span>Fecha:</span><span><?= date('d/m/Y H:i', strtotime($venta['fecha'])) ?></span></div>
<div class="row"><span>Cajero:</span><span><?= htmlspecialchars($venta['cajero']) ?></span></div>
<div class="row"><span>Cliente:</span><span><?= htmlspecialchars($venta['cliente'] ?? 'General') ?></span></div>
<?php if ($venta['cedula']): ?>
<div class="row"><span>Cédula:</span><span><?= $venta['cedula'] ?></span></div>
<?php endif; ?>
<div class="line"></div>

<table>
  <tr><td class="bold">Producto</td><td class="bold" style="text-align:right;">Cant</td><td class="bold" style="text-align:right;">Precio</td><td class="bold" style="text-align:right;">Sub</td></tr>
  <tr><td colspan="4"><div class="line"></div></td></tr>
  <?php while ($d = $detalle->fetch_assoc()): ?>
  <tr>
    <td><?= htmlspecialchars($d['producto']) ?></td>
    <td style="text-align:right;"><?= $d['cantidad'] ?></td>
    <td style="text-align:right;"><?= number_format($d['precio_unitario'], 2) ?></td>
    <td style="text-align:right;"><?= number_format($d['subtotal'], 2) ?></td>
  </tr>
  <?php endwhile; ?>
</table>

<div class="line"></div>
<div class="total-row"><span>TOTAL:</span><span>RD$ <?= number_format($venta['total'], 2) ?></span></div>
<div class="row"><span>Método de pago:</span><span><?= ucfirst($venta['metodo_pago']) ?></span></div>
<div class="line"></div>

<div class="center">¡Gracias por su compra!</div>
<div class="center">Vuelva pronto</div>
<div class="center" style="margin-top:6px; font-size:10px;">Sistema MiniMarket G2 — 2026</div>

<button class="btn-print" onclick="window.print()">🖨️ Imprimir Factura</button>

</body>
</html>
