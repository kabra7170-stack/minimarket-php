<?php
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$nombre  = trim($input['nombre']  ?? '');
$correo  = trim($input['correo']  ?? '');
$asunto  = trim($input['asunto']  ?? '');
$mensaje = trim($input['mensaje'] ?? '');

if (!$nombre || !$mensaje) {
    http_response_code(400);
    echo json_encode(['error' => 'Nombre y mensaje son requeridos']);
    exit();
}

$db = getDB();
$stmt = $db->prepare("INSERT INTO mensajes (nombre, correo, asunto, mensaje) VALUES (?, ?, ?, ?)");
$stmt->bind_param('ssss', $nombre, $correo, $asunto, $mensaje);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al guardar el mensaje']);
}
$stmt->close();
