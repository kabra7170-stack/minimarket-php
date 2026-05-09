<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../views/layouts/toast.php';
requireLogin();

$db = getDB();
$pageTitle = 'Gestión de Clientes';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'crear') {
    $stmt = $db->prepare("INSERT INTO clientes (nombre,cedula,telefono,email,direccion) VALUES (?,?,?,?,?)");
    $stmt->bind_param('sssss', $_POST['nombre'], $_POST['cedula'], $_POST['telefono'], $_POST['email'], $_POST['direccion']);
    $stmt->execute() ? setToast('success','Cliente registrado correctamente.') : setToast('error','Error: '.$db->error);
    $stmt->close();
    header('Location: index.php'); exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'editar') {
    $stmt = $db->prepare("UPDATE clientes SET nombre=?,cedula=?,telefono=?,email=?,direccion=? WHERE id=?");
    $stmt->bind_param('sssssi', $_POST['nombre'], $_POST['cedula'], $_POST['telefono'], $_POST['email'], $_POST['direccion'], $_POST['id']);
    $stmt->execute() ? setToast('success','Cliente actualizado correctamente.') : setToast('error','Error: '.$db->error);
    $stmt->close();
    header('Location: index.php'); exit();
}

if (isset($_GET['delete'])) {
    $db->query("DELETE FROM clientes WHERE id=".(int)$_GET['delete']);
    setToast('warning','Cliente eliminado.');
    header('Location: index.php'); exit();
}

$editCli = null;
if (isset($_GET['edit']))
    $editCli = $db->query("SELECT * FROM clientes WHERE id=".(int)$_GET['edit'])->fetch_assoc();

$clientes = $db->query("SELECT c.*, COUNT(v.id) AS total_compras, IFNULL(SUM(v.total),0) AS total_gastado FROM clientes c LEFT JOIN ventas v ON c.id=v.cliente_id AND v.estado='completada' GROUP BY c.id ORDER BY c.nombre");

include '../views/layouts/header.php';
?>

<div class="card mb-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-person-plus me-2"></i><?= $editCli ? 'Editar Cliente' : 'Nuevo Cliente' ?></span>
    <?php if ($editCli): ?><a href="index.php" class="btn btn-light btn-sm">Cancelar</a><?php endif; ?>
  </div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="action" value="<?= $editCli ? 'editar' : 'crear' ?>">
      <?php if ($editCli): ?><input type="hidden" name="id" value="<?= $editCli['id'] ?>"><?php endif; ?>
      <div class="row g-2">
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Nombre completo *</label>
          <input type="text" name="nombre" class="form-control form-control-sm" value="<?= htmlspecialchars($editCli['nombre'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Cédula</label>
          <input type="text" name="cedula" class="form-control form-control-sm" value="<?= htmlspecialchars($editCli['cedula'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold">Teléfono</label>
          <input type="text" name="telefono" class="form-control form-control-sm" value="<?= htmlspecialchars($editCli['telefono'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Correo</label>
          <input type="email" name="email" class="form-control form-control-sm" value="<?= htmlspecialchars($editCli['email'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Dirección</label>
          <input type="text" name="direccion" class="form-control form-control-sm" value="<?= htmlspecialchars($editCli['direccion'] ?? '') ?>">
        </div>
        <div class="col-12 mt-1">
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i><?= $editCli ? 'Guardar cambios' : 'Registrar cliente' ?></button>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header"><i class="bi bi-people me-2"></i>Listado de Clientes</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover table-sm mb-0">
        <thead><tr><th>#</th><th>Nombre</th><th>Cédula</th><th>Teléfono</th><th>Correo</th><th>Compras</th><th>Total gastado</th><th>Acciones</th></tr></thead>
        <tbody>
          <?php while ($c = $clientes->fetch_assoc()): ?>
          <tr>
            <td class="small"><?= $c['id'] ?></td>
            <td class="small"><strong><?= htmlspecialchars($c['nombre']) ?></strong></td>
            <td class="small"><?= htmlspecialchars($c['cedula'] ?? '—') ?></td>
            <td class="small"><?= htmlspecialchars($c['telefono'] ?? '—') ?></td>
            <td class="small"><?= htmlspecialchars($c['email'] ?? '—') ?></td>
            <td><span class="badge bg-info text-dark"><?= $c['total_compras'] ?></span></td>
            <td class="small">RD$ <?= number_format($c['total_gastado'],2) ?></td>
            <td>
              <a href="?edit=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="bi bi-pencil"></i></a>
              <a href="?delete=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="return confirmar(event,this.href,'¿Eliminar este cliente?')"><i class="bi bi-trash"></i></a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function confirmar(e, url, msg) {
    e.preventDefault();
    const div = document.createElement('div');
    div.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9998;display:flex;align-items:center;justify-content:center;';
    div.innerHTML = `<div style="background:#fff;border-radius:16px;padding:28px 32px;max-width:340px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.3);text-align:center;">
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