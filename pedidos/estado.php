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

        // ── NOTIFICAR A N8N ──
        $r = $db->query("
            SELECT p.*, c.nombre AS cliente_nombre, c.email AS cliente_email, c.telefono AS cliente_telefono
            FROM pedidos p
            LEFT JOIN clientes c ON p.cliente_id = c.id
            WHERE p.id = $id
        ");
        if ($r && $r->num_rows > 0) {
            $pedido = $r->fetch_assoc();
            $payload = json_encode([
                'pedido_id'  => $pedido['id'],
                'num_pedido' => $pedido['num_pedido'],
                'total'      => $pedido['total'],
                'tipo'       => $pedido['tipo'],
                'estado'     => $estado,
                'cliente'    => $pedido['cliente_nombre'],
                'email'      => $pedido['cliente_email'],
                'telefono'   => $pedido['cliente_telefono'],
                'fecha'      => $pedido['fecha'],
            ]);
            $ch = curl_init('https://n8n-production-91d2.up.railway.app/webhook/pedido_nuevo');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_exec($ch);
            curl_close($ch);
        }
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