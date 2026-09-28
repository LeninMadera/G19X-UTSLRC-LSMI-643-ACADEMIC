/* ============================================================
   Detalle de un dataset (ficha)
   ============================================================ */

/* global api, esc, fmtFecha, APP_URL, APP_USER */

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
    const btnConfirmar = document.getElementById('btn-confirmar-clasif');
    const tbodyA    = document.getElementById('d-detections');

    const estado = { puedeEdit: false, confirmarId: null };

    async function cargar() {
        const json = await api('/api/datasets.php?action=detail&id=' + id);
        if (!json.success) {
            cargando.textContent = json.message;
            cargando.classList.remove('muted');
            return;
        }
        cargarConData(json.data);
        cargarCompliance();
    }

    const TONO_SEVERIDAD = { alta: 'error', media: 'warn', baja: 'ok' };
    const TONO_CLASIFICACION = { publico: 'info', interno: 'warn', confidencial: 'error', restringido: 'error' };
    const TONO_EVAL = { cumple: 'ok', no_cumple: 'error', pendiente: 'warn', no_aplica: 'neutral' };
    const TONO_DETECCION = { pendiente: 'warn', revisada: 'info', aplicada: 'ok', descartada: 'neutral' };

    function colorScore(s) {
        return s >= 85 ? 'score-good' : s >= 70 ? 'score-warn' : 'score-bad';
    }

    function sevBadge(sev) {
        const clave = String(sev || '').toLowerCase();
        return '<span class="badge badge-' + (TONO_SEVERIDAD[clave] || 'neutral') + '">' + esc(sev) + '</span>';
    }

    function cargarConData(d) {
        const q = d.ultima_calidad;

        document.getElementById('d-nombre').textContent = d.nombre;
        document.getElementById('d-categoria').textContent = d.categoria;
        document.getElementById('d-categoria-ia').textContent = d.categoria_ia || 'no sugerida';
        document.getElementById('d-origen').textContent = d.origen;
        document.getElementById('d-clasificacion').innerHTML =
            '<span class="badge badge-' + (TONO_CLASIFICACION[d.clasificacion] || 'neutral') + '">'
            + esc(d.clasificacion || 'publico') + '</span>';
        document.getElementById('d-owner').textContent = d.owner_name + ' (' + d.owner_email + ')';
        document.getElementById('d-descripcion').textContent = d.descripcion || 'Sin descripcion.';
        document.getElementById('d-creado').textContent = d.fecha_creacion;
        document.getElementById('d-actualizado').textContent = d.fecha_actualizacion;
        document.getElementById('d-estado').innerHTML =
            '<span class="badge badge-' + (d.activo == 1 ? 'ok' : 'neutral') + '">'
            + (d.activo == 1 ? 'activo' : 'inactivo') + '</span>';

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
            document.getElementById('d-score').className = 'kpi-num score-badge ' + colorScore(parseFloat(q.score));
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
                card.className = 'card kpi';
                card.innerHTML = '<div class="kpi-head"><span class="kpi-label">' + label + '</span></div>' +
                    '<p class="kpi-num score-badge ' + colorScore(parseFloat(val)) + '">' +
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
            tr.innerHTML = '<td class="col-strong">' + esc(p.titulo) + '</td>' +
                '<td><span class="badge badge-info">' + esc(p.tipo) + '</span></td>' +
                '<td><span class="badge badge-' + (p.estado === 'activa' ? 'ok' : 'neutral') + '">' + esc(p.estado) + '</span></td>';
            tbodyP.appendChild(tr);
        });

        // Linaje de datos: recibe de / envia a
        renderLinaje(d.linaje_entrada, 'd-linaje-entrada', 'origen_id', 'origen_nombre', 'Sin relaciones de entrada.');
        renderLinaje(d.linaje_salida, 'd-linaje-salida', 'destino_id', 'destino_nombre', 'Sin relaciones de salida.');

        // Permisos de edicion segun rol y propiedad
        const rol = document.querySelector('.badge-role')?.textContent || '';
        const userId = (window.APP_USER && window.APP_USER.id) || null;
        estado.puedeEdit = ['admin', 'steward'].includes(rol) ||
            (rol === 'owner' && Number(d.owner_id) === Number(userId));

        // Detecciones IA
        tbodyA.innerHTML = '';
        if (d.detections.length === 0) {
            tbodyA.innerHTML = '<tr><td colspan="7" class="muted center">Sin analisis de IA.</td></tr>';
        }
        d.detections.forEach(a => {
            const acciones = estado.puedeEdit ? accionesDeteccion(a) : '-';
            const tr = document.createElement('tr');
            tr.dataset.detId = a.id;
            tr.innerHTML = '<td><span class="badge badge-info">' + esc(a.tipo) + '</span></td>' +
                '<td>' + sevBadge(a.severidad) + '</td>' +
                '<td class="col-soft">' + esc(a.descripcion_ia || '') + '</td>' +
                '<td class="col-soft">' + esc(a.recomendacion_ia || '-') + '</td>' +
                '<td><span class="badge badge-' + (TONO_DETECCION[a.estado] || 'neutral') + '">' + esc(a.estado) + '</span></td>' +
                '<td class="col-date">' + fmtFecha(a.fecha) + '</td>' +
                '<td class="actions-cell">' + acciones + '</td>';
            tbodyA.appendChild(tr);
        });

        // Boton editar (si el rol lo permite)
        if (estado.puedeEdit) {
            btnEditar.classList.remove('hidden');
            btnEditar.onclick = () => {
                window.location.href = window.APP_URL + '/views/datasets.php?editar=' + d.id;
            };
        }

        // Confirmar clasificacion IA (si hay una pendiente/revisada)
        let pendienteClasif = null;
        d.detections.forEach(a => {
            if (a.tipo === 'clasificacion' && a.estado !== 'aplicada' && !pendienteClasif) {
                pendienteClasif = a;
            }
        });
        if (estado.puedeEdit && pendienteClasif) {
            estado.confirmarId = pendienteClasif.id;
            btnConfirmar.classList.remove('hidden');
        } else {
            estado.confirmarId = null;
            btnConfirmar.classList.add('hidden');
        }

        cargando.classList.add('hidden');
        detalle.classList.remove('hidden');
    }

    // Pinta una de las dos tablas de linaje (entradas o salidas).
    // idCampo/idNombre indican la columna del dataset contraparte.
    function renderLinaje(relaciones, idTbody, idCampo, idNombre, vacio) {
        const cuerpo = document.getElementById(idTbody);
        if (!cuerpo) return;
        cuerpo.innerHTML = '';
        if (!relaciones || relaciones.length === 0) {
            cuerpo.innerHTML = '<tr><td colspan="3" class="muted center">' + esc(vacio) + '</td></tr>';
            return;
        }
        relaciones.forEach(r => {
            const tr = document.createElement('tr');
            tr.innerHTML =
                '<td><a href="dataset_detail.php?id=' + r[idCampo] + '">' + esc(r[idNombre]) + '</a></td>' +
                '<td><span class="tag">' + esc(r.tipo) + '</span></td>' +
                '<td>' + esc(r.descripcion || '-') + '</td>';
            cuerpo.appendChild(tr);
        });
    }

    function accionesDeteccion(a) {
        let botones = [];
        if (a.tipo === 'clasificacion' && a.estado === 'pendiente') {
            botones.push('<button class="btn btn-ghost btn-sm" data-res="revisada">Revisar</button>');
        }
        if (a.estado === 'pendiente' || a.estado === 'revisada') {
            if (a.tipo !== 'clasificacion') {
                botones.push('<button class="btn btn-ghost btn-sm" data-res="aplicada">Aplicar</button>');
            }
            botones.push('<button class="btn btn-danger btn-sm" data-res="descartada">Descartar</button>');
        }
        if (a.estado === 'aplicada' || a.estado === 'descartada') {
            return '-';
        }
        return botones.join(' ');
    }

    async function resolver(detectionId, nuevoEstado) {
        const json = await api('/api/ai.php?action=resolve', { detection_id: Number(detectionId), estado: nuevoEstado });
        msg.className = 'alert alert-' + (json.success ? 'success' : 'error');
        msg.textContent = json.message;
        msg.classList.remove('hidden');
        if (json.success) await cargar();
    }

    async function confirmarClasificacion() {
        if (!estado.confirmarId) return;
        const json = await api('/api/ai.php?action=confirm_classify', { detection_id: Number(estado.confirmarId) });
        msg.className = 'alert alert-' + (json.success ? 'success' : 'error');
        msg.textContent = json.message;
        msg.classList.remove('hidden');
        if (json.success) await cargar();
    }

    btnConfirmar.addEventListener('click', confirmarClasificacion);

    tbodyA.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-res]');
        if (!btn) return;
        const tr = e.target.closest('tr');
        if (!tr || !tr.dataset.detId) return;
        resolver(tr.dataset.detId, btn.dataset.res);
    });

    // ------------------ Cumplimiento normativo ------------------
    const compForm  = document.getElementById('eval-form');
    const compModal = document.getElementById('modal-eval');
    const compError = document.getElementById('eval-form-error');
    const comp = { puede: false, controles: [], evaluaciones: [] };

    function badgeEvalEstado(est) {
        return '<span class="badge badge-' + (TONO_EVAL[est] || 'neutral') + '">'
            + esc(String(est).replace('_', ' ')) + '</span>';
    }

    async function cargarCompliance() {
        const json = await api('/api/compliance.php?action=dataset_status&dataset_id=' + id);
        const tbodyC = document.getElementById('d-compliance');
        if (!json.success) {
            tbodyC.innerHTML = '<tr><td colspan="9" class="muted center">' + esc(json.message) + '</td></tr>';
            return;
        }
        comp.puede = json.data.puede_evaluar;
        comp.evaluaciones = json.data.evaluaciones;
        comp.controles = json.data.controles_disponibles;

        const ind = json.data.indicadores;
        document.getElementById('dc-aplicables').textContent = ind.aplicables;
        document.getElementById('dc-evaluados').textContent = ind.evaluados;
        document.getElementById('dc-pendientes').textContent = ind.pendientes;
        document.getElementById('dc-noaplica').textContent = ind.no_aplica;
        document.getElementById('dc-cumplimiento').textContent =
            ind.cumplimiento_pct != null ? ind.cumplimiento_pct + '%' : '--';

        const chipCumplimiento = document.getElementById('dc-cumplimiento');
        const pct = ind.cumplimiento_pct;
        chipCumplimiento.className = 'kpi-num' + (
            pct == null ? ''
                : pct >= 85 ? ' score-good' : pct >= 70 ? ' score-warn' : ' score-bad'
        );

        const btnAgregar = document.getElementById('btn-agregar-eval');
        if (comp.puede && comp.controles.length > 0) {
            btnAgregar.classList.remove('hidden');
        } else {
            btnAgregar.classList.add('hidden');
        }

        tbodyC.innerHTML = '';
        if (comp.evaluaciones.length === 0) {
            tbodyC.innerHTML = '<tr><td colspan="9" class="muted center">Sin evaluaciones registradas para este dataset.</td></tr>';
            return;
        }
        comp.evaluaciones.forEach(ev => {
            const acciones = comp.puede
                ? '<button class="btn btn-ghost btn-sm" data-cedit="' + ev.control_id + '">Evaluar</button>'
                : '-';
            const tr = document.createElement('tr');
            tr.innerHTML =
                '<td class="col-strong">' + esc(ev.control) + '</td>' +
                '<td class="col-soft">' + esc(ev.referencia || '-') + '</td>' +
                '<td>' + (ev.severidad ? sevBadge(ev.severidad) : '<span class="muted">-</span>') + '</td>' +
                '<td>' + badgeEvalEstado(ev.estado) + '</td>' +
                '<td class="col-soft">' + esc(ev.observaciones || '-') + '</td>' +
                '<td class="col-soft">' + esc(ev.evidencia || '-') + '</td>' +
                '<td class="col-soft">' + esc(ev.evaluador || '-') + '</td>' +
                '<td class="col-date">' + fmtFecha(ev.fecha) + '</td>' +
                '<td class="actions-cell">' + acciones + '</td>';
            tbodyC.appendChild(tr);
        });
    }

    function abrirEval(controlId) {
        const sel = document.getElementById('eval-cselect');
        const evaluacion = comp.evaluaciones.find(x => String(x.control_id) === String(controlId));

        compForm.reset();
        compError.classList.add('hidden');

        if (evaluacion) {
            document.getElementById('eval-title').textContent = 'Reevaluar control';
            sel.innerHTML = '';
            const opt = document.createElement('option');
            opt.value = evaluacion.control_id;
            opt.textContent = evaluacion.control;
            sel.appendChild(opt);
            sel.disabled = true;
            document.getElementById('eval-control').value = evaluacion.control_id;
            document.getElementById('eval-estado').value = evaluacion.estado;
            document.getElementById('eval-observaciones').value = evaluacion.observaciones || '';
            document.getElementById('eval-evidencia').value = evaluacion.evidencia || '';
        } else {
            document.getElementById('eval-title').textContent = 'Agregar evaluacion';
            sel.disabled = false;
            sel.innerHTML = '<option value="">Selecciona un control</option>';
            comp.controles.forEach(c => {
                const o = document.createElement('option');
                o.value = c.id;
                o.textContent = c.nombre;
                sel.appendChild(o);
            });
            document.getElementById('eval-control').value = '';
            document.getElementById('eval-estado').value = 'cumple';
        }
        compModal.classList.remove('hidden');
    }

    document.getElementById('btn-agregar-eval').addEventListener('click', () => abrirEval(null));

    document.getElementById('d-compliance').addEventListener('click', (e) => {
        const btn = e.target.closest('[data-cedit]');
        if (btn) abrirEval(btn.dataset.cedit);
    });

    document.querySelectorAll('[data-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById(btn.dataset.close).classList.add('hidden');
        });
    });
    compModal.addEventListener('click', (e) => {
        if (e.target === compModal) compModal.classList.add('hidden');
    });

    compForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = new FormData(compForm);
        let controlId = document.getElementById('eval-control').value;
        if (!controlId) controlId = document.getElementById('eval-cselect').value;
        if (!controlId) {
            compError.textContent = 'Debes seleccionar un control.';
            compError.classList.remove('hidden');
            return;
        }
        data.set('control_id', Number(controlId));
        data.set('dataset_id', Number(id));

        const json = await api('/api/compliance.php?action=evaluate', data);
        msg.className = 'alert alert-' + (json.success ? 'success' : 'error');
        msg.textContent = json.message;
        msg.classList.remove('hidden');
        if (json.success) {
            compModal.classList.add('hidden');
            await cargar();
        } else {
            compError.textContent = json.message;
            compError.classList.remove('hidden');
        }
    });

    cargar();
});