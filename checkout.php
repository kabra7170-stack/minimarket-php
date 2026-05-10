<?php
require_once 'config/database.php';
require_once 'config/session.php';
requireCliente();

$db      = getDB();
$cliente = currentCliente();

if (empty($_SESSION['carrito'])) {
    header('Location: catalogo.php'); exit();
}

// Cargar datos del cliente
$cli = $db->query("SELECT * FROM clientes WHERE id=".(int)$cliente['id'])->fetch_assoc();

// Cargar items del carrito
$items = []; $subtotal = 0;
$ids = implode(',', array_keys($_SESSION['carrito']));
$res = $db->query("SELECT id,nombre,precio_venta,stock_actual FROM productos WHERE id IN ($ids)");
while ($r = $res->fetch_assoc()) {
    $qty = min($_SESSION['carrito'][$r['id']], $r['stock_actual']);
    $r['qty'] = $qty;
    $r['total_item'] = $qty * $r['precio_venta'];
    $subtotal += $r['total_item'];
    $items[] = $r;
}
$envio = 150;
$total = $subtotal + $envio;

$error = '';

// PROCESAR PEDIDO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $direccion  = trim($_POST['direccion']  ?? $cli['direccion'] ?? '');
    $telefono   = trim($_POST['telefono']   ?? $cli['telefono']  ?? '');
    $metodo     = $_POST['metodo_pago']    ?? 'efectivo';
    $referencia = trim($_POST['referencia'] ?? '');

    if (!$direccion || !$telefono) {
        $error = 'Dirección y teléfono son obligatorios.';
    } else {
        $db->begin_transaction();
        try {
            // Buscar usuario cajero por defecto (id=1)
            $usuario_id = 1;

            // Crear venta
            $num_fac = 'FAC-CLI-' . strtoupper(substr(uniqid(),-6));
            $impuesto = 0;
            $stmt = $db->prepare("INSERT INTO ventas (usuario_id,cliente_id,num_factura,subtotal,impuesto,total,metodo_pago,estado) VALUES (?,?,?,?,?,?,'efectivo','completada')");
            $stmt->bind_param('iisddd', $usuario_id, $cliente['id'], $num_fac, $subtotal, $impuesto, $total);
            $stmt->execute();
            $venta_id = $stmt->insert_id;
            $stmt->close();

            // Detalle venta + descontar stock
            foreach ($items as $it) {
                $st = $db->prepare("INSERT INTO detalle_ventas (venta_id,producto_id,cantidad,precio_unitario,subtotal) VALUES (?,?,?,?,?)");
                $st->bind_param('iiidd', $venta_id, $it['id'], $it['qty'], $it['precio_venta'], $it['total_item']);
                $st->execute(); $st->close();
                $db->query("UPDATE productos SET stock_actual=stock_actual-{$it['qty']} WHERE id={$it['id']}");
            }

            // Crear pedido domicilio
            $num_ped = 'PED-CLI-' . strtoupper(substr(uniqid(),-6));
            $tipo = 'domicilio';
            $stP = $db->prepare("INSERT INTO pedidos (cliente_id,usuario_id,num_pedido,estado,tipo,subtotal,total,notas) VALUES (?,?,'$num_ped','pendiente',?,?,?,?)");
            $stP->bind_param('iisdds', $cliente['id'], $usuario_id, $tipo, $subtotal, $total, $referencia);
            $stP->execute();
            $pedido_id = $stP->insert_id;
            $stP->close();
            // Detalle pedido
            foreach ($items as $it) {
             $stPD = $db->prepare("INSERT INTO detalle_pedidos (pedido_id,producto_id,cantidad,precio_unitario,subtotal) VALUES (?,?,?,?,?)");
             $stPD->bind_param('iiidd', $pedido_id, $it['id'], $it['qty'], $it['precio_venta'], $it['total_item']);
              $stPD->execute(); $stPD->close();
              }
            // Info domicilio
            $stD = $db->prepare("INSERT INTO pedidos_domicilio (pedido_id,direccion,telefono,referencia,costo_envio) VALUES (?,?,?,?,?)");
            $stD->bind_param('isssd', $pedido_id, $direccion, $telefono, $referencia, $envio);
            $stD->execute(); $stD->close();

            $db->commit();
            $_SESSION['carrito'] = [];
            header("Location: factura_cliente.php?venta=$venta_id"); exit();
        } catch (Exception $e) {
            $db->rollback();
            $error = 'Error al procesar el pedido. Intenta de nuevo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout — MiniMarket G2</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
  :root{--primary:#1F4E79;--accent:#2E75B6;}
  body{background:#F0F4F8;font-family:'Segoe UI',sans-serif;}
  .navbar-top{background:linear-gradient(135deg,var(--primary),var(--accent));padding:10px 24px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 12px rgba(0,0,0,.2);}
  .navbar-top .brand{color:#fff;font-weight:700;font-size:18px;text-decoration:none;}
  .btn-nav{background:rgba(255,255,255,.15);color:#fff;border:2px solid rgba(255,255,255,.4);border-radius:10px;padding:6px 16px;font-weight:600;font-size:13px;text-decoration:none;}
  .btn-nav:hover{background:#fff;color:var(--primary);}
  .content{padding:24px;max-width:960px;margin:0 auto;}
  .card{border:none;border-radius:14px;box-shadow:0 2px 10px rgba(0,0,0,.07);}
  .card-header{background:var(--primary);color:#fff;border-radius:14px 14px 0 0!important;font-weight:600;padding:14px 20px;}
  .form-label{font-size:13px;font-weight:600;}
  .form-control,.form-select{border-radius:8px;border:1.5px solid #E5E7EB;font-size:13px;}
  .form-control:focus{border-color:#2E75B6;box-shadow:0 0 0 3px rgba(46,117,182,.12);}
  .btn-pagar{background:linear-gradient(135deg,#0F6E56,#1aad87);color:#fff;border:none;border-radius:10px;padding:12px;font-size:15px;font-weight:700;width:100%;}
  .btn-pagar:hover{opacity:.88;color:#fff;}
  .step-badge{background:var(--primary);color:#fff;border-radius:50%;width:26px;height:26px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;margin-right:8px;}
</style>
</head>
<body>
<div class="navbar-top">
  <a href="catalogo.php" class="brand"><i class="bi bi-shop me-2"></i>MiniMarket G2</a>
  <a href="carrito.php" class="btn-nav"><i class="bi bi-arrow-left me-1"></i>Volver al carrito</a>
</div>

<div class="content">
  <h5 class="fw-bold mb-3" style="color:var(--primary)"><i class="bi bi-credit-card me-2"></i>Finalizar Compra</h5>

  <?php if ($error): ?>
    <div class="alert alert-danger py-2 mb-3" style="font-size:13px;border-radius:10px;">
      <i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="POST">
  <div class="row g-3">

    <!-- Datos de entrega -->
    <div class="col-md-7">
      <div class="card mb-3">
        <div class="card-header"><span class="step-badge">1</span>Datos de Entrega</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Nombre</label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($cli['nombre']) ?>" readonly>
            </div>
            <div class="col-md-8">
              <label class="form-label">Dirección de entrega *</label>
              <input type="text" name="direccion" class="form-control" placeholder="Calle, número, sector..." value="<?= htmlspecialchars($cli['direccion'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Teléfono *</label>
              <input type="text" name="telefono" class="form-control" placeholder="809-000-0000" value="<?= htmlspecialchars($cli['telefono'] ?? '') ?>" required>
            </div>
            <div class="col-12">
              <label class="form-label">Referencia / Notas</label>
              <textarea name="referencia" class="form-control" rows="2" placeholder="Color de la casa, punto de referencia..."></textarea>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><span class="step-badge">2</span>Método de Pago</div>
        <div class="card-body">
          <div class="row g-2">
            <div class="col-4">
              <input type="radio" class="btn-check" name="metodo_pago" id="mEfectivo" value="efectivo" checked>
              <label class="btn btn-outline-primary w-100 py-3" for="mEfectivo">
                <i class="bi bi-cash-stack d-block fs-4 mb-1"></i><small>Efectivo</small>
              </label>
            </div>
            <div class="col-4">
              <input type="radio" class="btn-check" name="metodo_pago" id="mTarjeta" value="tarjeta">
              <label class="btn btn-outline-primary w-100 py-3" for="mTarjeta">
                <i class="bi bi-credit-card d-block fs-4 mb-1"></i><small>Tarjeta</small>
              </label>
            </div>
            <div class="col-4">
              <input type="radio" class="btn-check" name="metodo_pago" id="mTransfer" value="transferencia">
              <label class="btn btn-outline-primary w-100 py-3" for="mTransfer">
                <i class="bi bi-phone d-block fs-4 mb-1"></i><small>Transferencia</small>
              </label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Resumen -->
    <div class="col-md-5">
      <div class="card">
        <div class="card-header"><span class="step-badge">3</span>Resumen del Pedido</div>
        <div class="card-body p-0">
          <?php foreach ($items as $it): ?>
          <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
            <div>
              <div class="small fw-semibold"><?= htmlspecialchars($it['nombre']) ?></div>
              <div class="text-muted" style="font-size:11px">x<?= $it['qty'] ?> × RD$ <?= number_format($it['precio_venta'],2) ?></div>
            </div>
            <strong class="small">RD$ <?= number_format($it['total_item'],2) ?></strong>
          </div>
          <?php endforeach; ?>
          <div class="p-3">
            <div class="d-flex justify-content-between small mb-1"><span>Subtotal:</span><span>RD$ <?= number_format($subtotal,2) ?></span></div>
            <div class="d-flex justify-content-between small mb-2"><span>Envío:</span><span>RD$ <?= number_format($envio,2) ?></span></div>
            <hr class="my-2">
            <div class="d-flex justify-content-between mb-3">
              <strong>TOTAL:</strong>
              <strong class="text-primary fs-5">RD$ <?= number_format($total,2) ?></strong>
            </div>
            <button type="submit" class="btn-pagar">
              <i class="bi bi-check-circle me-2"></i>Confirmar Pedido
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
  </form>
</div>
</body>
</html>
