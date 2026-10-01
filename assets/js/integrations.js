/* ============================================================
   Fuentes externas / Integraciones: fuentes y sincronizaciones
   ============================================================ */

/* global api, esc, fmtFecha, abrirModal, cerrarModal */

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
            ? '<span class="badge badge-ok">activo</span>'
            : '<span class="badge badge-neutral">inactivo</span>';
    }

    function badgeResultado(est) {
        if (!est) return '<span class="muted">--</span>';
        return est === 'ok'
            ? '<span class="badge badge-ok">ok</span>'
            : '<span class="badge badge-error">error</span>';
    }

    function pintarKpis(r) {
        estado.resumen = r;
        document.getElementById('kpi-activas').textContent = r.activas;
        document.getElementById('kpi-total').textContent = r.total;
        document.getElementById('kpi-ok').textContent = r.ultimas_ok;
        document.getElementById('kpi-registros').textContent = r.registros_leidos;

        const pill = document.getElementById('int-resumen');
        const inactivas = Number(r.total) - Number(r.activas);
        pill.className = 'pill-summary ' + (inactivas > 0 ? 'is-warn' : 'is-ok');
        pill.innerHTML = '<span class="dot"></span>' + esc(
            r.activas + ' de ' + r.total + ' fuentes activas'
            + (inactivas > 0 ? ' y ' + inactivas + ' inactivas' : '')
        );
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
            tbody.innerHTML = '<tr><td colspan="8" class="muted center">Sin fuentes externas registradas.</td></tr>';
            document.getElementById('int-tabla-info').textContent = 'Registra una API REST para empezar a leer datos externos.';
            return;
        }
        estado.sources.forEach(s => {
            const gestiona = s.puede_gestionar === true || s.puede_gestionar == 1;
            // Los botones van dentro de .actions-stack: es solo maquetacion
            // (flex-wrap) para que la celda no se corte ni se salga del panel.
            // Los atributos data-* y el resto de la accion no cambian.
            const acciones = gestiona ? (
                '<div class="actions-stack">' +
                '<button class="btn btn-ghost btn-sm" data-probar="' + s.id + '">Probar conexion</button>' +
                '<button class="btn btn-ghost btn-sm" data-sync="' + s.id + '">Sincronizar</button>' +
                '<button class="btn btn-ghost btn-sm" data-edit="' + s.id + '">Editar</button>' +
                '<button class="btn btn-ghost btn-sm" data-toggle="' + s.id + '">' +
                    (s.activo == 1 ? 'Desactivar' : 'Activar') + '</button>' +
                '<button class="btn btn-ghost btn-sm" data-hist="' + s.id + '">Historial</button>' +
                '<button class="btn btn-danger btn-sm" data-del="' + s.id + '">Eliminar</button>' +
                '</div>'
            ) : '-';

            const tr = document.createElement('tr');
            tr.dataset.source = s.id;
            tr.innerHTML =
                '<td class="col-strong">' + esc(s.nombre) + '</td>' +
                '<td><span class="badge badge-info">' + esc(s.tipo) + '</span></td>' +
                '<td class="col-soft">' + (s.dataset ? esc(s.dataset) : '<span class="muted">sin vincular</span>') + '</td>' +
                '<td class="col-estado">' + badgeEstado(s.activo) + '</td>' +
                '<td>' + badgeResultado(s.ultimo_estado) + '</td>' +
                '<td class="col-date">' + (s.ultima_ejecucion ? fmtFecha(s.ultima_ejecucion) : '<span class="muted">-</span>') + '</td>' +
                '<td class="col-num">' + s.corridas + '</td>' +
                '<td class="actions-cell actions-cell-stack col-acciones">' + acciones + '</td>';
            tbody.appendChild(tr);
        });

        const conError = estado.sources.filter(s => s.ultimo_estado && s.ultimo_estado !== 'ok').length;
        document.getElementById('int-tabla-info').textContent =
            estado.sources.length + ' fuentes, ' + conError + ' con ultimo resultado distinto de ok.';
    }

    async function cargar() {
        const json = await api('/api/integrations.php?action=list', {}, 'GET');
        tbody.innerHTML = '';
        if (!json.success) {
            tbody.innerHTML = '<tr><td colspan="8" class="muted center">' + esc(json.message) + '</td></tr>';
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
        abrirModal(modFuente);
    }

    async function abrirHistorial(id) {
        const s = estado.sources.find(x => String(x.id) === String(id));
        document.getElementById('hist-title').textContent = s ? s.nombre : '# ' + id;
        histTbody.innerHTML = '<tr><td colspan="6" class="muted center">Cargando...</td></tr>';
        abrirModal(modHist);

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
                '<td class="col-date">' + fmtFecha(r.fecha) + '</td>' +
                '<td><span class="badge badge-info">' + esc(r.tipo_ejecucion === 'prueba' ? 'prueba' : 'sync') + '</span></td>' +
                '<td>' + badgeResultado(r.estado) + '</td>' +
                '<td class="col-num">' + r.registros_leidos + '</td>' +
                '<td class="col-num">' + r.duracion_ms + ' ms</td>' +
                '<td class="col-soft">' + esc(r.mensaje || '') + (md ? '<br>' + md : '') + '</td>';
            histTbody.appendChild(tr);
        });
    }

    function actualizarFilaToggle(activo, sourceId) {
        const tr = tbody.querySelector('tr[data-source="' + sourceId + '"]');
        if (tr) {
            const celda = tr.querySelector('.col-estado');
            if (celda) celda.innerHTML = badgeEstado(activo);
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

    // El cierre (data-close, clic en el fondo, Escape, foco, trampa de
    // Tab y scroll de fondo) lo resuelve el delegado de app.js.

    tbody.addEventListener('click', async (e) => {
        const btnProbar = e.target.closest('[data-probar]');
        const btnSync   = e.target.closest('[data-sync]');
        const btnEdit   = e.target.closest('[data-edit]');
        const btnTog    = e.target.closest('[data-toggle]');
        const btnHist   = e.target.closest('[data-hist]');
        const btnDel    = e.target.closest('[data-del]');

        if (btnProbar || btnSync) {
            const btn = btnProbar || btnSync;
            const id = Number(btn.dataset[btnProbar ? 'probar' : 'sync']);
            btn.disabled = true;
            try {
                const accion = btnProbar ? 'probar' : 'sincronizar';
                const json = await api('/api/integrations.php?action=' + accion, { id: id });
                mostrarMsg(String(json.message || (json.data && json.data.mensaje) || '').slice(0, 220), json.success ? 'success' : 'error');
                if (json.success) cargar();
            } finally {
                // Igual que en "Monitorear ahora": si falla, la tabla no se
                // re-renderiza y el boton quedaria muerto en el DOM.
                btn.disabled = false;
            }
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
            cerrarModal(modFuente);
            mostrarMsg(json.message, 'success');
            cargar();
        } else {
            fError.textContent = json.message;
            fError.classList.remove('hidden');
        }
    });

    cargar();
});