/* ============================================================
   Funciones JavaScript base del proyecto.
   Se carga una copia del APP_URL en window (definido por PHP).
   ============================================================ */

// PRIVADO: acceso al backend; respeta el rol definido por el servidor.
// Nunca lanza excepciones: ante un fallo de red o respuesta invalida
// devuelve un JSON con success=false para que cada modulo lo muestre.
async function api(action, data = {}, method = 'POST') {
    const url = window.APP_URL + action;
    const opts = { method, headers: {} };
    const admiteCuerpo = method !== 'GET' && method !== 'HEAD';

    if (data instanceof FormData) {
        if (admiteCuerpo) opts.body = data;
    } else if (admiteCuerpo) {
        opts.headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(data);
    }

    let res;
    try {
        res = await fetch(url, opts);
    } catch (err) {
        return { success: false, message: 'Error de conexion con el servidor.' };
    }

    const type = (res.headers.get('content-type') || '');
    if (type.indexOf('application/json') === -1) {
        const texto = await res.text().catch(() => '');
        return {
            success: false,
            message: texto ? 'Respuesta invalida del servidor.' : 'Sin respuesta del servidor.',
        };
    }

    try {
        return await res.json();
    } catch (err) {
        return { success: false, message: 'El servidor no respondio en formato valido.' };
    }
}

// Escapa texto para insertarlo de forma segura en HTML (anti-XSS).
function esc(t) {
    const div = document.createElement('div');
    div.textContent = t == null ? '' : String(t);
    return div.innerHTML;
}

// Cierre de sesion global (boton con id="btn-logout")
document.addEventListener('click', async (e) => {
    if (e.target.closest('#btn-logout')) {
        e.preventDefault();
        const json = await api('/api/auth.php?action=logout');
        window.location.href = window.APP_URL + '/views/login.php';
    }
});

// Grafico de barras con Canvas en blanco (sin librerias).
// items: [{ label, value }] con value numerico >= 0.
function drawBars(canvas, items) {
    const ctx = canvas.getContext('2d');
    const dpr = window.devicePixelRatio || 1;
    const W = canvas.clientWidth || canvas.parentNode.clientWidth;
    const H = canvas.clientHeight || 260;
    canvas.width = W * dpr;
    canvas.height = H * dpr;
    ctx.scale(dpr, dpr);
    ctx.clearRect(0, 0, W, H);

    if (!items || items.length === 0) {
        ctx.fillStyle = '#94a3b8';
        ctx.font = '13px sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('Sin datos', W / 2, H / 2);
        return;
    }

    const pad = { top: 22, right: 10, bottom: 34, left: 42 };
    const chartW = W - pad.left - pad.right;
    const chartH = H - pad.top - pad.bottom;
    const max = Math.max(1, ...items.map(i => i.value));
    const maxVal = Math.ceil(max / 5) * 5;

    ctx.strokeStyle = '#e2e8f0';
    ctx.fillStyle = '#64748b';
    ctx.font = '11px sans-serif';
    ctx.lineWidth = 1;
    for (let g = 0; g <= 4; g++) {
        const y = pad.top + chartH - (chartH * g) / 4;
        ctx.textAlign = 'right';
        ctx.fillText(String(Math.round((maxVal * g) / 4)), pad.left - 7, y + 4);
        ctx.beginPath();
        ctx.moveTo(pad.left, y);
        ctx.lineTo(W - pad.right, y);
        ctx.stroke();
    }

    const bw = chartW / items.length;
    ctx.textAlign = 'center';
    items.forEach((item, i) => {
        const x = pad.left + i * bw;
        const h = (chartH * item.value) / maxVal;
        const y = pad.top + chartH - h;
        ctx.fillStyle = item.value >= 85 ? '#16a34a' : item.value >= 70 ? '#d97706' : '#dc2626';
        ctx.fillRect(x + bw * 0.22, y, bw * 0.56, Math.max(h, 2));
        ctx.fillStyle = '#475569';
        ctx.font = 'bold 11px sans-serif';
        ctx.fillText(String(item.value), x + bw / 2, y - 6);
        ctx.font = '10px sans-serif';
        ctx.fillStyle = '#334155';
        const label = item.label.length > 17 ? item.label.slice(0, 16) + String.fromCharCode(8230) : item.label;
        ctx.fillText(label, x + bw / 2, H - 10);
    });
}

// Formatea fecha/hora UTC del backend a formato local corto.
function fmtFecha(valor) {
    if (!valor) return '-';
    const d = new Date(valor);
    if (isNaN(d.getTime())) return String(valor);
    const p = (n) => String(n).padStart(2, '0');
    return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate()) + ' ' + p(d.getHours()) + ':' + p(d.getMinutes());
}