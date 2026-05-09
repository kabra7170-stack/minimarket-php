<?php
require_once 'config/database.php';
require_once 'config/session.php';
requireLogin();

$db = getDB();
$pageTitle = 'Dashboard';

$ventasHoy = $db->query("SELECT COUNT(*) AS total, IFNULL(SUM(total),0) AS ingresos FROM ventas WHERE DATE(fecha)=CURDATE() AND estado='completada'")->fetch_assoc();
$stockBajo = $db->query("SELECT COUNT(*) AS total FROM productos WHERE stock_actual<=stock_minimo AND activo=1")->fetch_assoc();
$porVencer = $db->query("SELECT COUNT(*) AS total FROM productos WHERE fecha_vencimiento IS NOT NULL AND fecha_vencimiento<=DATE_ADD(CURDATE(),INTERVAL 7 DAY) AND fecha_vencimiento>=CURDATE() AND activo=1")->fetch_assoc();
$pedHoy    = $db->query("SELECT COUNT(*) AS total FROM pedidos WHERE DATE(fecha)=CURDATE()")->fetch_assoc();

$prods_bajo   = $db->query("SELECT p.nombre,p.stock_actual,p.stock_minimo FROM productos p WHERE p.stock_actual<=p.stock_minimo AND p.activo=1 LIMIT 6");
$prods_vencer = $db->query("SELECT nombre,fecha_vencimiento,DATEDIFF(fecha_vencimiento,CURDATE()) AS dias FROM productos WHERE fecha_vencimiento IS NOT NULL AND fecha_vencimiento<=DATE_ADD(CURDATE(),INTERVAL 7 DAY) AND fecha_vencimiento>=CURDATE() AND activo=1 ORDER BY dias LIMIT 6");

// Ventas últimos 7 días — rellenar todos los días
$data7 = [];
for ($i = 6; $i >= 0; $i--) $data7[date('d/m', strtotime("-$i days"))] = 0;
$r7 = $db->query("SELECT DATE(fecha) AS dia, IFNULL(SUM(total),0) AS total FROM ventas WHERE fecha>=DATE_SUB(CURDATE(),INTERVAL 6 DAY) AND estado='completada' GROUP BY DATE(fecha)");
while ($r = $r7->fetch_assoc()) { $k = date('d/m', strtotime($r['dia'])); if(isset($data7[$k])) $data7[$k]=(float)$r['total']; }

// Top 5 vendidos
$labelsV = []; $dataV = [];
$mv = $db->query("SELECT p.nombre,SUM(dv.cantidad) AS qty FROM detalle_ventas dv JOIN productos p ON dv.producto_id=p.id JOIN ventas v ON dv.venta_id=v.id WHERE v.estado='completada' GROUP BY p.id ORDER BY qty DESC LIMIT 5");
while ($r = $mv->fetch_assoc()) {
    $labelsV[] = strlen($r['nombre'])>16 ? substr($r['nombre'],0,14).'…' : $r['nombre'];
    $dataV[]   = (int)$r['qty'];
}
if (empty($dataV)) { $labelsV=['Sin ventas']; $dataV=[1]; }

// Ventas por método de pago
$metodos = ['efectivo'=>0,'tarjeta'=>0,'transferencia'=>0];
$rm = $db->query("SELECT metodo_pago, COUNT(*) AS cnt FROM ventas WHERE DATE(fecha)>=DATE_SUB(CURDATE(),INTERVAL 30 DAY) AND estado='completada' GROUP BY metodo_pago");
while ($r = $rm->fetch_assoc()) $metodos[$r['metodo_pago']] = (int)$r['cnt'];

include 'views/layouts/header.php';
?>

<!-- TARJETAS -->
<div class="row g-3 mb-3">
  <div class="col-6 col-md-3">
    <div class="card text-white" style="background:#1F4E79;">
      <div class="card-body py-3 d-flex justify-content-between align-items-center">
        <div><div class="small opacity-75">Ventas hoy</div><div class="fs-4 fw-bold"><?= $ventasHoy['total'] ?></div></div>
        <i class="bi bi-receipt fs-2 opacity-40"></i>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card text-white" style="background:#0F6E56;">
      <div class="card-body py-3 d-flex justify-content-between align-items-center">
        <div><div class="small opacity-75">Ingresos hoy</div><div class="fs-5 fw-bold">RD$ <?= number_format($ventasHoy['ingresos'],0) ?></div></div>
        <i class="bi bi-cash-stack fs-2 opacity-40"></i>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card text-white" style="background:<?= $stockBajo['total']>0?'#C00000':'#555' ?>;">
      <div class="card-body py-3 d-flex justify-content-between align-items-center">
        <div><div class="small opacity-75">Stock bajo</div><div class="fs-4 fw-bold"><?= $stockBajo['total'] ?></div></div>
        <i class="bi bi-exclamation-triangle fs-2 opacity-40"></i>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card text-white" style="background:#5B4FCF;">
      <div class="card-body py-3 d-flex justify-content-between align-items-center">
        <div><div class="small opacity-75">Pedidos hoy</div><div class="fs-4 fw-bold"><?= $pedHoy['total'] ?></div></div>
        <i class="bi bi-bag-check fs-2 opacity-40"></i>
      </div>
    </div>
  </div>
</div>

