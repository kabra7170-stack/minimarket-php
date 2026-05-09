<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../views/layouts/toast.php';
requireLogin();
requireRole(['administrador']);

$db = getDB();
$pageTitle = 'Gestión de Compras';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'crear') {
    $proveedor_id  = (int)$_POST['proveedor_id'];
    $usuario_id    = $_SESSION['usuario_id'];
    $fecha_entrega = !empty($_POST['fecha_entrega']) ? $_POST['fecha_entrega'] : null;
    $productos_ids = $_POST['producto_id'] ?? [];
    $cantidades    = $_POST['cantidad'] ?? [];
    $precios       = $_POST['precio_unitario'] ?? [];

    if (empty($productos_ids)) {
        setToast('error','Debes agregar al menos un producto.');
    } else {
        $num_orden = 'OC-'.date('Ymd').'-'.str_pad(rand(1,9999),4,'0',STR_PAD_LEFT);
        $total = 0;
        foreach ($cantidades as $k => $cant) $total += $cant * $precios[$k];

        $db->begin_transaction();
        try {
            $stmt = $db->prepare("INSERT INTO compras (proveedor_id,usuario_id,num_orden,total,fecha_entrega) VALUES (?,?,?,?,?)");
            $stmt->bind_param('iisds', $proveedor_id, $usuario_id, $num_orden, $total, $fecha_entrega);
            $stmt->execute(); $compra_id = $db->insert_id; $stmt->close();

            foreach ($productos_ids as $k => $prod_id) {
                $cant = (int)$cantidades[$k]; $precio = (float)$precios[$k]; $subtotal = $cant * $precio;
                $st = $db->prepare("INSERT INTO detalle_compras (compra_id,producto_id,cantidad,precio_unitario,subtotal) VALUES (?,?,?,?,?)");
                $st->bind_param('iiidd', $compra_id, $prod_id, $cant, $precio, $subtotal);
                $st->execute(); $st->close();
            }
            $db->commit();
            setToast('success',"Orden <strong>$num_orden</strong> registrada — Total: RD$ ".number_format($total,2));
        } catch (Exception $e) {
            $db->rollback();
            setToast('error', $e->getMessage());
        }
    }
    header('Location: index.php'); exit();
}

if (isset($_GET['recibir'])) {
    $id = (int)$_GET['recibir'];
    $db->query("UPDATE productos p JOIN detalle_compras dc ON p.id=dc.producto_id SET p.stock_actual=p.stock_actual+dc.cantidad WHERE dc.compra_id=$id");
    $db->query("UPDATE compras SET estado='recibida' WHERE id=$id");
    setToast('success','Compra recibida. Inventario actualizado correctamente.');
    header('Location: index.php'); exit();
}

if (isset($_GET['pagar'])) {
    $id = (int)$_GET['pagar'];
    $db->query("UPDATE compras SET estado='pagada' WHERE id=$id AND estado='recibida'");
    setToast('success','Compra marcada como pagada.');
    header('Location: index.php'); exit();
}

$compras     = $db->query("SELECT c.*,p.nombre AS proveedor,u.nombre AS usuario FROM compras c JOIN proveedores p ON c.proveedor_id=p.id JOIN usuarios u ON c.usuario_id=u.id ORDER BY c.fecha DESC");
$proveedores = $db->query("SELECT * FROM proveedores WHERE activo=1 ORDER BY nombre");
$productos   = $db->query("SELECT id,nombre,precio_compra FROM productos WHERE activo=1 ORDER BY nombre");
$prods_arr   = [];
while ($p = $productos->fetch_assoc()) $prods_arr[] = $p;

include '../views/layouts/header.php';
?>

