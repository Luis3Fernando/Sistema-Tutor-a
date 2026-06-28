<?php
require_once __DIR__ . '/../../app/helpers/Auth.php';
$user = Auth::user();
$routeActual = $_GET['route'] ?? 'login';
$nombreCompleto = trim((string)(($user['nombre'] ?? '') ?: (($user['nombres'] ?? '') . ' ' . ($user['apellidos'] ?? ''))));
if ($nombreCompleto === '') {
    $nombreCompleto = 'Usuario';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Tutorías - UNAMBA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="topbar app-topbar">
    <div class="topbar-left">
    
        <div>
            <strong class="topbar-title">Sistema de Tutorías - UNAMBA</strong>
           
        </div>
    </div>
    <nav class="topbar-nav">
        <?php if ($user): ?>
            <span class="topbar-user"><?= htmlspecialchars($nombreCompleto) ?> (<?= htmlspecialchars((string)($user['rol'] ?? '')) ?>)</span>
            <a class="topbar-logout" href="index.php?route=logout">Cerrar sesión</a>
        <?php endif; ?>
    </nav>

</header>
<div class="container-fluid app-shell">
