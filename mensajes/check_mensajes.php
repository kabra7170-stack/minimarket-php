<?php
require_once '../config/database.php';
require_once '../config/session.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

header('Content-Type: application/json');

$db = getDB();
$ultimo_id = (int)($_GET['ultimo_id'] ?? 0);

$nuevos = [];
$result = $db->query("SELECT id, nombre, correo, asunto, LEFT(mensaje, 100) AS mensaje, fecha FROM mensajes WHERE id > $ultimo_id ORDER BY id ASC");
while ($r = $result->fetch_assoc()) {
    $nuevos[] = $r;
}

$ultimo = $db->query("SELECT MAX(id) AS id FROM mensajes")->fetch_assoc();

echo json_encode([
    'nuevos'    => $nuevos,
    'ultimo_id' => $ultimo['id'] ?? $ultimo_id,
    'total_no_leidos' => (int)$db->query("SELECT COUNT(*) AS t FROM mensajes WHERE leido=0")->fetch_assoc()['t']
]);
