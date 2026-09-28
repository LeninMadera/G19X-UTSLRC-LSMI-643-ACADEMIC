/* ============================================================
   Fuentes externas / Integraciones: fuentes y sincronizaciones
   ============================================================ */

/* global api, esc, fmtFecha */

document.addEventListener('DOMContentLoaded', () => {
    const tbody      = document.getElementById('sources-tbody');
    const histTbody  = document.getElementById('hist-tbody');
    const msg        = document.getElementById('msg');
    const modFuente  = document.getElementById('modal-fuente');
    const modHist    = document.getElementById('modal-hist');
    const formFuente = document.getElementById('fuente-form');
    const fError     = document.getElementById('fuente-form-error');
    const btnNueva   = document.getElementById('btn-nueva');

    const estado = { sources: [], datasets: [], resumen: {} };

    const rol = window.APP_USER && window.APP_USER.rol ? window.APP_USER.rol : '';

    function mostrarMsg(texto, tipo = 'success') {
        msg.textContent = texto;
        msg.className = 'alert alert-' + tipo;
        setTimeout(() => msg.classList.add('hidden'), 5000);
    }

    function badgeEstado(activo) {
        return activo == 1
            ? '<span class="badge badge-active">activo</span>'
            : '<span class="badge badge-inactive">inactivo</span>';
    }

    function badgeResultado(est) {
        if (!est) return '<span class="muted">--</span>';
        return est === 'ok'
            ? '<span class="badge badge-active">ok</span>'
            : '<span class="badge badge-inactive">error</span>';
    }

    function urlCorta(url) {
        if (url.length <= 40) return url;
        return url.slice(0, 37) + '...';
    }

    function pintarKpis(r) {
        estado.resumen = r;
        document.getElementById('kpi-activas').textContent = r.activas;
        document.getElementById('kpi-total').textContent = r.total;
        document.getElementById('kpi-ok').textContent = r.ultimas_ok;
        document.getElementById('kpi-registros').textContent = r.registros_leidos;
    }

    function llenarDatasetSelect(seleccionado) {
        const sel = document.getElementById('dataset_id');
        sel.innerHTML = '<option value="">Sin vincular</option>';
        estado.datasets.forEach(d => {
            const opt = document.createElement('option');
            opt.value = d.id;
            opt.textContent = d.nombre;
            sel.appendChild(opt);
        });
        sel.value = seleccionado != null ? seleccionado : '';
    }

    function pintarTabla() {
        tbody.innerHTML = '';
        if (estado.sources.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="muted center">Sin fuentes externas registradas.</td></tr>';
            return;
        }
        estado.sources.forEach(s => {
            const gestiona = s.puede_gestionar === true || s.puede_gestionar == 1;
            const acciones = gestiona ? (
                '<button class="btn btn-ghost btn-sm" data-probar="' + s.id + '">Probar conexion</button> ' +
                '<button class="btn btn-ghost btn-sm" data-sync="' + s.id + '">Sincronizar</button> ' +
                '<button class="btn btn-ghost btn-sm" data-edit="' + s.id + '">Editar</button> ' +
                '<button class="btn btn-ghost btn-sm" data-toggle="' + s.id + '">' +
                    (s.activo == 1 ? 'Desactivar' : 'Activar') + '</button> ' +
                '<button class="btn btn-ghost btn-sm" data-hist="' + s.id + '">Historial</button> ' +
                '<button class="btn btn-danger btn-sm" data-del="' + s.id + '">Eliminar</button>'
            ) : '-';

            const tr = document.createElement('tr');
            tr.dataset.source = s.id;
            tr.innerHTML =
                '<td class="muted">' + s.id + '</td>' +
                '<td>' + esc(s.nombre) + '</td>' +
                '<td>' + esc(s.tipo) + '</td>' +
                '<td>' + (s.dataset ? esc(s.dataset) : '<span class="muted">sin vincular</span>') + '</td>' +
                '<td>' + badgeEstado(s.activo) + '</td>' +
                '<td>' + badgeResultado(s.ultimo_estado) + '</td>' +
                '<td>' + (s.ultima_ejecucion ? fmtFecha(s.ultima_ejecucion) : '-') + '</td>' +
                '<td>' + s.corridas + '</td>' +
                '<td class="actions-cell">' + acciones + '</td>';
            tbody.appendChild(tr);
        });
    }

    async function cargar() {
        const json = await api('/api/integrations.php?action=list', {}, 'GET');
        tbody.innerHTML = '';
        if (!json.success) {
            tbody.innerHTML = '<tr><td colspan="9" class="muted center">' + esc(json.message) + '</td></tr>';
            return;
        }
        estado.sources  = json.data.sources;
        estado.datasets = json.data.datasets;
        pintarKpis(json.data.resumen);
        pintarTabla();
    }

    function abrirForm(fuente) {
        formFuente.reset();
        fError.classList.add('hidden');
        document.getElementById('fuente-id').value = '0';
        document.getElementById('timeout').value = '15';
        document.getElementById('activo').value = '1';
        llenarDatasetSelect('');

        if (fuente) {
            document.getElementById('fuente-title').textContent = 'Editar fuente';
            document.getElementById('fuente-id').value = fuente.id;
            document.getElementById('nombre').value = fuente.nombre;
            document.getElementById('descripcion').value = fuente.descripcion || '';
            document.getElementById('url').value = fuente.url;
            document.getElementById('timeout').value = fuente.timeout_seg;
            document.getElementById('activo').value = fuente.activo;
            llenarDatasetSelect(fuente.dataset_id || '');
        } else {
            document.getElementById('fuente-title').textContent = 'Nueva fuente';
        }
        modFuente.classList.remove('hidden');
    }

    async function abrirHistorial(id) {
        const s = estado.sources.find(x => String(x.id) === String(id));
        document.getElementById('hist-title').textContent = s ? s.nombre : '# ' + id;
        histTbody.innerHTML = '<tr><td colspan="6" class="muted center">Cargando...</td></tr>';
        modHist.classList.remove('hidden');

        const json = await api('/api/integrations.php?action=historial&source_id=' + id, {}, 'GET');
        histTbody.innerHTML = '';
        if (!json.success) {
            histTbody.innerHTML = '<tr><td colspan="6" class="muted center">' + esc(json.message) + '</td></tr>';
            return;
        }
        if (json.data.runs.length === 0) {
            histTbody.innerHTML = '<tr><td colspan="6" class="muted center">Sin ejecuciones registradas.</td></tr>';
            return;
        }
        json.data.runs.forEach(r => {
            const md = r.metadatos
                ? 'registros: ' + (r.metadatos.registros != null ? r.metadatos.registros : '-') +
                  (r.metadatos.campos && r.metadatos.campos.length ? ' | campos: ' + esc(r.metadatos.campos.join(', ')) : '')
                : '';
            const tr = document.createElement('tr');
            tr.innerHTML =
                '<td>' + fmtFecha(r.fecha) + '</td>' +
                '<td>' + esc(r.tipo_ejecucion === 'prueba' ? 'prueba' : 'sync') + '</td>' +
                '<td>' + badgeResultado(r.estado) + '</td>' +
                '<td>' + r.registros_leidos + '</td>' +
                '<td>' + r.duracion_ms + ' ms</td>' +
                '<td class="muted small">' + esc(r.mensaje || '') + (md ? '<br>' + md : '') + '</td>';
            histTbody.appendChild(tr);
        });
    }

    function actualizarFilaToggle(activo, sourceId) {
        const tr = tbody.querySelector('tr[data-source="' + sourceId + '"]');
        if (tr) {
            const celdas = tr.querySelectorAll('td');
            celdas[4].innerHTML = badgeEstado(activo);
            const btn = tr.querySelector('[data-toggle]');
            if (btn) btn.textContent = activo == 1 ? 'Desactivar' : 'Activar';
        }
        const src = estado.sources.find(x => String(x.id) === String(sourceId));
        if (src) {
            src.activo = activo;
            const r = estado.resumen;
            r.total = estado.sources.length;
            r.activas = estado.sources.filter(x => x.activo == 1).length;
            pintarKpis(r);
        }
    }

    if (btnNueva) btnNueva.addEventListener('click', () => abrirForm(null));

    document.querySelectorAll('[data-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById(btn.dataset.close).classList.add('hidden');
        });
    });
    [modFuente, modHist].forEach(m => {
        m.addEventListener('click', (e) => {
            if (e.target === m) m.classList.add('hidden');
        });
    });

    tbody.addEventListener('click', async (e) => {
        const btnProbar = e.target.closest('[data-probar]');
        const btnSync   = e.target.closest('[data-sync]');
        const btnEdit   = e.target.closest('[data-edit]');
        const btnTog    = e.target.closest('[data-toggle]');
        const btnHist   = e.target.closest('[data-hist]');
        const btnDel    = e.target.closest('[data-del]');

        if (btnProbar || btnSync) {
            const id = Number((btnProbar || btnSync).dataset[btnProbar ? 'probar' : 'sync']);
            btnProbar ? btnProbar.disabled = true : btnSync.disabled = true;
            const accion = btnProbar ? 'probar' : 'sincronizar';
            const json = await api('/api/integrations.php?action=' + accion, { id: id });
            mostrarMsg(String(json.message || (json.data && json.data.mensaje) || '').slice(0, 220), json.success ? 'success' : 'error');
            if (json.success) cargar();
        }
        if (btnEdit) {
            const s = estado.sources.find(x => String(x.id) === btnEdit.dataset.edit);
            if (s) abrirForm(s);
        }
        if (btnTog) {
            const json = await api('/api/integrations.php?action=toggle', { id: Number(btnTog.dataset.toggle) });
            if (json.success) {
                actualizarFilaToggle(json.data.activo, json.data.id);
                mostrarMsg(json.message, 'success');
            } else {
                mostrarMsg(json.message, 'error');
            }
        }
        if (btnHist) {
            abrirHistorial(btnHist.dataset.hist);
        }
        if (btnDel) {
            if (!confirm('¿Eliminar esta fuente externa y todo su historial de sincronizaciones?')) return;
            const json = await api('/api/integrations.php?action=delete', { id: Number(btnDel.dataset.del) });
            mostrarMsg(json.message, json.success ? 'success' : 'error');
            if (json.success) cargar();
        }
    });

    formFuente.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('fuente-id').value;
        const esCrear = !id || Number(id) <= 0;

        const data = {
            id: id,
            nombre: document.getElementById('nombre').value.trim(),
            descripcion: document.getElementById('descripcion').value.trim(),
            url: document.getElementById('url').value.trim(),
            dataset_id: document.getElementById('dataset_id').value,
            timeout: Number(document.getElementById('timeout').value),
            activo: Number(document.getElementById('activo').value),
        };

        const http = /^https?:\/\//i;
        if (!http.test(data.url)) {
            fError.textContent = 'La URL debe comenzar con http:// o https://.';
            fError.classList.remove('hidden');
            return;
        }
        if (!Number.isInteger(data.timeout) || data.timeout < 1 || data.timeout > 60) {
            fError.textContent = 'El timeout debe ser un entero entre 1 y 60 segundos.';
            fError.classList.remove('hidden');
            return;
        }
        if (data.dataset_id === '') {
            data.dataset_id = 0;
        } else {
            data.dataset_id = Number(data.dataset_id);
        }

        const json = await api('/api/integrations.php?action=' + (esCrear ? 'create' : 'update'), data);
        if (json.success) {
            modFuente.classList.add('hidden');
            mostrarMsg(json.message, 'success');
            cargar();
        } else {
            fError.textContent = json.message;
            fError.classList.remove('hidden');
        }
    });

    cargar();
});