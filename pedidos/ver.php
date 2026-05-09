<?php
require_once '../config/database.php';
require_once '../config/session.php';
requireLogin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$pedido = $db->query("
    SELECT p.*, c.nombre AS cliente, c.telefono AS cli_tel, u.nombre AS cajero
    FROM pedidos p
    LEFT JOIN clientes c ON p.cliente_id = c.id
    JOIN usuarios u ON p.usuario_id = u.id
    WHERE p.id = $id
")->fetch_assoc();

if (!$pedido) { header('Location: index.php'); exit(); }

$detalle = $db->query("
    SELECT dp.*, pr.nombre AS producto
    FROM detalle_pedidos dp
    JOIN productos pr ON dp.producto_id = pr.id
    WHERE dp.pedido_id = $id
");

$domicilio = $db->query("SELECT * FROM pedidos_domicilio WHERE pedido_id = $id")->fetch_assoc();

$pageTitle = 'Pedido ' . $pedido['num_pedido'];
include '../views/layouts/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
<div class="alert alert-success py-2"><i class="bi bi-check-circle me-2"></i>Pedido creado exitosamente.</div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-md-8">
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-receipt me-2"></i><?= $pedido['num_pedido'] ?></span>
        <?php
          $badges = ['pendiente'=>'bg-warning text-dark','en_proceso'=>'bg-primary','listo'=>'bg-success','entregado'=>'bg-secondary','cancelado'=>'bg-danger'];
          $b = $badges[$pedido['estado']] ?? 'bg-secondary';
        ?>
        <span class="badge <?= $b ?> fs-6"><?= ucfirst(str_replace('_',' ',$pedido['estado'])) ?></span>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead><tr><th>Producto</th><th>Cant.</th><th>Precio</th><th>Subtotal</th></tr></thead>
          <tbody>
            <?php while ($d = $detalle->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($d['producto']) ?></td>
              <td><?= $d['cantidad'] ?></td>
              <td>RD$ <?= number_format($d['precio_unitario'],2) ?></td>
              <td>RD$ <?= number_format($d['subtotal'],2) ?></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
          <tfoot>
            <tr><td colspan="3" class="text-end fw-semibold">Subtotal:</td><td>RD$ <?= number_format($pedido['subtotal'],2) ?></td></tr>
            <?php if ($domicilio): ?>
            <tr><td colspan="3" class="text-end fw-semibold">Envío:</td><td>RD$ <?= number_format($domicilio['costo_envio'],2) ?></td></tr>
            <?php endif; ?>
            <tr class="table-active"><td colspan="3" class="text-end fw-bold">TOTAL:</td><td class="fw-bold text-primary">RD$ <?= number_format($pedido['total'],2) ?></td></tr>
          </tfoot>
        </table>
      </div>
    </div>

    <?php if ($pedido['notas']): ?>
    <div class="alert-mini mb-3"><i class="bi bi-chat-left-text me-2"></i><?= htmlspecialchars($pedido['notas']) ?></div>
    <?php endif; ?>

    <?php if ($domicilio): ?>
    <div class="card">
      <div class="card-header"><i class="bi bi-geo-alt me-2"></i>Datos de Entrega</div>
      <div class="card-body">
        <div class="row g-2 small">
          <div class="col-md-6"><strong>Dirección:</strong><br><?= htmlspecialchars($domicilio['direccion']) ?></div>
          <div class="col-md-3"><strong>Teléfono:</strong><br><?= htmlspecialchars($domicilio['telefono']) ?></div>
          <div class="col-md-3"><strong>Estado entrega:</strong><br>
            <span class="badge bg-info text-dark"><?= ucfirst(str_replace('_',' ',$domicilio['estado_entrega'])) ?></span>
          </div>
          <?php if ($domicilio['referencia']): ?>
          <div class="col-md-6"><strong>Referencia:</strong><br><?= htmlspecialchars($domicilio['referencia']) ?></div>
          <?php endif; ?>
          <?php if ($domicilio['repartidor']): ?>
          <div class="col-md-6"><strong>Repartidor:</strong><br><?= htmlspecialchars($domicilio['repartidor']) ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div class="col-md-4">
    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-info-circle me-2"></i>Información</div>
      <div class="card-body small">
        <p class="mb-1"><strong>Cliente:</strong> <?= htmlspecialchars($pedido['cliente'] ?? 'Cliente General') ?></p>
        <p class="mb-1"><strong>Tipo:</strong>
          <?= $pedido['tipo'] === 'domicilio' ? '<span class="badge bg-info text-dark">Domicilio</span>' : '<span class="badge bg-secondary">Mostrador</span>' ?>
        </p>
        <p class="mb-1"><strong>Atendido por:</strong> <?= htmlspecialchars($pedido['cajero']) ?></p>
        <p class="mb-0"><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($pedido['fecha'])) ?></p>
      </div>
    </div>

    <?php if (!in_array($pedido['estado'], ['entregado','cancelado'])): ?>
    <div class="card">
      <div class="card-header"><i class="bi bi-arrow-right-circle me-2"></i>Cambiar Estado</div>
      <div class="card-body">
        <form method="POST" action="estado.php">
          <input type="hidden" name="id" value="<?= $id ?>">
          <select name="estado" class="form-select form-select-sm mb-2">
            <option value="pendiente"   <?= $pedido['estado']=='pendiente'   ? 'selected':'' ?>>Pendiente</option>
            <option value="en_proceso"  <?= $pedido['estado']=='en_proceso'  ? 'selected':'' ?>>En Proceso</option>
            <option value="listo"       <?= $pedido['estado']=='listo'       ? 'selected':'' ?>>Listo</option>
            <option value="entregado"   <?= $pedido['estado']=='entregado'   ? 'selected':'' ?>>Entregado</option>
            <option value="cancelado"   <?= $pedido['estado']=='cancelado'   ? 'selected':'' ?>>Cancelado</option>
          </select>
          <?php if ($domicilio && !in_array($domicilio['estado_entrega'],['entregado','fallido'])): ?>
          <select name="estado_entrega" class="form-select form-select-sm mb-2">
            <option value="pendiente"  <?= $domicilio['estado_entrega']=='pendiente'  ?'selected':''?>>Entrega: Pendiente</option>
            <option value="en_camino"  <?= $domicilio['estado_entrega']=='en_camino'  ?'selected':''?>>Entrega: En Camino</option>
            <option value="entregado"  <?= $domicilio['estado_entrega']=='entregado'  ?'selected':''?>>Entrega: Entregado</option>
            <option value="fallido"    <?= $domicilio['estado_entrega']=='fallido'    ?'selected':''?>>Entrega: Fallido</option>
          </select>
          <?php endif; ?>
          <button type="submit" class="btn btn-primary btn-sm w-100">Actualizar Estado</button>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <a href="index.php" class="btn btn-outline-secondary btn-sm w-100 mt-2"><i class="bi bi-arrow-left me-1"></i>Volver</a>
  </div>
</div>

<?php include '../views/layouts/footer.php'; ?>
