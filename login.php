<?php
require_once 'config/database.php';
require_once 'config/session.php';

if (isLoggedIn())   { header('Location: dashboard.php'); exit(); }
if (isClienteLoggedIn()) { header('Location: catalogo.php'); exit(); }

$error = $success = '';
$modo  = $_GET['modo'] ?? 'login';

// ── LOGIN EMPLEADO ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'login') {
    $email = trim($_POST['email'] ?? '');
    $pass  = trim($_POST['password'] ?? '');
    if ($email && $pass) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id,nombre,email,password,rol FROM usuarios WHERE email=? AND activo=1");
        $stmt->bind_param('s',$email); $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['nombre']     = $user['nombre'];
            $_SESSION['email']      = $user['email'];
            $_SESSION['rol']        = $user['rol'];
            header('Location: dashboard.php'); exit();
        } else { $error = 'Correo o contraseña incorrectos.'; $modo = 'login'; }
    } else { $error = 'Completa todos los campos.'; $modo = 'login'; }
}

// ── LOGIN CLIENTE ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'login_cliente') {
    $email = trim($_POST['email_c'] ?? '');
    $pass  = trim($_POST['password_c'] ?? '');
    if ($email && $pass) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id,nombre,email,password FROM clientes WHERE email=? AND activo=1");
        $stmt->bind_param('s',$email); $stmt->execute();
        $cli = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if ($cli && password_verify($pass, $cli['password'])) {
            $_SESSION['cliente_id']     = $cli['id'];
            $_SESSION['cliente_nombre'] = $cli['nombre'];
            $_SESSION['cliente_email']  = $cli['email'];
            header('Location: catalogo.php'); exit();
        } else { $error = 'Correo o contraseña incorrectos.'; $modo = 'cliente'; }
    } else { $error = 'Completa todos los campos.'; $modo = 'cliente'; }
}

