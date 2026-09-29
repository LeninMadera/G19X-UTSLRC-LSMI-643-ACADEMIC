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

    /* Classes de badge segun significado: ok / warn / error / neutral. */
    const TONO_SEVERIDAD = { alta: 'error', media: 'warn', baja: 'ok' };

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

        /* Datos que la API ya devuelve y antes no se mostraban. */
        const pendientes = (d.alertas_pendientes || 0) + (d.sugerencias_pendientes || 0);
        const resumen = document.getElementById('dl-pendientes');
        resumen.className = 'pill-summary ' + (pendientes === 0 ? 'is-ok' : 'is-warn');
        resumen.innerHTML = '<span class="dot"></span>' + (pendientes === 0
            ? 'Sin trabajo pendiente'
            : (d.alertas_pendientes || 0) + ' alertas y ' + (d.sugerencias_pendientes || 0) + ' sugerencias');

        document.getElementById('dl-mon-fecha').textContent = mon && mon.ultima_ejecucion
            ? 'Ultima ejecucion: ' + fmtFecha(mon.ultima_ejecucion)
            : 'Sin ejecuciones registradas';

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
            cat.innerHTML = '<p class="list-notif"><span class="empty">Sin datasets.</span></p>';
        }

        const listAl = document.getElementById('dl-alertas');
        listAl.innerHTML = '';
        (d.alertas || []).forEach(a => {
            const li = document.createElement('li');
            const severidad = String(a.severidad || '').toLowerCase();
            const clase = TONO_SEVERIDAD[severidad] || 'neutral';
            // Mismo <wbr> que en la lista de actividad: aqui "dataset" y la
            // fecha tampoco Tenian punto de corte entre si.
            li.innerHTML = '<span class="badge badge-' + clase + '">' + esc(a.severidad) + '</span> '
                + esc(a.dataset) +
                '<wbr><span class="meta">' + fmtFecha(a.fecha) + '</span>' +
                '<div class="desc">' + esc(a.descripcion_ia) + '</div>';
            listAl.appendChild(li);
        });
        if ((d.alertas || []).length === 0) {
            listAl.innerHTML = '<li class="empty">Sin alertas.</li>';
        }

        const listAct = document.getElementById('dl-actividad');
        listAct.innerHTML = '';
        (d.actividad || []).forEach(a => {
            const li = document.createElement('li');
            // <wbr> antes de cada .meta: da puntos de corte sin ocupa ancho.
            // Sin el, accion+usuario+fecha quedan pegados en una sola hilera
            // indivisible y el panel no baja del min-content en movil.
            li.innerHTML = '<strong>' + esc(a.accion) + '</strong>'
                + '<wbr><span class="meta">' + esc(a.usuario || 'sistema') + '</span>'
                + '<wbr><span class="meta">' + fmtFecha(a.fecha) + '</span>'
                + (a.detalle ? '<div class="desc">' + esc(a.detalle) + '</div>' : '');
            listAct.appendChild(li);
        });
        if ((d.actividad || []).length === 0) {
            listAct.innerHTML = '<li class="empty">Sin actividad reciente.</li>';
        }
    }

    document.getElementById('dl-refresh').addEventListener('click', cargar);
    cargar();
});
