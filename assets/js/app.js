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

    // Token anti-CSRF en toda peticion que modifica estado (POST, etc.).
    if (admiteCuerpo && window.CSRF_TOKEN) {
        opts.headers['X-CSRF-Token'] = window.CSRF_TOKEN;
    }

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
        await api('/api/auth.php?action=logout');
        window.location.href = window.APP_URL + '/views/login.php';
    }
});

// ------------------------------------------------------------
//   Semantica visual compartida
//   Un solo lugar decide que tono corresponde a cada severidad.
//   Antes cada vista traia su propia copia y divergieron entre si:
//   'critica' caia en gris porque nadie la contemplaba, y 'baja'
//   salia verde en unas pantallas y gris en otras para la misma
//   palabra. app.js se carga antes que el script de cada vista
//   (views/includes/footer.php), asi que esta disponible para todas.
// ------------------------------------------------------------
const TONO_SEVERIDAD = {
    critica: 'error',
    alta:    'error',
    media:   'warn',
    baja:    'neutral',
};

// ------------------------------------------------------------
//   Gestion de modales
//   Los 12 modales de views/ comparten la misma estructura: un
//   .modal-backdrop que envuelve a un .modal. Aqui se centraliza
//   lo que antes cada vista repetia por su cuenta: la apertura, el
//   cierre, el bloqueo del scroll de fondo, la trampa de foco, la
//   tecla Escape y la devolucion del foco al elemento que abrio.
//
//   Uso desde los modulos:
//     abrirModal(document.getElementById('modal-x'))
//     cerrarModal(document.getElementById('modal-x'))
//   El cierre por boton (data-close="id-modal") y por clic en el
//   fondo ya no lo conecta cada vista: lo resuelve el delegado de
//   abajo. Ningun modulo debe volver a tocar .hidden a mano.
// ------------------------------------------------------------

const FOCUSABLE = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

// Elemento que tenia el foco antes de abrir cada modal, para devolverlo al cerrar.
const modalFocoPrevio = new WeakMap();

// Elementos que el usuario puede alcanzar con el teclado ahora mismo.
function enfocatables(caja) {
    return Array.prototype.slice
        .call(caja.querySelectorAll(FOCUSABLE))
        .filter((el) => el.offsetWidth > 0 || el.offsetHeight > 0 || el === document.activeElement);
}

// El modal abierto mas reciente; es el que responde a Escape y al foco.
function modalSuperior() {
    const abiertos = document.querySelectorAll('.modal-backdrop:not(.hidden)');
    return abiertos.length ? abiertos[abiertos.length - 1] : null;
}

function abrirModal(modal) {
    if (!modal) return;
    if (modal.classList.contains('hidden')) {
        modalFocoPrevio.set(modal, document.activeElement);
    } else {
        // Reabrir sobre otro registro no debe devolver el foco a un boton viejo.
        modalFocoPrevio.delete(modal);
    }

    modal.classList.remove('hidden');
    document.body.classList.add('modal-abierto');

    const caja = modal.querySelector('.modal') || modal;
    if (!caja.hasAttribute('tabindex')) caja.setAttribute('tabindex', '-1');

    const primero = enfocatables(caja)[0];
    (primero || caja).focus();
}

function cerrarModal(modal) {
    if (!modal || modal.classList.contains('hidden')) return;
    modal.classList.add('hidden');
    if (!document.querySelector('.modal-backdrop:not(.hidden)')) {
        document.body.classList.remove('modal-abierto');
    }
    const previo = modalFocoPrevio.get(modal);
    if (previo && document.contains(previo)) previo.focus();
    modalFocoPrevio.delete(modal);
}

// Cierre delegado: cualquier boton con data-close="id-modal" y cualquier
// click sobre el fondo oscurecido. Sustituye a los 8 bloques duplicados
// que cada vista tenia conectados a mano.
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-close]');
    if (btn) {
        const modal = document.getElementById(btn.dataset.close);
        if (modal) {
            e.preventDefault();
            cerrarModal(modal);
        }
        return;
    }
    if (e.target.classList && e.target.classList.contains('modal-backdrop')) {
        cerrarModal(e.target);
    }
});

