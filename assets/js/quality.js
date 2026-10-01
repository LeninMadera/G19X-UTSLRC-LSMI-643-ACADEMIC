/* ============================================================
   Modulo de calidad de datos
   ============================================================ */

/* global api, esc, APP_USER */

document.addEventListener('DOMContentLoaded', () => {
    const selDataset = document.getElementById('sel-dataset');
    const panelHis   = document.getElementById('panel-historico');
    const panelForm  = document.getElementById('panel-form');
    const tbody      = document.getElementById('history-tbody');
    const msg        = document.getElementById('msg');
    const form       = document.getElementById('quality-form');
    const formError  = document.getElementById('form-error');

    const rol = document.querySelector('.badge-role')?.textContent || '';
    const puedeBorrar    = ['admin', 'steward'].includes(rol);
    const userId         = (window.APP_USER && window.APP_USER.id) || null;

    // El color sigue la leyenda del dashboard (85+ excelente / 70-84
    // aceptable / <70 por mejorar). Antes se comparaba contra 80 y un
    // score de 75 salia rojo aqui y ambar en el resto de la aplicacion.
    // El badge "bajo umbral" no sale de esta constante sino del backend
    // (m.bajo_umbral), que usa su propio criterio.
    const UMBRAL_COLOR = 70;

    function color(n) {
        return n >= 85 ? 'score-good' : n >= UMBRAL_COLOR ? 'score-warn' : 'score-bad';
    }

    function mostrarMsg(texto, tipo = 'success') {
        msg.textContent = texto;
        msg.className = 'alert alert-' + tipo;
        setTimeout(() => msg.classList.add('hidden'), 4000);
    }

    async function cargarDatasets() {
        const json = await api('/api/datasets.php?action=list');
        if (!json.success) return;
        selDataset.innerHTML = '<option value="">Selecciona un dataset</option>';
        json.data.datasets.forEach(d => {
            if (rol === 'owner' && Number(d.owner_id) !== Number(userId)) return;
            const opt = document.createElement('option');
            opt.value = d.id;
            opt.textContent = d.nombre + ' (' + d.categoria + ')';
            selDataset.appendChild(opt);
        });
    }

    async function cargarHistorial(datasetId) {
        tbody.innerHTML = '<tr><td colspan="9" class="muted center">Cargando...</td></tr>';
        const json = await api('/api/quality.php?action=history&dataset_id=' + datasetId);
        if (!json.success) {
            tbody.innerHTML = '<tr><td colspan="9" class="muted center">' + esc(json.message) + '</td></tr>';
            return;
        }

        if (json.data.metrics.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="muted center">Sin mediciones registradas.</td></tr>';
            return;
        }

        tbody.innerHTML = '';
        json.data.metrics.forEach(m => {
            const warn = m.bajo_umbral
                ? ' <span class="badge badge-warn">bajo umbral</span>' : '';
            const tr = document.createElement('tr');
            tr.innerHTML =
                '<td>' + esc(m.fecha_medicion) + '</td>' +
                '<td>' + parseFloat(m.completitud).toFixed(1) + '</td>' +
                '<td>' + parseFloat(m.exactitud).toFixed(1) + '</td>' +
                '<td>' + parseFloat(m.consistencia).toFixed(1) + '</td>' +
                '<td>' + parseFloat(m.vigencia).toFixed(1) + '</td>' +
                '<td>' + parseFloat(m.unicidad).toFixed(1) + '</td>' +
                '<td><span class="score-badge ' + color(parseFloat(m.score)) + '">' +
                    parseFloat(m.score).toFixed(1) + '</span>' + warn + '</td>' +
                '<td>' + esc(m.registrado_por || 'sistema') + '</td>' +
                '<td>' + (puedeBorrar ? '<button class="btn btn-danger btn-sm" data-del="' + m.id + '">Eliminar</button>' : '') + '</td>';
            tbody.appendChild(tr);
        });

        if (panelForm) {
            panelForm.style.display = 'block';
            form.reset();
        }
    }

    selDataset.addEventListener('change', () => {
        const id = selDataset.value;
        panelHis.style.display = id ? 'block' : 'none';
        if (panelForm) panelForm.style.display = id ? 'block' : 'none';
        if (id) cargarHistorial(id);
    });

    tbody.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-del]');
        if (!btn) return;
        if (!confirm('¿Eliminar esta medicion?')) return;
        const json = await api('/api/quality.php?action=delete', { id: Number(btn.dataset.del) });
        mostrarMsg(json.message, json.success ? 'success' : 'error');
        if (json.success) cargarHistorial(selDataset.value);
    });

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            formError.classList.add('hidden');
            const data = new FormData(form);
            data.set('dataset_id', selDataset.value);

            const json = await api('/api/quality.php?action=register', data);
            if (json.success) {
                mostrarMsg(json.message, 'success');
                cargarHistorial(selDataset.value);
            } else {
                formError.textContent = json.message;
                formError.classList.remove('hidden');
            }
        });
    }

    cargarDatasets();
});