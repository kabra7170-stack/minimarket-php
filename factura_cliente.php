<?php
require_once 'config/database.php';
require_once 'config/session.php';
requireCliente();

$db       = getDB();
$cliente  = currentCliente();
$venta_id = (int)($_GET['venta'] ?? 0);

$venta = $db->query("
    SELECT v.*, c.nombre AS cliente_nom, c.cedula, c.telefono, c.email AS cliente_email, c.direccion
    FROM ventas v
    JOIN clientes c ON v.cliente_id = c.id
    WHERE v.id = $venta_id AND v.cliente_id = {$cliente['id']}
")->fetch_assoc();

if (!$venta) { header('Location: catalogo.php'); exit(); }

$detalle = $db->query("
    SELECT dv.*, p.nombre AS producto
    FROM detalle_ventas dv
    JOIN productos p ON dv.producto_id = p.id
    WHERE dv.venta_id = $venta_id
");

$domicilio = $db->query("
    SELECT pd.* FROM pedidos_domicilio pd
    JOIN pedidos p ON pd.pedido_id = p.id
    WHERE p.cliente_id = {$cliente['id']}
    ORDER BY p.fecha DESC LIMIT 1
")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Factura <?= $venta['num_factura'] ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
  :root{--primary:#1F4E79;}
  body{background:#F0F4F8;font-family:'Segoe UI',sans-serif;}
  .navbar-top{background:linear-gradient(135deg,#1F4E79,#2E75B6);padding:10px 24px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 12px rgba(0,0,0,.2);}
  .navbar-top .brand{color:#fff;font-weight:700;font-size:18px;text-decoration:none;}
  .btn-nav{background:rgba(255,255,255,.15);color:#fff;border:2px solid rgba(255,255,255,.4);border-radius:10px;padding:6px 16px;font-weight:600;font-size:13px;text-decoration:none;}
  .btn-nav:hover{background:#fff;color:#1F4E79;}
  .content{padding:24px;max-width:700px;margin:0 auto;}
  .factura{background:#fff;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.1);overflow:hidden;}
  .fac-header{background:linear-gradient(135deg,#1F4E79,#2E75B6);color:#fff;padding:28px 32px;text-align:center;}
  .fac-header i{font-size:42px;opacity:.9;}
  .fac-header h4{font-weight:800;margin:8px 0 4px;font-size:20px;}
  .fac-header small{opacity:.8;font-size:12px;}
  .fac-num{background:rgba(255,255,255,.2);padding:6px 18px;border-radius:20px;font-size:13px;font-weight:700;margin-top:10px;display:inline-block;}
  .fac-body{padding:24px 32px;}
  .info-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;}
  .info-item label{font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:.6px;display:block;margin-bottom:2px;}
  .info-item span{font-size:13px;font-weight:600;color:#1E293B;}
  .tabla-fac{width:100%;border-collapse:collapse;margin-bottom:16px;}
  .tabla-fac thead tr{background:#F8FAFC;}
  .tabla-fac thead th{padding:8px 12px;font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #E2E8F0;}
  .tabla-fac tbody td{padding:10px 12px;font-size:13px;border-bottom:1px solid #F1F5F9;}
  .totales{background:#F8FAFC;border-radius:10px;padding:16px 20px;}
  .success-banner{background:linear-gradient(135deg,#0F6E56,#1aad87);color:#fff;padding:14px 20px;border-radius:10px;margin-bottom:20px;display:flex;align-items:center;gap:12px;}
  .success-banner i{font-size:28px;}
  @media print {
    .navbar-top,.btn-imprimir,.btn-nav { display:none!important; }
    body { background:#fff; }
    .content { padding:0; max-width:100%; }
    .factura { box-shadow:none; border-radius:0; }
  }
</style>
</head>
<body>

<div class="navbar-top">
  <a href="catalogo.php" class="brand"><i class="bi bi-shop me-2"></i>MiniMarket G2</a>
  <div class="d-flex gap-2">
    <button onclick="window.print()" class="btn-nav btn-imprimir"><i class="bi bi-printer me-1"></i>Imprimir</button>
    <a href="catalogo.php" class="btn-nav"><i class="bi bi-shop me-1"></i>Seguir comprando</a>
  </div>
</div>

<div class="content mt-4">

  <!-- Banner éxito -->
  <div class="success-banner">
    <i class="bi bi-check-circle-fill"></i>
    <div>
      <div class="fw-bold fs-6">¡Pedido confirmado!</div>
      <div style="font-size:13px;opacity:.9">Tu pedido está siendo procesado. Te contactaremos pronto.</div>
    </div>
  </div>

  <div class="factura">
    <!-- Encabezado -->
    <div class="fac-header">
      <i class="bi bi-receipt"></i>
      <h4>MiniMarket Grupo 2</h4>
      <small>Sistema de Gestión Integral — Grupo 2, 5to I, 2026</small>
      <div class="fac-num"><?= htmlspecialchars($venta['num_factura']) ?></div>
    </div>

    <div class="fac-body">

      <!-- Info cliente y venta -->
      <div class="info-grid">
        <div class="info-item">
          <label>Cliente</label>
          <span><?= htmlspecialchars($venta['cliente_nom']) ?></span>
        </div>
        <div class="info-item">
          <label>Fecha</label>
          <span><?= date('d/m/Y H:i', strtotime($venta['fecha'])) ?></span>
        </div>
        <div class="info-item">
          <label>Teléfono</label>
          <span><?= htmlspecialchars($venta['telefono'] ?? '—') ?></span>
        </div>
        <div class="info-item">
          <label>Método de pago</label>
          <span><?= ucfirst($venta['metodo_pago']) ?></span>
        </div>
        <?php if ($domicilio): ?>
        <div class="info-item" style="grid-column:span 2">
          <label>Dirección de entrega</label>
          <span><?= htmlspecialchars($domicilio['direccion']) ?></span>
        </div>
        <?php endif; ?>
      </div>

      <hr style="border-color:#E2E8F0;margin:0 0 18px;">

      <!-- Tabla productos -->
      <table class="tabla-fac">
        <thead>
          <tr>
            <th>Producto</th>
            <th class="text-center">Cant.</th>
            <th class="text-end">Precio</th>
            <th class="text-end">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($d = $detalle->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($d['producto']) ?></td>
            <td class="text-center"><?= $d['cantidad'] ?></td>
            <td class="text-end">RD$ <?= number_format($d['precio_unitario'],2) ?></td>
            <td class="text-end">RD$ <?= number_format($d['subtotal'],2) ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>

      <!-- Totales -->
      <div class="totales">
        <div class="d-flex justify-content-between small mb-1">
          <span class="text-muted">Subtotal productos:</span>
          <span>RD$ <?= number_format($venta['subtotal'],2) ?></span>
        </div>
        <?php if ($domicilio): ?>
        <div class="d-flex justify-content-between small mb-1">
          <span class="text-muted">Costo de envío:</span>
          <span>RD$ <?= number_format($domicilio['costo_envio'],2) ?></span>
        </div>
        <?php endif; ?>
        <hr style="border-color:#E2E8F0;margin:8px 0;">
        <div class="d-flex justify-content-between">
          <strong style="font-size:16px;">TOTAL:</strong>
          <strong style="font-size:20px;color:#1F4E79;">RD$ <?= number_format($venta['total'],2) ?></strong>
        </div>
      </div>

      <p class="text-center text-muted mt-4 mb-0" style="font-size:11px;">
        Gracias por tu compra • MiniMarket G2 • Grupo 2 — 5to I — 2026
      </p>
    </div>
  </div>
</div>
</body>
</html>
