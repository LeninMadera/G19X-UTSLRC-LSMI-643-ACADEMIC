/* ============================================================
   Catalogo de datasets
   ============================================================ */

/* global api, esc, APP_USER */

document.addEventListener('DOMContentLoaded', () => {
    const tbody      = document.getElementById('datasets-tbody');
    const msg        = document.getElementById('msg');
    const buscar     = document.getElementById('buscar');
    const btnBuscarIA= document.getElementById('btn-buscar-ia');
    const infoIA     = document.getElementById('busqueda-ia-info');
    const terminosIA = document.getElementById('terminos-ia');
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
    const CLASIF_BADGE = { publico: 'active', interno: 'pending', confidencial: 'inactive', restringido: 'inactive' };
    let rol = document.querySelector('.badge-role')?.textContent || '';
    let userId = (window.APP_USER && window.APP_USER.id) || null;
    // true mientras la tabla muestre resultados de busqueda semantica
    let modoIA = false;

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

    /**
     * Dibuja las filas del catalogo. Con semantico=true anade la
     * relevancia y los terminos por los que cada dataset coincidio.
     */
    function pintarFilas(lista, semantico) {
        tbody.innerHTML = '';
        if (lista.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="muted center">'
                + (semantico ? 'Sin coincidencias para esta busqueda.' : 'Sin datasets registrados.')
                + '</td></tr>';
            return;
        }

        lista.forEach(d => {
            const score = d.ultimo_score == null ? '--' : parseFloat(d.ultimo_score).toFixed(1);
            const cls = d.ultimo_score == null ? '' :
                (d.ultimo_score >= 85 ? 'score-good' : d.ultimo_score >= 70 ? 'score-warn' : 'score-bad');
            const esPropio = userId && Number(d.owner_id) === Number(userId);
            const puedeEditar = !(rol === 'owner' && !esPropio);
            const clasificacion = d.clasificacion || 'publico';

            // Extras de la busqueda semantica: relevancia y motivos del match
            let extra = '';
            if (semantico) {
                const rel = Number(d.relevancia || 0);
                const motivos = Array.isArray(d.motivos) && d.motivos.length
                    ? d.motivos.join(', ')
                    : 'sin terminos coincidentes';
                extra = '<div class="tag" title="Puntos: ' + Number(d.puntos || 0) + '">'
                          + 'relevancia ' + rel + '%</div>'
                      + '<div class="muted small">coincide con: ' + esc(motivos) + '</div>';
            }

            const tr = document.createElement('tr');
            tr.innerHTML =
                '<td><a href="dataset_detail.php?id=' + d.id + '">' + esc(d.nombre) + '</a>' + extra + '</td>' +
                '<td>' + esc(d.categoria) + '</td>' +
                '<td>' + esc(d.origen) + '</td>' +
                '<td><span class="badge badge-' + (CLASIF_BADGE[clasificacion] || 'active') + '">' + esc(clasificacion) + '</span></td>' +
                '<td>' + esc(d.owner_name) + '</td>' +
                '<td><span class="badge badge-' + (d.activo == 1 ? 'active' : 'inactive') + '">' +
                    (d.activo == 1 ? 'activo' : 'inactivo') + '</span></td>' +
                '<td class="score-badge ' + cls + '">' + score + '</td>' +
                '<td><a class="btn btn-ghost btn-sm" href="dataset_detail.php?id=' + d.id + '">Ver</a> ' +
                    (puedeEditar ? '<button class="btn btn-ghost btn-sm" data-edit="' + d.id + '">Editar</button> ' : '') +
                    '<button class="btn btn-danger btn-sm hidden" data-del="' + d.id + '">Eliminar</button></td>';
            tbody.appendChild(tr);

            // Botones segun rol: eliminar solo admin/steward
            const btnDel = tr.querySelector('[data-del]');
            if (['admin', 'steward'].includes(rol)) btnDel.classList.remove('hidden');
        });
    }

    /** Llena el filtro de categorias la primera vez. */
    function pintarFiltroCategorias(categorias) {
        if (filtroC.options.length > 1) return;
        categorias.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c;
            opt.textContent = c;
            filtroC.appendChild(opt);
        });
    }

    async function cargar() {
        modoIA = false;
        limpiarInfoIA();

        const params = new URLSearchParams({ action: 'list' });
        if (buscar.value.trim()) params.set('search', buscar.value.trim());
        if (filtroC.value) params.set('categoria', filtroC.value);

        const json = await api('/api/datasets.php?' + params.toString());
        if (!json.success) {
            tbody.innerHTML = '<tr><td colspan="8" class="muted center">' + esc(json.message) + '</td></tr>';
            return;
        }

        datos.categorias = json.data.categorias;
        datos.owners = json.data.owners;
        pintarFiltroCategorias(json.data.categorias);
        pintarFilas(json.data.datasets, false);
    }

    function limpiarInfoIA() {
        infoIA.textContent = '';
        infoIA.classList.add('hidden');
        terminosIA.innerHTML = '';
        terminosIA.classList.add('hidden');
        if (btnBuscarIA) btnBuscarIA.disabled = false;
    }

    /** Muestra el aviso de modo, los terminos usados y el boton de volver. */
    function pintarInfoIA(d) {
        const etiquetas = [d.simulado ? 'modo simulado' : 'con IA'];
        if (d.fallback) etiquetas.push('respaldo: busqueda literal');
        etiquetas.push(d.datasets.length + (d.datasets.length === 1 ? ' resultado' : ' resultados'));

        infoIA.textContent = 'Busqueda semantica asistida por IA (' + etiquetas.join(' | ') + ')';
        if (d.interpretacion) {
            infoIA.textContent += ': ' + d.interpretacion;
        }

        const btnLiteral = document.createElement('button');
        btnLiteral.type = 'button';
        btnLiteral.className = 'btn btn-ghost btn-sm';
        btnLiteral.textContent = 'Usar busqueda normal';
        btnLiteral.addEventListener('click', cargar);
        infoIA.appendChild(btnLiteral);
        infoIA.classList.remove('hidden');

        // Chips con los terminos buscados
        terminosIA.innerHTML = '';
        (d.terminos || []).forEach(t => {
            const tag = document.createElement('span');
            tag.className = 'tag';
            tag.textContent = t;
            terminosIA.appendChild(tag);
        });
        terminosIA.classList.remove('hidden');
    }

    /**
     * Busqueda semantica asistida por IA: la IA expande la consulta y el
     * servidor devuelve los datasets ordenados por relevancia. Si el
     * proveedor falla, el servidor responde con la busqueda literal.
     */
    async function buscarConIA() {
        const q = buscar.value.trim();
        if (!q) {
            cargar();
            return;
        }

        if (btnBuscarIA) btnBuscarIA.disabled = true;
        infoIA.textContent = 'Buscando con IA...';
        infoIA.classList.remove('hidden');

        const params = new URLSearchParams({ action: 'search', q });
        if (filtroC.value) params.set('categoria', filtroC.value);

        const json = await api('/api/datasets.php?' + params.toString());
        if (btnBuscarIA) btnBuscarIA.disabled = false;

        if (!json.success) {
            limpiarInfoIA();
            tbody.innerHTML = '<tr><td colspan="8" class="muted center">' + esc(json.message) + '</td></tr>';
            return;
        }

        modoIA = true;
        if (!datos.categorias.length) {
            datos.categorias = json.data.categorias;
            pintarFiltroCategorias(json.data.categorias);
        }
        pintarInfoIA(json.data);
        pintarFilas(json.data.datasets, true);
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
            document.getElementById('clasificacion').value = dataset.clasificacion || 'publico';
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

    if (btnNuevo) btnNuevo.addEventListener('click', () => abrirModal(null));
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
    // Enter o el boton disparan la busqueda semantica
    buscar.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(debounceT);
            buscarConIA();
        }
    });
    if (btnBuscarIA) btnBuscarIA.addEventListener('click', buscarConIA);

    // En modo semantico el filtro se reaplica sobre la misma busqueda
    filtroC.addEventListener('change', () => {
        if (modoIA) buscarConIA();
        else cargar();
    });

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