// Escape cierra y Tab queda atrapado dentro del modal abierto.
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        if (cerrarCajon()) return;
        const modal = modalSuperior();
        if (!modal) return;
        e.preventDefault();
        cerrarModal(modal);
        return;
    }
    if (e.key !== 'Tab') return;

    const modal = modalSuperior();
    if (!modal) return;
    const caja = modal.querySelector('.modal') || modal;

    const items = enfocatables(caja);
    if (items.length === 0) {
        e.preventDefault();
        caja.focus();
        return;
    }

    const primero = items[0];
    const ultimo = items[items.length - 1];
    const dentro = caja.contains(document.activeElement);

    if (e.shiftKey && (!dentro || document.activeElement === primero)) {
        e.preventDefault();
        ultimo.focus();
    } else if (!e.shiftKey && document.activeElement === ultimo) {
        e.preventDefault();
        primero.focus();
    }
});

// ------------------------------------------------------------
//   Cajon lateral movil
//   La apertura y el cierre son CSS puro (#nav-toggle:checked ~ .app
//   .sidebar). Aqui solo se sincroniza lo que CSS no puede: el
//   bloqueo del scroll de fondo y la tecla Escape. En escritorio el
//   checkbox esta oculto y no hace nada.
//   El estado abierto/cerrado ya lo anuncia el propio checkbox, que
//   es el control de teclado (Tab hasta el y Espacio para togglear).
// ------------------------------------------------------------
const navToggle = document.getElementById('nav-toggle');

function cajonAbierto() {
    return !!(navToggle && navToggle.checked);
}

function cerrarCajon() {
    if (!cajonAbierto()) return false;
    navToggle.checked = false;
    sincronizarCajon();
    // El foco vuelve al interruptor, que es el control que lo abrio.
    navToggle.focus();
    return true;
}

function sincronizarCajon() {
    document.body.classList.toggle('nav-abierto', cajonAbierto());

    // translateX no saca el sidebar del recorrido del teclado: con el cajon
    // cerrado el foco entraba en enlaces que estan fuera de pantalla y sin
    // indicador visible. inert lo resuelve, asi que no hace falta aria-hidden
    // (que ademas avisaria en conflicto si el foco siguiera dentro).
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;

    const oculto = window.innerWidth <= 820 && !cajonAbierto();

    // Antes de volverlo inaccesible hay que sacar el foco: si el elemento
    // activo esta dentro, inert lo dejaria huerfano y el usuario no veria
    // donde esta. Se devuelve al interruptor, el control que abre el cajon.
    if (oculto && sidebar.contains(document.activeElement)) {
        if (navToggle) navToggle.focus();
        else document.body.focus();
    }

    sidebar.toggleAttribute('inert', oculto);
}

if (navToggle) {
    navToggle.addEventListener('change', sincronizarCajon);
    sincronizarCajon();

    // Al cambiar de ancho hay que resincronizar siempre: al pasar a escritorio
    // el sidebar vuelve a ser visible y hay que quitarle el inert; al volver
    // a movil hay que volver a ponerselo. Y el cajon se desmonta solo, para
    // que al regresar a movil no aparezca abierto sin pedirlo.
    window.addEventListener('resize', () => {
        if (window.innerWidth > 820 && cajonAbierto()) {
            navToggle.checked = false;
        }
        sincronizarCajon();
    });
}

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

    // Los colores salen de los tokens de style.css para que el grafico
    // y la leyenda de cada vista nunca se desincronicen.
    const tokens = getComputedStyle(document.documentElement);
    const tono = (nombre) => tokens.getPropertyValue(nombre).trim();

    if (!items || items.length === 0) {
        ctx.fillStyle = tono('--text-3');
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

    ctx.strokeStyle = tono('--border-2');
    ctx.fillStyle = tono('--text-3');
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
        ctx.fillStyle = item.value >= 85 ? tono('--ok') : item.value >= 70 ? tono('--warn') : tono('--danger');
        ctx.fillRect(x + bw * 0.22, y, bw * 0.56, Math.max(h, 2));
        ctx.fillStyle = tono('--text-2');
        ctx.font = 'bold 11px sans-serif';
        ctx.fillText(String(item.value), x + bw / 2, y - 6);
        ctx.font = '10px sans-serif';
        ctx.fillStyle = tono('--text');
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