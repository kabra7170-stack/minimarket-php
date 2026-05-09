<?php
require_once '../config/database.php';
require_once '../config/session.php';

if (!isLoggedIn()) {
    http_response_code(401);
    exit();
}

header('Content-Type: application/json');

$db = getDB();
$db->query("UPDATE mensajes SET leido=1 WHERE leido=0");

echo json_encode(['success' => true]);
