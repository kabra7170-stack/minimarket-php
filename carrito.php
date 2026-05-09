<?php
require_once 'config/database.php';
require_once 'config/session.php';
requireCliente();

$db      = getDB();
$cliente = currentCliente();

if (!isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];

// Acciones
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
$pid    = (int)($_POST['producto_id'] ?? $_GET['pid'] ?? 0);

if ($accion === 'actualizar' && $pid) {
    $qty = (int)($_POST['cantidad'] ?? 1);
    if ($qty <= 0) unset($_SESSION['carrito'][$pid]);
    else $_SESSION['carrito'][$pid] = $qty;
    header('Location: carrito.php'); exit();
}
if ($accion === 'eliminar' && $pid) {
    unset($_SESSION['carrito'][$pid]);
    header('Location: carrito.php'); exit();
}
if ($accion === 'vaciar') {
    $_SESSION['carrito'] = [];
    header('Location: carrito.php'); exit();
}

// Cargar productos del carrito
$items = [];
$subtotal = 0;
if (!empty($_SESSION['carrito'])) {
    $ids = implode(',', array_keys($_SESSION['carrito']));
    $res = $db->query("SELECT id,nombre,precio_venta,stock_actual,imagen FROM productos WHERE id IN ($ids)");
    while ($r = $res->fetch_assoc()) {
        $qty = $_SESSION['carrito'][$r['id']];
        $qty = min($qty, $r['stock_actual']); // no superar stock
        $r['qty'] = $qty;
        $r['total_item'] = $qty * $r['precio_venta'];
        $subtotal += $r['total_item'];
        $items[] = $r;
    }
}
$envio = 150; // costo fijo envío
$total = $subtotal + $envio;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Carrito — MiniMarket G2</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
  :root{--primary:#1F4E79;--accent:#2E75B6;}
  body{background:#F0F4F8;font-family:'Segoe UI',sans-serif;}
  .navbar-top{background:linear-gradient(135deg,var(--primary),var(--accent));padding:10px 24px;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(0,0,0,.2);}
  .navbar-top .brand{color:#fff;font-weight:700;font-size:18px;text-decoration:none;}
  .btn-nav{background:rgba(255,255,255,.15);color:#fff;border:2px solid rgba(255,255,255,.4);border-radius:10px;padding:6px 16px;font-weight:600;font-size:13px;text-decoration:none;transition:all .2s;}
  .btn-nav:hover{background:#fff;color:var(--primary);}
  .content{padding:24px;max-width:1000px;margin:0 auto;}
  .card{border:none;border-radius:14px;box-shadow:0 2px 10px rgba(0,0,0,.07);}
  .card-header{background:var(--primary);color:#fff;border-radius:14px 14px 0 0!important;font-weight:600;padding:14px 20px;}
  .item-img{width:60px;height:60px;object-fit:cover;border-radius:8px;background:#E2E8F0;}
  .item-placeholder{width:60px;height:60px;border-radius:8px;background:#E2E8F0;display:flex;align-items:center;justify-content:center;color:#94A3B8;font-size:22px;}
  .btn-primary{background:var(--primary);border-color:var(--primary);}
  .btn-primary:hover{background:var(--accent);border-color:var(--accent);}
  .resumen-card{background:#fff;border-radius:14px;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,.07);position:sticky;top:80px;}
</style>
</head>
<body>

<div class="navbar-top">
  <a href="catalogo.php" class="brand"><i class="bi bi-shop me-2"></i>MiniMarket G2</a>
  <div class="d-flex gap-2">
    <a href="catalogo.php" class="btn-nav"><i class="bi bi-arrow-left me-1"></i>Seguir comprando</a>
    <a href="logout_cliente.php" class="btn-nav" style="background:rgba(255,0,0,.2)"><i class="bi bi-box-arrow-right"></i></a>
  </div>
</div>

<div class="content">
  <h5 class="fw-bold mb-3" style="color:var(--primary)"><i class="bi bi-cart3 me-2"></i>Mi Carrito</h5>

  <?php if (empty($items)): ?>
  <div class="card text-center py-5">
    <div class="card-body">
      <i class="bi bi-cart-x fs-1 text-muted d-block mb-3"></i>
      <h5 class="text-muted">Tu carrito está vacío</h5>
      <a href="catalogo.php" class="btn btn-primary mt-3"><i class="bi bi-shop me-2"></i>Ver productos</a>
    </div>
  </div>
  <?php else: ?>

  <div class="row g-3">
    <!-- Items -->
    <div class="col-md-8">
      <div class="card">
        <div class="card-header"><i class="bi bi-list-check me-2"></i>Productos (<?= count($items) ?>)</div>
        <div class="card-body p-0">
          <?php foreach ($items as $item): ?>
          <div class="d-flex align-items-center gap-3 p-3 border-bottom">
            <?php if ($item['imagen'] && file_exists('uploads/productos/'.$item['imagen'])): ?>
              <img src="uploads/productos/<?= htmlspecialchars($item['imagen']) ?>" class="item-img">
            <?php else: ?>
              <div class="item-placeholder"><i class="bi bi-box-seam"></i></div>
            <?php endif; ?>
            <div class="flex-grow-1">
              <div class="fw-semibold small"><?= htmlspecialchars($item['nombre']) ?></div>
              <div class="text-primary fw-bold">RD$ <?= number_format($item['precio_venta'],2) ?></div>
            </div>
            <form method="POST" class="d-flex align-items-center gap-2">
              <input type="hidden" name="accion" value="actualizar">
              <input type="hidden" name="producto_id" value="<?= $item['id'] ?>">
              <input type="number" name="cantidad" value="<?= $item['qty'] ?>" min="0" max="<?= $item['stock_actual'] ?>" class="form-control form-control-sm" style="width:70px" onchange="this.form.submit()">
            </form>
            <div class="text-end" style="min-width:90px">
              <div class="fw-bold small">RD$ <?= number_format($item['total_item'],2) ?></div>
              <a href="carrito.php?accion=eliminar&pid=<?= $item['id'] ?>" class="text-danger small"><i class="bi bi-trash"></i> quitar</a>
            </div>
          </div>
          <?php endforeach; ?>
          <div class="p-3">
            <a href="carrito.php?accion=vaciar" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Vaciar carrito?')">
              <i class="bi bi-trash me-1"></i>Vaciar carrito
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Resumen -->
    <div class="col-md-4">
      <div class="resumen-card">
        <h6 class="fw-bold mb-3" style="color:var(--primary)"><i class="bi bi-calculator me-2"></i>Resumen</h6>
        <div class="d-flex justify-content-between mb-2 small">
          <span>Subtotal:</span><strong>RD$ <?= number_format($subtotal,2) ?></strong>
        </div>
        <div class="d-flex justify-content-between mb-2 small">
          <span>Envío:</span><strong>RD$ <?= number_format($envio,2) ?></strong>
        </div>
        <hr>
        <div class="d-flex justify-content-between mb-3">
          <span class="fw-bold">TOTAL:</span>
          <strong class="fs-5 text-primary">RD$ <?= number_format($total,2) ?></strong>
        </div>
        <a href="checkout.php" class="btn btn-primary w-100 fw-semibold">
          <i class="bi bi-credit-card me-2"></i>Proceder al pago
        </a>
        <a href="catalogo.php" class="btn btn-outline-secondary w-100 mt-2 btn-sm">
          <i class="bi bi-arrow-left me-1"></i>Seguir comprando
        </a>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