<!-- GRÁFICOS -->
<div class="row g-3 mb-3">
  <div class="col-md-5">
    <div class="card h-100">
      <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <span><i class="bi bi-bar-chart me-2"></i>Ventas 7 días</span>
        <span class="badge bg-light text-dark" style="font-size:10px;" id="lblActualizado"></span>
      </div>
      <div class="card-body py-2" style="height:190px;position:relative;">
        <canvas id="grafVentas"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header py-2"><i class="bi bi-pie-chart me-2"></i>Más vendidos</div>
      <div class="card-body py-2 d-flex align-items-center justify-content-center" style="height:190px;position:relative;">
        <canvas id="grafTop"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-header py-2"><i class="bi bi-credit-card me-2"></i>Métodos pago</div>
      <div class="card-body py-2 d-flex align-items-center justify-content-center" style="height:190px;position:relative;">
        <canvas id="grafMetodos"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- ALERTAS -->
<div class="row g-3">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header py-2 text-white" style="background:#C00000;"><i class="bi bi-exclamation-triangle me-2"></i>Stock Bajo</div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead><tr><th>Producto</th><th>Stock</th><th>Mínimo</th></tr></thead>
          <tbody>
            <?php $cnt=0; while ($p=$prods_bajo->fetch_assoc()): $cnt++; ?>
            <tr>
              <td class="small"><?= htmlspecialchars($p['nombre']) ?></td>
              <td><span class="badge bg-danger"><?= $p['stock_actual'] ?></span></td>
              <td class="small"><?= $p['stock_minimo'] ?></td>
            </tr>
            <?php endwhile; ?>
            <?php if ($cnt===0): ?><tr><td colspan="3" class="text-center text-muted py-2 small">✅ Sin alertas</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <div class="card-header py-2 text-white" style="background:#BF5800;"><i class="bi bi-calendar-x me-2"></i>Por Vencer (7 días)</div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead><tr><th>Producto</th><th>Vence</th><th>Días</th></tr></thead>
          <tbody>
            <?php $cnt2=0; while ($p=$prods_vencer->fetch_assoc()): $cnt2++; ?>
            <tr>
              <td class="small"><?= htmlspecialchars($p['nombre']) ?></td>
              <td class="small"><?= date('d/m/Y', strtotime($p['fecha_vencimiento'])) ?></td>
              <td><span class="badge bg-warning text-dark"><?= $p['dias'] ?>d</span></td>
            </tr>
            <?php endwhile; ?>
            <?php if ($cnt2===0): ?><tr><td colspan="3" class="text-center text-muted py-2 small">✅ Sin productos por vencer</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
// ── DATOS INICIALES ────────────────────────────────────
const initLabels7  = <?= json_encode(array_keys($data7)) ?>;
const initData7    = <?= json_encode(array_values($data7)) ?>;
const initLabelsV  = <?= json_encode($labelsV) ?>;
const initDataV    = <?= json_encode($dataV) ?>;
const initMetodos  = <?= json_encode(array_values($metodos)) ?>;

let chartVentas, chartTop, chartMetodos;

document.addEventListener('DOMContentLoaded', () => {
    // Gráfico 1 — Barras ventas 7 días
    chartVentas = new Chart(document.getElementById('grafVentas'), {
        type: 'bar',
        data: {
            labels: initLabels7,
            datasets: [{ label:'RD$', data:initData7, backgroundColor:'#2E75B6', borderRadius:5, borderSkipped:false }]
        },
        options: {
            responsive:true, maintainAspectRatio:false,
            plugins: { legend:{display:false}, tooltip:{ callbacks:{ label: c=>'RD$ '+c.parsed.y.toLocaleString('es-DO',{minimumFractionDigits:2}) } } },
            scales: { y:{beginAtZero:true, ticks:{font:{size:10}, callback:v=>'$'+v.toLocaleString()}}, x:{ticks:{font:{size:10}}} }
        }
    });

    // Gráfico 2 — Dona top vendidos
    chartTop = new Chart(document.getElementById('grafTop'), {
        type: 'doughnut',
        data: {
            labels: initLabelsV,
            datasets: [{ data:initDataV, backgroundColor:['#1F4E79','#2E75B6','#0F6E56','#BF5800','#7030A0'], borderWidth:2, borderColor:'#fff' }]
        },
        options: {
            responsive:true, maintainAspectRatio:false,
            plugins: { legend:{ position:'bottom', labels:{ font:{size:10}, boxWidth:12 } } }
        }
    });

    // Gráfico 3 — Dona métodos de pago
    chartMetodos = new Chart(document.getElementById('grafMetodos'), {
        type: 'doughnut',
        data: {
            labels: ['Efectivo','Tarjeta','Transferencia'],
            datasets: [{ data:initMetodos, backgroundColor:['#0F6E56','#1F4E79','#BF5800'], borderWidth:2, borderColor:'#fff' }]
        },
        options: {
            responsive:true, maintainAspectRatio:false,
            plugins: { legend:{ position:'bottom', labels:{ font:{size:10}, boxWidth:12 } } }
        }
    });

    actualizarHora();
    // Auto-actualizar cada 60 segundos
    setInterval(autoRefresh, 60000);
});

function actualizarHora() {
    const el = document.getElementById('lblActualizado');
    if (el) el.textContent = 'Act. ' + new Date().toLocaleTimeString('es-DO',{hour:'2-digit',minute:'2-digit'});
}

function autoRefresh() {
    fetch('/minimarket/api/dashboard_data.php')
        .then(r => r.json())
        .then(d => {
            if (!d) return;
            // Actualizar gráfico ventas
            chartVentas.data.labels   = d.labels7;
            chartVentas.data.datasets[0].data = d.data7;
            chartVentas.update('none');
            // Actualizar top vendidos
            chartTop.data.labels = d.labelsV;
            chartTop.data.datasets[0].data = d.dataV;
            chartTop.update('none');
            // Actualizar métodos
            chartMetodos.data.datasets[0].data = d.metodos;
            chartMetodos.update('none');
            actualizarHora();
        })
        .catch(() => {}); // silencioso si falla
}
</script>

<?php include 'views/layouts/footer.php'; ?>