<?php
require_once '../config/database.php';
require_once '../config/session.php';

if (!isLoggedIn()) { http_response_code(401); exit(); }

$db = getDB();

// Ventas 7 días
$data7 = [];
for ($i = 6; $i >= 0; $i--) $data7[date('d/m', strtotime("-$i days"))] = 0;
$r7 = $db->query("SELECT DATE(fecha) AS dia, IFNULL(SUM(total),0) AS total FROM ventas WHERE fecha>=DATE_SUB(CURDATE(),INTERVAL 6 DAY) AND estado='completada' GROUP BY DATE(fecha)");
while ($r = $r7->fetch_assoc()) { $k = date('d/m', strtotime($r['dia'])); if(isset($data7[$k])) $data7[$k]=(float)$r['total']; }

// Top 5
$labelsV = []; $dataV = [];
$mv = $db->query("SELECT p.nombre,SUM(dv.cantidad) AS qty FROM detalle_ventas dv JOIN productos p ON dv.producto_id=p.id JOIN ventas v ON dv.venta_id=v.id WHERE v.estado='completada' GROUP BY p.id ORDER BY qty DESC LIMIT 5");
while ($r = $mv->fetch_assoc()) {
    $labelsV[] = strlen($r['nombre'])>16 ? substr($r['nombre'],0,14).'…' : $r['nombre'];
    $dataV[]   = (int)$r['qty'];
}
if (empty($dataV)) { $labelsV=['Sin ventas']; $dataV=[1]; }

// Métodos
$metodos = [0,0,0];
$rm = $db->query("SELECT metodo_pago,COUNT(*) AS cnt FROM ventas WHERE DATE(fecha)>=DATE_SUB(CURDATE(),INTERVAL 30 DAY) AND estado='completada' GROUP BY metodo_pago");
$map = ['efectivo'=>0,'tarjeta'=>1,'transferencia'=>2];
while ($r = $rm->fetch_assoc()) if(isset($map[$r['metodo_pago']])) $metodos[$map[$r['metodo_pago']]]=(int)$r['cnt'];

header('Content-Type: application/json');
echo json_encode([
    'labels7' => array_keys($data7),
    'data7'   => array_values($data7),
    'labelsV' => $labelsV,
    'dataV'   => $dataV,
    'metodos' => $metodos,
]);
