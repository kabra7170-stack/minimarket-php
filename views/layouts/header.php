<?php
require_once __DIR__ . '/../../config/session.php';
requireLogin();
$user = currentUser();
require_once __DIR__ . '/toast.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MiniMarket G2 — <?= $pageTitle ?? 'Sistema' ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
  :root { --sidebar-width:240px; --primary:#1F4E79; --accent:#2E75B6; }
  body { background:#F0F4F8; font-family:'Segoe UI',sans-serif; }
  .sidebar { position:fixed; top:0; left:0; height:100vh; width:var(--sidebar-width); background:var(--primary); z-index:1000; overflow-y:auto; }
  .sidebar-brand { padding:20px 16px; border-bottom:1px solid rgba(255,255,255,.1); }
  .sidebar-brand h6 { color:#fff; margin:0; font-weight:700; font-size:15px; }
  .sidebar-brand small { color:rgba(255,255,255,.6); font-size:11px; }
  .sidebar-nav { padding:12px 0; }
  .nav-section { padding:8px 16px 4px; color:rgba(255,255,255,.4); font-size:10px; font-weight:600; letter-spacing:1px; text-transform:uppercase; }
  .sidebar-nav a { display:flex; align-items:center; gap:10px; padding:10px 16px; color:rgba(255,255,255,.8); text-decoration:none; font-size:14px; transition:all .2s; border-left:3px solid transparent; }
  .sidebar-nav a:hover,.sidebar-nav a.active { background:rgba(255,255,255,.1); color:#fff; border-left-color:#fff; }
  .sidebar-nav a i { font-size:18px; width:22px; }
  .main-content { margin-left:var(--sidebar-width); min-height:100vh; }
  .topbar { background:#fff; padding:12px 24px; border-bottom:1px solid #E2E8F0; display:flex; justify-content:space-between; align-items:center; position:sticky; top:0; z-index:100; }
  .topbar h5 { margin:0; font-weight:600; color:var(--primary); font-size:16px; }
  .content-area { padding:24px; }
  .badge-rol { font-size:11px; padding:4px 10px; border-radius:20px; }
  .card { border:none; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.06); }
  .card-header { background:var(--primary); color:#fff; border-radius:12px 12px 0 0!important; font-weight:600; }
  .btn-primary { background:var(--primary); border-color:var(--primary); }
  .btn-primary:hover { background:var(--accent); border-color:var(--accent); }
  table thead { background:#F8FAFC; }
  .alert-mini { background:#FFF3CD; border-left:4px solid #F59E0B; border-radius:4px; padding:8px 12px; font-size:13px; }
</style>
</head>
<body>

<div class="sidebar">
  <div class="sidebar-brand">
    <h6><i class="bi bi-shop me-2"></i>MiniMarket G2</h6>
    <small>Sistema de Gestión</small>
  </div>
  <div class="sidebar-nav">
    <?php $self = $_SERVER['PHP_SELF']; ?>
    <div class="nav-section">Principal</div>
    <a href="/dashboard.php" class="<?= basename($self)=='dashboard.php'?'active':'' ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>

    <div class="nav-section">Inventario</div>
    <a href="/productos/index.php" class="<?= strpos($self,'productos')!==false?'active':'' ?>"><i class="bi bi-box-seam"></i> Productos</a>
    <a href="/categorias/index.php" class="<?= strpos($self,'categorias')!==false?'active':'' ?>"><i class="bi bi-tags"></i> Categorías</a>
    <a href="/proveedores/index.php" class="<?= strpos($self,'proveedores')!==false?'active':'' ?>"><i class="bi bi-truck"></i> Proveedores</a>
    <a href="/compras/index.php" class="<?= strpos($self,'compras')!==false?'active':'' ?>"><i class="bi bi-cart-plus"></i> Compras</a>

    <div class="nav-section">Ventas</div>
    <a href="/ventas/index.php" class="<?= strpos($self,'ventas')!==false?'active':'' ?>"><i class="bi bi-bag-plus"></i> Ventas</a>
    <a href="/clientes/index.php" class="<?= strpos($self,'clientes')!==false?'active':'' ?>"><i class="bi bi-people"></i> Clientes</a>

    <div class="nav-section">Pedidos</div>
    <a href="/pedidos/nuevo.php" class="<?= basename($self)=='nuevo.php'&&strpos($self,'pedidos')!==false?'active':'' ?>"><i class="bi bi-bag-plus"></i> Nuevo Pedido</a>
    <a href="/pedidos/index.php?tipo=mostrador" class="<?= basename($self)=='index.php'&&strpos($self,'pedidos')!==false?'active':'' ?>"><i class="bi bi-list-check"></i> Pedidos Mostrador</a>
    <a href="/pedidos/index.php?tipo=domicilio"><i class="bi bi-bicycle"></i> Pedidos Domicilio</a>

    <div class="nav-section">Reportes</div>
    <a href="/reportes/ventas.php" class="<?= strpos($self,'reportes/ventas')!==false?'active':'' ?>"><i class="bi bi-bar-chart"></i> Reporte Ventas</a>
    <a href="/reportes/inventario.php" class="<?= strpos($self,'inventario')!==false?'active':'' ?>"><i class="bi bi-clipboard-data"></i> Reporte Inventario</a>
    <a href="/alertas/index.php" class="<?= strpos($self,'alertas')!==false?'active':'' ?>"><i class="bi bi-bell"></i> Alertas</a>

    <div class="nav-section">Comunicación</div>
    <a href="/mensajes/index.php" class="<?= strpos($self,'mensajes')!==false?'active':'' ?>">
        <i class="bi bi-chat-dots"></i> Mensajes
        <?php
        $db_menu = getDB();
        $no_leidos_menu = $db_menu->query("SELECT COUNT(*) AS t FROM mensajes WHERE leido=0")->fetch_assoc()['t'];
        if ($no_leidos_menu > 0):
        ?>
        <span class="badge bg-danger ms-auto" style="font-size:10px"><?= $no_leidos_menu ?></span>
        <?php endif; ?>
    </a>

    <?php if ($user['rol'] === 'administrador'): ?>
    <div class="nav-section">Administración</div>
    <a href="/usuarios/index.php" class="<?= strpos($self,'usuarios')!==false?'active':'' ?>"><i class="bi bi-person-gear"></i> Usuarios</a>
    <?php endif; ?>
  </div>
</div>

<div class="main-content">
  <div class="topbar">
    <h5><?= $pageTitle ?? 'Dashboard' ?></h5>
    <div class="d-flex align-items-center gap-3">
      <?php $badgeColor = match($user['rol']) { 'administrador'=>'bg-primary','gerente'=>'bg-warning text-dark',default=>'bg-success' }; ?>
      <span class="badge <?= $badgeColor ?> badge-rol"><?= ucfirst($user['rol']) ?></span>
      <span class="text-muted" style="font-size:13px;"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($user['nombre']) ?></span>
      <a href="/logout.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right"></i></a>
    </div>
  </div>
  <div class="content-area">

<?php include __DIR__ . '/toast.php'; ?>
