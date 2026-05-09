<?php
require_once '../config/database.php';
require_once '../config/session.php';
requireLogin();

require_once '../views/layouts/toast.php';

$db = getDB();
$pageTitle = 'Ventas';

// ── ANULAR VENTA ───────────────────────────────────────────
if (isset($_GET['anular'])) {
    $id = (int)$_GET['anular'];
    $db->query("UPDATE ventas SET estado='anulada' WHERE id=$id");
    setToast('warning', 'Venta anulada correctamente.');
    header('Location: index.php'); exit();
}

// ── REGISTRAR VENTA ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'crear') {
    $usuario_id    = $_SESSION['usuario_id'];
    $cliente_id    = !empty($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : null;
    $metodo_pago   = $_POST['metodo_pago'];
    $productos_ids = $_POST['producto_id'] ?? [];
    $cantidades    = $_POST['cantidad'] ?? [];
    $precios       = $_POST['precio_unitario'] ?? [];

    if (empty($productos_ids)) {
        setToast('error', 'Debes agregar al menos un producto.');
    } else {
        $num_factura = 'FAC-' . date('Ymd') . '-' . str_pad(rand(1,9999),4,'0',STR_PAD_LEFT);
        $total = 0;
        foreach ($cantidades as $k => $cant) $total += $cant * $precios[$k];

        $db->begin_transaction();
        try {
            $stmt = $db->prepare("INSERT INTO ventas (usuario_id,cliente_id,num_factura,total,metodo_pago) VALUES (?,?,?,?,?)");
            $stmt->bind_param('iisds', $usuario_id, $cliente_id, $num_factura, $total, $metodo_pago);
            $stmt->execute();
            $venta_id = $db->insert_id;
            $stmt->close();

            foreach ($productos_ids as $k => $prod_id) {
                $cant     = (int)$cantidades[$k];
                $precio   = (float)$precios[$k];
                $subtotal = $cant * $precio;

                $r    = $db->query("SELECT stock_actual,nombre,fecha_vencimiento FROM productos WHERE id=$prod_id AND activo=1");
                $prod = $r->fetch_assoc();
                if (!$prod) throw new Exception("Producto no encontrado.");
                if ($prod['stock_actual'] < $cant) throw new Exception("Stock insuficiente: {$prod['nombre']}");
                if ($prod['fecha_vencimiento'] && $prod['fecha_vencimiento'] < date('Y-m-d')) throw new Exception("Producto vencido: {$prod['nombre']}");

                $stmt2 = $db->prepare("INSERT INTO detalle_ventas (venta_id,producto_id,cantidad,precio_unitario,subtotal) VALUES (?,?,?,?,?)");
                $stmt2->bind_param('iiidd', $venta_id, $prod_id, $cant, $precio, $subtotal);
                $stmt2->execute(); $stmt2->close();
                $db->query("UPDATE productos SET stock_actual=stock_actual-$cant WHERE id=$prod_id");
            }

            $db->commit();
            setToast('success', "Venta registrada — Factura: <strong>$num_factura</strong> — Total: RD$ " . number_format($total,2));
        } catch (Exception $e) {
            $db->rollback();
            setToast('error', $e->getMessage());
        }
    }
    header('Location: index.php'); exit();
}

$ventas    = $db->query("SELECT v.*,u.nombre AS cajero,c.nombre AS cliente FROM ventas v JOIN usuarios u ON v.usuario_id=u.id LEFT JOIN clientes c ON v.cliente_id=c.id ORDER BY v.fecha DESC LIMIT 50");
$productos = $db->query("SELECT id,nombre,precio_venta,stock_actual FROM productos WHERE activo=1 AND stock_actual>0 ORDER BY nombre");
$clientes  = $db->query("SELECT * FROM clientes ORDER BY nombre");
$prods_arr = [];
while ($p = $productos->fetch_assoc()) $prods_arr[] = $p;

include '../views/layouts/header.php';
?>

