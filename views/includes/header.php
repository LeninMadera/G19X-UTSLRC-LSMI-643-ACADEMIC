<?php
/*
 * Cabecera base de las vistas autenticadas.
 * Incluye el control de sesion, la barra lateral de navegacion
 * con estado activo y el sprite de iconos en linea.
 * Se espera $pageTitle antes de incluir este archivo.
 */
require_once __DIR__ . '/../../includes/functions.php';

if (currentUser() === null) {
    header('Location: ' . APP_URL . '/views/login.php');
    exit;
}
$current = currentUser();
$titleText = $pageTitle ?? 'Gobierno de Datos';

/* Vista actual: se usa para marcar el enlace activo del menu. */
$currentPage = basename($_SERVER['PHP_SELF']);

/*
 * Estructura del menu lateral. Solo presenta los mismos destinos,
 * textos y permisos que antes; no altera la navegacion.
 * 'match' agrupa las vistas que pertenecen a la misma seccion.
 */
$navItems = [
    ['group' => 'Principal', 'label' => 'Dashboard', 'icon' => 'i-grid',
     'href' => 'dashboard.php', 'match' => ['dashboard.php']],

    ['group' => 'Gobierno del dato', 'label' => 'Catalogo de datos', 'icon' => 'i-db',
     'href' => 'datasets.php', 'match' => ['datasets.php', 'dataset_detail.php']],
    ['label' => 'Calidad de datos', 'icon' => 'i-gauge',
     'href' => 'quality.php', 'match' => ['quality.php']],
    ['label' => 'Politicas', 'icon' => 'i-file',
     'href' => 'policies.php', 'match' => ['policies.php']],
    ['label' => 'Cumplimiento', 'icon' => 'i-shield',
     'href' => 'compliance.php', 'match' => ['compliance.php']],

    ['group' => 'Operacion', 'label' => 'Monitoreo', 'icon' => 'i-activity',
     'href' => 'monitoring.php', 'match' => ['monitoring.php']],
    ['label' => 'Fuentes externas', 'icon' => 'i-globe',
     'href' => 'integrations.php', 'match' => ['integrations.php']],

    ['group' => 'Analisis', 'label' => 'Linaje de datos', 'icon' => 'i-branch',
     'href' => 'lineage.php', 'match' => ['lineage.php']],
    ['label' => 'Asistente IA', 'icon' => 'i-sparkles',
     'href' => 'ai.php', 'match' => ['ai.php']],
    ['label' => 'Reportes', 'icon' => 'i-chart',
     'href' => 'reports.php', 'match' => ['reports.php']],

    ['group' => 'Administracion', 'label' => 'Usuarios', 'icon' => 'i-users',
     'href' => 'users.php', 'match' => ['users.php'], 'adminOnly' => true],
];

/* Iniciales del usuario para el avatar. */
$nameParts = preg_split('/\s+/u', trim((string)$current['name']));
$initials = mb_strtoupper(
    mb_substr($nameParts[0] ?? '', 0, 1) . mb_substr($nameParts[1] ?? '', 0, 1)
);
if ($initials === '') {
    $initials = 'U';
}
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
        window.CSRF_TOKEN = "<?= e(csrfToken()) ?>";
    </script>
</head>
<body>

