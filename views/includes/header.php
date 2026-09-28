<?php
/*
 * Cabecera base de las vistas autenticadas.
 * Incluye el control de sesion y la barra lateral de navegacion.
 * Se espera $pageTitle antes de incluir este archivo.
 */
require_once __DIR__ . '/../../includes/functions.php';

if (currentUser() === null) {
    header('Location: ' . APP_URL . '/views/login.php');
    exit;
}
$current = currentUser();
$titleText = $pageTitle ?? 'Gobierno de Datos';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titleText) ?> | Gobierno de Datos IA</title>
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/style.css">
    <script>
        window.APP_URL = "<?= e(APP_URL) ?>";
        window.APP_USER = { id: <?= (int)$current['id'] ?>, rol: "<?= e($current['rol']) ?>" };
    </script>
</head>
<body>
<div class="app">
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="logo-badge">IA</div>
        <span>Gobierno de Datos</span>
    </div>
    <nav class="sidebar-nav">
        <a href="<?= e(APP_URL) ?>/views/dashboard.php">Dashboard</a>
        <a href="<?= e(APP_URL) ?>/views/datasets.php">Catalogo de datos</a>
        <a href="<?= e(APP_URL) ?>/views/quality.php">Calidad de datos</a>
        <a href="<?= e(APP_URL) ?>/views/policies.php">Politicas</a>
        <a href="<?= e(APP_URL) ?>/views/compliance.php">Cumplimiento</a>
        <a href="<?= e(APP_URL) ?>/views/monitoring.php">Monitoreo</a>
        <a href="<?= e(APP_URL) ?>/views/lineage.php">Linaje de datos</a>
        <a href="<?= e(APP_URL) ?>/views/ai.php">Asistente IA</a>
        <a href="<?= e(APP_URL) ?>/views/reports.php">Reportes</a>
        <?php if ($current['rol'] === 'admin'): ?>
        <a href="<?= e(APP_URL) ?>/views/users.php">Usuarios</a>
        <?php endif; ?>
    </nav>
</aside>
<main class="content">
    <header class="topbar">
        <div class="topbar-title"><?= e($titleText) ?></div>
        <div class="topbar-user">
            <span class="user-name"><?= e($current['name']) ?></span>
            <span class="badge-role"><?= e($current['rol']) ?></span>
            <button id="btn-logout" class="btn btn-ghost btn-sm">Cerrar sesion</button>
        </div>
    </header>