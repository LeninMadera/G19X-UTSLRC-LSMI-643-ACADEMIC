<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();
// Si ya hay sesion, ir directo al dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ' . APP_URL . '/views/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesion | Gobierno de Datos IA</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body class="login-body">
    <main class="login-card">
        <div class="login-logo">
            <div class="logo-badge">IA</div>
            <h1>Gobierno de Datos</h1>
            <p class="muted">Plataforma de gobierno de datos con Inteligencia Artificial</p>
        </div>

        <form id="login-form" autocomplete="off">
            <div class="field">
                <label for="email">Correo electronico</label>
                <input type="email" id="email" name="email" placeholder="tu@correo.com" required>
            </div>
            <div class="field">
                <label for="password">Contrasena</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
            <div id="login-error" class="alert hidden" role="alert"></div>
            <button type="submit" class="btn btn-primary btn-block" id="btn-login">
                Iniciar sesion
            </button>
        </form>
        <p class="muted small center">Usuarios de prueba: admin@example.com / Admin123!</p>
    </main>

    <script src="<?= APP_URL ?>/assets/js/app.js"></script>
    <script>
        window.APP_URL = "<?= e(APP_URL) ?>";
        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn   = document.getElementById('btn-login');
            const error = document.getElementById('login-error');

            btn.disabled = true;
            btn.textContent = 'Verificando...';
            error.classList.add('hidden');

            try {
                const res = await fetch(APP_URL + '/api/auth.php?action=login', {
                    method: 'POST',
                    body: new FormData(e.target)
                });
                const json = await res.json();

                if (json.success) {
                    window.location.href = APP_URL + '/views/dashboard.php';
                } else {
                    error.textContent = json.message;
                    error.classList.remove('hidden');
                }
            } catch (err) {
                error.textContent = 'Error de conexion. Intenta de nuevo.';
                error.classList.remove('hidden');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Iniciar sesion';
            }
        });
    </script>
</body>
</html>