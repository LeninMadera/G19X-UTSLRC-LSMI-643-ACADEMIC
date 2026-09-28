/* ============================================================
   Reportes: estado de calidad, bitacora y alertas IA
   ============================================================ */

/* global api, esc, fmtFecha */

document.addEventListener('DOMContentLoaded', () => {
    const esAdmin = document.getElementById('rp-log') !== null;

    function sevBadge(sev) {
        const clase = sev === 'baja' ? 'badge-active'
            : sev === 'media' ? 'badge-pending'
            : 'badge-inactive';
        return '<span class="badge ' + clase + '">' + esc(sev) + '</span>';
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
                    ? '<span class="badge badge-inactive">Sin mediciones</span>'
                    : (s.score >= 85
                        ? '<span class="badge badge-active">Excelente</span>'
                        : s.score >= 70
                            ? '<span class="badge badge-pending">Aceptable</span>'
                            : '<span class="badge badge-inactive">Por mejorar</span>');
                tr.innerHTML =
                    '<td>' + esc(s.nombre) + '</td>' +
                    '<td>' + esc(s.categoria) + '</td>' +
                    '<td><strong>' + (s.score === null ? '-' : s.score) + '</strong></td>' +
                    '<td>' + fmtFecha(s.ultima_medicion) + '</td>' +
                    '<td>' + nivel + '</td>';
                tbody.appendChild(tr);
            });
        }

        // Alertas AI
        const al = await api('/api/reports.php?action=alerts&limit=15', {}, 'GET');
        if (al.success) {
            const tbody = document.getElementById('rp-alertas');
            tbody.innerHTML = '';
            (al.data.alerts || []).forEach(a => {
                const tr = document.createElement('tr');
                tr.innerHTML =
                    '<td>' + fmtFecha(a.fecha) + '</td>' +
                    '<td>' + esc(a.dataset) + '</td>' +
                    '<td><span class="badge badge-pending">' + esc(a.tipo) + '</span></td>' +
                    '<td>' + sevBadge(a.severidad) + '</td>' +
                    '<td>' + esc(a.descripcion_ia || '') + '</td>' +
                    '<td>' + esc(a.recomendacion_ia || '-') + '</td>';
                tbody.appendChild(tr);
            });
            if ((al.data.alerts || []).length === 0) {
                document.getElementById('rp-alertas').innerHTML =
                    '<tr><td colspan="6" class="muted">Sin alertas registradas.</td></tr>';
            }
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
                        '<td>' + fmtFecha(l.fecha) + '</td>' +
                        '<td>' + esc(l.usuario || 'sistema') + '</td>' +
                        '<td><code>' + esc(l.accion) + '</code></td>' +
                        '<td class="muted small">' + esc(l.detalle) + '</td>';
                    tbody.appendChild(tr);
                });
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