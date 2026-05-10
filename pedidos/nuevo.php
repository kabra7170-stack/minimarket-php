<?php
require_once '../config/database.php';
require_once '../config/session.php';
requireLogin();

$db = getDB();
$pageTitle = 'Nuevo Pedido';
$user = currentUser();
$error = '';
$success = '';

$clientes  = $db->query("SELECT id, nombre FROM clientes ORDER BY nombre");
$productos = $db->query("SELECT id, nombre, precio_venta, stock_actual FROM productos WHERE activo=1 AND stock_actual>0 ORDER BY nombre");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo       = $_POST['tipo'] ?? 'mostrador';
    $cliente_id = $_POST['cliente_id'] ?: null;
    $notas      = trim($_POST['notas'] ?? '');
    $items      = $_POST['producto_id'] ?? [];
    $cantidades = $_POST['cantidad'] ?? [];

    // filtrar items vacíos
    $lineas = [];
    foreach ($items as $i => $pid) {
        $qty = (int)($cantidades[$i] ?? 0);
        if ($pid && $qty > 0) $lineas[] = ['pid' => (int)$pid, 'qty' => $qty];
    }

    if (empty($lineas)) {
        $error = 'Agrega al menos un producto.';
    } else {
        $num = 'PED-' . strtoupper(substr(uniqid(), -6));
        $subtotal = 0;

        // calcular subtotal
        foreach ($lineas as &$l) {
            $r = $db->query("SELECT precio_venta FROM productos WHERE id={$l['pid']}")->fetch_assoc();
            $l['precio'] = (float)$r['precio_venta'];
            $l['sub']    = $l['precio'] * $l['qty'];
            $subtotal   += $l['sub'];
        }

        // envio si domicilio
        $costo_envio = ($tipo === 'domicilio') ? (float)($_POST['costo_envio'] ?? 0) : 0;
        $total = $subtotal + $costo_envio;

        $stmt = $db->prepare("INSERT INTO pedidos (cliente_id, usuario_id, num_pedido, estado, tipo, subtotal, total, notas) VALUES (?,?,?,'pendiente',?,?,?,?)");
        $stmt->bind_param('iissdds', $cliente_id, $user['id'], $num, $tipo, $subtotal, $total, $notas);
        $stmt->execute();
        $pedido_id = $stmt->insert_id;
        $stmt->close();

        // detalle
        foreach ($lineas as $l) {
            $st = $db->prepare("INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?,?,?,?,?)");
            $st->bind_param('iiidd', $pedido_id, $l['pid'], $l['qty'], $l['precio'], $l['sub']);
            $st->execute(); $st->close();
        }

        // si domicilio guardar info entrega
        if ($tipo === 'domicilio') {
            $dir  = trim($_POST['direccion'] ?? '');
            $tel  = trim($_POST['telefono'] ?? '');
            $ref  = trim($_POST['referencia'] ?? '');
            $rep  = trim($_POST['repartidor'] ?? '');
            $st2  = $db->prepare("INSERT INTO pedidos_domicilio (pedido_id, direccion, telefono, referencia, repartidor, costo_envio) VALUES (?,?,?,?,?,?)");
            $st2->bind_param('issssd', $pedido_id, $dir, $tel, $ref, $rep, $costo_envio);
            $st2->execute(); $st2->close();
        }

header("Location: ver.php?id=$pedido_id&ok=1");
exit();

    }
}

include '../views/layouts/header.php';
?>

<?php if ($error): ?>
<div class="alert alert-danger py-2"><?= $error ?></div>
<?php endif; ?>

