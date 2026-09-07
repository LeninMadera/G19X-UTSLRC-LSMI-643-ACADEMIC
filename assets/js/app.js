/* ============================================================
   Funciones JavaScript base del proyecto.
   Se carga una copia del APP_URL en window (definido por PHP).
   ============================================================ */

// PRIVADO: acceso al backend; respeta el rol definido por el servidor.
async function api(action, data = {}, method = 'POST') {
    const url = window.APP_URL + action;
    const opts = { method, headers: {} };

    if (data instanceof FormData) {
        opts.body = data;
    } else {
        opts.headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(data);
    }

    const res = await fetch(url, opts);
    return res.json();
}

function showAlert(containerId, message, type = 'error') {
    const el = document.getElementById(containerId);
    if (!el) return;
    el.className = 'alert alert-' + type;
    el.textContent = message;
}

function hideAlert(containerId) {
    const el = document.getElementById(containerId);
    if (el) el.classList.add('hidden');
}

// Cierre de sesion global (boton con id="btn-logout")
document.addEventListener('click', async (e) => {
    if (e.target.closest('#btn-logout')) {
        e.preventDefault();
        const json = await api('/api/auth.php?action=logout');
        window.location.href = window.APP_URL + '/views/login.php';
    }
});