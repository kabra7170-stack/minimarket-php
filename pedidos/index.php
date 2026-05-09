<?php
require_once '../config/database.php';
require_once '../config/session.php';
requireLogin();

$db = getDB();
$pageTitle = 'Pedidos';

// Filtro tipo
$tipo = $_GET['tipo'] ?? '';
$where = $tipo ? "WHERE p.tipo = '$tipo'" : "";

$pedidos = $db->query("
    SELECT p.*, c.nombre AS cliente, u.nombre AS cajero
    FROM pedidos p
    LEFT JOIN clientes c ON p.cliente_id = c.id
    JOIN usuarios u ON p.usuario_id = u.id
    $where
    ORDER BY p.fecha DESC
    LIMIT 100
");

include '../views/layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="d-flex gap-2">
    <a href="index.php" class="btn btn-sm <?= !$tipo ? 'btn-primary' : 'btn-outline-primary' ?>">Todos</a>
    <a href="index.php?tipo=mostrador" class="btn btn-sm <?= $tipo=='mostrador' ? 'btn-primary' : 'btn-outline-primary' ?>"><i class="bi bi-shop me-1"></i>Mostrador</a>
    <a href="index.php?tipo=domicilio" class="btn btn-sm <?= $tipo=='domicilio' ? 'btn-primary' : 'btn-outline-primary' ?>"><i class="bi bi-bicycle me-1"></i>Domicilio</a>
  </div>
  <a href="nuevo.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nuevo Pedido</a>
</div>

<div class="card">
  <div class="card-header"><i class="bi bi-list-check me-2"></i>Lista de Pedidos</div>
  <div class="card-body p-0">
    <table class="table table-hover table-sm mb-0">
      <thead>
        <tr>
          <th>#Pedido</th><th>Cliente</th><th>Tipo</th><th>Estado</th>
          <th>Total</th><th>Fecha</th><th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($p = $pedidos->fetch_assoc()): ?>
        <tr>
          <td><strong><?= htmlspecialchars($p['num_pedido']) ?></strong></td>
          <td><?= htmlspecialchars($p['cliente'] ?? 'Cliente General') ?></td>
          <td>
            <?php if ($p['tipo'] === 'domicilio'): ?>
              <span class="badge bg-info text-dark"><i class="bi bi-bicycle me-1"></i>Domicilio</span>
            <?php else: ?>
              <span class="badge bg-secondary"><i class="bi bi-shop me-1"></i>Mostrador</span>
            <?php endif; ?>
          </td>
          <td>
            <?php
              $badges = [
                'pendiente'   => 'bg-warning text-dark',
                'en_proceso'  => 'bg-primary',
                'listo'       => 'bg-success',
                'entregado'   => 'bg-secondary',
                'cancelado'   => 'bg-danger',
              ];
              $badge = $badges[$p['estado']] ?? 'bg-secondary';
            ?>
            <span class="badge <?= $badge ?>"><?= ucfirst(str_replace('_',' ',$p['estado'])) ?></span>
          </td>
          <td>RD$ <?= number_format($p['total'], 2) ?></td>
          <td><?= date('d/m/Y H:i', strtotime($p['fecha'])) ?></td>
          <td>
            <a href="ver.php?id=<?= $p['id'] ?>" class="btn btn-xs btn-outline-primary btn-sm py-0 px-2"><i class="bi bi-eye"></i></a>
            <?php if (!in_array($p['estado'], ['entregado','cancelado'])): ?>
            <a href="estado.php?id=<?= $p['id'] ?>" class="btn btn-xs btn-outline-success btn-sm py-0 px-2"><i class="bi bi-arrow-right-circle"></i></a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../views/layouts/footer.php'; ?>
