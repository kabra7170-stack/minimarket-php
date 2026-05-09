<?php
require_once '../config/database.php';

header('Content-Type: application/json');

$db = getDB();

$result = $db->query("
    SELECT p.id, p.nombre, p.stock_actual, p.stock_minimo, c.nombre AS categoria
    FROM productos p
    JOIN categorias c ON p.categoria_id = c.id
    WHERE p.stock_actual <= p.stock_minimo AND p.activo = 1
    ORDER BY p.stock_actual ASC
");

$productos = [];
while ($r = $result->fetch_assoc()) {
    $productos[] = $r;
}

echo json_encode([
    'total'     => count($productos),
    'productos' => $productos
]);
