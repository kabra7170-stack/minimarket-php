<?php
require_once __DIR__ . '/../../config/session.php';
requireLogin();
$user = currentUser();
require_once __DIR__ . '/toast.php';

// Detectar sección activa para abrir el acordeón correcto
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
  --sidebar-width:240px;--primary:#1F4E79;--accent:#2E75B6;
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
body,.topbar,.sidebar,.card,.card-header,.form-control,.form-select,.input-group-text,.table,.modal-content,.dropdown-menu{transition:background-color .3s ease,border-color .3s ease,color .2s ease;}
body{background:var(--bg);font-family:'Segoe UI',sans-serif;color:var(--text);}

/* ── SIDEBAR ── */
.sidebar{position:fixed;top:0;left:0;height:100vh;width:var(--sidebar-width);background:var(--primary);z-index:1000;overflow-y:auto;overflow-x:hidden;}
[data-theme="dark"] .sidebar{background:#0a1628;border-right:1px solid var(--border);}
.sidebar::-webkit-scrollbar{width:4px;}
.sidebar::-webkit-scrollbar-track{background:transparent;}
.sidebar::-webkit-scrollbar-thumb{background:rgba(255,255,255,0.2);border-radius:4px;}

.sidebar-brand{padding:16px;border-bottom:1px solid rgba(255,255,255,.1);display:flex;align-items:center;gap:10px;}
.sidebar-brand .brand-icon{width:36px;height:36px;border-radius:8px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;}
.sidebar-brand .brand-text h6{color:#fff;margin:0;font-weight:700;font-size:15px;letter-spacing:1px;}
.sidebar-brand .brand-text small{color:rgba(255,255,255,.5);font-size:10px;letter-spacing:2px;text-transform:uppercase;}

/* ── ACORDEÓN SIDEBAR ── */
.nav-group{border-bottom:1px solid rgba(255,255,255,.06);}
.nav-group-header{
  display:flex;align-items:center;justify-content:space-between;
  padding:11px 16px;cursor:pointer;
  color:rgba(255,255,255,.5);font-size:10px;font-weight:700;
  letter-spacing:1.5px;text-transform:uppercase;
  transition:all .2s;user-select:none;
}
.nav-group-header:hover{color:rgba(255,255,255,.8);background:rgba(255,255,255,.04);}
.nav-group-header.active-group{color:#F59E0B;}
.nav-group-header .group-icon{font-size:14px;margin-right:6px;}
.nav-group-header .chevron{font-size:12px;transition:transform .3s;}
.nav-group-header.open .chevron{transform:rotate(180deg);}

.nav-group-items{overflow:hidden;max-height:0;transition:max-height .35s ease;}
.nav-group-items.open{max-height:400px;}

.sidebar-nav a{
  display:flex;align-items:center;gap:10px;
  padding:9px 16px 9px 28px;
  color:rgba(255,255,255,.75);text-decoration:none;font-size:13px;
  transition:all .2s;border-left:3px solid transparent;
}
.sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(255,255,255,.1);color:#fff;border-left-color:#F59E0B;}
[data-theme="dark"] .sidebar-nav a:hover,[data-theme="dark"] .sidebar-nav a.active{background:rgba(46,117,182,0.15);border-left-color:#F59E0B;}
.sidebar-nav a i{font-size:16px;width:20px;flex-shrink:0;}

/* Link directo (sin grupo) */
.sidebar-nav .nav-direct{
  display:flex;align-items:center;gap:10px;
  padding:11px 16px;
  color:rgba(255,255,255,.85);text-decoration:none;font-size:14px;
  transition:all .2s;border-left:3px solid transparent;
  font-weight:500;
}
.sidebar-nav .nav-direct:hover,.sidebar-nav .nav-direct.active{background:rgba(255,255,255,.12);color:#fff;border-left-color:#F59E0B;}
.sidebar-nav .nav-direct i{font-size:18px;width:22px;}

/* ── MAIN ── */
.main-content{margin-left:var(--sidebar-width);min-height:100vh;}
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

/* ── BOTONES ── */
.btn-primary{background:var(--primary);border-color:var(--primary);}
.btn-primary:hover{background:var(--accent);border-color:var(--accent);}
[data-theme="dark"] .btn-outline-light{border-color:var(--border);color:var(--text-muted);}
[data-theme="dark"] .btn-outline-light:hover{background:rgba(46,117,182,0.2);color:#fff;}
[data-theme="dark"] .btn-outline-secondary{border-color:var(--border);color:var(--text-muted);}

/* ── TABLES ── */
table thead{background:var(--surface-2);}
[data-theme="dark"] table{color:var(--text);border-color:var(--border);}
[data-theme="dark"] .table>:not(caption)>*>*{background:transparent;border-color:var(--border);color:var(--text);}
[data-theme="dark"] .table-hover tbody tr:hover td{background:rgba(46,117,182,0.08)!important;}
[data-theme="dark"] thead tr{background:var(--surface-2);}

/* ── FORMS ── */
[data-theme="dark"] .form-control,[data-theme="dark"] .form-select{background:var(--input-bg);border-color:var(--border);color:var(--text);}
[data-theme="dark"] .form-control:focus,[data-theme="dark"] .form-select:focus{background:var(--input-bg);border-color:var(--accent);color:var(--text);box-shadow:0 0 0 3px rgba(46,117,182,0.2);}
[data-theme="dark"] .input-group-text{background:var(--input-bg);border-color:var(--border);color:var(--text-muted);}
[data-theme="dark"] .form-label{color:var(--text);}
[data-theme="dark"] .text-muted{color:var(--text-muted)!important;}
[data-theme="dark"] .text-dark{color:var(--text)!important;}
[data-theme="dark"] small{color:var(--text-muted);}
.alert-mini{background:var(--alert-bg);border-left:4px solid #F59E0B;border-radius:4px;padding:8px 12px;font-size:13px;}
[data-theme="dark"] .alert{background:rgba(46,117,182,0.08);border-color:var(--border);color:var(--text);}
[data-theme="dark"] .alert-danger{background:rgba(192,0,0,0.12);border-color:rgba(192,0,0,0.3);}
[data-theme="dark"] .alert-success{background:rgba(15,110,86,0.12);border-color:rgba(15,110,86,0.3);}
[data-theme="dark"] .badge.bg-light{background:var(--surface-2)!important;color:var(--text)!important;}
[data-theme="dark"] .badge.bg-secondary{background:#1e3a52!important;}
[data-theme="dark"] .modal-content{background:var(--surface);color:var(--text);border-color:var(--border);}
[data-theme="dark"] .modal-header,[data-theme="dark"] .modal-footer{border-color:var(--border);}
[data-theme="dark"] .dropdown-menu{background:var(--surface);border-color:var(--border);}
[data-theme="dark"] .dropdown-item{color:var(--text);}
[data-theme="dark"] .dropdown-item:hover{background:rgba(46,117,182,0.12);}

/* ── API STATUS ── */
.api-status{display:flex;align-items:center;gap:5px;font-size:11px;font-weight:600;padding:4px 10px;border-radius:20px;background:var(--surface-2);border:1px solid var(--border);cursor:default;color:var(--text);}
.api-dot{width:8px;height:8px;border-radius:50%;background:#9CA3AF;animation:pulse-gray 2s infinite;}
.api-dot.online{background:#10B981;animation:pulse-green 2s infinite;}
.api-dot.offline{background:#EF4444;animation:none;}
@keyframes pulse-green{0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,0.4)}50%{box-shadow:0 0 0 4px rgba(16,185,129,0)}}
@keyframes pulse-gray{0%,100%{opacity:1}50%{opacity:.4}}

/* ── THEME TOGGLE ── */
.theme-toggle{width:38px;height:22px;border-radius:11px;background:#CBD5E0;border:none;position:relative;cursor:pointer;transition:background .3s;padding:0;flex-shrink:0;}
.theme-toggle.dark-on{background:linear-gradient(135deg,#1F4E79,#2E75B6);}
.theme-toggle::after{content:'';position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;transition:transform .3s;box-shadow:0 1px 4px rgba(0,0,0,0.2);}
.theme-toggle.dark-on::after{transform:translateX(16px);}
.theme-label{font-size:11px;font-weight:600;color:var(--text-muted);display:flex;align-items:center;gap:6px;}

/* ── MOBILE ── */
.mobile-menu-btn{display:none;background:none;border:none;color:var(--primary);font-size:22px;cursor:pointer;padding:4px;}
[data-theme="dark"] .mobile-menu-btn{color:#6eafd4;}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:999;}
.sidebar-overlay.open{display:block;}
@media(max-width:768px){
  .sidebar{transform:translateX(-100%);transition:transform .3s;}
  .sidebar.open{transform:translateX(0);}
  .main-content{margin-left:0;}
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
})();
if('serviceWorker' in navigator){navigator.serviceWorker.register('/service-worker.js');}
</script>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon">⚡</div>
    <div class="brand-text">
      <h6>NEXSYS</h6>
      <small>Sistema de Gestión</small>
    </div>
  </div>

  <div class="sidebar-nav">
    <?php $self = $_SERVER['PHP_SELF']; ?>

    <!-- DASHBOARD directo -->
    <a href="/dashboard.php" class="nav-direct <?= basename($self)=='dashboard.php'?'active':'' ?>">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <!-- INVENTARIO -->
    <div class="nav-group">
      <div class="nav-group-header <?= $seccionActiva=='inventario'?'active-group open':'' ?>" onclick="toggleGroup(this)">
        <span><span class="group-icon"><i class="bi bi-box-seam"></i></span>Inventario</span>
        <i class="bi bi-chevron-down chevron"></i>
      </div>
      <div class="nav-group-items <?= $seccionActiva=='inventario'?'open':'' ?>">
        <a href="/productos/index.php" class="<?= strpos($self,'productos')!==false?'active':'' ?>"><i class="bi bi-box-seam"></i> Productos</a>
        <a href="/categorias/index.php" class="<?= strpos($self,'categorias')!==false?'active':'' ?>"><i class="bi bi-tags"></i> Categorías</a>
        <a href="/proveedores/index.php" class="<?= strpos($self,'proveedores')!==false?'active':'' ?>"><i class="bi bi-truck"></i> Proveedores</a>
        <a href="/compras/index.php" class="<?= strpos($self,'compras')!==false?'active':'' ?>"><i class="bi bi-cart-plus"></i> Compras</a>
      </div>
    </div>

    <!-- VENTAS -->
    <div class="nav-group">
      <div class="nav-group-header <?= $seccionActiva=='ventas'?'active-group open':'' ?>" onclick="toggleGroup(this)">
        <span><span class="group-icon"><i class="bi bi-bag-plus"></i></span>Ventas</span>
        <i class="bi bi-chevron-down chevron"></i>
      </div>
      <div class="nav-group-items <?= $seccionActiva=='ventas'?'open':'' ?>">
        <a href="/ventas/index.php" class="<?= strpos($self,'ventas')!==false?'active':'' ?>"><i class="bi bi-bag-plus"></i> Ventas</a>
        <a href="/clientes/index.php" class="<?= strpos($self,'clientes')!==false?'active':'' ?>"><i class="bi bi-people"></i> Clientes</a>
      </div>
    </div>

    <!-- PEDIDOS -->
    <div class="nav-group">
      <div class="nav-group-header <?= $seccionActiva=='pedidos'?'active-group open':'' ?>" onclick="toggleGroup(this)">
        <span><span class="group-icon"><i class="bi bi-bicycle"></i></span>Pedidos</span>
        <i class="bi bi-chevron-down chevron"></i>
      </div>
      <div class="nav-group-items <?= $seccionActiva=='pedidos'?'open':'' ?>">
        <a href="/pedidos/nuevo.php" class="<?= basename($self)=='nuevo.php'&&strpos($self,'pedidos')!==false?'active':'' ?>"><i class="bi bi-bag-plus"></i> Nuevo Pedido</a>
        <a href="/pedidos/index.php?tipo=mostrador" class="<?= basename($self)=='index.php'&&strpos($self,'pedidos')!==false?'active':'' ?>"><i class="bi bi-list-check"></i> Mostrador</a>
        <a href="/pedidos/index.php?tipo=domicilio"><i class="bi bi-bicycle"></i> Domicilio</a>
      </div>
    </div>

    <!-- REPORTES -->
    <div class="nav-group">
      <div class="nav-group-header <?= $seccionActiva=='reportes'?'active-group open':'' ?>" onclick="toggleGroup(this)">
        <span><span class="group-icon"><i class="bi bi-bar-chart"></i></span>Reportes</span>
        <i class="bi bi-chevron-down chevron"></i>
      </div>
      <div class="nav-group-items <?= $seccionActiva=='reportes'?'open':'' ?>">
        <a href="/reportes/ventas.php" class="<?= strpos($self,'reportes/ventas')!==false?'active':'' ?>"><i class="bi bi-bar-chart"></i> Reporte Ventas</a>
        <a href="/reportes/inventario.php" class="<?= strpos($self,'inventario')!==false?'active':'' ?>"><i class="bi bi-clipboard-data"></i> Reporte Inventario</a>
        <a href="/alertas/index.php" class="<?= strpos($self,'alertas')!==false?'active':'' ?>"><i class="bi bi-bell"></i> Alertas</a>
      </div>
    </div>

    <!-- COMUNICACIÓN -->
    <div class="nav-group">
      <div class="nav-group-header <?= $seccionActiva=='comunicacion'?'active-group open':'' ?>" onclick="toggleGroup(this)">
        <span><span class="group-icon"><i class="bi bi-chat-dots"></i></span>Comunicación</span>
        <i class="bi bi-chevron-down chevron"></i>
      </div>
      <div class="nav-group-items <?= $seccionActiva=='comunicacion'?'open':'' ?>">
        <a href="/mensajes/index.php" class="<?= strpos($self,'mensajes')!==false?'active':'' ?>">
          <i class="bi bi-chat-dots"></i> Mensajes
          <?php
          $db_menu = getDB();
          $no_leidos_menu = $db_menu->query("SELECT COUNT(*) AS t FROM mensajes WHERE leido=0")->fetch_assoc()['t'];
          if ($no_leidos_menu > 0): ?>
            <span class="badge bg-danger ms-auto" style="font-size:10px"><?= $no_leidos_menu ?></span>
          <?php endif; ?>
        </a>
      </div>
    </div>

    <?php if ($user['rol'] === 'administrador'): ?>
    <!-- ADMINISTRACIÓN -->
    <div class="nav-group">
      <div class="nav-group-header <?= $seccionActiva=='admin'?'active-group open':'' ?>" onclick="toggleGroup(this)">
        <span><span class="group-icon"><i class="bi bi-gear"></i></span>Administración</span>
        <i class="bi bi-chevron-down chevron"></i>
      </div>
      <div class="nav-group-items <?= $seccionActiva=='admin'?'open':'' ?>">
        <a href="/usuarios/index.php" class="<?= strpos($self,'usuarios')!==false?'active':'' ?>"><i class="bi bi-person-gear"></i> Usuarios</a>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<div class="main-content">
  <div class="topbar">
    <div class="d-flex align-items-center gap-2">
      <button class="mobile-menu-btn" onclick="openSidebar()"><i class="bi bi-list"></i></button>
      <h5><?= $pageTitle ?? 'Dashboard' ?></h5>
    </div>
    <div class="d-flex align-items-center gap-3">
      <div class="api-status" id="apiStatus" title="Estado de la API">
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
/* ── ACORDEÓN SIDEBAR ── */
function toggleGroup(header) {
  const items = header.nextElementSibling;
  const isOpen = header.classList.contains('open');
  // Cerrar todos
  document.querySelectorAll('.nav-group-header').forEach(h => {
    h.classList.remove('open');
    h.nextElementSibling.classList.remove('open');
  });
  // Abrir el clickeado si estaba cerrado
  if (!isOpen) {
    header.classList.add('open');
    items.classList.add('open');
  }
}

/* ── API STATUS ── */
const API_URL = 'https://api-fastapi-production-fa70.up.railway.app';
function checkApiStatus() {
  fetch(API_URL + '/health', { signal: AbortSignal.timeout(5000) })
    .then(r => {
      const dot = document.getElementById('apiDot');
      const lbl = document.getElementById('apiLabel');
      if (r.ok) { dot.className='api-dot online'; lbl.textContent='NEXSYS API Online'; lbl.style.color='#10B981'; }
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
  const cur = document.documentElement.getAttribute('data-theme') || 'light';
  const next = cur === 'dark' ? 'light' : 'dark';
  localStorage.setItem('mm-theme', next);
  applyTheme(next);
}
applyTheme(localStorage.getItem('mm-theme') || 'light');

/* ── SIDEBAR MOBILE ── */
function openSidebar()  { document.getElementById('sidebar').classList.add('open'); document.getElementById('sidebarOverlay').classList.add('open'); }
function closeSidebar() { document.getElementById('sidebar').classList.remove('open'); document.getElementById('sidebarOverlay').classList.remove('open'); }
</script>