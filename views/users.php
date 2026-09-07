<?php
// Vista exclusiva de administradores
require_once __DIR__ . '/includes/header.php';

if ($current['rol'] !== 'admin') {
    header('Location: ' . APP_URL . '/views/dashboard.php');
    exit;
}
?>
<section class="panel">
    <h2>Gestion de usuarios</h2>
    <p class="muted">Este modulo se implementara en la Etapa 2 (CRUD de usuarios por administrador).</p>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>