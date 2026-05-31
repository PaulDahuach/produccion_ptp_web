/* Movimientos de Lotes — front (nivel dinámico: detalle / agrupados). */
(function () {
    var table = null, lastNivel = null;
    function esc(s) { return (s === null || s === undefined) ? '' : String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
    function miles(v) { return (v === null || v === '' || v === undefined) ? '' : Number(v).toLocaleString('es-AR'); }
    function val(id) { return document.getElementById(id).value.trim(); }

    function params() {
        return { desde: val('fDesde'), hasta: val('fHasta'), sector: val('fSector'), nivel: val('fNivel') };
    }
    function qs(o) { var p = new URLSearchParams(); Object.keys(o).forEach(function (k) { if (o[k] !== '' && o[k] != null) p.set(k, o[k]); }); return p.toString(); }

    function pad(n) { return (n < 10 ? '0' : '') + n; }
    async function init() {
        var hoy = new Date(), ayer = new Date(Date.now() - 86400000);
        document.getElementById('fHasta').value = pad(hoy.getDate()) + '/' + pad(hoy.getMonth() + 1) + '/' + hoy.getFullYear();
        document.getElementById('fDesde').value = pad(ayer.getDate()) + '/' + pad(ayer.getMonth() + 1) + '/' + ayer.getFullYear();
        var j = await (await fetch('api.php?action=init')).json();
        if (j.ok) {
            var s = document.getElementById('fSector');
            s.innerHTML = '<option value="">— Todos —</option>' + j.data.sectores.map(function (o) { return '<option value="' + esc(o.id) + '">' + esc(o.den) + '</option>'; }).join('');
        }
        load();
    }

    var HEADERS = {
        detalle: ['Fecha', 'Hora', 'Tipo', 'ODP N°', 'Orden', 'Lote', 'Sector', 'Proceso', 'S. Personal', 'Planta', 'Ingreso', 'Egreso'],
        grupo: ['', 'Ingresos', 'Egresos', 'Neto', 'Movimientos']
    };

    async function load() {
        document.getElementById('resumen').textContent = 'Cargando…';
        var p = params();
        var j = await (await fetch('api.php?action=list&' + qs(p))).json();
        if (!j.ok) { document.getElementById('resumen').textContent = 'Error: ' + j.error; return; }
        var nivel = j.data.nivel, rows = j.data.rows || [];
        var isDet = (nivel === 'detalle');
        var heads = isDet ? HEADERS.detalle : HEADERS.grupo;
        if (!isDet) heads = [grupoLabel(nivel), 'Ingresos', 'Egresos', 'Neto', 'Movimientos'];

        // Reconstruir tabla si cambió el nivel (cambian columnas)
        if (table && lastNivel !== nivel) { table.destroy(); document.querySelector('#tbl tbody').innerHTML = ''; table = null; }
        lastNivel = nivel;
        document.getElementById('thd').innerHTML = '<tr>' + heads.map(function (h, i) {
            var cls = (isDet ? (i >= 10) : (i >= 1)) ? ' class="text-end"' : '';
            return '<th' + cls + '>' + esc(h) + '</th>';
        }).join('') + '</tr>';

        var data, totIng = 0, totEgr = 0;
        if (isDet) {
            data = rows.map(function (x) {
                totIng += Number(x.INGMOV) || 0; totEgr += Number(x.EGRMOV) || 0;
                return [esc(x.FECHA), esc(x.HORA),
                    (x.TIPO === 'Ingreso' ? '<span class="text-success">▲ Ingreso</span>' : '<span class="text-danger">▼ Egreso</span>'),
                    x.ODP, x.ORDEN, x.LOTE, esc(x.SECTOR), esc(x.PROCESO), esc(x.SECTORP), esc(x.PLANTA),
                    x.INGMOV ? miles(x.INGMOV) : '', x.EGRMOV ? miles(x.EGRMOV) : ''];
            });
        } else {
            data = rows.map(function (x) {
                totIng += Number(x.ING) || 0; totEgr += Number(x.EGR) || 0;
                return [esc(x.GRUPO), miles(x.ING), miles(x.EGR), miles((Number(x.ING) || 0) - (Number(x.EGR) || 0)), miles(x.MOVS)];
            });
        }

        var endCols = isDet ? [10, 11] : [1, 2, 3, 4];
        if (!table) {
            table = new DataTable('#tbl', {
                data: data, pageLength: 50, order: [],
                columnDefs: [{ targets: endCols, className: 'text-end' }],
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-AR.json' }
            });
        } else { table.clear().rows.add(data).draw(); }

        document.getElementById('resumen').textContent = rows.length + (isDet ? ' movimiento(s)' : ' grupo(s)');
        document.getElementById('totales').innerHTML =
            '<span class="text-success me-3">Ingresos: <b>' + miles(totIng) + '</b></span>' +
            '<span class="text-danger me-3">Egresos: <b>' + miles(totEgr) + '</b></span>' +
            '<span>Neto: <b>' + miles(totIng - totEgr) + '</b></span>';
        document.getElementById('btnImprimir').classList.toggle('disabled', !rows.length);
        document.getElementById('btnExcel').classList.toggle('disabled', !rows.length);
        document.getElementById('btnImprimir').href = 'print.php?' + qs(p);
        document.getElementById('btnExcel').href = 'export.php?' + qs(p);
    }

    function grupoLabel(n) { return n === 'personal' ? 'Sector Personal' : (n === 'planta' ? 'Planta' : 'Sector Producción'); }

    document.getElementById('btnFiltrar').addEventListener('click', load);
    document.getElementById('btnReload').addEventListener('click', load);
    document.getElementById('fNivel').addEventListener('change', load);
    ['fDesde', 'fHasta'].forEach(function (id) { document.getElementById(id).addEventListener('keydown', function (e) { if (e.key === 'Enter') load(); }); });
    init();
})();
