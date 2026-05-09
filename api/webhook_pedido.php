<?php
require_once '../config/database.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$pedido_id = (int)($input['pedido_id'] ?? 0);

if (!$pedido_id) {
    http_response_code(400);
    echo json_encode(['error' => 'pedido_id requerido']);
    exit();
}

$db = getDB();
$r  = $db->query("
    SELECT p.*, c.nombre AS cliente_nombre, c.email AS cliente_email, c.telefono AS cliente_telefono
    FROM pedidos p
    LEFT JOIN clientes c ON p.cliente_id = c.id
    WHERE p.id = $pedido_id
");

if (!$r || $r->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Pedido no encontrado']);
    exit();
}

$pedido = $r->fetch_assoc();

echo json_encode([
    'pedido_id'   => $pedido['id'],
    'num_pedido'  => $pedido['num_pedido'],
    'total'       => $pedido['total'],
    'tipo'        => $pedido['tipo'],
    'estado'      => $pedido['estado'],
    'cliente'     => $pedido['cliente_nombre'],
    'email'       => $pedido['cliente_email'],
    'telefono'    => $pedido['cliente_telefono'],
    'fecha'       => $pedido['fecha'],
]);