<!-- FORMULARIO NUEVA VENTA -->
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-bag-plus me-2"></i>Registrar Nueva Venta</div>
  <div class="card-body">
    <form method="POST" id="formVenta">
      <input type="hidden" name="action" value="crear">
      <div class="row g-2 mb-3">
        <div class="col-md-5">
          <label class="form-label small fw-semibold">Cliente</label>
          <select name="cliente_id" class="form-select form-select-sm">
            <option value="">Cliente General</option>
            <?php while ($c = $clientes->fetch_assoc()): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Método de pago</label>
          <select name="metodo_pago" class="form-select form-select-sm">
            <option value="efectivo">Efectivo</option>
            <option value="tarjeta">Tarjeta</option>
            <option value="transferencia">Transferencia</option>
          </select>
        </div>
      </div>

      <div class="table-responsive mb-2">
        <table class="table table-bordered table-sm" id="tablaProductos">
          <thead class="table-light">
            <tr><th>Producto</th><th style="width:120px">Precio</th><th style="width:100px">Cantidad</th><th style="width:120px">Subtotal</th><th style="width:40px"></th></tr>
          </thead>
          <tbody id="filas">
            <tr id="fila_0">
              <td>
                <select name="producto_id[]" class="form-select form-select-sm prod-select" onchange="actualizarPrecio(0)" required>
                  <option value="">Seleccionar producto...</option>
                  <?php foreach ($prods_arr as $p): ?>
                    <option value="<?= $p['id'] ?>" data-precio="<?= $p['precio_venta'] ?>" data-stock="<?= $p['stock_actual'] ?>">
                      <?= htmlspecialchars($p['nombre']) ?> (Stock: <?= $p['stock_actual'] ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td><input type="number" step="0.01" name="precio_unitario[]" id="precio_0" class="form-control form-control-sm" readonly></td>
              <td><input type="number" name="cantidad[]" id="cant_0" class="form-control form-control-sm" min="1" value="1" oninput="calcularSubtotal(0)" required></td>
              <td><input type="number" step="0.01" name="subtotal[]" id="sub_0" class="form-control form-control-sm" readonly></td>
              <td><button type="button" class="btn btn-sm btn-danger py-0" onclick="eliminarFila(0)"><i class="bi bi-trash"></i></button></td>
            </tr>
          </tbody>
          <tfoot>
            <tr><td colspan="3" class="text-end fw-bold small">TOTAL:</td><td colspan="2"><span id="totalVenta" class="fw-bold text-primary">RD$ 0.00</span></td></tr>
          </tfoot>
        </table>
      </div>

      <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="agregarFila()">
          <i class="bi bi-plus-circle me-1"></i>Agregar producto
        </button>
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="bi bi-check-circle me-2"></i>Registrar Venta
        </button>
      </div>
    </form>
  </div>
</div>

<!-- HISTORIAL -->
<div class="card">
  <div class="card-header"><i class="bi bi-receipt me-2"></i>Últimas 50 Ventas</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover table-sm mb-0">
        <thead><tr><th>Factura</th><th>Cajero</th><th>Cliente</th><th>Total</th><th>Método</th><th>Fecha</th><th>Estado</th><th>Acción</th></tr></thead>
        <tbody>
          <?php while ($v = $ventas->fetch_assoc()): ?>
          <tr>
            <td class="small"><strong><?= $v['num_factura'] ?></strong></td>
            <td class="small"><?= htmlspecialchars($v['cajero']) ?></td>
            <td class="small"><?= htmlspecialchars($v['cliente'] ?? 'General') ?></td>
            <td class="small">RD$ <?= number_format($v['total'],2) ?></td>
            <td class="small"><?= ucfirst($v['metodo_pago']) ?></td>
            <td class="small"><?= date('d/m/Y H:i', strtotime($v['fecha'])) ?></td>
            <td><span class="badge bg-<?= $v['estado']==='completada'?'success':'danger' ?>"><?= ucfirst($v['estado']) ?></span></td>
            <td>
              <a href="factura.php?id=<?= $v['id'] ?>" class="btn btn-xs btn-outline-primary btn-sm py-0 px-2" target="_blank"><i class="bi bi-printer"></i></a>
              <?php if ($v['estado'] === 'completada'): ?>
              <a href="?anular=<?= $v['id'] ?>" class="btn btn-xs btn-outline-danger btn-sm py-0 px-2"
                 onclick="return confirmarAnular(event, this.href)"><i class="bi bi-x-circle"></i></a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
const productos = <?= json_encode($prods_arr) ?>;
let filaCount = 1;

function actualizarPrecio(idx) {
    const sel = document.querySelector(`#fila_${idx} .prod-select`);
    const opt = sel.options[sel.selectedIndex];
    document.getElementById(`precio_${idx}`).value = opt.dataset.precio || 0;
    calcularSubtotal(idx);
}
function calcularSubtotal(idx) {
    const precio = parseFloat(document.getElementById(`precio_${idx}`).value) || 0;
    const cant   = parseFloat(document.getElementById(`cant_${idx}`).value)   || 0;
    document.getElementById(`sub_${idx}`).value = (precio * cant).toFixed(2);
    calcularTotal();
}
function calcularTotal() {
    let total = 0;
    document.querySelectorAll('[id^="sub_"]').forEach(el => total += parseFloat(el.value) || 0);
    document.getElementById('totalVenta').textContent = 'RD$ ' + total.toLocaleString('es-DO', {minimumFractionDigits:2});
}
function agregarFila() {
    const idx  = filaCount++;
    const opts = productos.map(p => `<option value="${p.id}" data-precio="${p.precio_venta}" data-stock="${p.stock_actual}">${p.nombre} (Stock: ${p.stock_actual})</option>`).join('');
    document.getElementById('filas').insertAdjacentHTML('beforeend', `
        <tr id="fila_${idx}">
          <td><select name="producto_id[]" class="form-select form-select-sm prod-select" onchange="actualizarPrecio(${idx})" required><option value="">Seleccionar...</option>${opts}</select></td>
          <td><input type="number" step="0.01" name="precio_unitario[]" id="precio_${idx}" class="form-control form-control-sm" readonly></td>
          <td><input type="number" name="cantidad[]" id="cant_${idx}" class="form-control form-control-sm" min="1" value="1" oninput="calcularSubtotal(${idx})" required></td>
          <td><input type="number" step="0.01" name="subtotal[]" id="sub_${idx}" class="form-control form-control-sm" readonly></td>
          <td><button type="button" class="btn btn-sm btn-danger py-0" onclick="eliminarFila(${idx})"><i class="bi bi-trash"></i></button></td>
        </tr>`);
}
function eliminarFila(idx) {
    const f = document.getElementById(`fila_${idx}`);
    if (f) { f.remove(); calcularTotal(); }
}

// Confirmación sin ventana emergente
function confirmarAnular(e, url) {
    e.preventDefault();
    const div = document.createElement('div');
    div.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9998;display:flex;align-items:center;justify-content:center;';
    div.innerHTML = `
        <div style="background:#fff;border-radius:16px;padding:28px 32px;max-width:360px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.3);text-align:center;">
            <i class="bi bi-exclamation-triangle-fill" style="font-size:40px;color:#BF5800;"></i>
            <h5 style="margin:12px 0 8px;color:#1F4E79;font-weight:700;">¿Anular esta venta?</h5>
            <p style="color:#64748B;font-size:14px;margin-bottom:20px;">Esta acción no se puede deshacer.</p>
            <div style="display:flex;gap:10px;justify-content:center;">
                <button onclick="this.closest('div[style]').remove()" style="flex:1;padding:10px;border:2px solid #E2E8F0;border-radius:8px;background:#fff;font-weight:600;cursor:pointer;">Cancelar</button>
                <a href="${url}" style="flex:1;padding:10px;background:linear-gradient(135deg,#C00000,#e03131);color:#fff;border-radius:8px;font-weight:600;text-decoration:none;display:flex;align-items:center;justify-content:center;">Sí, anular</a>
            </div>
        </div>`;
    document.body.appendChild(div);
    return false;
}
</script>

<?php include '../views/layouts/footer.php'; ?>
