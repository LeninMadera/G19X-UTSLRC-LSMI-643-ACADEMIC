/* ============================================================
   Cumplimiento normativo: controles y evaluaciones
   ============================================================ */

/* global api, esc, abrirModal, cerrarModal, TONO_SEVERIDAD */

document.addEventListener('DOMContentLoaded', () => {
    const tbody      = document.getElementById('controls-tbody');
    const msg        = document.getElementById('msg');
    const modControl = document.getElementById('modal-control');
    const modEval    = document.getElementById('modal-eval');
    const formControl = document.getElementById('control-form');
    const formEval   = document.getElementById('eval-form');
    const cError     = document.getElementById('control-form-error');
    const eError     = document.getElementById('eval-form-error');
    const btnNuevo   = document.getElementById('btn-nuevo');
    const filtroSev  = document.getElementById('filtro-severidad');
    const filtroEst  = document.getElementById('filtro-estado');

    const estado = {
        controles: [], datasets: [], policies: []
    };

    const rol = document.querySelector('.badge-role')?.textContent || '';
    const puedeEditar = ['admin', 'steward'].includes(rol);

    function mostrarMsg(texto, tipo = 'success') {
        msg.textContent = texto;
        msg.className = 'alert alert-' + tipo;
        setTimeout(() => msg.classList.add('hidden'), 4000);
    }

    function badgeSeveridad(sev) {
        const clave = String(sev || '').toLowerCase();
        return '<span class="badge badge-' + (TONO_SEVERIDAD[clave] || 'neutral') + '">' + esc(sev) + '</span>';
    }

    function badgeEstado(est) {
        return '<span class="badge badge-' + (est === 'activo' ? 'ok' : 'neutral') + '">' + esc(est) + '</span>';
    }

    function llenarSeveridades(valor) {
        const sel = document.getElementById('severidad');
        sel.value = valor || 'media';
    }

    async function cargar() {
        const json = await api('/api/compliance.php?action=list');
        tbody.innerHTML = '';
        if (!json.success) {
            tbody.innerHTML = '<tr><td colspan="8" class="muted center">' + esc(json.message) + '</td></tr>';
            return;
        }
        estado.controles = json.data.controles;
        estado.datasets  = json.data.datasets;
        estado.policies  = json.data.policies;
        pintarKpis(json.data.resumen);
        pintarResumen(json.data.resumen);
        llenarSelects();
        pintarTabla();
    }

    function pintarKpis(r) {
        document.getElementById('kpi-controles').textContent    = r.total_controles;
        document.getElementById('kpi-activos').textContent      = r.activos;
        document.getElementById('kpi-criticas').textContent     = r.criticas;
        document.getElementById('kpi-evaluaciones').textContent = r.evaluaciones;
        document.getElementById('kpi-cumplimiento').textContent =
            r.cumplimiento_pct != null ? r.cumplimiento_pct + '%' : '--';
    }

    /* Chip de resumen: cumplimiento global de los controles evaluados. */
    function pintarResumen(r) {
        const pct = r.cumplimiento_pct;
        const pill = document.getElementById('comp-resumen');
        if (pct == null) {
            pill.className = 'pill-summary is-neutral';
            pill.innerHTML = '<span class="dot"></span>Sin evaluaciones todavia';
            return;
        }
        const tono = pct >= 85 ? 'is-ok' : pct >= 70 ? 'is-warn' : 'is-error';
        pill.className = 'pill-summary ' + tono;
        pill.innerHTML = '<span class="dot"></span>' + esc(
            r.criticas > 0
                ? pct + '% de cumplimiento con ' + r.criticas + ' control(es) criticos o de alta'
                : pct + '% de cumplimiento global'
        );
    }

    function llenarSelects() {
        const politicas = document.getElementById('policy_id');
        politicas.innerHTML = '<option value="">Sin politica</option>';
        estado.policies.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = p.titulo;
            politicas.appendChild(opt);
        });

        const datasets = document.getElementById('eval-dataset');
        datasets.innerHTML = '<option value="">Selecciona un dataset</option>';
        estado.datasets.forEach(d => {
            const opt = document.createElement('option');
            opt.value = d.id;
            opt.textContent = d.nombre;
            datasets.appendChild(opt);
        });
    }

    function pintarTabla() {
        const sev = filtroSev.value;
        const est = filtroEst.value;

        tbody.innerHTML = '';
        const filtrados = estado.controles.filter(c =>
            (!sev || c.severidad === sev) && (!est || c.estado === est)
        );

        if (filtrados.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="muted center">Sin controles para los filtros elegidos.</td></tr>';
            document.getElementById('comp-tabla-info').textContent = estado.controles.length > 0
                ? 'Ajusta los filtros para ver los ' + estado.controles.length + ' controles registrados.'
                : 'Aun no hay controles de cumplimiento registrados.';
            return;
        }
        filtrados.forEach(c => {
            const acciones = puedeEditar ? (
                '<button class="btn btn-ghost btn-sm" data-edit="' + c.id + '">Editar</button> ' +
                '<button class="btn btn-ghost btn-sm" data-eval="' + c.id + '">Evaluar</button> ' +
                '<button class="btn btn-danger btn-sm" data-del="' + c.id + '">Eliminar</button>'
            ) : '-';
            const tr = document.createElement('tr');
            tr.innerHTML =
                '<td class="col-strong">' + esc(c.nombre) + '</td>' +
                '<td class="col-soft">' + esc(c.referencia || '-') + '</td>' +
                '<td>' + badgeSeveridad(c.severidad) + '</td>' +
                '<td>' + badgeEstado(c.estado) + '</td>' +
                '<td class="col-soft">' + esc(c.policy_titulo || '-') + '</td>' +
                '<td class="col-num">' + c.evaluaciones + '</td>' +
                '<td class="col-soft">' + esc(c.creado_por || '-') + '</td>' +
                '<td class="actions-cell col-acciones">' + acciones + '</td>';
            tbody.appendChild(tr);
        });
        document.getElementById('comp-tabla-info').textContent =
            'Mostrando ' + filtrados.length + ' de ' + estado.controles.length + ' controles.';
    }

    filtroSev.addEventListener('change', pintarTabla);
    filtroEst.addEventListener('change', pintarTabla);

    function abrirControl(control) {
        formControl.reset();
        cError.classList.add('hidden');
        llenarSeveridades(null);

        if (control) {
            document.getElementById('control-title').textContent = 'Editar control';
            document.getElementById('control-id').value = control.id;
            document.getElementById('nombre').value = control.nombre;
            document.getElementById('descripcion').value = control.descripcion || '';
            document.getElementById('referencia').value = control.referencia || '';
            document.getElementById('severidad').value = control.severidad;
            document.getElementById('estado').value = control.estado;
            document.getElementById('policy_id').value = control.policy_id || '';
        } else {
            document.getElementById('control-title').textContent = 'Nuevo control';
            document.getElementById('severidad').value = 'media';
            document.getElementById('estado').value = 'activo';
            document.getElementById('policy_id').value = '';
        }
        abrirModal(modControl);
    }

    function abrirEval(control) {
        formEval.reset();
        eError.classList.add('hidden');
        document.getElementById('eval-title').textContent = esc(control.nombre);
        document.getElementById('eval-control').value = control.id;
        document.getElementById('eval-estado').value = 'cumple';
        abrirModal(modEval);
    }

    if (btnNuevo) btnNuevo.addEventListener('click', () => abrirControl(null));

    // El cierre (data-close, clic en el fondo, Escape, foco, trampa de
    // Tab y scroll de fondo) lo resuelve el delegado de app.js.

    tbody.addEventListener('click', async (e) => {
        const btnEdit = e.target.closest('[data-edit]');
        const btnEval = e.target.closest('[data-eval]');
        const btnDel  = e.target.closest('[data-del]');

        if (btnEdit) {
            const c = estado.controles.find(x => String(x.id) === btnEdit.dataset.edit);
            if (c) abrirControl(c);
        }
        if (btnEval) {
            const c = estado.controles.find(x => String(x.id) === btnEval.dataset.eval);
            if (c) abrirEval(c);
        }
        if (btnDel) {
            if (!confirm('¿Eliminar este control? Se quita de todas las evaluaciones del catalogo.')) return;
            const json = await api('/api/compliance.php?action=delete', { id: Number(btnDel.dataset.del) });
            mostrarMsg(json.message, json.success ? 'success' : 'error');
            if (json.success) cargar();
        }
    });

    formControl.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('control-id').value;
        const action = id && Number(id) > 0 ? 'update' : 'create';
        const data = new FormData(formControl);
        if (action === 'update') data.set('id', id);

        const json = await api('/api/compliance.php?action=' + action, data);
        if (json.success) {
            cerrarModal(modControl);
            mostrarMsg(json.message, 'success');
            cargar();
        } else {
            cError.textContent = json.message;
            cError.classList.remove('hidden');
        }
    });

    formEval.addEventListener('submit', async (e) => {
        e.preventDefault();
        const json = await api('/api/compliance.php?action=evaluate', new FormData(formEval));
        if (json.success) {
            cerrarModal(modEval);
            mostrarMsg(json.message, 'success');
            cargar();
        } else {
            eError.textContent = json.message;
            eError.classList.remove('hidden');
        }
    });

    cargar();
});