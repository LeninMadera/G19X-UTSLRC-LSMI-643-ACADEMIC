/* ============================================================
   Cumplimiento normativo: controles y evaluaciones
   ============================================================ */

/* global api, esc */

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
        const cls = (sev === 'critica' || sev === 'alta') ? 'inactive'
            : (sev === 'media' ? 'pending' : 'active');
        return '<span class="badge badge-' + cls + '">' + esc(sev) + '</span>';
    }

    function badgeEstado(est) {
        const cls = est === 'activo' ? 'active' : 'inactive';
        return '<span class="badge badge-' + cls + '">' + esc(est) + '</span>';
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
                '<td>' + esc(c.nombre) + '</td>' +
                '<td>' + esc(c.referencia || '-') + '</td>' +
                '<td>' + badgeSeveridad(c.severidad) + '</td>' +
                '<td>' + badgeEstado(c.estado) + '</td>' +
                '<td>' + esc(c.policy_titulo || '-') + '</td>' +
                '<td>' + c.evaluaciones + '</td>' +
                '<td>' + esc(c.creado_por || '-') + '</td>' +
                '<td class="actions-cell">' + acciones + '</td>';
            tbody.appendChild(tr);
        });
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
        modControl.classList.remove('hidden');
    }

    function abrirEval(control) {
        formEval.reset();
        eError.classList.add('hidden');
        document.getElementById('eval-title').textContent = esc(control.nombre);
        document.getElementById('eval-control').value = control.id;
        document.getElementById('eval-estado').value = 'cumple';
        modEval.classList.remove('hidden');
    }

    if (btnNuevo) btnNuevo.addEventListener('click', () => abrirControl(null));

    document.querySelectorAll('[data-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById(btn.dataset.close).classList.add('hidden');
        });
    });
    [modControl, modEval].forEach(m => {
        m.addEventListener('click', (e) => {
            if (e.target === m) m.classList.add('hidden');
        });
    });

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
            modControl.classList.add('hidden');
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
            modEval.classList.add('hidden');
            mostrarMsg(json.message, 'success');
            cargar();
        } else {
            eError.textContent = json.message;
            eError.classList.remove('hidden');
        }
    });

    cargar();
});