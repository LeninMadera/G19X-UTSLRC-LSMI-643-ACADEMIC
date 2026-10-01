/* ============================================================
   Modulo de linaje de datos
   ============================================================ */

/* global api, esc, fmtFecha, abrirModal, cerrarModal, APP_USER */

document.addEventListener('DOMContentLoaded', () => {
    const tbody     = document.getElementById('lineage-tbody');
    const msg       = document.getElementById('msg');
    const selFiltro = document.getElementById('sel-dataset');
    const modLineage = document.getElementById('modal-lineage');
    const form      = document.getElementById('lineage-form');
    const formError = document.getElementById('lineage-form-error');
    const btnNueva  = document.getElementById('btn-nueva');
    const selOrigen  = document.getElementById('origen_id');
    const selDestino = document.getElementById('destino_id');
    const selTipo    = document.getElementById('tipo');

    const estado = { relaciones: [], datasets: [], tipos: [] };

    // El servidor valida los permisos; aqui solo se oculta lo que
    // el rol no puede usar. admin y steward gestionan todas las
    // relaciones, owner solo las de sus datasets, analyst ninguna.
    const rol = (window.APP_USER && window.APP_USER.rol) || '';
    const puedeGestionar = ['admin', 'steward', 'owner'].includes(rol);
    const urlDetalle = (id) => 'dataset_detail.php?id=' + id;

    function mostrarMsg(texto, tipo = 'success') {
        msg.textContent = texto;
        msg.className = 'alert alert-' + tipo;
        setTimeout(() => msg.classList.add('hidden'), 4000);
    }

    function llenarDatasets() {
        // Un mismo <select> no puede tener selected en varias opciones:
        // se reconstruyen por completo
        selFiltro.innerHTML = '';
        const optTodos = document.createElement('option');
        optTodos.value = '';
        optTodos.textContent = 'Todos los datasets';
        selFiltro.appendChild(optTodos);

        [selOrigen, selDestino].forEach(sel => sel.innerHTML = '');

        estado.datasets.forEach(d => {
            [selFiltro, selOrigen, selDestino].forEach(sel => {
                const opt = document.createElement('option');
                opt.value = d.id;
                opt.textContent = d.nombre;
                sel.appendChild(opt);
            });
        });
    }

    function llenarTipos(valor) {
        selTipo.innerHTML = '';
        estado.tipos.forEach(t => {
            const opt = document.createElement('option');
            opt.value = t;
            opt.textContent = t;
            if (valor && t === valor) opt.selected = true;
            selTipo.appendChild(opt);
        });
    }

    // Se llama abrirForm y no abrirModal para no tapar la funcion
    // centralizada de app.js, que se usa al final para mostrar el modal.
    function abrirForm(relacion) {
        form.reset();
        formError.classList.add('hidden');
        llenarTipos(relacion ? relacion.tipo : null);

        if (relacion) {
            document.getElementById('lineage-title').textContent = 'Editar relacion de linaje';
            document.getElementById('lineage-id').value = relacion.id;
            document.getElementById('descripcion').value = relacion.descripcion || '';

            // Origen y destino no se modifican: solo el tipo y la descripcion
            selOrigen.value = relacion.origen_id;
            selDestino.value = relacion.destino_id;
            selOrigen.disabled = true;
            selDestino.disabled = true;
            // El cursor de "no permitido" parece un problema de permisos cuando
            // en realidad es que el campo no es editable en este modo.
            document.getElementById('lineage-inmutable').classList.remove('hidden');
        } else {
            document.getElementById('lineage-title').textContent = 'Nueva relacion de linaje';
            selOrigen.disabled = false;
            selDestino.disabled = false;
            document.getElementById('lineage-inmutable').classList.add('hidden');
        }
        abrirModal(modLineage);
    }

    async function cargar() {
        const params = new URLSearchParams({ action: 'list' });
        if (selFiltro.value) params.set('dataset_id', selFiltro.value);

        const json = await api('/api/lineage.php?' + params.toString());
        tbody.innerHTML = '';
        if (!json.success) {
            tbody.innerHTML = '<tr><td colspan="8" class="muted center">' + esc(json.message) + '</td></tr>';
            return;
        }

        const primeraCarga = estado.datasets.length === 0;
        estado.relaciones = json.data.relaciones;
        estado.datasets = json.data.datasets;
        estado.tipos = json.data.tipos;

        if (primeraCarga) {
            llenarDatasets();
        }

        if (estado.relaciones.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="muted center">Sin relaciones registradas.</td></tr>';
            return;
        }

        estado.relaciones.forEach(r => {
            const tr = document.createElement('tr');
            tr.innerHTML =
                '<td><a href="' + urlDetalle(r.origen_id) + '">' + esc(r.origen_nombre) + '</a></td>' +
                '<td class="center lineage-arrow">&rarr;</td>' +
                '<td><a href="' + urlDetalle(r.destino_id) + '">' + esc(r.destino_nombre) + '</a></td>' +
                '<td><span class="tag">' + esc(r.tipo) + '</span></td>' +
                '<td>' + esc(r.descripcion || '-') + '</td>' +
                '<td>' + esc(r.creado_por_nombre || '-') + '</td>' +
                '<td>' + esc(fmtFecha(r.fecha_creacion)) + '</td>' +
                '<td class="actions-cell col-acciones">' + acciones(r) + '</td>';
            tbody.appendChild(tr);
        });
    }

    function acciones(r) {
        if (!puedeGestionar) return '-';
        return '<button class="btn btn-ghost btn-sm" data-edit="' + r.id + '">Editar</button> ' +
            '<button class="btn btn-danger btn-sm" data-del="' + r.id + '">Eliminar</button>';
    }

    if (btnNueva) btnNueva.addEventListener('click', () => abrirForm(null));

    // El cierre (data-close, clic en el fondo, Escape, foco, trampa de
    // Tab y scroll de fondo) lo resuelve el delegado de app.js.

    selFiltro.addEventListener('change', cargar);

    tbody.addEventListener('click', async (e) => {
        const btnEdit = e.target.closest('[data-edit]');
        const btnDel  = e.target.closest('[data-del]');

        if (btnEdit) {
            const r = estado.relaciones.find(x => String(x.id) === btnEdit.dataset.edit);
            if (r) abrirForm(r);
        }
        if (btnDel) {
            if (!confirm('¿Eliminar esta relacion de linaje?')) return;
            const json = await api('/api/lineage.php?action=delete', { id: Number(btnDel.dataset.del) });
            mostrarMsg(json.message, json.success ? 'success' : 'error');
            if (json.success) cargar();
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        formError.classList.add('hidden');

        const id = document.getElementById('lineage-id').value;
        const action = id && Number(id) > 0 ? 'update' : 'create';
        const data = new FormData(form);

        // Un select deshabilitado no viaja en el FormData
        if (action === 'create') {
            data.set('origen_id', selOrigen.value);
            data.set('destino_id', selDestino.value);
        } else {
            data.set('id', id);
        }

        const json = await api('/api/lineage.php?action=' + action, data);
        if (json.success) {
            cerrarModal(modLineage);
            mostrarMsg(json.message, 'success');
            cargar();
        } else {
            formError.textContent = json.message;
            formError.classList.remove('hidden');
        }
    });

    cargar();
});
