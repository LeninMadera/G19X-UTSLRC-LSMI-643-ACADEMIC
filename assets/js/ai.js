/* ============================================================
   Asistente de Inteligencia Artificial
   ============================================================ */

/* global api, esc, APP_USER */

document.addEventListener('DOMContentLoaded', () => {
    const selDataset = document.getElementById('sel-dataset');
    const panelAcc   = document.getElementById('panel-acciones');
    const panelRes   = document.getElementById('panel-resultados');
    const resultado  = document.getElementById('resultado');
    const msg        = document.getElementById('msg');
    const aiMode     = document.getElementById('ai-mode');
    const aiEngine   = document.getElementById('ai-engine');
    const aiAccion   = document.getElementById('ai-result-accion');

    /* Etiqueta legible de cada accion: separa que se pidio de lo que
       devolvio la IA. Solo rotulos, la peticion es la misma. */
    const TITULO_ACCION = {
        classify:  'Clasificar dataset',
        anomalies: 'Detectar anomalias',
        summary:   'Resumen del estado',
        suggest:   'Sugerencias de mejora',
    };

    const rol = document.querySelector('.badge-role')?.textContent || '';
    const puedeUsar = ['admin', 'steward', 'owner'].includes(rol);
    const userId    = (window.APP_USER && window.APP_USER.id) || null;

    if (!puedeUsar && panelAcc) panelAcc.style.display = 'none';

    function mostrarMsg(texto, tipo = 'success') {
        msg.textContent = texto;
        msg.className = 'alert alert-' + tipo;
        setTimeout(() => msg.classList.add('hidden'), 4000);
    }

    /* La severidad de una anomalia se pinta por gravedad real: alta y
       critica son error, media pide atencion y baja es informativa. */
    function TONO_ANOMALIA(sev) {
        return ['alta', 'critica'].includes(sev) ? 'error' : sev === 'media' ? 'warn' : 'info';
    }

    function renderResultado(accion, json) {
        const d = json.data || {};
        resultado.innerHTML = '';
        const box = document.createElement('div');
        box.className = 'ai-result';

        if (aiAccion) {
            aiAccion.textContent = TITULO_ACCION[accion] || accion;
            aiAccion.className = 'pill-summary is-info';
        }

        if (accion === 'classify') {
            box.innerHTML =
                '<div class="ai-result-field"><span class="ai-result-label">Categoria sugerida</span>' +
                '<span class="ai-result-value">' + esc(d.categoria) + '</span></div>' +
                '<div class="ai-result-field"><span class="ai-result-label">Etiquetas</span>' +
                '<div class="tags"></div></div>';
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
                box.innerHTML = '<p class="ai-result-empty">' +
                    '<svg class="ico" aria-hidden="true"><use href="#i-check"></use></svg>' +
                    'No se detectaron anomalias en este dataset.</p>';
            } else {
                const ul = document.createElement('ul');
                ul.className = 'ai-result-list';
                list.forEach(a => {
                    const li = document.createElement('li');
                    li.innerHTML = '<span class="badge badge-' + TONO_ANOMALIA(a.severidad) +
                        '">' + esc(a.severidad) + '</span> ' + esc(a.descripcion);
                    ul.appendChild(li);
                });
                box.appendChild(ul);
            }
        } else if (accion === 'summary') {
            box.innerHTML = '<p class="ai-result-text">' + esc(d.resumen) + '</p>';
        } else if (accion === 'suggest') {
            const list = d.sugerencias || [];
            if (list.length === 0) {
                box.innerHTML = '<p class="ai-result-empty">' +
                    '<svg class="ico" aria-hidden="true"><use href="#i-alert"></use></svg>' +
                    'Sin sugerencias: revisa las mediciones de calidad del dataset.</p>';
            } else {
                const ul = document.createElement('ul');
                ul.className = 'ai-result-list';
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
            if (rol === 'owner' && Number(d.owner_id) !== Number(userId)) return;
            const opt = document.createElement('option');
            opt.value = d.id;
            opt.textContent = d.nombre;
            selDataset.appendChild(opt);
        });
    }

    /* El host crudo (api.groq.com) no se muestra: se reduce a la marca y se
       acompana del modelo, que es lo que identifica el servicio. El prefijo
       api./www. se descarta antes de tomar la etiqueta. */
    const MARCA_CONOCIDA = { groq: 'Groq', openai: 'OpenAI' };

    function MARCA_PROVEEDOR(host) {
        const base = String(host || '').split('/')[0].toLowerCase()
            .replace(/^(api|www)\./, '');
        const clave = base.split('.')[0] || base;
        return MARCA_CONOCIDA[clave] || (clave.charAt(0).toUpperCase() + clave.slice(1));
    }

    /* El estado lo sigue dictando el endpoint; aqui solo se traduce a chip.
       unavailable = no se pudo siquiera consultar el estado. */
    async function cargarEstadoIA() {
        const json = await api('/api/ai.php?action=status');
        const tono = { ok: 'is-ok', warn: 'is-warn', error: 'is-error' };
        const pintar = (estado, etiqueta, detalle) => {
            aiMode.className = 'pill-summary ai-status ' + tono[estado];
            aiMode.querySelector('span:last-child').textContent = etiqueta;
            if (aiEngine) {
                aiEngine.textContent = detalle || '';
                aiEngine.classList.toggle('is-muted', !detalle);
            }
        };

        if (!json.success) {
            pintar('error', 'IA no disponible', '');
            return;
        }

        const marca = MARCA_PROVEEDOR(json.data.proveedor);
        if (json.data.disponible) {
            pintar('ok', 'IA conectada', marca + ' \u00b7 ' + esc(json.data.modelo));
        } else {
            pintar('warn', 'IA simulada', marca + ' \u00b7 heuristicas locales');
        }
    }

    selDataset.addEventListener('change', () => {
        if (!puedeUsar) return;
        panelAcc.style.display = selDataset.value ? 'block' : 'none';
        panelRes.style.display = 'none';
    });

    panelAcc.addEventListener('click', async (e) => {
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
                if (aiAccion) { aiAccion.textContent = TITULO_ACCION[accion] || accion; aiAccion.className = 'pill-summary is-error'; }
                resultado.innerHTML = '<div class="ai-result"><p class="alert alert-error">' + esc(json.message) + '</p></div>';
                panelRes.style.display = 'block';
            }
        } catch (err) {
            resultado.innerHTML = '<div class="ai-result"><p class="alert alert-error">Error de conexion con el servidor.</p></div>';
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