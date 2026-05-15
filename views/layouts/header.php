<?php
require_once __DIR__ . '/../../config/session.php';
requireLogin();
$user = currentUser();
require_once __DIR__ . '/toast.php';

$self = $_SERVER['PHP_SELF'];
$seccionActiva = 'principal';
if (strpos($self,'productos')!==false || strpos($self,'categorias')!==false || strpos($self,'proveedores')!==false || strpos($self,'compras')!==false) $seccionActiva = 'inventario';
elseif (strpos($self,'ventas')!==false || strpos($self,'clientes')!==false) $seccionActiva = 'ventas';
elseif (strpos($self,'pedidos')!==false) $seccionActiva = 'pedidos';
elseif (strpos($self,'reportes')!==false || strpos($self,'alertas')!==false) $seccionActiva = 'reportes';
elseif (strpos($self,'mensajes')!==false) $seccionActiva = 'comunicacion';
elseif (strpos($self,'usuarios')!==false) $seccionActiva = 'admin';
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NEXSYS — <?= $pageTitle ?? 'Sistema' ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root {
  --sidebar-width:240px;
  --sidebar-collapsed:60px;
  --primary:#1F4E79;--accent:#2E75B6;--gold:#F59E0B;
  --bg:#F0F4F8;--surface:#fff;--surface-2:#F8FAFC;--border:#E2E8F0;
  --text:#1a1a2e;--text-muted:#6B7280;--topbar-bg:#fff;--input-bg:#F9FAFB;
  --shadow:0 2px 8px rgba(0,0,0,.06);--alert-bg:#FFF3CD;
}
[data-theme="dark"]{
  --bg:#0d1b2a;--surface:#142233;--surface-2:#1a2d42;--border:#1e3a52;
  --text:#d6e4f0;--text-muted:#6e9ab8;--topbar-bg:#0f1e2e;--input-bg:#1a2d42;
  --shadow:0 2px 12px rgba(0,0,0,.4);--alert-bg:rgba(245,158,11,0.12);
}
*,*::before,*::after{box-sizing:border-box;}
body,.topbar,.sidebar,.card,.card-header,.form-control,.form-select,.input-group-text,.table,.modal-content,.dropdown-menu{
  transition:background-color .3s ease,border-color .3s ease,color .2s ease;
}
body{background:var(--bg);font-family:'Segoe UI',sans-serif;color:var(--text);}

