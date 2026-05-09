<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// ── EMPLEADOS ──────────────────────────────────────────
function isLoggedIn() {
    return isset($_SESSION['usuario_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /minimarket/login.php');
        exit();
    }
}

function requireRole($roles) {
    requireLogin();
    if (!in_array($_SESSION['rol'], (array)$roles)) {
        header('Location: /minimarket/acceso_denegado.php');
        exit();
    }
}

function currentUser() {
    return [
        'id'     => $_SESSION['usuario_id'] ?? null,
        'nombre' => $_SESSION['nombre']     ?? '',
        'rol'    => $_SESSION['rol']        ?? '',
        'email'  => $_SESSION['email']      ?? ''
    ];
}

// ── CLIENTES ───────────────────────────────────────────
function isClienteLoggedIn() {
    return isset($_SESSION['cliente_id']);
}

function requireCliente() {
    if (!isClienteLoggedIn()) {
        header('Location: /minimarket/login.php?modo=cliente');
        exit();
    }
}

function currentCliente() {
    return [
        'id'     => $_SESSION['cliente_id']     ?? null,
        'nombre' => $_SESSION['cliente_nombre'] ?? '',
        'email'  => $_SESSION['cliente_email']  ?? ''
    ];
}