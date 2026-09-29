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
    <title>Iniciar sesión | Gobierno de Datos IA</title>
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/style.css">
</head>
<body class="login-body">

<!-- Sprite local: solo los simbolos que usa esta pantalla,
     copiados del sprite general de views/includes/header.php. El login
     no incluye la cabecera, asi que no puede reutilizarlo por <use>. -->
<svg class="ico-sprite" aria-hidden="true" focusable="false"><defs>
    <symbol id="i-globe" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="9"/><path d="M3 12h18"/>
        <path d="M12 3a15 15 0 0 1 0 18a15 15 0 0 1 0-18"/>
    </symbol>
    <symbol id="i-shield" viewBox="0 0 24 24">
        <path d="M12 3l7.5 3v5.5c0 4.4-3.1 8.1-7.5 9.5-4.4-1.4-7.5-5.1-7.5-9.5V6z"/><path d="M9 12l2 2 4-4"/>
    </symbol>
    <symbol id="i-alert" viewBox="0 0 24 24">
        <path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>
        <path d="M12 9v4"/><path d="M12 17h.01"/>
    </symbol>
    <symbol id="i-file" viewBox="0 0 24 24">
        <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M9 13h6"/><path d="M9 17h4"/>
    </symbol>
    <symbol id="i-db" viewBox="0 0 24 24">
        <ellipse cx="12" cy="6" rx="8" ry="3"/>
        <path d="M4 6v6c0 1.66 3.58 3 8 3s8-1.34 8-3V6"/><path d="M4 12v6c0 1.66 3.58 3 8 3s8-1.34 8-3v-6"/>
    </symbol>
    <symbol id="i-activity" viewBox="0 0 24 24">
        <path d="M3 12h4l3-7 4 14 3-7h4"/>
    </symbol>
    <symbol id="i-check" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.5 2.5L16 9.5"/>
    </symbol>
</defs></svg>

    <main class="login-shell">

        <!-- Panel de identidad: da contexto de plataforma antes del formulario -->
        <section class="login-aside">
            <div class="login-brand">
                <div class="logo-badge">IA</div>
                <div class="login-brand-txt">
                    <h1>Gobierno de Datos</h1>
                    <p class="login-tagline">Plataforma de gobierno de datos con Inteligencia Artificial</p>
                </div>
            </div>

            <ul class="login-features">
                <li>
                    <span class="login-feature-ico"><svg class="ico" aria-hidden="true"><use href="#i-db"></use></svg></span>
                    <div>
                        <strong>Catalogacion centralizada</strong>
                        <span>Datasets, mediciones de calidad y linaje en un solo lugar.</span>
                    </div>
                </li>
                <li>
                    <span class="login-feature-ico"><svg class="ico" aria-hidden="true"><use href="#i-activity"></use></svg></span>
                    <div>
                        <strong>Deteccion de anomalias con IA</strong>
                        <span>Revalidacion periodica del score de cada dataset.</span>
                    </div>
                </li>
                <li>
                    <span class="login-feature-ico"><svg class="ico" aria-hidden="true"><use href="#i-shield"></use></svg></span>
                    <div>
                        <strong>Politicas y cumplimiento</strong>
                        <span>Controles normativos evaluados por dataset.</span>
                    </div>
                </li>
            </ul>

            <p class="login-aside-foot">
                <svg class="ico" aria-hidden="true"><use href="#i-check"></use></svg>
                <span>Acceso restringido a usuarios autorizados de la plataforma.</span>
            </p>
        </section>

        <!-- Panel del formulario -->
        <section class="login-form-side">
            <p class="login-eyebrow">Acceso a la plataforma</p>
            <h2 class="login-title">Iniciar sesión</h2>
            <p class="login-intro">Introduce tus credenciales para entrar al panel de gobierno de datos.</p>

            <hr class="login-rule">

            <form id="login-form" autocomplete="off">
                <div class="field">
                    <label for="email">Correo electrónico</label>
                    <div class="login-control">
                        <svg class="ico" aria-hidden="true"><use href="#i-globe"></use></svg>
                        <input type="email" id="email" name="email" placeholder="tu@correo.com"
                               autocomplete="username" autocapitalize="none" spellcheck="false" required>
                    </div>
                </div>
                <div class="field">
                    <label for="password">Contraseña</label>
                    <div class="login-control">
                        <svg class="ico" aria-hidden="true"><use href="#i-shield"></use></svg>
                        <input type="password" id="password" name="password" placeholder="Tu contraseña"
                               autocomplete="current-password" required>
                    </div>
                </div>
                <div id="login-error" class="alert alert-error login-error hidden" role="alert">
                    <svg class="ico" aria-hidden="true"><use href="#i-alert"></use></svg>
                    <span></span>
                </div>
                <button type="submit" class="btn btn-primary btn-block login-submit" id="btn-login">
                    Iniciar sesión
                </button>
            </form>

            <p class="login-foot">
                <svg class="ico" aria-hidden="true"><use href="#i-file"></use></svg>
                <span>Las cuentas de prueba estan documentadas en el README del proyecto.</span>
            </p>
        </section>
    </main>

    <script>
        window.APP_URL = "<?= e(APP_URL) ?>";
    </script>
    <script src="<?= e(APP_URL) ?>/assets/js/app.js"></script>
    <script>
        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn   = document.getElementById('btn-login');
            const error = document.getElementById('login-error');
            const errorTxt = error.querySelector('span');

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
                    errorTxt.textContent = json.message;
                    error.classList.remove('hidden');
                }
            } catch (err) {
                errorTxt.textContent = 'Error de conexion. Intenta de nuevo.';
                error.classList.remove('hidden');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Iniciar sesión';
            }
        });
    </script>
</body>
</html>
