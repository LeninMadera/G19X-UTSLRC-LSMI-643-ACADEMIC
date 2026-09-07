/* ============================================================
   Asistente de Inteligencia Artificial
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
    const selDataset = document.getElementById('sel-dataset');
    const panelAcc   = document.getElementById('panel-acciones');
    const panelRes   = document.getElementById('panel-resultados');
    const resultado  = document.getElementById('resultado');
    const msg        = document.getElementById('msg');
    const aiMode     = document.getElementById('ai-mode');

    const rol = document.querySelector('.badge-role')?.textContent || '';
    const puedeUsar = ['admin', 'steward', 'owner'].includes(rol);

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

    function renderResultado(accion, json) {
        const d = json.data || {};
        resultado.innerHTML = '';
        const box = document.createElement('div');
        box.className = 'panel';

        if (accion === 'classify') {
            box.innerHTML =
                '<p><strong>Categoria sugerida:</strong> ' + esc(d.categoria) + '</p>' +
                '<p><strong>Etiquetas:</strong></p><div class="tags"></div>';
            const tags = box.querySelector('.tags');
            (d.etiquetas || []).forEach(t => {
                const s = document.createElement('span');
                s.className = 'tag';
                s.textContent = t;
                tags.appendChild(s);
            });
        } else if (accion === 'anomalies') {
            const list = d.anomalias || [];
            if (list.length === 0) {
                box.innerHTML = '<p class="muted">No se detectaron anomalias en este dataset.</p>';
            } else {
                const ul = document.createElement('ul');
                list.forEach(a => {
                    const li = document.createElement('li');
                    li.innerHTML = '<span class="badge badge-' +
                        (['alta', 'critica'].includes(a.severidad) ? 'inactive' : a.severidad === 'media' ? 'pending' : 'active') +
                        '">' + esc(a.severidad) + '</span> ' + esc(a.descripcion);
                    ul.appendChild(li);
                });
                box.appendChild(ul);
            }
        } else if (accion === 'summary') {
            box.innerHTML = '<p>' + esc(d.resumen) + '</p>';
        } else if (accion === 'suggest') {
            const list = d.sugerencias || [];
            if (list.length === 0) {
                box.innerHTML = '<p class="muted">Sin sugerencias: revisa las mediciones de calidad del dataset.</p>';
            } else {
                const ul = document.createElement('ul');
                list.forEach(s => {
                    const li = document.createElement('li');
                    li.textContent = s.recomendacion;
                    ul.appendChild(li);
                });
                box.appendChild(ul);
            }
        }
        resultado.appendChild(box);
        panelRes.style.display = 'block';
    }

    async function cargarDatasets() {
        const json = await api('/api/datasets.php?action=list');
        if (!json.success) return;
        selDataset.innerHTML = '<option value="">Selecciona un dataset</option>';
        json.data.datasets.forEach(d => {
            const opt = document.createElement('option');
            opt.value = d.id;
            opt.textContent = d.nombre;
            selDataset.appendChild(opt);
        });
    }

    async function cargarEstadoIA() {
        const json = await api('/api/ai.php?action=status');
        if (json.success) {
            const modo = json.data.disponible
                ? 'IA REAL conectada — proveedor ' + esc(json.data.proveedor) + ', modelo ' + esc(json.data.modelo)
                : 'MODO SIMULADO: sin API key configurada. Se usan heuristicas locales.';
            aiMode.textContent = 'Estado: ' + modo;
            if (!json.data.disponible) aiMode.classList.add('badge-pending');
        }
    }

    selDataset.addEventListener('change', () => {
        panelAcc.style.display = selDataset.value ? 'block' : 'none';
        panelRes.style.display = 'none';
    });

    document.getElementById('panel-acciones').addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-accion]');
        if (!btn || !selDataset.value) return;
        panelRes.style.display = 'none';

        const accion = btn.dataset.accion;
        btn.disabled = true;
        btn.textContent = 'Procesando...';
        try {
            const json = await api('/api/ai.php?action=' + accion, { dataset_id: Number(selDataset.value) });
            if (json.success) {
                renderResultado(accion, json);
                mostrarMsg(json.message, 'success');
            } else {
                resultado.innerHTML = '<p class="alert alert-error">' + esc(json.message) + '</p>';
                panelRes.style.display = 'block';
            }
        } catch (err) {
            resultado.innerHTML = '<p class="alert alert-error">Error de conexion con el servidor.</p>';
            panelRes.style.display = 'block';
        } finally {
            btn.disabled = false;
            btn.textContent = accion === 'classify' ? 'Clasificar' :
                accion === 'anomalies' ? 'Detectar' :
                accion === 'summary' ? 'Resumir' : 'Sugerir';
        }
    });

    cargarEstadoIA();
    cargarDatasets();
});