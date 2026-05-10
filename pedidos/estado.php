<?php
require_once '../config/database.php';
require_once '../config/session.php';
requireLogin();

$db = getDB();
$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $estado = $_POST['estado'] ?? '';
    $estados_validos = ['pendiente','en_proceso','listo','entregado','cancelado'];
    if (in_array($estado, $estados_validos)) {
        $stmt = $db->prepare("UPDATE pedidos SET estado=? WHERE id=?");
        $stmt->bind_param('si', $estado, $id);
        $stmt->execute();
        $stmt->close();
    }
    if (isset($_POST['estado_entrega'])) {
        $ee = $_POST['estado_entrega'];
        $ee_validos = ['pendiente','en_camino','entregado','fallido'];
        if (in_array($ee, $ee_validos)) {
            $fecha = ($ee === 'entregado') ? date('Y-m-d H:i:s') : null;
            $stmt2 = $db->prepare("UPDATE pedidos_domicilio SET estado_entrega=?, fecha_entrega=? WHERE pedido_id=?");
            $stmt2->bind_param('ssi', $ee, $fecha, $id);
            $stmt2->execute();
            $stmt2->close();
        }
    }
}

header("Location: ver.php?id=$id");
exit();
