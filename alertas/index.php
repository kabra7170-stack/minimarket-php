<?php
require_once '../config/database.php';
require_once '../config/session.php';
requireLogin();

$db = getDB();
$pageTitle = 'Alertas del Sistema';

// Marcar como leída
if (isset($_GET['leer'])) {
    $db->query("UPDATE alertas SET leida=1 WHERE id=" . (int)$_GET['leer']);
}
if (isset($_GET['leer_todas'])) {
    $db->query("UPDATE alertas SET leida=1");
}

// Generar alertas automáticas
$db->query("INSERT INTO alertas (producto_id, tipo, mensaje)
    SELECT id, 'stock_bajo', CONCAT('Stock bajo: \"', nombre, '\" tiene ', stock_actual, ' unidades (mínimo: ', stock_minimo, ')')
    FROM productos WHERE stock_actual <= stock_minimo AND activo=1
    AND id NOT IN (SELECT producto_id FROM alertas WHERE tipo='stock_bajo' AND leida=0 AND DATE(fecha)=CURDATE())");

$db->query("INSERT INTO alertas (producto_id, tipo, mensaje)
    SELECT id, 'vencimiento', CONCAT('Por vencer: \"', nombre, '\" vence el ', fecha_vencimiento, ' (', DATEDIFF(fecha_vencimiento, CURDATE()), ' días)')
    FROM productos WHERE fecha_vencimiento IS NOT NULL
    AND fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    AND fecha_vencimiento >= CURDATE() AND activo=1
    AND id NOT IN (SELECT producto_id FROM alertas WHERE tipo='vencimiento' AND leida=0 AND DATE(fecha)=CURDATE())");

$alertas = $db->query("SELECT a.*, p.nombre AS producto FROM alertas a JOIN productos p ON a.producto_id=p.id ORDER BY a.leida ASC, a.fecha DESC");
$total_no_leidas = $db->query("SELECT COUNT(*) AS t FROM alertas WHERE leida=0")->fetch_assoc()['t'];

include '../views/layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <span class="badge bg-danger fs-6"><?= $total_no_leidas ?> alertas sin leer</span>
  <a href="?leer_todas=1" class="btn btn-outline-secondary btn-sm"><i class="bi bi-check-all me-1"></i>Marcar todas como leídas</a>
</div>

<div class="card">
  <div class="card-header"><i class="bi bi-bell me-2"></i>Centro de Alertas</div>
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead><tr><th>Tipo</th><th>Producto</th><th>Mensaje</th><th>Fecha</th><th>Estado</th><th></th></tr></thead>
      <tbody>
        <?php while ($a = $alertas->fetch_assoc()):
          $color = $a['tipo'] === 'stock_bajo' ? 'danger' : 'warning';
          $icon  = $a['tipo'] === 'stock_bajo' ? 'exclamation-triangle' : 'calendar-x';
        ?>
        <tr class="<?= !$a['leida'] ? 'table-'.$color.' bg-opacity-10' : '' ?>">
          <td><span class="badge bg-<?= $color ?>"><?= $a['tipo'] === 'stock_bajo' ? 'Stock Bajo' : 'Vencimiento' ?></span></td>
          <td><strong><?= htmlspecialchars($a['producto']) ?></strong></td>
          <td><?= htmlspecialchars($a['mensaje']) ?></td>
          <td><?= date('d/m/Y H:i', strtotime($a['fecha'])) ?></td>
          <td><?= $a['leida'] ? '<span class="badge bg-secondary">Leída</span>' : '<span class="badge bg-danger">Nueva</span>' ?></td>
          <td><?php if (!$a['leida']): ?><a href="?leer=<?= $a['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-check"></i></a><?php endif; ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../views/layouts/footer.php'; ?>
