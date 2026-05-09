<?php
require_once '../config/database.php';
require_once '../config/session.php';
requireLogin();

$db = getDB();
$pageTitle = 'Reporte de Ventas';

$desde  = $_GET['desde']  ?? date('Y-m-01');
$hasta  = $_GET['hasta']  ?? date('Y-m-d');
$metodo = $_GET['metodo'] ?? '';

$where = "WHERE v.estado='completada' AND DATE(v.fecha) BETWEEN '$desde' AND '$hasta'";
if ($metodo) $where .= " AND v.metodo_pago='$metodo'";

$resumen = $db->query("SELECT COUNT(*) AS total_ventas, IFNULL(SUM(v.total),0) AS ingresos FROM ventas v $where")->fetch_assoc();
$ventas  = $db->query("SELECT v.*, u.nombre AS cajero, c.nombre AS cliente FROM ventas v JOIN usuarios u ON v.usuario_id=u.id LEFT JOIN clientes c ON v.cliente_id=c.id $where ORDER BY v.fecha DESC");
$por_dia = $db->query("SELECT DATE(v.fecha) AS dia, COUNT(*) AS cant, SUM(v.total) AS total FROM ventas v $where GROUP BY DATE(v.fecha) ORDER BY dia");

// Rellenar todos los días del rango aunque no haya ventas
$diasMap = [];
$d = new DateTime($desde);
$fin = new DateTime($hasta);
while ($d <= $fin) {
    $diasMap[$d->format('d/m')] = 0;
    $d->modify('+1 day');
}
while ($r = $por_dia->fetch_assoc()) {
    $k = date('d/m', strtotime($r['dia']));
    if (isset($diasMap[$k])) $diasMap[$k] = (float)$r['total'];
}
$diasLabels = array_keys($diasMap);
$diasData   = array_values($diasMap);

include '../views/layouts/header.php';
?>

<!-- FILTROS -->
<div class="card mb-3">
  <div class="card-body py-3">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small fw-semibold">Desde</label>
        <input type="date" name="desde" class="form-control form-control-sm" value="<?= $desde ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-semibold">Hasta</label>
        <input type="date" name="hasta" class="form-control form-control-sm" value="<?= $hasta ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-semibold">Método de pago</label>
        <select name="metodo" class="form-select form-select-sm">
          <option value="">Todos</option>
          <option value="efectivo"      <?= $metodo=='efectivo'     ?'selected':''?>>Efectivo</option>
          <option value="tarjeta"       <?= $metodo=='tarjeta'      ?'selected':''?>>Tarjeta</option>
          <option value="transferencia" <?= $metodo=='transferencia'?'selected':''?>>Transferencia</option>
        </select>
      </div>
      <div class="col-md-3">
        <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i>Filtrar</button>
      </div>
    </form>
  </div>
</div>

<!-- RESUMEN -->
<div class="row g-3 mb-3">
  <div class="col-6">
    <div class="card text-white" style="background:#1F4E79;">
      <div class="card-body py-3 d-flex justify-content-between align-items-center">
        <div><div class="small opacity-75">Total ventas</div><div class="fs-3 fw-bold"><?= $resumen['total_ventas'] ?></div></div>
        <i class="bi bi-receipt fs-2 opacity-40"></i>
      </div>
    </div>
  </div>
  <div class="col-6">
    <div class="card text-white" style="background:#0F6E56;">
      <div class="card-body py-3 d-flex justify-content-between align-items-center">
        <div><div class="small opacity-75">Ingresos totales</div><div class="fs-4 fw-bold">RD$ <?= number_format($resumen['ingresos'],2) ?></div></div>
        <i class="bi bi-cash-stack fs-2 opacity-40"></i>
      </div>
    </div>
  </div>
</div>

<!-- GRÁFICO -->
<div class="card mb-3">
  <div class="card-header py-2"><i class="bi bi-bar-chart me-2"></i>Ventas por día</div>
  <div class="card-body py-2" style="height:200px; position:relative;">
    <canvas id="grafDias"></canvas>
  </div>
</div>

<!-- TABLA -->
<div class="card">
  <div class="card-header py-2 d-flex justify-content-between align-items-center">
    <span><i class="bi bi-table me-2"></i>Detalle de Ventas</span>
    
    <button onclick="window.print()" class="btn btn-sm btn-outline-light py-0"><i class="bi bi-printer me-1"></i>Imprimir</button>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover table-sm mb-0">
        <thead><tr><th>Factura</th><th>Cajero</th><th>Cliente</th><th>Total</th><th>Método</th><th>Fecha</th><th>Estado</th></tr></thead>
        <tbody>
          <?php while ($v = $ventas->fetch_assoc()): ?>
          <tr>
            <td><strong class="small"><?= $v['num_factura'] ?></strong></td>
            <td class="small"><?= htmlspecialchars($v['cajero']) ?></td>
            <td class="small"><?= htmlspecialchars($v['cliente'] ?? 'General') ?></td>
            <td class="small">RD$ <?= number_format($v['total'],2) ?></td>
            <td><span class="badge bg-secondary small"><?= ucfirst($v['metodo_pago']) ?></span></td>
            <td class="small"><?= date('d/m/Y H:i', strtotime($v['fecha'])) ?></td>
            <td><span class="badge bg-<?= $v['estado']=='completada'?'success':'danger' ?>"><?= ucfirst($v['estado']) ?></span></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const ctx = document.getElementById('grafDias');
  if (!ctx) return;
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?= json_encode($diasLabels) ?>,
      datasets: [{
        label: 'Ingresos RD$',
        data: <?= json_encode($diasData) ?>,
        backgroundColor: '#2E75B6',
        borderRadius: 5,
        borderSkipped: false
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: ctx => 'RD$ ' + ctx.parsed.y.toLocaleString('es-DO', {minimumFractionDigits:2})
          }
        }
      },
      scales: {
        y: { beginAtZero: true, ticks: { font: { size: 11 }, callback: v => 'RD$'+v.toLocaleString() } },
        x: { ticks: { font: { size: 11 } } }
      }
    }
  });
});
</script>

<?php include '../views/layouts/footer.php'; ?>
