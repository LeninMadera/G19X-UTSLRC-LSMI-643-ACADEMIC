/* ============================================================
   Politicas de gobierno de datos
   ============================================================ */

/* global api, esc, abrirModal, cerrarModal */

document.addEventListener('DOMContentLoaded', () => {
    const tbody      = document.getElementById('policies-tbody');
    const msg        = document.getElementById('msg');
    const modPolicy  = document.getElementById('modal-policy');
    const modAssign  = document.getElementById('modal-assign');
    const form       = document.getElementById('policy-form');
    const formError  = document.getElementById('policy-form-error');
    const btnNueva   = document.getElementById('btn-nueva');

    const estado = {
        policies: [], datasets: [], current: null, preview: new Set()
    };

    const rol = document.querySelector('.badge-role')?.textContent || '';
    const puedeEditar = ['admin', 'steward'].includes(rol);

    function mostrarMsg(texto, tipo = 'success') {
        msg.textContent = texto;
        msg.className = 'alert alert-' + tipo;
        setTimeout(() => msg.classList.add('hidden'), 4000);
    }

    /* Badge de estado por significado: "activa" es el estado normal de
       una politica y "inactiva" no es un error, asi que va en neutro. */
    function badgeEstado(est) {
        return '<span class="badge badge-' + (est === 'activa' ? 'ok' : 'neutral') + '">'
            + esc(est) + '</span>';
    }

    async function cargar() {
        const json = await api('/api/policies.php?action=list');
        tbody.innerHTML = '';
        if (!json.success) {
            tbody.innerHTML = '<tr><td colspan="6" class="muted center">' + esc(json.message) + '</td></tr>';
            return;
        }
        estado.policies = json.data.policies;
        estado.datasets = json.data.datasets;

        if (json.data.policies.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="muted center">Sin politicas registradas.</td></tr>';
        }
        json.data.policies.forEach(p => {
            const tr = document.createElement('tr');
            const acciones = puedeEditar ? (
                '<button class="btn btn-ghost btn-sm" data-edit="' + p.id + '">Editar</button> ' +
                '<button class="btn btn-ghost btn-sm" data-assign="' + p.id + '">Asignar</button> ' +
                '<button class="btn btn-danger btn-sm" data-del="' + p.id + '">Eliminar</button>'
            ) : '-';
            tr.innerHTML =
                '<td>' + esc(p.titulo) + '</td>' +
                '<td>' + esc(p.tipo) + '</td>' +
                '<td>' + badgeEstado(p.estado) + '</td>' +
                '<td>' + p.datasets_asignados + '</td>' +
                '<td>' + esc(p.creado_por || '-') + '</td>' +
                '<td class="actions-cell">' + acciones + '</td>';
            tbody.appendChild(tr);
        });
    }

    function llenarTipos(valor) {
        const sel = document.getElementById('tipo');
        sel.innerHTML = '';
        ['naming', 'retencion', 'acceso', 'clasificacion'].forEach(t => {
            const opt = document.createElement('option');
            opt.value = t;
            opt.textContent = t;
            if (valor && t === valor) opt.selected = true;
            sel.appendChild(opt);
        });
    }

    // Se llama abrirForm y no abrirModal para no tapar la funcion
    // centralizada de app.js, que se usa al final para mostrar el modal.
    function abrirForm(policy) {
        form.reset();
        formError.classList.add('hidden');
        llenarTipos(policy ? policy.tipo : null);

        if (policy) {
            document.getElementById('policy-title').textContent = 'Editar politica';
            document.getElementById('policy-id').value = policy.id;
            document.getElementById('titulo').value = policy.titulo;
            document.getElementById('descripcion').value = policy.descripcion || '';
            document.getElementById('estado').value = policy.estado;
        } else {
            document.getElementById('policy-title').textContent = 'Nueva politica';
            document.getElementById('estado').value = 'activa';
        }
        abrirModal(modPolicy);
    }

    function abrirAsignacion(policy) {
        estado.current = policy;
        estado.preview = new Set((policy.dataset_ids || []).map(Number));
        document.getElementById('assign-title').textContent = esc(policy.titulo);
        document.getElementById('assign-error').classList.add('hidden');

        const cont = document.getElementById('assign-datasets');
        cont.innerHTML = '';
        if (estado.datasets.length === 0) {
            cont.innerHTML = '<p class="muted">No hay datasets registrados.</p>';
        }
        estado.datasets.forEach(d => {
            const label = document.createElement('label');
            label.className = 'assign-item';
            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.value = d.id;
            cb.checked = estado.preview.has(d.id);
            cb.addEventListener('change', (e) => {
                if (e.target.checked) estado.preview.add(Number(d.id));
                else estado.preview.delete(Number(d.id));
            });
            const span = document.createElement('span');
            span.textContent = d.nombre;
            label.appendChild(cb);
            label.appendChild(span);
            cont.appendChild(label);
        });
        abrirModal(modAssign);
    }

    if (btnNueva) btnNueva.addEventListener('click', () => abrirForm(null));

    // El cierre (data-close, clic en el fondo, Escape, foco, trampa de
    // Tab y scroll de fondo) lo resuelve el delegado de app.js.

    tbody.addEventListener('click', async (e) => {
        const btnEdit   = e.target.closest('[data-edit]');
        const btnAssign = e.target.closest('[data-assign]');
        const btnDel    = e.target.closest('[data-del]');

        if (btnEdit) {
            const p = estado.policies.find(x => String(x.id) === btnEdit.dataset.edit);
            if (p) abrirForm(p);
        }
        if (btnAssign) {
            const p = estado.policies.find(x => String(x.id) === btnAssign.dataset.assign);
            if (p) abrirAsignacion(p);
        }
        if (btnDel) {
            if (!confirm('¿Eliminar esta politica? Se quita de todos los datasets.')) return;
            const json = await api('/api/policies.php?action=delete', { id: Number(btnDel.dataset.del) });
            mostrarMsg(json.message, json.success ? 'success' : 'error');
            if (json.success) cargar();
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('policy-id').value;
        const action = id && Number(id) > 0 ? 'update' : 'create';
        const data = new FormData(form);
        if (action === 'update') data.set('id', id);

        const json = await api('/api/policies.php?action=' + action, data);
        if (json.success) {
            cerrarModal(modPolicy);
            mostrarMsg(json.message, 'success');
            cargar();
        } else {
            formError.textContent = json.message;
            formError.classList.remove('hidden');
        }
    });

    // Guardar asignacion: compara el estado previo con los checkboxes
    document.getElementById('btn-guardar-asignacion').addEventListener('click', async () => {
        const prev = new Set((estado.current.dataset_ids || []).map(Number));
        const nuevos = [...estado.preview];
        const agrega = nuevos.filter(x => !prev.has(x));
        const quita  = [...prev].filter(x => !estado.preview.has(x));

        const errores = [];
        for (const did of agrega) {
            const r = await api('/api/policies.php?action=assign', { policy_id: estado.current.id, dataset_id: did });
            if (!r.success) errores.push(r.message);
        }
        for (const did of quita) {
            const r = await api('/api/policies.php?action=unassign', { policy_id: estado.current.id, dataset_id: did });
            if (!r.success) errores.push(r.message);
        }

        if (errores.length > 0) {
            document.getElementById('assign-error').textContent = errores.join(' ');
            document.getElementById('assign-error').classList.remove('hidden');
        } else {
            cerrarModal(modAssign);
            mostrarMsg('Asignacion actualizada.', 'success');
            cargar();
        }
    });

    cargar();
});