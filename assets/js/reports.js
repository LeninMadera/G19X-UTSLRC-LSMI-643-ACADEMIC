/* ============================================================
   Reportes: estado de calidad, bitacora y alertas IA
   ============================================================ */

/* global api, esc, fmtFecha */

document.addEventListener('DOMContentLoaded', () => {
    const esAdmin = document.getElementById('rp-log') !== null;

    /* Classes de badge segun significado: ok / warn / error / neutral. */
    const TONO_SEVERIDAD = { alta: 'error', media: 'warn', baja: 'ok' };

    function sevBadge(sev) {
        const clase = TONO_SEVERIDAD[String(sev || '').toLowerCase()] || 'neutral';
        return '<span class="badge badge-' + clase + '">' + esc(sev) + '</span>';
    }

    async function cargar() {
        // Tabla de scores
        const ov = await api('/api/reports.php?action=overview', {}, 'GET');
        if (ov.success) {
            const tbody = document.getElementById('rp-tabla');
            tbody.innerHTML = '';
            (ov.data.scores || []).forEach(s => {
                const tr = document.createElement('tr');
                const nivel = s.score === null
                    ? '<span class="badge badge-neutral">Sin mediciones</span>'
                    : (s.score >= 85
                        ? '<span class="badge badge-ok">Excelente</span>'
                        : s.score >= 70
                            ? '<span class="badge badge-warn">Aceptable</span>'
                            : '<span class="badge badge-error">Por mejorar</span>');
                tr.innerHTML =
                    '<td class="col-strong">' + esc(s.nombre) + '</td>' +
                    '<td class="col-soft">' + esc(s.categoria) + '</td>' +
                    '<td class="col-num"><strong>' + (s.score === null ? '-' : s.score) + '</strong></td>' +
                    '<td class="col-date">' + fmtFecha(s.ultima_medicion) + '</td>' +
                    '<td>' + nivel + '</td>';
                tbody.appendChild(tr);
            });
            if ((ov.data.scores || []).length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="muted">Sin datasets registrados.</td></tr>';
            }
            const conMedicion = (ov.data.scores || []).filter(s => s.score !== null).length;
            document.getElementById('rp-tabla-info').textContent =
                (ov.data.scores || []).length + ' datasets, ' + conMedicion + ' con medicion de calidad.';
        }

        // Alertas AI
        const al = await api('/api/reports.php?action=alerts&limit=15', {}, 'GET');
        if (al.success) {
            const tbody = document.getElementById('rp-alertas');
            tbody.innerHTML = '';
            (al.data.alerts || []).forEach(a => {
                const tr = document.createElement('tr');
                tr.innerHTML =
                    '<td class="col-date">' + fmtFecha(a.fecha) + '</td>' +
                    '<td class="col-strong">' + esc(a.dataset) + '</td>' +
                    '<td><span class="badge badge-info">' + esc(a.tipo) + '</span></td>' +
                    '<td>' + sevBadge(a.severidad) + '</td>' +
                    '<td class="col-soft">' + esc(a.descripcion_ia || '') + '</td>' +
                    '<td class="col-soft">' + esc(a.recomendacion_ia || '-') + '</td>';
                tbody.appendChild(tr);
            });
            if ((al.data.alerts || []).length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="muted">Sin alertas registradas.</td></tr>';
            }
            document.getElementById('rp-alertas-info').textContent =
                'Mostrando ' + (al.data.alerts || []).length + ' de 15 alertas solicitadas.';
        }

        // Bitacora (solo admin)
        if (esAdmin) {
            const lg = await api('/api/reports.php?action=logs&limit=100', {}, 'GET');
            if (lg.success) {
                const tbody = document.getElementById('rp-log');
                tbody.innerHTML = '';
                (lg.data.logs || []).forEach(l => {
                    const tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td class="col-date">' + fmtFecha(l.fecha) + '</td>' +
                        '<td>' + esc(l.usuario || 'sistema') + '</td>' +
                        '<td><code>' + esc(l.accion) + '</code></td>' +
                        '<td class="col-soft">' + esc(l.detalle) + '</td>';
                    tbody.appendChild(tr);
                });
                if ((lg.data.logs || []).length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="muted">Sin movimientos registrados.</td></tr>';
                }
                document.getElementById('rp-log-info').textContent =
                    'Ultimos ' + lg.data.logs.length + ' movimientos.'
                    + (lg.data.logs.length === 100 ? ' (limite alcanzado)' : '');
            }
        }
    }

    const btn = document.getElementById('rp-refresh');
    if (btn) btn.addEventListener('click', cargar);
    cargar();
});
