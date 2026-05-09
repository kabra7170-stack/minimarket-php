<?php
require_once 'config/database.php';
require_once 'config/session.php';
requireCliente();

$db      = getDB();
$cliente = currentCliente();

// Carrito en sesión
if (!isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];

// Acciones carrito rápido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid = (int)($_POST['producto_id'] ?? 0);
    $qty = (int)($_POST['cantidad']    ?? 1);
    if ($pid > 0 && $qty > 0) {
        if (isset($_SESSION['carrito'][$pid])) {
            $_SESSION['carrito'][$pid] += $qty;
        } else {
            $_SESSION['carrito'][$pid] = $qty;
        }
    }
    header('Location: catalogo.php?agregado=1'); exit();
}

// Filtros
$buscar = trim($_GET['buscar'] ?? '');
$cat    = (int)($_GET['cat']  ?? 0);

$where = "WHERE p.activo=1 AND p.stock_actual > 0";
if ($buscar) $where .= " AND p.nombre LIKE '%".mysqli_real_escape_string($db,$buscar)."%'";
if ($cat)    $where .= " AND p.categoria_id=$cat";

$productos  = $db->query("SELECT p.*, c.nombre AS categoria FROM productos p JOIN categorias c ON p.categoria_id=c.id $where ORDER BY p.nombre");
$categorias = $db->query("SELECT * FROM categorias ORDER BY nombre");
$totalCarrito = array_sum($_SESSION['carrito']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MiniMarket G2 — Catálogo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
  :root { --primary:#1F4E79; --accent:#2E75B6; }
  body  { background:#F0F4F8; font-family:'Segoe UI',sans-serif; }

  /* NAVBAR */
  .navbar-top { background:linear-gradient(135deg,var(--primary),var(--accent)); padding:10px 24px; display:flex; justify-content:space-between; align-items:center; position:sticky; top:0; z-index:100; box-shadow:0 2px 12px rgba(0,0,0,.2); }
  .navbar-top .brand { color:#fff; font-weight:700; font-size:18px; text-decoration:none; }
  .navbar-top .brand i { margin-right:8px; }
  .btn-carrito { background:rgba(255,255,255,.15); color:#fff; border:2px solid rgba(255,255,255,.4); border-radius:10px; padding:6px 16px; font-weight:600; font-size:13px; text-decoration:none; transition:all .2s; }
  .btn-carrito:hover { background:#fff; color:var(--primary); }
  .user-info { color:rgba(255,255,255,.85); font-size:13px; }

  /* HERO */
  .hero { background:linear-gradient(135deg,var(--primary),var(--accent)); color:#fff; padding:32px 24px; text-align:center; }
  .hero h2 { font-weight:700; font-size:24px; margin:0 0 6px; }
  .hero p  { opacity:.8; font-size:14px; margin:0; }

  /* FILTROS */
  .filtros { background:#fff; padding:16px 24px; border-bottom:1px solid #E2E8F0; }

  /* CARDS */
  .prod-card { background:#fff; border-radius:14px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,.07); transition:transform .2s,box-shadow .2s; height:100%; display:flex; flex-direction:column; }
  .prod-card:hover { transform:translateY(-4px); box-shadow:0 8px 24px rgba(0,0,0,.12); }
  .prod-img { width:100%; height:170px; object-fit:contain; background:#fff; padding:8px; }
  .prod-img-placeholder { width:100%; height:170px; background:linear-gradient(135deg,#E2E8F0,#CBD5E1); display:flex; align-items:center; justify-content:center; color:#94A3B8; font-size:40px; }
  .prod-body { padding:14px; flex:1; display:flex; flex-direction:column; }
  .prod-cat  { font-size:10px; font-weight:600; color:var(--accent); text-transform:uppercase; letter-spacing:.8px; margin-bottom:4px; }
  .prod-name { font-weight:700; font-size:14px; color:#1E293B; margin-bottom:6px; line-height:1.3; }
  .prod-price{ font-size:18px; font-weight:800; color:var(--primary); margin-bottom:10px; }
  .prod-stock{ font-size:11px; color:#64748B; margin-bottom:10px; }
  .btn-add   { background:linear-gradient(135deg,var(--primary),var(--accent)); color:#fff; border:none; border-radius:8px; padding:8px; font-size:13px; font-weight:600; width:100%; margin-top:auto; transition:opacity .2s; }
  .btn-add:hover { opacity:.85; }

  /* TOAST */
  .toast-agregado { position:fixed; bottom:20px; right:20px; background:#0F6E56; color:#fff; padding:12px 20px; border-radius:10px; font-size:13px; font-weight:600; z-index:9999; box-shadow:0 4px 16px rgba(0,0,0,.2); display:none; }

  .content { padding:24px; max-width:1400px; margin:0 auto; }
  .badge-cat { font-size:11px; }
</style>
</head>
<body>

<!-- NAVBAR -->
<div class="navbar-top">
  <a href="catalogo.php" class="brand"><i class="bi bi-shop"></i>MiniMarket G2</a>
  <div class="d-flex align-items-center gap-3">
    <span class="user-info"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($cliente['nombre']) ?></span>
    <a href="carrito.php" class="btn-carrito">
      <i class="bi bi-cart3 me-1"></i>Carrito
      <?php if ($totalCarrito > 0): ?>
        <span class="badge bg-warning text-dark ms-1"><?= $totalCarrito ?></span>
      <?php endif; ?>
    </a>
    <a href="logout_cliente.php" class="btn-carrito" style="background:rgba(255,0,0,.2);border-color:rgba(255,100,100,.4);">
      <i class="bi bi-box-arrow-right"></i>
    </a>
  </div>
</div>

<!-- HERO -->
<div class="hero">
  <h2><i class="bi bi-bag-heart me-2"></i>Nuestros Productos</h2>
  <p>Compra desde casa, recibe en tu puerta</p>
</div>

<!-- FILTROS -->
<div class="filtros">
  <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
    <div class="input-group" style="max-width:280px;">
      <span class="input-group-text"><i class="bi bi-search"></i></span>
      <input type="text" name="buscar" class="form-control form-control-sm" placeholder="Buscar producto..." value="<?= htmlspecialchars($buscar) ?>">
    </div>
    <select name="cat" class="form-select form-select-sm" style="max-width:180px;" onchange="this.form.submit()">
      <option value="0">Todas las categorías</option>
      <?php while ($c = $categorias->fetch_assoc()): ?>
        <option value="<?= $c['id'] ?>" <?= $cat==$c['id']?'selected':''?>><?= htmlspecialchars($c['nombre']) ?></option>
      <?php endwhile; ?>
    </select>
    <button type="submit" class="btn btn-primary btn-sm">Buscar</button>
    <?php if ($buscar || $cat): ?>
      <a href="catalogo.php" class="btn btn-outline-secondary btn-sm">Limpiar</a>
    <?php endif; ?>
  </form>
</div>

<!-- PRODUCTOS -->
<div class="content">
  <?php if (isset($_GET['agregado'])): ?>
    <div class="alert alert-success py-2 mb-3" style="font-size:13px;border-radius:10px;">
      <i class="bi bi-check-circle me-2"></i>Producto agregado al carrito. <a href="carrito.php">Ver carrito →</a>
    </div>
  <?php endif; ?>

  <div class="row g-3">
    <?php
      $count = 0;
      while ($p = $productos->fetch_assoc()):
      $count++;
    ?>
    <div class="col-6 col-md-4 col-lg-3">
      <div class="prod-card">
        <?php if ($p['imagen']): ?>
            <img src="uploads/productos/<?= htmlspecialchars($p['imagen']) ?>" class="prod-img" alt="<?= htmlspecialchars($p['nombre']) ?>">
           <?php else: ?>
         <div class="prod-img-placeholder"><i class="bi bi-box-seam"></i></div>
           <?php endif; ?>
        <div class="prod-body">
          <div class="prod-cat"><?= htmlspecialchars($p['categoria']) ?></div>
          <div class="prod-name"><?= htmlspecialchars($p['nombre']) ?></div>
          <div class="prod-price">RD$ <?= number_format($p['precio_venta'],2) ?></div>
          <div class="prod-stock"><i class="bi bi-check-circle-fill text-success me-1"></i><?= $p['stock_actual'] ?> disponibles</div>
          <form method="POST">
            <input type="hidden" name="producto_id" value="<?= $p['id'] ?>">
            <div class="input-group input-group-sm mb-2">
              <span class="input-group-text">Cant.</span>
              <input type="number" name="cantidad" class="form-control" value="1" min="1" max="<?= $p['stock_actual'] ?>">
            </div>
            <button type="submit" class="btn-add"><i class="bi bi-cart-plus me-1"></i>Agregar</button>
          </form>
        </div>
      </div>
    </div>
    <?php endwhile; ?>

    <?php if ($count === 0): ?>
    <div class="col-12 text-center py-5 text-muted">
      <i class="bi bi-search fs-1 d-block mb-3"></i>
      <h5>No se encontraron productos</h5>
      <a href="catalogo.php" class="btn btn-outline-primary btn-sm mt-2">Ver todos</a>
    </div>
    <?php endif; ?>
  </div>
</div>

</body>
</html>