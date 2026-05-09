<?php
function getDB() {
    $conn = new mysqli('localhost', 'Deurys', 'deurys092226..', 'minimarket_g2', 3306);
    if ($conn->connect_error) {
        die('Error de conexion: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}