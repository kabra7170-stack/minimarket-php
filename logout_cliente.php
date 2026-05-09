<?php
session_start();
// Limpiar solo sesión de cliente
unset($_SESSION['cliente_id'], $_SESSION['cliente_nombre'], $_SESSION['cliente_email'], $_SESSION['carrito']);
header('Location: /login.php?modo=cliente');
exit();
