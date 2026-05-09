<?php
require_once '../config/database.php';
require_once '../config/session.php';
requireLogin();
requireRole('administrador');

$db = getDB();
$pageTitle = 'Gestión de Usuarios';
$msg = '';
$error = '';

// CREAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'crear') {
    if ($_POST['password'] !== $_POST['password2']) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($_POST['password']) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
        $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?,?,?,?)");
        $stmt->bind_param('ssss', $_POST['nombre'], $_POST['email'], $hash, $_POST['rol']);
        if ($stmt->execute()) {
            $msg = 'Usuario registrado correctamente.';
        } else {
            $error = 'Error: ese correo ya existe o hay un problema.';
        }
        $stmt->close();
    }
}

// EDITAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'editar') {
    if (!empty($_POST['password'])) {
        if ($_POST['password'] !== $_POST['password2']) { $error = 'Las contraseñas no coinciden.'; }
        else {
            $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE usuarios SET nombre=?, email=?, password=?, rol=? WHERE id=?");
            $stmt->bind_param('ssssi', $_POST['nombre'], $_POST['email'], $hash, $_POST['rol'], $_POST['id']);
            $stmt->execute() ? $msg = 'Usuario actualizado.' : $error = $db->error;
            $stmt->close();
        }
    } else {
        $stmt = $db->prepare("UPDATE usuarios SET nombre=?, email=?, rol=? WHERE id=?");
        $stmt->bind_param('sssi', $_POST['nombre'], $_POST['email'], $_POST['rol'], $_POST['id']);
        $stmt->execute() ? $msg = 'Usuario actualizado.' : $error = $db->error;
        $stmt->close();
    }
}

// DESACTIVAR
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $activo = (int)$_GET['activo'];
    $nuevo = $activo ? 0 : 1;
    $db->query("UPDATE usuarios SET activo=$nuevo WHERE id=$id");
    $msg = $nuevo ? 'Usuario activado.' : 'Usuario desactivado.';
}

$editUser = null;
if (isset($_GET['edit'])) {
    $editUser = $db->query("SELECT * FROM usuarios WHERE id=" . (int)$_GET['edit'])->fetch_assoc();
}

$usuarios = $db->query("SELECT * FROM usuarios ORDER BY activo DESC, nombre");

include '../views/layouts/header.php';
?>

<?php if ($msg): ?><div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= $msg ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><i class="bi bi-x-circle me-2"></i><?= $error ?></div><?php endif; ?>

<!-- FORMULARIO -->
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-person-plus me-2"></i><?= $editUser ? 'Editar Usuario' : 'Nuevo Usuario' ?></span>
    <?php if ($editUser): ?><a href="index.php" class="btn btn-light btn-sm">Cancelar</a><?php endif; ?>
  </div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="action" value="<?= $editUser ? 'editar' : 'crear' ?>">
      <?php if ($editUser): ?><input type="hidden" name="id" value="<?= $editUser['id'] ?>"><?php endif; ?>
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label fw-semibold">Nombre completo *</label>
          <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($editUser['nombre'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Correo electrónico *</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($editUser['email'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Rol *</label>
          <select name="rol" class="form-select" required>
            <option value="cajero" <?= ($editUser['rol'] ?? '') === 'cajero' ? 'selected' : '' ?>>Cajero</option>
            <option value="administrador" <?= ($editUser['rol'] ?? '') === 'administrador' ? 'selected' : '' ?>>Administrador</option>
            <option value="gerente" <?= ($editUser['rol'] ?? '') === 'gerente' ? 'selected' : '' ?>>Gerente</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Contraseña <?= $editUser ? '(dejar vacío para no cambiar)' : '*' ?></label>
          <input type="password" name="password" class="form-control" <?= !$editUser ? 'required' : '' ?> minlength="6" placeholder="Mínimo 6 caracteres">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Confirmar contraseña</label>
          <input type="password" name="password2" class="form-control" placeholder="Repetir contraseña">
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i><?= $editUser ? 'Guardar cambios' : 'Registrar usuario' ?></button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- TABLA -->
<div class="card">
  <div class="card-header"><i class="bi bi-people me-2"></i>Usuarios del Sistema</div>
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead><tr><th>#</th><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Registrado</th><th>Acciones</th></tr></thead>
      <tbody>
        <?php while ($u = $usuarios->fetch_assoc()):
          $badgeRol = match($u['rol']) { 'administrador' => 'primary', 'gerente' => 'warning', default => 'success' };
        ?>
        <tr class="<?= !$u['activo'] ? 'opacity-50' : '' ?>">
          <td><?= $u['id'] ?></td>
          <td><strong><?= htmlspecialchars($u['nombre']) ?></strong></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><span class="badge bg-<?= $badgeRol ?>"><?= ucfirst($u['rol']) ?></span></td>
          <td><span class="badge bg-<?= $u['activo'] ? 'success' : 'secondary' ?>"><?= $u['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
          <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
          <td>
            <a href="?edit=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <a href="?toggle=<?= $u['id'] ?>&activo=<?= $u['activo'] ?>" class="btn btn-sm btn-outline-<?= $u['activo'] ? 'danger' : 'success' ?>"
               onclick="return confirm('<?= $u['activo'] ? 'Desactivar' : 'Activar' ?> este usuario?')">
              <i class="bi bi-<?= $u['activo'] ? 'person-x' : 'person-check' ?>"></i>
            </a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../views/layouts/footer.php'; ?>
