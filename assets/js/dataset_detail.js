/* ============================================================
   Detalle de un dataset (ficha)
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const id = params.get('id');

    if (!id) {
        window.location.href = window.APP_URL + '/views/datasets.php';
        return;
    }

    const msg       = document.getElementById('msg');
    const cargando  = document.getElementById('cargando');
    const detalle   = document.getElementById('detalle');
    const btnEditar = document.getElementById('btn-editar');

    async function cargar() {
        const json = await api('/api/datasets.php?action=detail&id=' + id);
        if (!json.success) {
            cargando.textContent = json.message;
            cargando.classList.remove('muted');
            return;
        }
        cargarConData(json.data);
    }

    function colorScore(s) {
        return s >= 85 ? 'score-good' : s >= 70 ? 'score-warn' : 'score-bad';
    }

    function cargarConData(d) {
        const q = d.ultima_calidad;

        document.getElementById('d-nombre').textContent = d.nombre;
        document.getElementById('d-categoria').textContent = d.categoria;
        document.getElementById('d-categoria-ia').textContent = d.categoria_ia || 'no sugerida';
        document.getElementById('d-origen').textContent = d.origen;
        document.getElementById('d-owner').textContent = d.owner_name + ' (' + d.owner_email + ')';
        document.getElementById('d-descripcion').textContent = d.descripcion || 'Sin descripcion.';
        document.getElementById('d-creado').textContent = d.fecha_creacion;
        document.getElementById('d-actualizado').textContent = d.fecha_actualizacion;
        document.getElementById('d-estado').innerHTML =
            '<span class="badge badge-' + (d.activo == 1 ? 'active' : 'inactive') + '">' +
            (d.activo == 1 ? 'activo' : 'inactivo') + '</span>';

        // Etiquetas IA
        const tags = document.getElementById('d-etiquetas');
        tags.innerHTML = '';
        let etiquetas = [];
        try { etiquetas = JSON.parse(d.etiquetas_ia || '[]'); } catch (err) { etiquetas = []; }
        if (etiquetas.length === 0) tags.innerHTML = '<span class="muted small">Sin etiquetas de IA</span>';
        etiquetas.forEach(t => {
            const span = document.createElement('span');
            span.className = 'tag';
            span.textContent = t;
            tags.appendChild(span);
        });

        // Score
        if (q) {
            document.getElementById('d-score').textContent = parseFloat(q.score).toFixed(1);
            document.getElementById('d-score').className = 'card-num score-badge ' + colorScore(parseFloat(q.score));
            document.getElementById('d-score-fecha').textContent = 'Medicion: ' + q.fecha_medicion;
        } else {
            document.getElementById('d-score').textContent = '--';
            document.getElementById('d-score-fecha').textContent = 'Sin mediciones aun';
        }

        // Dimensiones
        const dims = document.getElementById('d-dimensiones');
        dims.innerHTML = '';
        if (q) {
            const dim = [
                ['Completitud', q.completitud],
                ['Exactitud', q.exactitud],
                ['Consistencia', q.consistencia],
                ['Vigencia', q.vigencia],
                ['Unicidad', q.unicidad]
            ];
            dim.forEach(([label, val]) => {
                const card = document.createElement('div');
                card.className = 'card';
                card.innerHTML = '<h3>' + label + '</h3>' +
                    '<p class="card-num score-badge ' + colorScore(parseFloat(val)) + '">' +
                    parseFloat(val).toFixed(1) + '</p>';
                dims.appendChild(card);
            });
        } else {
            dims.innerHTML = '<p class="muted">No hay mediciones registradas.</p>';
        }

        // Politicas
        const tbodyP = document.getElementById('d-politicas');
        tbodyP.innerHTML = '';
        if (d.politicas.length === 0) {
            tbodyP.innerHTML = '<tr><td colspan="3" class="muted center">Sin politicas asignadas.</td></tr>';
        }
        d.politicas.forEach(p => {
            const tr = document.createElement('tr');
            tr.innerHTML = '<td>' + esc(p.titulo) + '</td>' +
                '<td>' + esc(p.tipo) + '</td>' +
                '<td><span class="badge badge-' + (p.estado === 'activa' ? 'active' : 'inactive') + '">' + esc(p.estado) + '</span></td>';
            tbodyP.appendChild(tr);
        });

        // Detecciones IA
        const tbodyA = document.getElementById('d-detections');
        tbodyA.innerHTML = '';
        if (d.detections.length === 0) {
            tbodyA.innerHTML = '<tr><td colspan="5" class="muted center">Sin analisis de IA.</td></tr>';
        }
        d.detections.forEach(a => {
            const tr = document.createElement('tr');
            tr.innerHTML = '<td>' + esc(a.tipo) + '</td>' +
                '<td><span class="badge badge-' +
                    (a.severidad === 'critica' || a.severidad === 'alta' ? 'inactive' : a.severidad === 'media' ? 'pending' : 'active') +
                    '">' + esc(a.severidad) + '</span></td>' +
                '<td>' + esc(a.descripcion_ia || a.recomendacion_ia || '') + '</td>' +
                '<td>' + esc(a.estado) + '</td>' +
                '<td>' + esc(a.fecha) + '</td>';
            tbodyA.appendChild(tr);
        });

        // Boton editar (si el rol lo permite)
        const rol = document.querySelector('.badge-role')?.textContent || '';
        if (['admin', 'steward'].includes(rol)) {
            btnEditar.classList.remove('hidden');
            btnEditar.addEventListener('click', () => {
                window.location.href = window.APP_URL + '/views/datasets.php?editar=' + d.id;
            });
        }

        cargando.classList.add('hidden');
        detalle.classList.remove('hidden');
    }

    function esc(t) {
        const div = document.createElement('div');
        div.textContent = t == null ? '' : String(t);
        return div.innerHTML;
    }

    cargar();
});