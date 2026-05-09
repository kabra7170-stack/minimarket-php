<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../views/layouts/toast.php';
requireLogin();

$db = getDB();
$pageTitle = 'Gestión de Proveedores';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'crear') {
    $stmt = $db->prepare("INSERT INTO proveedores (nombre,rnc,telefono,email,direccion) VALUES (?,?,?,?,?)");
    $stmt->bind_param('sssss', $_POST['nombre'], $_POST['rnc'], $_POST['telefono'], $_POST['email'], $_POST['direccion']);
    $stmt->execute() ? setToast('success','Proveedor registrado correctamente.') : setToast('error','Error: '.$db->error);
    $stmt->close();
    header('Location: index.php'); exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'editar') {
    $stmt = $db->prepare("UPDATE proveedores SET nombre=?,rnc=?,telefono=?,email=?,direccion=? WHERE id=?");
    $stmt->bind_param('sssssi', $_POST['nombre'], $_POST['rnc'], $_POST['telefono'], $_POST['email'], $_POST['direccion'], $_POST['id']);
    $stmt->execute() ? setToast('success','Proveedor actualizado correctamente.') : setToast('error','Error: '.$db->error);
    $stmt->close();
    header('Location: index.php'); exit();
}

if (isset($_GET['delete'])) {
    $db->query("UPDATE proveedores SET activo=0 WHERE id=".(int)$_GET['delete']);
    setToast('warning','Proveedor desactivado.');
    header('Location: index.php'); exit();
}

$editProv = null;
if (isset($_GET['edit']))
    $editProv = $db->query("SELECT * FROM proveedores WHERE id=".(int)$_GET['edit'])->fetch_assoc();

$proveedores = $db->query("SELECT * FROM proveedores WHERE activo=1 ORDER BY nombre");

include '../views/layouts/header.php';
?>

<div class="card mb-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-truck me-2"></i><?= $editProv ? 'Editar Proveedor' : 'Nuevo Proveedor' ?></span>
    <?php if ($editProv): ?><a href="index.php" class="btn btn-light btn-sm">Cancelar</a><?php endif; ?>
  </div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="action" value="<?= $editProv ? 'editar' : 'crear' ?>">
      <?php if ($editProv): ?><input type="hidden" name="id" value="<?= $editProv['id'] ?>"><?php endif; ?>
      <div class="row g-2">
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Nombre *</label>
          <input type="text" name="nombre" class="form-control form-control-sm" value="<?= htmlspecialchars($editProv['nombre'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">RNC / Cédula</label>
          <input type="text" name="rnc" class="form-control form-control-sm" value="<?= htmlspecialchars($editProv['rnc'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold">Teléfono</label>
          <input type="text" name="telefono" class="form-control form-control-sm" value="<?= htmlspecialchars($editProv['telefono'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Correo</label>
          <input type="email" name="email" class="form-control form-control-sm" value="<?= htmlspecialchars($editProv['email'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Dirección</label>
          <input type="text" name="direccion" class="form-control form-control-sm" value="<?= htmlspecialchars($editProv['direccion'] ?? '') ?>">
        </div>
        <div class="col-12 mt-1">
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i><?= $editProv ? 'Guardar cambios' : 'Registrar proveedor' ?></button>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header"><i class="bi bi-table me-2"></i>Listado de Proveedores</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover table-sm mb-0">
        <thead><tr><th>#</th><th>Nombre</th><th>RNC</th><th>Teléfono</th><th>Correo</th><th>Dirección</th><th>Acciones</th></tr></thead>
        <tbody>
          <?php while ($p = $proveedores->fetch_assoc()): ?>
          <tr>
            <td class="small"><?= $p['id'] ?></td>
            <td class="small"><strong><?= htmlspecialchars($p['nombre']) ?></strong></td>
            <td class="small"><?= htmlspecialchars($p['rnc'] ?? '—') ?></td>
            <td class="small"><?= htmlspecialchars($p['telefono'] ?? '—') ?></td>
            <td class="small"><?= htmlspecialchars($p['email'] ?? '—') ?></td>
            <td class="small"><?= htmlspecialchars($p['direccion'] ?? '—') ?></td>
            <td>
              <a href="?edit=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="bi bi-pencil"></i></a>
              <a href="?delete=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="return confirmar(event,this.href,'¿Desactivar este proveedor?')"><i class="bi bi-trash"></i></a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function confirmar(e,url,msg) {
    e.preventDefault();
    const div=document.createElement('div');
    div.style.cssText='position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9998;display:flex;align-items:center;justify-content:center;';
    div.innerHTML=`<div style="background:#fff;border-radius:16px;padding:28px 32px;max-width:340px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.3);text-align:center;">
        <i class="bi bi-exclamation-triangle-fill" style="font-size:38px;color:#BF5800;"></i>
        <h5 style="margin:10px 0 6px;color:#1F4E79;font-weight:700;">Confirmar acción</h5>
        <p style="color:#64748B;font-size:14px;margin-bottom:20px;">${msg}</p>
        <div style="display:flex;gap:10px;justify-content:center;">
            <button onclick="this.closest('div[style]').remove()" style="flex:1;padding:9px;border:2px solid #E2E8F0;border-radius:8px;background:#fff;font-weight:600;cursor:pointer;">Cancelar</button>
            <a href="${url}" style="flex:1;padding:9px;background:linear-gradient(135deg,#C00000,#e03131);color:#fff;border-radius:8px;font-weight:600;text-decoration:none;display:flex;align-items:center;justify-content:center;">Confirmar</a>
        </div></div>`;
    document.body.appendChild(div);
    return false;
}
</script>

<?php include '../views/layouts/footer.php'; ?>