/* ── SIDEBAR ── */
.sidebar{
  position:fixed;top:0;left:0;height:100vh;
  width:var(--sidebar-width);
  background:var(--primary);
  z-index:1000;overflow:hidden;
  transition:width .3s cubic-bezier(.4,0,.2,1);
}
.sidebar.collapsed{width:var(--sidebar-collapsed);}
[data-theme="dark"] .sidebar{background:#0a1628;border-right:1px solid var(--border);}
.sidebar::-webkit-scrollbar{width:3px;}
.sidebar::-webkit-scrollbar-thumb{background:rgba(255,255,255,0.2);border-radius:4px;}

/* Brand */
.sidebar-brand{
  padding:14px 12px;
  border-bottom:1px solid rgba(255,255,255,.1);
  display:flex;align-items:center;gap:10px;
  cursor:pointer;user-select:none;
  transition:padding .3s;
  min-height:64px;
}
.sidebar-brand:hover{background:rgba(255,255,255,.06);}
.brand-icon{
  width:36px;height:36px;flex-shrink:0;
  border-radius:8px;
  background:rgba(255,255,255,.15);
  border:1px solid rgba(245,158,11,.4);
  display:flex;align-items:center;justify-content:center;
  font-size:18px;font-weight:700;color:#fff;
  transition:all .3s;
  box-shadow:0 0 12px rgba(245,158,11,.2);
}
.brand-icon:hover{background:rgba(245,158,11,.2);}
.brand-texts{overflow:hidden;transition:opacity .2s,width .3s;white-space:nowrap;}
.sidebar.collapsed .brand-texts{opacity:0;width:0;}
.brand-texts h6{color:#fff;margin:0;font-weight:700;font-size:15px;letter-spacing:2px;}
.brand-texts small{color:rgba(255,255,255,.5);font-size:10px;letter-spacing:2px;text-transform:uppercase;}

/* Nav */
.sidebar-nav{padding:8px 0;overflow-y:auto;overflow-x:hidden;height:calc(100vh - 64px);}
.sidebar-nav::-webkit-scrollbar{width:3px;}
.sidebar-nav::-webkit-scrollbar-thumb{background:rgba(255,255,255,0.15);border-radius:4px;}

/* Link directo */
.nav-direct{
  display:flex;align-items:center;gap:10px;
  padding:11px 12px;
  color:rgba(255,255,255,.85);text-decoration:none;
  font-size:14px;font-weight:500;
  transition:all .2s;border-left:3px solid transparent;
  white-space:nowrap;
}
.nav-direct:hover,.nav-direct.active{background:rgba(255,255,255,.12);color:#fff;border-left-color:var(--gold);}
.nav-direct i{font-size:18px;width:36px;flex-shrink:0;text-align:center;}
.nav-direct .link-text{overflow:hidden;transition:opacity .2s,width .3s;white-space:nowrap;}
.sidebar.collapsed .nav-direct .link-text{opacity:0;width:0;}

/* Grupos acordeón */
.nav-group{border-bottom:1px solid rgba(255,255,255,.05);}
.nav-group-header{
  display:flex;align-items:center;justify-content:space-between;
  padding:11px 12px;cursor:pointer;
  color:rgba(255,255,255,.5);font-size:11px;font-weight:700;
  letter-spacing:1.5px;text-transform:uppercase;
  transition:all .2s;user-select:none;white-space:nowrap;
}
.nav-group-header:hover{color:rgba(255,255,255,.8);background:rgba(255,255,255,.04);}
.nav-group-header.active-group{color:var(--gold);}
.nav-group-header .gh-left{display:flex;align-items:center;gap:8px;}
.nav-group-header .gh-left i{font-size:18px;width:36px;text-align:center;flex-shrink:0;}
.nav-group-header .gh-text{overflow:hidden;transition:opacity .2s,width .3s;white-space:nowrap;}
.sidebar.collapsed .nav-group-header .gh-text{opacity:0;width:0;}
.nav-group-header .chevron{font-size:11px;transition:transform .3s;flex-shrink:0;}
.nav-group-header.open .chevron{transform:rotate(180deg);}
.sidebar.collapsed .chevron{opacity:0;}

.nav-group-items{overflow:hidden;max-height:0;transition:max-height .35s ease;}
.nav-group-items.open{max-height:400px;}
.sidebar.collapsed .nav-group-items{max-height:0!important;}

.sidebar-nav a:not(.nav-direct){
  display:flex;align-items:center;gap:10px;
  padding:9px 12px 9px 20px;
  color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;
  transition:all .2s;border-left:3px solid transparent;white-space:nowrap;
}
.sidebar-nav a:not(.nav-direct):hover,
.sidebar-nav a:not(.nav-direct).active{background:rgba(255,255,255,.1);color:#fff;border-left-color:var(--gold);}
[data-theme="dark"] .sidebar-nav a:not(.nav-direct):hover,
[data-theme="dark"] .sidebar-nav a:not(.nav-direct).active{background:rgba(46,117,182,.15);border-left-color:var(--gold);}
.sidebar-nav a:not(.nav-direct) i{font-size:15px;width:20px;flex-shrink:0;}
.sidebar-nav a:not(.nav-direct) .link-text{overflow:hidden;transition:opacity .2s;white-space:nowrap;}

/* Tooltip en modo colapsado */
.sidebar.collapsed .nav-group-header{justify-content:center;}
.sidebar.collapsed .nav-direct{justify-content:center;padding:11px 0;}

/* ── MAIN ── */
.main-content{
  margin-left:var(--sidebar-width);
  min-height:100vh;
  transition:margin-left .3s cubic-bezier(.4,0,.2,1);
}
.main-content.sidebar-collapsed{margin-left:var(--sidebar-collapsed);}

/* ── TOPBAR ── */
.topbar{background:var(--topbar-bg);padding:12px 24px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:100;}
.topbar h5{margin:0;font-weight:600;color:var(--primary);font-size:16px;}
[data-theme="dark"] .topbar h5{color:#6eafd4;}
.content-area{padding:24px;}
.badge-rol{font-size:11px;padding:4px 10px;border-radius:20px;}

/* ── CARDS ── */
.card{border:none;border-radius:12px;box-shadow:var(--shadow);background:var(--surface);}
[data-theme="dark"] .card{border:1px solid var(--border);}
.card-header{background:var(--primary);color:#fff;border-radius:12px 12px 0 0!important;font-weight:600;}
[data-theme="dark"] .card-header{background:linear-gradient(135deg,#1a3a5c,#0f2840);border-bottom:1px solid var(--border);}
.btn-primary{background:var(--primary);border-color:var(--primary);}
.btn-primary:hover{background:var(--accent);border-color:var(--accent);}
[data-theme="dark"] .btn-outline-light{border-color:var(--border);color:var(--text-muted);}
[data-theme="dark"] .btn-outline-light:hover{background:rgba(46,117,182,.2);color:#fff;}
[data-theme="dark"] .btn-outline-secondary{border-color:var(--border);color:var(--text-muted);}
table thead{background:var(--surface-2);}
[data-theme="dark"] table{color:var(--text);border-color:var(--border);}
[data-theme="dark"] .table>:not(caption)>*>*{background:transparent;border-color:var(--border);color:var(--text);}
[data-theme="dark"] .table-hover tbody tr:hover td{background:rgba(46,117,182,.08)!important;}
[data-theme="dark"] thead tr{background:var(--surface-2);}
[data-theme="dark"] .form-control,[data-theme="dark"] .form-select{background:var(--input-bg);border-color:var(--border);color:var(--text);}
[data-theme="dark"] .form-control:focus,[data-theme="dark"] .form-select:focus{background:var(--input-bg);border-color:var(--accent);color:var(--text);box-shadow:0 0 0 3px rgba(46,117,182,.2);}
[data-theme="dark"] .input-group-text{background:var(--input-bg);border-color:var(--border);color:var(--text-muted);}
[data-theme="dark"] .form-label{color:var(--text);}
[data-theme="dark"] .text-muted{color:var(--text-muted)!important;}
[data-theme="dark"] .text-dark{color:var(--text)!important;}
[data-theme="dark"] small{color:var(--text-muted);}
.alert-mini{background:var(--alert-bg);border-left:4px solid #F59E0B;border-radius:4px;padding:8px 12px;font-size:13px;}
[data-theme="dark"] .alert{background:rgba(46,117,182,.08);border-color:var(--border);color:var(--text);}
[data-theme="dark"] .alert-danger{background:rgba(192,0,0,.12);border-color:rgba(192,0,0,.3);}
[data-theme="dark"] .alert-success{background:rgba(15,110,86,.12);border-color:rgba(15,110,86,.3);}
[data-theme="dark"] .badge.bg-light{background:var(--surface-2)!important;color:var(--text)!important;}
[data-theme="dark"] .badge.bg-secondary{background:#1e3a52!important;}
[data-theme="dark"] .modal-content{background:var(--surface);color:var(--text);border-color:var(--border);}
[data-theme="dark"] .modal-header,[data-theme="dark"] .modal-footer{border-color:var(--border);}
[data-theme="dark"] .dropdown-menu{background:var(--surface);border-color:var(--border);}
[data-theme="dark"] .dropdown-item{color:var(--text);}
[data-theme="dark"] .dropdown-item:hover{background:rgba(46,117,182,.12);}

/* ── API STATUS ── */
.api-status{display:flex;align-items:center;gap:5px;font-size:11px;font-weight:600;padding:4px 10px;border-radius:20px;background:var(--surface-2);border:1px solid var(--border);cursor:default;color:var(--text);}
.api-dot{width:8px;height:8px;border-radius:50%;background:#9CA3AF;animation:pulse-gray 2s infinite;}
.api-dot.online{background:#10B981;animation:pulse-green 2s infinite;}
.api-dot.offline{background:#EF4444;animation:none;}
@keyframes pulse-green{0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,.4)}50%{box-shadow:0 0 0 4px rgba(16,185,129,0)}}
@keyframes pulse-gray{0%,100%{opacity:1}50%{opacity:.4}}

/* ── THEME TOGGLE ── */
.theme-toggle{width:38px;height:22px;border-radius:11px;background:#CBD5E0;border:none;position:relative;cursor:pointer;transition:background .3s;padding:0;flex-shrink:0;}
.theme-toggle.dark-on{background:linear-gradient(135deg,#1F4E79,#2E75B6);}
.theme-toggle::after{content:'';position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;transition:transform .3s;box-shadow:0 1px 4px rgba(0,0,0,.2);}
.theme-toggle.dark-on::after{transform:translateX(16px);}
.theme-label{font-size:11px;font-weight:600;color:var(--text-muted);display:flex;align-items:center;gap:6px;}

/* ── MOBILE ── */
.mobile-menu-btn{display:none;background:none;border:none;color:var(--primary);font-size:22px;cursor:pointer;padding:4px;}
[data-theme="dark"] .mobile-menu-btn{color:#6eafd4;}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:999;}
.sidebar-overlay.open{display:block;}
@media(max-width:768px){
  .sidebar{transform:translateX(-100%)!important;width:var(--sidebar-width)!important;transition:transform .3s!important;}
  .sidebar.mobile-open{transform:translateX(0)!important;}
  .main-content{margin-left:0!important;}
  .topbar{padding:10px 16px;}
  .content-area{padding:12px;}
  .mobile-menu-btn{display:flex!important;}
  #apiLabel{display:none;}
}
</style>
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#1F4E79">
<link rel="apple-touch-icon" href="/uploads/icon-192.png">
<script>
(function(){
  const t=localStorage.getItem('mm-theme')||'light';
  document.documentElement.setAttribute('data-theme',t);
  // Aplicar estado colapsado guardado
  if(localStorage.getItem('sidebar-collapsed')==='1'){
    document.documentElement.classList.add('sidebar-pre-collapsed');
  }
})();
if('serviceWorker' in navigator){navigator.serviceWorker.register('/service-worker.js');}
</script>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeMobileSidebar()"></div>

<div class="sidebar" id="sidebar">
  <!-- BRAND — clic para colapsar -->
  <div class="sidebar-brand" onclick="toggleSidebar()" title="Colapsar/expandir menú">
    <div class="brand-icon">⚡</div>
    <div class="brand-texts">
      <h6>NEXSYS</h6>
      <small>Sistema de Gestión</small>
    </div>
  </div>

  <div class="sidebar-nav">
    <?php $self = $_SERVER['PHP_SELF']; ?>

    <a href="/dashboard.php" class="nav-direct <?= basename($self)=='dashboard.php'?'active':'' ?>">
      <i class="bi bi-speedometer2"></i>
      <span class="link-text">Dashboard</span>
    </a>

    <!-- INVENTARIO -->
    <div class="nav-group">
      <div class="nav-group-header <?= $seccionActiva=='inventario'?'active-group open':'' ?>" onclick="toggleGroup(this)">
        <div class="gh-left"><i class="bi bi-box-seam"></i><span class="gh-text">Inventario</span></div>
        <i class="bi bi-chevron-down chevron"></i>
      </div>
      <div class="nav-group-items <?= $seccionActiva=='inventario'?'open':'' ?>">
        <a href="/productos/index.php" class="<?= strpos($self,'productos')!==false?'active':'' ?>"><i class="bi bi-box-seam"></i><span class="link-text">Productos</span></a>
        <a href="/categorias/index.php" class="<?= strpos($self,'categorias')!==false?'active':'' ?>"><i class="bi bi-tags"></i><span class="link-text">Categorías</span></a>
        <a href="/proveedores/index.php" class="<?= strpos($self,'proveedores')!==false?'active':'' ?>"><i class="bi bi-truck"></i><span class="link-text">Proveedores</span></a>
        <a href="/compras/index.php" class="<?= strpos($self,'compras')!==false?'active':'' ?>"><i class="bi bi-cart-plus"></i><span class="link-text">Compras</span></a>
      </div>
    </div>

    <!-- VENTAS -->
    <div class="nav-group">
      <div class="nav-group-header <?= $seccionActiva=='ventas'?'active-group open':'' ?>" onclick="toggleGroup(this)">
        <div class="gh-left"><i class="bi bi-bag-plus"></i><span class="gh-text">Ventas</span></div>
        <i class="bi bi-chevron-down chevron"></i>
      </div>
      <div class="nav-group-items <?= $seccionActiva=='ventas'?'open':'' ?>">
        <a href="/ventas/index.php" class="<?= strpos($self,'ventas')!==false?'active':'' ?>"><i class="bi bi-bag-plus"></i><span class="link-text">Ventas</span></a>
        <a href="/clientes/index.php" class="<?= strpos($self,'clientes')!==false?'active':'' ?>"><i class="bi bi-people"></i><span class="link-text">Clientes</span></a>
      </div>
    </div>

    <!-- PEDIDOS -->
    <div class="nav-group">
      <div class="nav-group-header <?= $seccionActiva=='pedidos'?'active-group open':'' ?>" onclick="toggleGroup(this)">
        <div class="gh-left"><i class="bi bi-bicycle"></i><span class="gh-text">Pedidos</span></div>
        <i class="bi bi-chevron-down chevron"></i>
      </div>
      <div class="nav-group-items <?= $seccionActiva=='pedidos'?'open':'' ?>">
        <a href="/pedidos/nuevo.php" class="<?= basename($self)=='nuevo.php'&&strpos($self,'pedidos')!==false?'active':'' ?>"><i class="bi bi-bag-plus"></i><span class="link-text">Nuevo Pedido</span></a>
        <a href="/pedidos/index.php?tipo=mostrador" class="<?= basename($self)=='index.php'&&strpos($self,'pedidos')!==false?'active':'' ?>"><i class="bi bi-list-check"></i><span class="link-text">Mostrador</span></a>
        <a href="/pedidos/index.php?tipo=domicilio"><i class="bi bi-bicycle"></i><span class="link-text">Domicilio</span></a>
      </div>
    </div>

    <!-- REPORTES -->
    <div class="nav-group">
      <div class="nav-group-header <?= $seccionActiva=='reportes'?'active-group open':'' ?>" onclick="toggleGroup(this)">
        <div class="gh-left"><i class="bi bi-bar-chart"></i><span class="gh-text">Reportes</span></div>
        <i class="bi bi-chevron-down chevron"></i>
      </div>
      <div class="nav-group-items <?= $seccionActiva=='reportes'?'open':'' ?>">
        <a href="/reportes/ventas.php" class="<?= strpos($self,'reportes/ventas')!==false?'active':'' ?>"><i class="bi bi-bar-chart"></i><span class="link-text">Reporte Ventas</span></a>
        <a href="/reportes/inventario.php" class="<?= strpos($self,'inventario')!==false?'active':'' ?>"><i class="bi bi-clipboard-data"></i><span class="link-text">Reporte Inventario</span></a>
        <a href="/alertas/index.php" class="<?= strpos($self,'alertas')!==false?'active':'' ?>"><i class="bi bi-bell"></i><span class="link-text">Alertas</span></a>
      </div>
    </div>

    <!-- COMUNICACIÓN -->
    <div class="nav-group">
      <div class="nav-group-header <?= $seccionActiva=='comunicacion'?'active-group open':'' ?>" onclick="toggleGroup(this)">
        <div class="gh-left"><i class="bi bi-chat-dots"></i><span class="gh-text">Comunicación</span></div>
        <i class="bi bi-chevron-down chevron"></i>
      </div>
      <div class="nav-group-items <?= $seccionActiva=='comunicacion'?'open':'' ?>">
        <a href="/mensajes/index.php" class="<?= strpos($self,'mensajes')!==false?'active':'' ?>">
          <i class="bi bi-chat-dots"></i><span class="link-text">Mensajes
          <?php
          $db_menu = getDB();
          $no_leidos_menu = $db_menu->query("SELECT COUNT(*) AS t FROM mensajes WHERE leido=0")->fetch_assoc()['t'];
          if ($no_leidos_menu > 0): ?>
            <span class="badge bg-danger ms-1" style="font-size:10px"><?= $no_leidos_menu ?></span>
          <?php endif; ?></span>
        </a>
      </div>
    </div>

    <?php if ($user['rol'] === 'administrador'): ?>
    <!-- ADMINISTRACIÓN -->
    <div class="nav-group">
      <div class="nav-group-header <?= $seccionActiva=='admin'?'active-group open':'' ?>" onclick="toggleGroup(this)">
        <div class="gh-left"><i class="bi bi-gear"></i><span class="gh-text">Administración</span></div>
        <i class="bi bi-chevron-down chevron"></i>
      </div>
      <div class="nav-group-items <?= $seccionActiva=='admin'?'open':'' ?>">
        <a href="/usuarios/index.php" class="<?= strpos($self,'usuarios')!==false?'active':'' ?>"><i class="bi bi-person-gear"></i><span class="link-text">Usuarios</span></a>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<div class="main-content" id="mainContent">
  <div class="topbar">
    <div class="d-flex align-items-center gap-2">
      <button class="mobile-menu-btn" onclick="openMobileSidebar()"><i class="bi bi-list"></i></button>
      <h5><?= $pageTitle ?? 'Dashboard' ?></h5>
    </div>
    <div class="d-flex align-items-center gap-3">
      <div class="api-status" id="apiStatus" title="NEXSYS API">
        <div class="api-dot" id="apiDot"></div>
        <span id="apiLabel">API...</span>
      </div>
      <label class="theme-label" title="Modo nocturno">
        <i class="bi bi-sun"></i>
        <button class="theme-toggle" id="themeToggleBtn" onclick="toggleTheme()"></button>
        <i class="bi bi-moon"></i>
      </label>
      <?php $badgeColor = match($user['rol']) { 'administrador'=>'bg-primary','gerente'=>'bg-warning text-dark',default=>'bg-success' }; ?>
      <span class="badge <?= $badgeColor ?> badge-rol"><?= ucfirst($user['rol']) ?></span>
      <span class="text-muted d-none d-md-inline" style="font-size:13px;"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($user['nombre']) ?></span>
      <a href="/logout.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right"></i></a>
    </div>
  </div>

<?php include __DIR__ . '/nexsys_widget.php'; ?>
  <div class="content-area">
<?php include __DIR__ . '/toast.php'; ?>

<script>
/* ── SIDEBAR COLAPSAR/EXPANDIR ── */
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const main    = document.getElementById('mainContent');
  const isCollapsed = sidebar.classList.contains('collapsed');

  if (isCollapsed) {
    sidebar.classList.remove('collapsed');
    main.classList.remove('sidebar-collapsed');
    localStorage.setItem('sidebar-collapsed', '0');
  } else {
    sidebar.classList.add('collapsed');
    main.classList.add('sidebar-collapsed');
    localStorage.setItem('sidebar-collapsed', '1');
  }
}

/* Aplicar estado guardado al cargar */
(function(){
  if (localStorage.getItem('sidebar-collapsed') === '1') {
    document.getElementById('sidebar').classList.add('collapsed');
    document.getElementById('mainContent').classList.add('sidebar-collapsed');
  }
})();

/* ── ACORDEÓN ── */
function toggleGroup(header) {
  const sidebar = document.getElementById('sidebar');
  if (sidebar.classList.contains('collapsed')) return;
  const items  = header.nextElementSibling;
  const isOpen = header.classList.contains('open');
  document.querySelectorAll('.nav-group-header').forEach(h => {
    h.classList.remove('open','active-group');
    h.nextElementSibling.classList.remove('open');
  });
  if (!isOpen) {
    header.classList.add('open');
    items.classList.add('open');
  }
}

/* ── SIDEBAR MOBILE ── */
function openMobileSidebar() {
  document.getElementById('sidebar').classList.add('mobile-open');
  document.getElementById('sidebarOverlay').classList.add('open');
}
function closeMobileSidebar() {
  document.getElementById('sidebar').classList.remove('mobile-open');
  document.getElementById('sidebarOverlay').classList.remove('open');
}

/* ── API STATUS ── */
const API_URL = 'https://api-fastapi-production-fa70.up.railway.app';
function checkApiStatus() {
  fetch(API_URL + '/health', { signal: AbortSignal.timeout(5000) })
    .then(r => {
      const dot = document.getElementById('apiDot');
      const lbl = document.getElementById('apiLabel');
      if (r.ok) { dot.className='api-dot online'; lbl.textContent='NEXSYS API'; lbl.style.color='#10B981'; }
      else setOffline();
    }).catch(() => setOffline());
}
function setOffline() {
  document.getElementById('apiDot').className = 'api-dot offline';
  const lbl = document.getElementById('apiLabel');
  lbl.textContent = 'API Offline'; lbl.style.color = '#EF4444';
}
checkApiStatus();
setInterval(checkApiStatus, 30000);

/* ── TEMA ── */
function applyTheme(t) {
  document.documentElement.setAttribute('data-theme', t);
  const btn = document.getElementById('themeToggleBtn');
  if (btn) btn.classList.toggle('dark-on', t === 'dark');
}
function toggleTheme() {
  const cur  = document.documentElement.getAttribute('data-theme') || 'light';
  const next = cur === 'dark' ? 'light' : 'dark';
  localStorage.setItem('mm-theme', next);
  applyTheme(next);
}
applyTheme(localStorage.getItem('mm-theme') || 'light');
</script>
