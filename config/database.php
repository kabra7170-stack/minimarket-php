<?php
function getDB() {
    $host = getenv('DB_HOST') ?: 'mysql.railway.internal';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASSWORD') ?: 'wMrTHmynRYxmWmnFuxndQpOQKSEQdMOc';
    $db   = getenv('DB_NAME') ?: 'railway';
    $port = getenv('DB_PORT') ?: 3306;

    $conn = new mysqli($host, $user, $pass, $db, $port);
    if ($conn->connect_error) {
        die('Error de conexion: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}