<!-- Sprite de iconos: sin dependencias externas, solo SVG en linea -->
<svg class="ico-sprite" aria-hidden="true" focusable="false"><defs>
    <symbol id="i-grid" viewBox="0 0 24 24">
        <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/>
        <rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
    </symbol>
    <symbol id="i-db" viewBox="0 0 24 24">
        <ellipse cx="12" cy="6" rx="8" ry="3"/>
        <path d="M4 6v6c0 1.66 3.58 3 8 3s8-1.34 8-3V6"/><path d="M4 12v6c0 1.66 3.58 3 8 3s8-1.34 8-3v-6"/>
    </symbol>
    <symbol id="i-gauge" viewBox="0 0 24 24">
        <path d="M4 18a8 8 0 1 1 16 0"/><path d="M12 18l3.5-7"/>
    </symbol>
    <symbol id="i-file" viewBox="0 0 24 24">
        <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M9 13h6"/><path d="M9 17h4"/>
    </symbol>
    <symbol id="i-shield" viewBox="0 0 24 24">
        <path d="M12 3l7.5 3v5.5c0 4.4-3.1 8.1-7.5 9.5-4.4-1.4-7.5-5.1-7.5-9.5V6z"/><path d="M9 12l2 2 4-4"/>
    </symbol>
    <symbol id="i-activity" viewBox="0 0 24 24">
        <path d="M3 12h4l3-7 4 14 3-7h4"/>
    </symbol>
    <symbol id="i-globe" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="9"/><path d="M3 12h18"/>
        <path d="M12 3a15 15 0 0 1 0 18a15 15 0 0 1 0-18"/>
    </symbol>
    <symbol id="i-branch" viewBox="0 0 24 24">
        <circle cx="6" cy="5" r="2.5"/><circle cx="6" cy="19" r="2.5"/><circle cx="18" cy="5" r="2.5"/>
        <path d="M6 7.5v9"/><path d="M18 7.5c0 4-4 4-8 5.5"/>
    </symbol>
    <symbol id="i-sparkles" viewBox="0 0 24 24">
        <path d="M11 3l1.7 4.4L17 9l-4.3 1.6L11 15l-1.7-4.4L5 9l4.3-1.6z"/>
        <path d="M18 14.5l.9 2.2 2.1.8-2.1.9-.9 2.1-.9-2.1-2.1-.9 2.1-.8z"/>
    </symbol>
    <symbol id="i-chart" viewBox="0 0 24 24">
        <path d="M3 20h18"/><path d="M6.5 20v-5"/><path d="M12 20V7"/><path d="M17.5 20v-8"/>
    </symbol>
    <symbol id="i-users" viewBox="0 0 24 24">
        <circle cx="9" cy="8" r="3.2"/><path d="M3.4 20a5.6 5.6 0 0 1 11.2 0"/>
        <path d="M16 5.4a3.2 3.2 0 0 1 0 5.2"/><path d="M17.4 14.6A5.6 5.6 0 0 1 20.6 20"/>
    </symbol>
    <symbol id="i-logout" viewBox="0 0 24 24">
        <path d="M14 8V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2-2v-2"/>
        <path d="M18 12H9"/><path d="M15 9l3 3-3 3"/>
    </symbol>
    <symbol id="i-bell" viewBox="0 0 24 24">
        <path d="M18 8a6 6 0 1 0-12 0c0 7-3 8-3 8h18s-3-1-3-8"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>
    </symbol>
    <symbol id="i-alert" viewBox="0 0 24 24">
        <path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>
        <path d="M12 9v4"/><path d="M12 17h.01"/>
    </symbol>
    <symbol id="i-clock" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
    </symbol>
    <symbol id="i-check" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.5 2.5L16 9.5"/>
    </symbol>
    <symbol id="i-list" viewBox="0 0 24 24">
        <path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/>
        <path d="M3.5 6h.01"/><path d="M3.5 12h.01"/><path d="M3.5 18h.01"/>
    </symbol>
    <symbol id="i-back" viewBox="0 0 24 24">
        <path d="M19 12H5"/><path d="M11 6l-6 6 6 6"/>
    </symbol>
    <symbol id="i-link" viewBox="0 0 24 24">
        <path d="M10 13.5a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1.2 1.2"/>
        <path d="M14 10.5a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1.2-1.2"/>
    </symbol>
    <symbol id="i-menu" viewBox="0 0 24 24">
        <path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h16"/>
    </symbol>
</defs></svg>

<!--
    Cajon lateral movil.
    #nav-toggle es el control real de teclado: esta oculto con la
    tecnica de clip (sigue siendo enfocable) y es hermano anterior a
    .app, que es lo que permite que todo el estado visual se resuelva
    en CSS con #nav-toggle:checked ~ .app .sidebar.
    El <label> que lo pulsa vive en el topbar, dentro de .app.
-->
<input type="checkbox" id="nav-toggle" class="nav-toggle-box">
<label class="nav-scrim" for="nav-toggle" aria-hidden="true"></label>

<div class="app">
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="logo-badge">IA</div>
        <div class="sidebar-brand-txt">
            <strong>Gobierno de Datos</strong>
            <span>Plataforma con IA</span>
        </div>
    </div>
    <nav class="sidebar-nav" aria-label="Navegacion principal">
        <?php foreach ($navItems as $item): ?>
            <?php if (!empty($item['group'])): ?>
                <div class="nav-group"><?= e($item['group']) ?></div>
            <?php endif; ?>
            <?php if (!empty($item['adminOnly']) && $current['rol'] !== 'admin') {
                continue;
            } ?>
            <a href="<?= e(APP_URL) ?>/views/<?= e($item['href']) ?>"
               <?= in_array($currentPage, $item['match'], true) ? ' class="is-active" aria-current="page"' : '' ?>>
                <svg class="ico" aria-hidden="true"><use href="#<?= e($item['icon']) ?>"></use></svg>
                <span><?= e($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-foot">Gobierno de Datos con IA</div>
</aside>
<main class="content">
    <header class="topbar">
        <div class="topbar-left">
            <label class="nav-toggle" for="nav-toggle">
                <svg class="ico" aria-hidden="true"><use href="#i-menu"></use></svg>
                <span class="sr-only">Menu de navegacion</span>
            </label>
            <div class="topbar-title"><?= e($titleText) ?></div>
        </div>
        <div class="topbar-user">
            <div class="user-block">
                <div class="user-avatar"><?= e($initials) ?></div>
                <div class="user-meta">
                    <span class="user-name"><?= e($current['name']) ?></span>
                    <span class="badge-role"><?= e($current['rol']) ?></span>
                </div>
            </div>
            <button id="btn-logout" class="btn btn-ghost btn-sm">
                <svg class="ico" aria-hidden="true"><use href="#i-logout"></use></svg>
                Cerrar sesion
            </button>
        </div>
    </header>
