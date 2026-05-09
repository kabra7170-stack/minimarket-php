<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../views/layouts/toast.php';
requireLogin();

$db = getDB();
$pageTitle = 'Gestión de Productos';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'crear') {
    $fv = !empty($_POST['fecha_vencimiento']) ? $_POST['fecha_vencimiento'] : null;
    $stmt = $db->prepare("INSERT INTO productos (categoria_id,proveedor_id,nombre,codigo_barras,precio_compra,precio_venta,stock_actual,stock_minimo,fecha_vencimiento) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param('iissddiis', $_POST['categoria_id'], $_POST['proveedor_id'], $_POST['nombre'], $_POST['codigo_barras'], $_POST['precio_compra'], $_POST['precio_venta'], $_POST['stock_actual'], $_POST['stock_minimo'], $fv);
    $stmt->execute() ? setToast('success','Producto registrado correctamente.') : setToast('error','Error: '.$db->error);
    $stmt->close();
    header('Location: index.php'); exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'editar') {
    $fv = !empty($_POST['fecha_vencimiento']) ? $_POST['fecha_vencimiento'] : null;
    $stmt = $db->prepare("UPDATE productos SET categoria_id=?,proveedor_id=?,nombre=?,codigo_barras=?,precio_compra=?,precio_venta=?,stock_actual=?,stock_minimo=?,fecha_vencimiento=? WHERE id=?");
    $stmt->bind_param('iissddiisd', $_POST['categoria_id'], $_POST['proveedor_id'], $_POST['nombre'], $_POST['codigo_barras'], $_POST['precio_compra'], $_POST['precio_venta'], $_POST['stock_actual'], $_POST['stock_minimo'], $fv, $_POST['id']);
    $stmt->execute() ? setToast('success','Producto actualizado correctamente.') : setToast('error','Error: '.$db->error);
    $stmt->close();
    header('Location: index.php'); exit();
}

if (isset($_GET['delete'])) {
    $db->query("UPDATE productos SET activo=0 WHERE id=".(int)$_GET['delete']);
    setToast('warning','Producto desactivado.');
    header('Location: index.php'); exit();
}

$editProduct = null;
if (isset($_GET['edit']))
    $editProduct = $db->query("SELECT * FROM productos WHERE id=".(int)$_GET['edit'])->fetch_assoc();

$productos   = $db->query("SELECT p.*,c.nombre AS categoria,pr.nombre AS proveedor FROM productos p JOIN categorias c ON p.categoria_id=c.id JOIN proveedores pr ON p.proveedor_id=pr.id WHERE p.activo=1 ORDER BY p.nombre");
$categorias  = $db->query("SELECT * FROM categorias ORDER BY nombre");
$proveedores = $db->query("SELECT * FROM proveedores WHERE activo=1 ORDER BY nombre");

include '../views/layouts/header.php';
?>

<div class="card mb-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-<?= $editProduct ? 'pencil' : 'plus-circle' ?> me-2"></i><?= $editProduct ? 'Editar Producto' : 'Nuevo Producto' ?></span>
    <?php if ($editProduct): ?><a href="index.php" class="btn btn-light btn-sm">Cancelar</a><?php endif; ?>
  </div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="action" value="<?= $editProduct ? 'editar' : 'crear' ?>">
      <?php if ($editProduct): ?><input type="hidden" name="id" value="<?= $editProduct['id'] ?>"><?php endif; ?>
      <div class="row g-2">
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Nombre *</label>
          <input type="text" name="nombre" class="form-control form-control-sm" value="<?= htmlspecialchars($editProduct['nombre'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Código de barras</label>
          <input type="text" name="codigo_barras" class="form-control form-control-sm" value="<?= htmlspecialchars($editProduct['codigo_barras'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Fecha vencimiento</label>
          <input type="date" name="fecha_vencimiento" class="form-control form-control-sm" value="<?= $editProduct['fecha_vencimiento'] ?? '' ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Categoría *</label>
          <select name="categoria_id" class="form-select form-select-sm" required>
            <option value="">Seleccionar...</option>
            <?php $categorias->data_seek(0); while ($c = $categorias->fetch_assoc()): ?>
              <option value="<?= $c['id'] ?>" <?= ($editProduct['categoria_id'] ?? '')==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['nombre']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Proveedor *</label>
          <select name="proveedor_id" class="form-select form-select-sm" required>
            <option value="">Seleccionar...</option>
            <?php $proveedores->data_seek(0); while ($p = $proveedores->fetch_assoc()): ?>
              <option value="<?= $p['id'] ?>" <?= ($editProduct['proveedor_id'] ?? '')==$p['id']?'selected':'' ?>><?= htmlspecialchars($p['nombre']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold">P. Compra *</label>
          <input type="number" step="0.01" name="precio_compra" class="form-control form-control-sm" value="<?= $editProduct['precio_compra'] ?? '' ?>" required>
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold">P. Venta *</label>
          <input type="number" step="0.01" name="precio_venta" class="form-control form-control-sm" value="<?= $editProduct['precio_venta'] ?? '' ?>" required>
        </div>
        <div class="col-md-1">
          <label class="form-label small fw-semibold">Stock</label>
          <input type="number" name="stock_actual" class="form-control form-control-sm" value="<?= $editProduct['stock_actual'] ?? 0 ?>" required>
        </div>
        <div class="col-md-1">
          <label class="form-label small fw-semibold">Mínimo</label>
          <input type="number" name="stock_minimo" class="form-control form-control-sm" value="<?= $editProduct['stock_minimo'] ?? 5 ?>" required>
        </div>
        <div class="col-12 mt-1">
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i><?= $editProduct ? 'Guardar cambios' : 'Registrar producto' ?></button>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header"><i class="bi bi-table me-2"></i>Listado de Productos</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover table-sm mb-0">
        <thead><tr><th>#</th><th>Nombre</th><th>Categoría</th><th>Proveedor</th><th>P.Compra</th><th>P.Venta</th><th>Stock</th><th>Vencimiento</th><th>Acciones</th></tr></thead>
        <tbody>
          <?php while ($p = $productos->fetch_assoc()):
            $sb = $p['stock_actual'] <= $p['stock_minimo'];
            $pv = $p['fecha_vencimiento'] && $p['fecha_vencimiento'] <= date('Y-m-d',strtotime('+7 days'));
          ?>
          <tr class="<?= $sb ? 'table-warning' : '' ?>">
            <td class="small"><?= $p['id'] ?></td>
            <td class="small"><strong><?= htmlspecialchars($p['nombre']) ?></strong></td>
            <td><span class="badge bg-secondary" style="font-size:10px"><?= htmlspecialchars($p['categoria']) ?></span></td>
            <td class="small"><?= htmlspecialchars($p['proveedor']) ?></td>
            <td class="small">RD$ <?= number_format($p['precio_compra'],2) ?></td>
            <td class="small">RD$ <?= number_format($p['precio_venta'],2) ?></td>
            <td><span class="badge bg-<?= $sb?'danger':'success' ?>"><?= $p['stock_actual'] ?><?= $sb?' ⚠':'' ?></span></td>
            <td class="small">
              <?php if ($p['fecha_vencimiento']): ?>
                <span class="badge bg-<?= $pv?'warning text-dark':'light text-dark' ?>"><?= date('d/m/Y',strtotime($p['fecha_vencimiento'])) ?></span>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td>
              <a href="?edit=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="bi bi-pencil"></i></a>
              <a href="?delete=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="return confirmar(event,this.href,'¿Desactivar este producto?')"><i class="bi bi-trash"></i></a>
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
