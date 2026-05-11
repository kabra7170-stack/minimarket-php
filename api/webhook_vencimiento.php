<?php
require_once '../config/database.php';

header('Content-Type: application/json');

$db = getDB();

$result = $db->query("
    SELECT p.id, p.nombre, p.fecha_vencimiento,
           DATEDIFF(p.fecha_vencimiento, CURDATE()) AS dias_restantes,
           c.nombre AS categoria
    FROM productos p
    JOIN categorias c ON p.categoria_id = c.id
    WHERE p.fecha_vencimiento IS NOT NULL
      AND p.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
      AND p.fecha_vencimiento >= CURDATE()
      AND p.activo = 1
    ORDER BY p.fecha_vencimiento ASC
");

$productos = [];
while ($r = $result->fetch_assoc()) {
    $productos[] = $r;
}

echo json_encode([
    'total'     => count($productos),
    'productos' => $productos
]);