<div class="card mb-3">
  <div class="card-header"><i class="bi bi-cart-plus me-2"></i>Nueva Orden de Compra</div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="action" value="crear">
      <div class="row g-2 mb-3">
        <div class="col-md-5">
          <label class="form-label small fw-semibold">Proveedor *</label>
          <select name="proveedor_id" class="form-select form-select-sm" required>
            <option value="">Seleccionar proveedor...</option>
            <?php while ($pr = $proveedores->fetch_assoc()): ?>
              <option value="<?= $pr['id'] ?>"><?= htmlspecialchars($pr['nombre']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Fecha entrega esperada</label>
          <input type="date" name="fecha_entrega" class="form-control form-control-sm" min="<?= date('Y-m-d') ?>">
        </div>
      </div>
      <div class="table-responsive mb-2">
        <table class="table table-bordered table-sm" id="tablaCompra">
          <thead class="table-light">
            <tr><th>Producto</th><th style="width:120px">Precio</th><th style="width:100px">Cantidad</th><th style="width:120px">Subtotal</th><th style="width:40px"></th></tr>
          </thead>
          <tbody id="filasCompra">
            <tr id="fc_0">
              <td>
                <select name="producto_id[]" class="form-select form-select-sm" onchange="setPrecioCompra(0)" required>
                  <option value="">Seleccionar...</option>
                  <?php foreach ($prods_arr as $p): ?>
                    <option value="<?= $p['id'] ?>" data-precio="<?= $p['precio_compra'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td><input type="number" step="0.01" name="precio_unitario[]" id="pc_0" class="form-control form-control-sm" onchange="calcSub(0)" required></td>
              <td><input type="number" name="cantidad[]" id="cc_0" class="form-control form-control-sm" min="1" value="1" onchange="calcSub(0)" required></td>
              <td><input type="number" step="0.01" id="sc_0" class="form-control form-control-sm" readonly></td>
              <td><button type="button" class="btn btn-sm btn-danger py-0" onclick="quitarFila(0)"><i class="bi bi-trash"></i></button></td>
            </tr>
          </tbody>
          <tfoot>
            <tr><td colspan="3" class="text-end fw-bold small">TOTAL:</td><td colspan="2"><span id="totalCompra" class="fw-bold text-primary">RD$ 0.00</span></td></tr>
          </tfoot>
        </table>
      </div>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addFilaCompra()"><i class="bi bi-plus-circle me-1"></i>Agregar producto</button>
        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i>Registrar Orden</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header"><i class="bi bi-list-ul me-2"></i>Historial de Órdenes</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover table-sm mb-0">
        <thead><tr><th>Orden</th><th>Proveedor</th><th>Total</th><th>Estado</th><th>F. Entrega</th><th>Fecha</th><th>Acciones</th></tr></thead>
        <tbody>
          <?php while ($c = $compras->fetch_assoc()):
            $badge = match($c['estado']) { 'pendiente'=>'bg-warning text-dark','recibida'=>'bg-info text-dark','pagada'=>'bg-success',default=>'bg-secondary' };
          ?>
          <tr>
            <td class="small"><strong><?= $c['num_orden'] ?></strong></td>
            <td class="small"><?= htmlspecialchars($c['proveedor']) ?></td>
            <td class="small">RD$ <?= number_format($c['total'],2) ?></td>
            <td><span class="badge <?= $badge ?>"><?= ucfirst($c['estado']) ?></span></td>
            <td class="small"><?= $c['fecha_entrega'] ? date('d/m/Y',strtotime($c['fecha_entrega'])) : '—' ?></td>
            <td class="small"><?= date('d/m/Y',strtotime($c['fecha'])) ?></td>
            <td>
              <?php if ($c['estado']==='pendiente'): ?>
                <a href="?recibir=<?= $c['id'] ?>" class="btn btn-sm btn-outline-info py-0 px-2" onclick="return confirmar(event,this.href,'¿Marcar como recibida? Actualizará el inventario.')"><i class="bi bi-check2-circle me-1"></i>Recibida</a>
              <?php elseif ($c['estado']==='recibida'): ?>
                <a href="?pagar=<?= $c['id'] ?>" class="btn btn-sm btn-outline-success py-0 px-2" onclick="return confirmar(event,this.href,'¿Marcar como pagada?')"><i class="bi bi-cash me-1"></i>Pagada</a>
              <?php else: ?><span class="text-muted small">—</span><?php endif; ?>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
const prodCompra = <?= json_encode($prods_arr) ?>;
let fcCount = 1;
function setPrecioCompra(idx) {
    const sel = document.querySelector(`#fc_${idx} select`);
    document.getElementById(`pc_${idx}`).value = sel.options[sel.selectedIndex].dataset.precio || '';
    calcSub(idx);
}
function calcSub(idx) {
    const p = parseFloat(document.getElementById(`pc_${idx}`).value)||0;
    const c = parseFloat(document.getElementById(`cc_${idx}`).value)||0;
    document.getElementById(`sc_${idx}`).value = (p*c).toFixed(2);
    calcTotalCompra();
}
function calcTotalCompra() {
    let t=0; document.querySelectorAll('[id^="sc_"]').forEach(el=>t+=parseFloat(el.value)||0);
    document.getElementById('totalCompra').textContent='RD$ '+t.toLocaleString('es-DO',{minimumFractionDigits:2});
}
function addFilaCompra() {
    const idx=fcCount++;
    const opts=prodCompra.map(p=>`<option value="${p.id}" data-precio="${p.precio_compra}">${p.nombre}</option>`).join('');
    document.getElementById('filasCompra').insertAdjacentHTML('beforeend',`<tr id="fc_${idx}">
        <td><select name="producto_id[]" class="form-select form-select-sm" onchange="setPrecioCompra(${idx})" required><option value="">Seleccionar...</option>${opts}</select></td>
        <td><input type="number" step="0.01" name="precio_unitario[]" id="pc_${idx}" class="form-control form-control-sm" onchange="calcSub(${idx})" required></td>
        <td><input type="number" name="cantidad[]" id="cc_${idx}" class="form-control form-control-sm" min="1" value="1" onchange="calcSub(${idx})" required></td>
        <td><input type="number" step="0.01" id="sc_${idx}" class="form-control form-control-sm" readonly></td>
        <td><button type="button" class="btn btn-sm btn-danger py-0" onclick="quitarFila(${idx})"><i class="bi bi-trash"></i></button></td>
    </tr>`);
}
function quitarFila(idx) { const f=document.getElementById(`fc_${idx}`); if(f){f.remove();calcTotalCompra();} }
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
            <a href="${url}" style="flex:1;padding:9px;background:linear-gradient(135deg,#0F6E56,#1aad87);color:#fff;border-radius:8px;font-weight:600;text-decoration:none;display:flex;align-items:center;justify-content:center;">Confirmar</a>
        </div></div>`;
    document.body.appendChild(div);
    return false;
}
</script>

<?php include '../views/layouts/footer.php'; ?>
