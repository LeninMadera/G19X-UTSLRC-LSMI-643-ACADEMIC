<?php
/**
 * Punto de entrada del proyecto.
 * En la Etapa 1 este archivo redigira al login si no hay sesion
 * o al dashboard si ya hay una. Por ahora solo verifica que la
 * configuracion basica este lista.
 */
require_once __DIR__ . '/config/app.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gobierno de Datos IA</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <main class="landing">
        <h1>Plataforma de Gobierno de Datos con IA</h1>
        <p>Instalacion en curso: ejecuta <code>sql/database.sql</code> y <code>sql/seed.sql</code> en MySQL.</p>
        <p class="muted">Verifica también tu conexion en <code>.env</code>.</p>
    </main>
</body>
</html>