<form method="POST" id="frmPedido">
<div class="row g-3">

  <!-- Columna izquierda -->
  <div class="col-md-8">
    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-bag-plus me-2"></i>Productos del Pedido</div>
      <div class="card-body">
        <table class="table table-sm" id="tblItems">
          <thead><tr><th>Producto</th><th style="width:100px">Cantidad</th><th style="width:110px">Precio</th><th style="width:40px"></th></tr></thead>
          <tbody id="filas">
            <tr class="fila-item">
              <td>
                <select name="producto_id[]" class="form-select form-select-sm sel-prod" required>
                  <option value="">— Selecciona —</option>
                  <?php
                    $productos->data_seek(0);
                    while ($pr = $productos->fetch_assoc()):
                  ?>
                  <option value="<?= $pr['id'] ?>" data-precio="<?= $pr['precio_venta'] ?>" data-stock="<?= $pr['stock_actual'] ?>">
                    <?= htmlspecialchars($pr['nombre']) ?> (Stock: <?= $pr['stock_actual'] ?>)
                  </option>
                  <?php endwhile; ?>
                </select>
              </td>
              <td><input type="number" name="cantidad[]" class="form-control form-control-sm inp-qty" min="1" value="1"></td>
              <td><input type="text" class="form-control form-control-sm inp-precio" readonly placeholder="RD$ 0.00"></td>
              <td><button type="button" class="btn btn-danger btn-sm py-0 btn-del"><i class="bi bi-trash"></i></button></td>
            </tr>
          </tbody>
        </table>
        <button type="button" class="btn btn-outline-primary btn-sm" id="btnAgregar"><i class="bi bi-plus me-1"></i>Agregar producto</button>
      </div>
    </div>

    <!-- Info domicilio (oculto por defecto) -->
    <div class="card" id="cardDomicilio" style="display:none">
      <div class="card-header"><i class="bi bi-geo-alt me-2"></i>Datos de Entrega</div>
      <div class="card-body">
        <div class="row g-2">
          <div class="col-md-8">
            <label class="form-label small">Dirección *</label>
            <input type="text" name="direccion" class="form-control form-control-sm" placeholder="Calle, número, sector...">
          </div>
          <div class="col-md-4">
            <label class="form-label small">Teléfono *</label>
            <input type="text" name="telefono" class="form-control form-control-sm" placeholder="809-000-0000">
          </div>
          <div class="col-md-6">
            <label class="form-label small">Referencia</label>
            <input type="text" name="referencia" class="form-control form-control-sm" placeholder="Punto de referencia">
          </div>
          <div class="col-md-3">
            <label class="form-label small">Repartidor</label>
            <input type="text" name="repartidor" class="form-control form-control-sm" placeholder="Nombre">
          </div>
          <div class="col-md-3">
            <label class="form-label small">Costo envío (RD$)</label>
            <input type="number" name="costo_envio" class="form-control form-control-sm" value="0" min="0" id="costoEnvio">
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Columna derecha -->
  <div class="col-md-4">
    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-info-circle me-2"></i>Datos del Pedido</div>
      <div class="card-body">
        <div class="mb-2">
          <label class="form-label small fw-semibold">Tipo de Pedido</label>
          <div class="d-flex gap-2">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="tipo" id="rMostrador" value="mostrador" checked>
              <label class="form-check-label small" for="rMostrador"><i class="bi bi-shop me-1"></i>Mostrador</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="tipo" id="rDomicilio" value="domicilio">
              <label class="form-check-label small" for="rDomicilio"><i class="bi bi-bicycle me-1"></i>Domicilio</label>
            </div>
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold">Cliente</label>
          <select name="cliente_id" class="form-select form-select-sm">
            <option value="">Cliente General</option>
            <?php $clientes->data_seek(0); while ($c = $clientes->fetch_assoc()): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold">Notas</label>
          <textarea name="notas" class="form-control form-control-sm" rows="2" placeholder="Instrucciones especiales..."></textarea>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><i class="bi bi-calculator me-2"></i>Resumen</div>
      <div class="card-body">
        <div class="d-flex justify-content-between mb-1"><span class="small">Subtotal:</span><strong id="lblSub">RD$ 0.00</strong></div>
        <div class="d-flex justify-content-between mb-1 d-none" id="rowEnvio"><span class="small">Envío:</span><strong id="lblEnvio">RD$ 0.00</strong></div>
        <hr class="my-2">
        <div class="d-flex justify-content-between"><span class="fw-bold">TOTAL:</span><strong class="text-primary fs-5" id="lblTotal">RD$ 0.00</strong></div>
        <button type="submit" class="btn btn-primary w-100 mt-3"><i class="bi bi-check-lg me-2"></i>Guardar Pedido</button>
        <a href="index.php" class="btn btn-outline-secondary w-100 mt-2 btn-sm">Cancelar</a>
      </div>
    </div>
  </div>
</div>
</form>

<script>
const fmt = n => 'RD$ ' + parseFloat(n||0).toLocaleString('es-DO', {minimumFractionDigits:2});

function calcTotal() {
  let sub = 0;
  document.querySelectorAll('.fila-item').forEach(f => {
    const sel = f.querySelector('.sel-prod');
    const qty = parseFloat(f.querySelector('.inp-qty').value) || 0;
    const opt = sel.options[sel.selectedIndex];
    const precio = parseFloat(opt?.dataset.precio || 0);
    f.querySelector('.inp-precio').value = fmt(precio * qty);
    sub += precio * qty;
  });
  const envio = parseFloat(document.getElementById('costoEnvio')?.value || 0);
  document.getElementById('lblSub').textContent = fmt(sub);
  document.getElementById('lblEnvio').textContent = fmt(envio);
  document.getElementById('lblTotal').textContent = fmt(sub + envio);
}

function clonarFila() {
  const orig = document.querySelector('.fila-item').cloneNode(true);
  orig.querySelector('.sel-prod').value = '';
  orig.querySelector('.inp-qty').value = 1;
  orig.querySelector('.inp-precio').value = '';
  orig.querySelector('.btn-del').addEventListener('click', () => { orig.remove(); calcTotal(); });
  orig.querySelector('.sel-prod').addEventListener('change', calcTotal);
  orig.querySelector('.inp-qty').addEventListener('input', calcTotal);
  document.getElementById('filas').appendChild(orig);
}

document.getElementById('btnAgregar').addEventListener('click', clonarFila);

document.getElementById('filas').addEventListener('change', calcTotal);
document.getElementById('filas').addEventListener('input', calcTotal);

document.querySelectorAll('input[name="tipo"]').forEach(r => {
  r.addEventListener('change', () => {
    const esDom = r.value === 'domicilio';
    document.getElementById('cardDomicilio').style.display = esDom ? '' : 'none';
    document.getElementById('rowEnvio').classList.toggle('d-none', !esDom);
    calcTotal();
  });
});

document.getElementById('costoEnvio')?.addEventListener('input', calcTotal);

// btn eliminar fila inicial
document.querySelector('.btn-del').addEventListener('click', function() {
  if (document.querySelectorAll('.fila-item').length > 1) { this.closest('tr').remove(); calcTotal(); }
});
</script>

<?php include '../views/layouts/footer.php'; ?>
