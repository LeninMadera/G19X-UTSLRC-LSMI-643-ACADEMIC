/* ============================================================
   Dashboard: indicadores + graficos + notificaciones
   ============================================================ */

/* global api, esc, fmtFecha, drawBars */

document.addEventListener('DOMContentLoaded', () => {
    const kpis = {
        datasets: document.getElementById('dl-kpi-datasets'),
        calidad:  document.getElementById('dl-kpi-calidad'),
        alertas:  document.getElementById('dl-kpi-alertas'),
        politicas: document.getElementById('dl-kpi-politicas'),
    };

    function nivelBadge(score) {
        if (score === null || score === undefined) {
            return '<span class="badge badge-inactive">S / N</span>';
        }
        const clase = score >= 85 ? 'badge-active' : score >= 70 ? 'badge-pending' : 'badge-inactive';
        const texto = score >= 85 ? 'Excelente' : score >= 70 ? 'Aceptable' : 'Por mejorar';
        return '<span class="badge ' + clase + '">' + texto + ' (' + score + ')</span>';
    }

    async function cargar() {
        const json = await api('/api/reports.php?action=overview', {}, 'GET');
        if (!json.success) return;
        const d = json.data;

        kpis.datasets.textContent = d.total_datasets;
        kpis.calidad.textContent = d.promedio_calidad === null ? '-' : d.promedio_calidad;
        kpis.alertas.textContent = d.alertas_pendientes;
        kpis.politicas.textContent = d.politicas_activas;

        const mon = d.monitoreo;
        if (mon) {
            document.getElementById('dl-kpi-mon-activas').textContent = mon.configs_activas;
            document.getElementById('dl-kpi-mon-ejecuciones').textContent = mon.ejecuciones;
            document.getElementById('dl-kpi-mon-fallos').textContent = mon.fallos;
            document.getElementById('dl-kpi-mon-tasa').textContent =
                mon.tasa_exito === null ? '-' : mon.tasa_exito + '%';
        } else {
            document.getElementById('dl-kpi-mon-activas').textContent = '-';
            document.getElementById('dl-kpi-mon-ejecuciones').textContent = '-';
            document.getElementById('dl-kpi-mon-fallos').textContent = '-';
            document.getElementById('dl-kpi-mon-tasa').textContent = '-';
        }

        document.getElementById('dl-fecha').textContent =
            'Actualizado: ' + new Date().toLocaleString();

        const conScore = (d.scores || []).filter(s => s.score !== null);
        drawBars(document.getElementById('dl-chart-scores'), conScore.map(s => ({
            label: s.nombre,
            value: Number(s.score),
        })));

        const cat = document.getElementById('dl-categorias');
        cat.innerHTML = '';
        const maxC = Math.max.apply(null, (d.por_categoria || []).map(c => c.total).concat([1]));
        (d.por_categoria || []).forEach(c => {
            const row = document.createElement('div');
            row.className = 'bar-row';
            const ancho = Math.round((c.total / maxC) * 100);
            row.innerHTML =
                '<span class="bar-label">' + esc(c.categoria) + '</span>' +
                '<div class="bar-track"><div class="bar-fill" style="width:' + ancho + '%"></div></div>' +
                '<span class="bar-val">' + c.total + '</span>';
            cat.appendChild(row);
        });
        if ((d.por_categoria || []).length === 0) {
            cat.innerHTML = '<p class="muted small">Sin datasets.</p>';
        }

        const listAl = document.getElementById('dl-alertas');
        listAl.innerHTML = '';
        (d.alertas || []).forEach(a => {
            const li = document.createElement('li');
            const badge = a.severidad === 'media'
                ? '<span class="badge badge-pending">media</span>'
                : '<span class="badge badge-inactive">' + esc(a.severidad) + '</span>';
            li.innerHTML = badge + ' ' + esc(a.dataset) +
                '<span class="muted small" style="margin-left:8px">' + fmtFecha(a.fecha) + '</span>' +
                '<div class="muted small">' + esc(a.descripcion_ia) + '</div>';
            listAl.appendChild(li);
        });
        if ((d.alertas || []).length === 0) {
            listAl.innerHTML = '<li class="muted small">Sin alertas.</li>';
        }

        const listAct = document.getElementById('dl-actividad');
        listAct.innerHTML = '';
        (d.actividad || []).forEach(a => {
            const li = document.createElement('li');
            li.innerHTML = '<strong>' + esc(a.accion) + '</strong>'
                + '<span class="muted small" style="margin:0 8px">' + esc(a.usuario || 'sistema') + '</span>'
                + '<span class="muted small">' + fmtFecha(a.fecha) + '</span>'
                + (a.detalle ? '<div class="muted small">' + esc(a.detalle) + '</div>' : '');
            listAct.appendChild(li);
        });
        if ((d.actividad || []).length === 0) {
            listAct.innerHTML = '<li class="muted small">Sin actividad reciente.</li>';
        }
    }

    document.getElementById('dl-refresh').addEventListener('click', cargar);
    cargar();
});