// ── REGISTRO CLIENTE ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'registro') {
    $nombre    = trim($_POST['nombre']    ?? '');
    $cedula    = trim($_POST['cedula']    ?? '');
    $telefono  = trim($_POST['telefono']  ?? '');
    $email     = trim($_POST['email_reg'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $pass      = trim($_POST['pass_reg']  ?? '');
    $pass2     = trim($_POST['pass_reg2'] ?? '');
    $modo = 'registro';

    if (!$nombre || !$email || !$pass) {
        $error = 'Nombre, correo y contraseña son obligatorios.';
    } elseif ($pass !== $pass2) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($pass) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
        $db = getDB();
        $st = $db->prepare("SELECT id FROM clientes WHERE email=?");
        $st->bind_param('s',$email); $st->execute();
        if ($st->get_result()->num_rows > 0) {
            $error = 'Ya existe una cuenta con ese correo.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $st2  = $db->prepare("INSERT INTO clientes (nombre,cedula,telefono,email,password,direccion,activo) VALUES (?,?,?,?,?,?,1)");
            $st2->bind_param('ssssss',$nombre,$cedula,$telefono,$email,$hash,$direccion);
            $st2->execute(); $st2->close();
            $success = '¡Cuenta creada! Ya puedes iniciar sesión.';
            $modo = 'cliente';
        }
        $st->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MiniMarket G2</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body { background: linear-gradient(135deg,#1F4E79 0%,#2E75B6 100%); min-height:100vh; display:flex; align-items:center; justify-content:center; font-family:'Segoe UI',sans-serif; }
  .card-auth { background:#fff; border-radius:20px; width:100%; max-width:460px; box-shadow:0 24px 64px rgba(0,0,0,.25); overflow:hidden; }
  .auth-header { background:linear-gradient(135deg,#1F4E79,#2E75B6); padding:24px 32px 18px; text-align:center; color:#fff; }
  .auth-header i { font-size:40px; }
  .auth-header h4 { margin:6px 0 2px; font-weight:700; font-size:19px; }
  .auth-header small { opacity:.75; font-size:11px; }
  .auth-tabs { display:flex; border-bottom:1px solid #E2E8F0; }
  .auth-tab { flex:1; padding:11px 6px; text-align:center; font-size:12px; font-weight:600; cursor:pointer; color:#888; border:none; background:none; transition:all .2s; }
  .auth-tab.active { color:#1F4E79; border-bottom:3px solid #1F4E79; }
  .auth-body { padding:22px 28px 24px; }
  .form-label { font-size:12px; font-weight:600; color:#374151; margin-bottom:3px; }
  .form-control,.form-select { border-radius:8px; border:1.5px solid #E5E7EB; font-size:13px; padding:8px 11px; }
  .form-control:focus { border-color:#2E75B6; box-shadow:0 0 0 3px rgba(46,117,182,.12); }
  .input-group-text { border-radius:8px 0 0 8px; border:1.5px solid #E5E7EB; background:#F9FAFB; color:#6B7280; font-size:13px; }
  .input-group .form-control { border-radius:0 8px 8px 0; }
  .btn-auth { background:linear-gradient(135deg,#1F4E79,#2E75B6); color:#fff; border:none; width:100%; padding:10px; border-radius:10px; font-size:14px; font-weight:600; transition:opacity .2s; margin-top:4px; }
  .btn-auth:hover { opacity:.88; color:#fff; }
  .btn-auth.green { background:linear-gradient(135deg,#0F6E56,#1aad87); }
  .tab-pane { display:none; } .tab-pane.show { display:block; }
  .footer-txt { text-align:center; color:#9CA3AF; font-size:11px; margin-top:14px; }
  .divider { text-align:center; color:#9CA3AF; font-size:11px; margin:10px 0; position:relative; }
  .divider::before,.divider::after { content:''; position:absolute; top:50%; width:42%; height:1px; background:#E5E7EB; }
  .divider::before { left:0; } .divider::after { right:0; }
</style>
</head>
<body>
<div class="card-auth">
  <div class="auth-header">
    <i class="bi bi-shop"></i>
    <h4>MiniMarket Grupo 2</h4>
    <small>Sistema de Gestión Integral</small>
  </div>

  <!-- PESTAÑAS -->
  <div class="auth-tabs">
    <button class="auth-tab <?= !in_array($modo,['cliente','registro'])?'active':'' ?>" onclick="switchTab('login')">
      <i class="bi bi-person-badge me-1"></i>Empleado
    </button>
    <button class="auth-tab <?= $modo=='cliente'?'active':'' ?>" onclick="switchTab('cliente')">
      <i class="bi bi-person me-1"></i>Cliente
    </button>
    <button class="auth-tab <?= $modo=='registro'?'active':'' ?>" onclick="switchTab('registro')">
      <i class="bi bi-person-plus me-1"></i>Registrarse
    </button>
  </div>

  <div class="auth-body">
    <?php if ($error): ?>
      <div class="alert alert-danger py-2 mb-3" style="font-size:12px;border-radius:8px;">
        <i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success py-2 mb-3" style="font-size:12px;border-radius:8px;">
        <i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>

    <!-- Login Empleado -->
    <div class="tab-pane <?= !in_array($modo,['cliente','registro'])?'show':'' ?>" id="tabLogin">
      <form method="POST">
        <input type="hidden" name="accion" value="login">
        <div class="mb-2">
          <label class="form-label">Correo</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" name="email" class="form-control" placeholder="usuario@minimarket.com" required>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Contraseña</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
          </div>
        </div>
        <button type="submit" class="btn-auth"><i class="bi bi-box-arrow-in-right me-2"></i>Entrar al Sistema</button>
      </form>
    </div>

    <!-- Login Cliente -->
    <div class="tab-pane <?= $modo=='cliente'?'show':'' ?>" id="tabCliente">
      <form method="POST">
        <input type="hidden" name="accion" value="login_cliente">
        <div class="mb-2">
          <label class="form-label">Correo</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" name="email_c" class="form-control" placeholder="tucorreo@gmail.com" required>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Contraseña</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" name="password_c" class="form-control" placeholder="••••••••" required>
          </div>
        </div>
        <button type="submit" class="btn-auth green"><i class="bi bi-shop me-2"></i>Ver Catálogo</button>
      </form>
      <div class="divider mt-3">¿No tienes cuenta?</div>
      <button class="btn btn-outline-primary w-100 btn-sm mt-1" onclick="switchTab('registro')">Crear cuenta gratis</button>
    </div>

    <!-- Registro -->
    <div class="tab-pane <?= $modo=='registro'?'show':'' ?>" id="tabRegistro">
      <form method="POST">
        <input type="hidden" name="accion" value="registro">
        <div class="row g-2">
          <div class="col-12">
            <label class="form-label">Nombre completo *</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-person"></i></span>
              <input type="text" name="nombre" class="form-control" placeholder="Tu nombre" required>
            </div>
          </div>
          <div class="col-6">
            <label class="form-label">Cédula</label>
            <input type="text" name="cedula" class="form-control" placeholder="000-0000000-0">
          </div>
          <div class="col-6">
            <label class="form-label">Teléfono</label>
            <input type="text" name="telefono" class="form-control" placeholder="809-000-0000">
          </div>
          <div class="col-12">
            <label class="form-label">Correo *</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-envelope"></i></span>
              <input type="email" name="email_reg" class="form-control" placeholder="tucorreo@gmail.com" required>
            </div>
          </div>
          <div class="col-12">
            <label class="form-label">Dirección</label>
            <input type="text" name="direccion" class="form-control" placeholder="Sector, calle...">
          </div>
          <div class="col-6">
            <label class="form-label">Contraseña *</label>
            <input type="password" name="pass_reg" class="form-control" placeholder="••••••" required>
          </div>
          <div class="col-6">
            <label class="form-label">Confirmar *</label>
            <input type="password" name="pass_reg2" class="form-control" placeholder="••••••" required>
          </div>
        </div>
        <button type="submit" class="btn-auth mt-3"><i class="bi bi-person-check me-2"></i>Crear Cuenta</button>
      </form>
    </div>

    <p class="footer-txt">Grupo 2 — 5to I — 2026</p>
  </div>
</div>

<script>
function switchTab(tab) {
  ['login','cliente','registro'].forEach(t => {
    document.getElementById('tab'+t.charAt(0).toUpperCase()+t.slice(1)).classList.toggle('show', t===tab);
  });
  document.querySelectorAll('.auth-tab').forEach((el,i) => {
    el.classList.toggle('active', ['login','cliente','registro'][i]===tab);
  });
}
</script>
</body>
</html>
