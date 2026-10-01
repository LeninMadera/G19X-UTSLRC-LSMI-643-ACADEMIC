/* ============================================================
   Monitoreo continuo: configuraciones y ejecuciones de calidad
   ============================================================ */

/* global api, esc, fmtFecha, abrirModal, cerrarModal */

document.addEventListener('DOMContentLoaded', () => {
    const tbody     = document.getElementById('configs-tbody');
    const runsTbody = document.getElementById('runs-tbody');
    const msg       = document.getElementById('msg');
    const modConfig = document.getElementById('modal-config');
    const modRuns   = document.getElementById('modal-runs');
    const formConfig = document.getElementById('config-form');
    const cError    = document.getElementById('config-form-error');
    const btnNueva  = document.getElementById('btn-nueva');

    const estado = { configs: [], datasets: [] };

    const rol = window.APP_USER && window.APP_USER.rol ? window.APP_USER.rol : '';
    const uid = window.APP_USER ? Number(window.APP_USER.id) : 0;
    const puedeEditar = ['admin', 'steward', 'owner'].includes(rol);

    function mostrarMsg(texto, tipo = 'success') {
        msg.textContent = texto;
        msg.className = 'alert alert-' + tipo;
        setTimeout(() => msg.classList.add('hidden'), 5000);
    }

    /* Classes de badge segun significado: ok / warn / error / neutral. */
    function badgeEstado(activo) {
        return activo == 1
            ? '<span class="badge badge-ok">activo</span>'
            : '<span class="badge badge-neutral">inactivo</span>';
    }

    function badgeResultado(est) {
        if (!est) return '<span class="muted">--</span>';
        return est === 'ok'
            ? '<span class="badge badge-ok">ok</span>'
            : '<span class="badge badge-error">fallo</span>';
    }

    function colorScore(s) {
        const n = parseFloat(s);
        if (Number.isNaN(n)) return '';
        return n >= 85 ? 'score-good' : n >= 70 ? 'score-warn' : 'score-bad';
    }

    function celdaScore(s) {
        return s == null
            ? '<span class="muted">-</span>'
            : '<strong class="score-badge ' + colorScore(s) + '">' + s + '</strong>';
    }

    function fmtFrecuencia(min) {
        if (min % 1440 === 0) return (min / 1440) + ' dia(s)';
        if (min % 60 === 0) return (min / 60) + ' hora(s)';
        return min + ' min';
    }

    function llenarFrecuencias() {
        const dl = document.getElementById('frecuencias-sugeridas');
        dl.innerHTML = '';
        [5, 15, 30, 60, 180, 360, 720, 1440].forEach(f => {
            const opt = document.createElement('option');
            opt.value = String(f);
            dl.appendChild(opt);
        });
    }

    async function cargar() {
        const json = await api('/api/monitoring.php?action=list', {}, 'GET');
        tbody.innerHTML = '';
        if (!json.success) {
            tbody.innerHTML = '<tr><td colspan="8" class="muted center">' + esc(json.message) + '</td></tr>';
            return;
        }
        estado.configs = json.data.configs;
        estado.datasets = json.data.datasets;
        pintarKpis(json.data.resumen);
        llenarDatasetSelect();
        llenarFrecuencias();
        pintarTabla();
    }

    function pintarKpis(r) {
        document.getElementById('kpi-configs').textContent    = r.configs;
        document.getElementById('kpi-activas').textContent    = r.activas;
        document.getElementById('kpi-ejecuciones').textContent = r.ejecuciones;
        document.getElementById('kpi-fallos').textContent     = r.fallos;
        document.getElementById('kpi-tasa').textContent       =
            r.tasa_exito != null ? r.tasa_exito + '%' : '--';

        const pill = document.getElementById('mon-resumen');
        const tono = Number(r.fallos) > 0 ? 'is-error' : 'is-ok';
        pill.className = 'pill-summary ' + tono;
        pill.innerHTML = '<span class="dot"></span>' + esc(
            r.fallos > 0
                ? r.fallos + ' ' + (r.fallos === 1 ? 'fallo' : 'fallos') + ' en ' + r.ejecuciones + ' ejecuciones'
                : 'Sin fallos en ' + r.ejecuciones + ' ejecuciones'
        );
    }

    function llenarDatasetSelect() {
        const sel = document.getElementById('dataset_id');
        const prev = sel.value;
        sel.innerHTML = '<option value="">Selecciona un dataset</option>';
        estado.datasets.forEach(d => {
            if (rol === 'owner' && Number(d.owner_id) !== uid) {
                return;
            }
            const opt = document.createElement('option');
            opt.value = d.id;
            opt.textContent = d.nombre;
            sel.appendChild(opt);
        });
        sel.value = prev;
    }

    function pintarTabla() {
        tbody.innerHTML = '';
        if (estado.configs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="muted center">Sin configuraciones de monitoreo.</td></tr>';
            document.getElementById('mon-tabla-info').textContent = 'Ningun dataset monitoreado todavia.';
            return;
        }
        estado.configs.forEach(c => {
            // El boton de toggle cambia de texto segun el estado vigente.
            const accionToggle = c.activo == 1 ? 'Desactivar' : 'Activar';

            // Los botones van dentro de .actions-stack: es solo maquetacion
            // (flex-wrap) para que la celda no se corte ni se salga del panel.
            // Los atributos data-* y el resto de la accion no cambian.
            const acciones = puedeEditar ? (
                '<div class="actions-stack">' +
                '<button class="btn btn-ghost btn-sm" data-run="' + c.id + '">Monitorear ahora</button>' +
                '<button class="btn btn-ghost btn-sm" data-edit="' + c.id + '">Editar</button>' +
                '<button class="btn btn-ghost btn-sm" data-toggle="' + c.id + '">' + accionToggle + '</button>' +
                '<button class="btn btn-ghost btn-sm" data-hist="' + c.id + '">Historial</button>' +
                '<button class="btn btn-danger btn-sm" data-del="' + c.id + '">Eliminar</button>' +
                '</div>'
            ) : '-';

            const anomalias = Number(c.anomalias_ultimas || 0);
            const celdaAnomalias = anomalias > 0
                ? '<span class="badge badge-warn">' + anomalias + '</span>'
                : '<span class="muted">0</span>';

            const tr = document.createElement('tr');
            tr.dataset.config = c.id;
            tr.innerHTML =
                '<td class="col-strong">' + esc(c.dataset) + '</td>' +
                '<td class="col-soft">' + fmtFrecuencia(Number(c.frecuencia)) + '</td>' +
                '<td class="col-estado">' + badgeEstado(c.activo) + '</td>' +
                '<td class="col-date">' + (c.proxima_ejecucion ? fmtFecha(c.proxima_ejecucion) : '<span class="muted">-</span>') + '</td>' +
                '<td>' + badgeResultado(c.estado_ultima) + '</td>' +
                '<td class="col-num">' + celdaScore(c.score_ultimo) + '</td>' +
                '<td class="col-num">' + celdaAnomalias + '</td>' +
                '<td class="actions-cell actions-cell-stack col-acciones">' + acciones + '</td>';
            tbody.appendChild(tr);
        });

        const activas = estado.configs.filter(c => c.activo == 1).length;
        document.getElementById('mon-tabla-info').textContent =
            estado.configs.length + ' configuraciones, ' + activas + ' activas.';
    }

    function abrirForm(config) {
        formConfig.reset();
        cError.classList.add('hidden');
        llenarDatasetSelect();
        document.getElementById('frecuencia').value = '1440';
        document.getElementById('activo').value = '1';
        const datasetField = document.getElementById('dataset_id').closest('.field');

        if (config) {
            document.getElementById('config-title').textContent = 'Editar configuracion';
            document.getElementById('config-id').value = config.id;
            document.getElementById('frecuencia').value = config.frecuencia;
            document.getElementById('activo').value = config.activo;
            if (datasetField) datasetField.classList.add('hidden');
        } else {
            document.getElementById('config-title').textContent = 'Nueva configuracion';
            document.getElementById('config-id').value = 0;
            if (datasetField) datasetField.classList.remove('hidden');
        }
        abrirModal(modConfig);
    }

    async function abrirHistorial(id) {
        const c = estado.configs.find(x => String(x.id) === String(id));
        document.getElementById('runs-title').textContent = c ? c.dataset : '# ' + id;
        runsTbody.innerHTML = '<tr><td colspan="6" class="muted center">Cargando...</td></tr>';
        abrirModal(modRuns);

        const json = await api('/api/monitoring.php?action=runs&config_id=' + id, {}, 'GET');
        runsTbody.innerHTML = '';
        if (!json.success) {
            runsTbody.innerHTML = '<tr><td colspan="6" class="muted center">' + esc(json.message) + '</td></tr>';
            return;
        }
        if (json.data.runs.length === 0) {
            runsTbody.innerHTML = '<tr><td colspan="6" class="muted center">Sin ejecuciones registradas.</td></tr>';
            return;
        }
        json.data.runs.forEach(r => {
            const tr = document.createElement('tr');
            tr.innerHTML =
                '<td class="col-date">' + fmtFecha(r.fecha) + '</td>' +
                '<td class="col-soft">' + esc(r.causa) + '</td>' +
                '<td>' + badgeResultado(r.estado) + '</td>' +
                '<td class="col-num">' + celdaScore(r.score) + '</td>' +
                '<td class="col-num">' + (Number(r.anomalias_nuevas) > 0
                    ? '<span class="badge badge-warn">' + r.anomalias_nuevas + '</span>'
                    : '<span class="muted">0</span>') + '</td>' +
                '<td class="col-soft">' + esc(r.resumen || '') + '</td>';
            runsTbody.appendChild(tr);
        });
    }

    if (btnNueva) btnNueva.addEventListener('click', () => abrirForm(null));

    // El cierre (data-close, clic en el fondo, Escape, foco, trampa de
    // Tab y scroll de fondo) lo resuelve el delegado de app.js.

    tbody.addEventListener('click', async (e) => {
        const btnRun   = e.target.closest('[data-run]');
        const btnEdit  = e.target.closest('[data-edit]');
        const btnTog   = e.target.closest('[data-toggle]');
        const btnHist  = e.target.closest('[data-hist]');
        const btnDel   = e.target.closest('[data-del]');

        if (btnRun) {
            btnRun.disabled = true;
            try {
                const json = await api('/api/monitoring.php?action=run', { id: Number(btnRun.dataset.run) });
                if (json.success && json.data && json.data.resumen) {
                    mostrarMsg(String(json.data.resumen).slice(0, 220), 'success');
                } else {
                    mostrarMsg(json.message, json.success ? 'success' : 'error');
                }
                if (json.success) cargar();
            } finally {
                // Si la ejecucion falla, cargar() no llega a re-renderizar la
                // tabla, asi que el boton tiene que rehabilitarse aqui o se
                // queda deshabilitado en el DOM hasta recargar la pagina.
                btnRun.disabled = false;
            }
        }
        if (btnEdit) {
            const c = estado.configs.find(x => String(x.id) === btnEdit.dataset.edit);
            if (c) abrirForm(c);
        }
        if (btnTog) {
            btnTog.disabled = true;
            const json = await api('/api/monitoring.php?action=toggle', { id: Number(btnTog.dataset.toggle) });
            mostrarMsg(json.message, json.success ? 'success' : 'error');
            if (json.success) {
                // Refleja el cambio en la fila sin recargar la tabla completa.
                const fila = btnTog.closest('tr');
                const celda = fila && fila.querySelector('.col-estado');
                const cfg = estado.configs.find(x => String(x.id) === String(btnTog.dataset.toggle));
                if (celda && cfg) {
                    cfg.activo = cfg.activo == 1 ? 0 : 1;
                    celda.innerHTML = badgeEstado(cfg.activo);
                    btnTog.textContent = cfg.activo == 1 ? 'Desactivar' : 'Activar';
                }
                btnTog.disabled = false;
            } else {
                btnTog.disabled = false;
            }
        }
        if (btnHist) {
            abrirHistorial(btnHist.dataset.hist);
        }
        if (btnDel) {
            if (!confirm('¿Eliminar esta configuracion y su historial de ejecuciones?')) return;
            const json = await api('/api/monitoring.php?action=delete', { id: Number(btnDel.dataset.del) });
            mostrarMsg(json.message, json.success ? 'success' : 'error');
            if (json.success) cargar();
        }
    });

    formConfig.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('config-id').value;
        const esCrear = !id || Number(id) <= 0;

        const data = {
            id: id,
            frecuencia: Number(document.getElementById('frecuencia').value),
            activo: Number(document.getElementById('activo').value),
        };
        if (esCrear) data.dataset_id = Number(document.getElementById('dataset_id').value);

        const freq = data.frecuencia;
        if (!Number.isInteger(freq) || freq < 1 || freq > 43200) {
            cError.textContent = 'La frecuencia debe ser un entero entre 1 y 43200 minutos.';
            cError.classList.remove('hidden');
            return;
        }
        if (esCrear && !data.dataset_id) {
            cError.textContent = 'Debes seleccionar un dataset.';
            cError.classList.remove('hidden');
            return;
        }

        const json = await api('/api/monitoring.php?action=' + (esCrear ? 'create' : 'update'), data);
        if (json.success) {
            cerrarModal(modConfig);
            mostrarMsg(json.message, 'success');
            cargar();
        } else {
            cError.textContent = json.message;
            cError.classList.remove('hidden');
        }
    });

    cargar();
});