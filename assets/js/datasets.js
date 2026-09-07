/* ============================================================
   Catalogo de datasets
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
    const tbody      = document.getElementById('datasets-tbody');
    const msg        = document.getElementById('msg');
    const buscar     = document.getElementById('buscar');
    const filtroC    = document.getElementById('filtro-categoria');
    const backdrop   = document.getElementById('modal-backdrop');
    const form       = document.getElementById('dataset-form');
    const formError  = document.getElementById('form-error');
    const btnNuevo   = document.getElementById('btn-nuevo');
    const btnCancel  = document.getElementById('btn-cancelar');

    const datos = {
        categorias: [], origenes: [], owners: [], editar: null
    };
    const ORIGENES = ['Base de datos', 'Archivo', 'API', 'CRM', 'SQL Server', 'CSV', 'Otro'];
    let rol = document.querySelector('.badge-role')?.textContent || '';
    let userId = null;

    function esc(t) {
        const div = document.createElement('div');
        div.textContent = t == null ? '' : String(t);
        return div.innerHTML;
    }

    function mostrarMsg(texto, tipo = 'success') {
        msg.textContent = texto;
        msg.className = 'alert alert-' + tipo;
        setTimeout(() => msg.classList.add('hidden'), 4000);
    }

    function renderCategoriaSelect() {
        const sel = document.getElementById('categoria');
        sel.innerHTML = '';
        datos.categorias.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c;
            opt.textContent = c;
            sel.appendChild(opt);
        });
    }

    function renderOrigenSelect() {
        const sel = document.getElementById('origen');
        sel.innerHTML = '';
        ORIGENES.forEach(o => {
            const opt = document.createElement('option');
            opt.value = o;
            opt.textContent = o;
            sel.appendChild(opt);
        });
    }

    async function cargar() {
        const params = new URLSearchParams({ action: 'list' });
        if (buscar.value.trim()) params.set('search', buscar.value.trim());
        if (filtroC.value) params.set('categoria', filtroC.value);

        const json = await api('/api/datasets.php?' + params.toString());
        tbody.innerHTML = '';
        if (!json.success) {
            tbody.innerHTML = '<tr><td colspan="7" class="muted center">' + esc(json.message) + '</td></tr>';
            return;
        }

        datos.categorias = json.data.categorias;
        datos.owners = json.data.owners;

        // Llenar select de categorias del filtro una sola vez
        if (filtroC.options.length <= 1) {
            json.data.categorias.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c;
                opt.textContent = c;
                filtroC.appendChild(opt);
            });
        }

        if (json.data.datasets.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="muted center">Sin datasets registrados.</td></tr>';
            return;
        }

        json.data.datasets.forEach(d => {
            const score = d.ultimo_score == null ? '--' : parseFloat(d.ultimo_score).toFixed(1);
            const cls = d.ultimo_score == null ? '' :
                (d.ultimo_score >= 85 ? 'score-good' : d.ultimo_score >= 70 ? 'score-warn' : 'score-bad');

            const tr = document.createElement('tr');
            tr.innerHTML =
                '<td><a href="dataset_detail.php?id=' + d.id + '">' + esc(d.nombre) + '</a></td>' +
                '<td>' + esc(d.categoria) + '</td>' +
                '<td>' + esc(d.origen) + '</td>' +
                '<td>' + esc(d.owner_name) + '</td>' +
                '<td><span class="badge badge-' + (d.activo == 1 ? 'active' : 'inactive') + '">' +
                    (d.activo == 1 ? 'activo' : 'inactivo') + '</span></td>' +
                '<td class="score-badge ' + cls + '">' + score + '</td>' +
                '<td><a class="btn btn-ghost btn-sm" href="dataset_detail.php?id=' + d.id + '">Ver</a> ' +
                    '<button class="btn btn-ghost btn-sm" data-edit="' + d.id + '">Editar</button> ' +
                    '<button class="btn btn-danger btn-sm hidden" data-del="' + d.id + '">Eliminar</button></td>';
            tbody.appendChild(tr);

            // Botones segun rol: eliminar solo admin/steward
            const btnDel = tr.querySelector('[data-del]');
            if (['admin', 'steward'].includes(rol)) btnDel.classList.remove('hidden');
        });
    }

    function abrirModal(dataset) {
        form.reset();
        formError.classList.add('hidden');
        renderCategoriaSelect();
        renderOrigenSelect();
        datos.editar = dataset;

        const ownerSel = document.getElementById('owner_id');
        ownerSel.innerHTML = '';
        datos.owners.forEach(o => {
            const opt = document.createElement('option');
            opt.value = o.id;
            opt.textContent = o.nombre + ' (' + o.rol + ')';
            ownerSel.appendChild(opt);
        });

        if (dataset) {
            document.getElementById('modal-title').textContent = 'Editar dataset';
            document.getElementById('dataset-id').value = dataset.id;
            document.getElementById('nombre').value = dataset.nombre;
            document.getElementById('descripcion').value = dataset.descripcion || '';
            document.getElementById('categoria').value = dataset.categoria;
            document.getElementById('origen').value = dataset.origen;
            document.getElementById('owner_id').value = dataset.owner_id;
            document.getElementById('activo').checked = dataset.activo == 1;
        } else {
            document.getElementById('modal-title').textContent = 'Registrar dataset';
        }

        // Rol 'owner': solo datasets propios, sin elegir dueño
        if (rol === 'owner') {
            document.getElementById('owner-field').classList.add('hidden');
        } else {
            document.getElementById('owner-field').classList.remove('hidden');
        }
        backdrop.classList.remove('hidden');
    }

    btnNuevo.addEventListener('click', () => abrirModal(null));
    btnCancel.addEventListener('click', () => backdrop.classList.add('hidden'));
    backdrop.addEventListener('click', (e) => {
        if (e.target === backdrop) backdrop.classList.add('hidden');
    });

    tbody.addEventListener('click', async (e) => {
        const btnEdit = e.target.closest('[data-edit]');
        const btnDel  = e.target.closest('[data-del]');

        if (btnEdit) {
            const json = await api('/api/datasets.php?action=detail&id=' + btnEdit.dataset.edit);
            if (json.success) abrirModal(json.data);
        }
        if (btnDel) {
            if (!confirm('¿Eliminar este dataset y su historial?')) return;
            const json = await api('/api/datasets.php?action=delete', { id: Number(btnDel.dataset.del) });
            mostrarMsg(json.message, json.success ? 'success' : 'error');
            if (json.success) cargar();
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('dataset-id').value;
        const action = id && Number(id) > 0 ? 'update' : 'create';
        const data = new FormData(form);
        data.set('activo', document.getElementById('activo').checked ? '1' : '0');
        if (action === 'update') data.set('id', id);

        const json = await api('/api/datasets.php?action=' + action, data);
        if (json.success) {
            backdrop.classList.add('hidden');
            mostrarMsg(json.message, 'success');
            cargar();
        } else {
            formError.textContent = json.message;
            formError.classList.remove('hidden');
        }
    });

    let debounceT;
    buscar.addEventListener('input', () => {
        clearTimeout(debounceT);
        debounceT = setTimeout(cargar, 300);
    });
    filtroC.addEventListener('change', cargar);

    // Si se llego desde la ficha con ?editar=N, abrir el modal de edicion
    const editParam = new URLSearchParams(window.location.search).get('editar');
    if (editParam) {
        (async () => {
            const json = await api('/api/datasets.php?action=detail&id=' + editParam);
            if (json.success) {
                await cargar();
                abrirModal(json.data);
            }
        })();
    } else {
        cargar();
    }
});