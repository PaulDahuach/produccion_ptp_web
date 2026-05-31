/* Consulta Órdenes de Proceso Retrasadas — front. */
(function () {
    var table = null;
    function esc(s) { return (s === null || s === undefined) ? '' : String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
    function miles(v) { return (v === null || v === '' || v === undefined) ? '' : Number(v).toLocaleString('es-AR'); }
    function val(id) { return document.getElementById(id).value.trim(); }

    function params() {
        return {
            dias: val('fDias') || '0', desde: val('fDesde'), hasta: val('fHasta'),
            odp: val('fOdp'), ocorte: val('fOcorte'), art: val('fArt'),
            cli: val('fCli'), mar: val('fMar'), pre: val('fPre')
        };
    }
    function qs(o) { var p = new URLSearchParams(); Object.keys(o).forEach(function (k) { if (o[k] !== '' && o[k] != null) p.set(k, o[k]); }); return p.toString(); }

    function fillSel(id, arr) {
        var s = document.getElementById(id), keep = s.firstElementChild.outerHTML;
        s.innerHTML = keep + arr.map(function (o) { return '<option value="' + esc(o.id) + '">' + esc(o.den) + '</option>'; }).join('');
    }

    async function init() {
        var j = await (await fetch('api.php?action=init')).json();
        if (j.ok) {
            if (!val('fDesde')) document.getElementById('fDesde').value = j.data.desde || '';
            if (!val('fHasta')) document.getElementById('fHasta').value = j.data.hasta || '';
            fillSel('fCli', j.data.clientes); fillSel('fMar', j.data.marcas); fillSel('fPre', j.data.prendas);
        }
        load();
    }

    async function load() {
        document.getElementById('resumen').textContent = 'Cargando…';
        var j = await (await fetch('api.php?action=list&' + qs(params()))).json();
        if (!j.ok) { document.getElementById('resumen').textContent = 'Error: ' + j.error; return; }
        var rows = j.data || [];
        var data = rows.map(function (x) {
            return [x.DIAS_DEF, x.DIAS_REC, esc(x.SECTOR), x.ODP, esc(x.CLIENTE), esc(x.PRENDA), esc(x.MARCA),
                esc(x.OCORTE), esc(x.CARTICULO), x.PTP, miles(x.CANTIDAD)];
        });
        if (table) { table.clear().rows.add(data).draw(); }
        else {
            table = new DataTable('#tblRet', {
                data: data, pageLength: 50, order: [[0, 'desc']],
                columnDefs: [{ targets: [0, 1, 10], className: 'text-end' }],
                createdRow: function (row, d) {
                    if (d[0] >= 30) row.classList.add('table-danger');
                    else if (d[0] >= 15) row.classList.add('table-warning');
                },
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-AR.json' }
            });
            document.querySelector('#tblRet tbody').addEventListener('click', function (e) {
                var tr = e.target.closest('tr'); if (!tr) return; var d = table.row(tr).data();
                if (d) window.open('../imprimir_orden/?id=' + encodeURIComponent(d[3]), '_blank');
            });
        }
        document.getElementById('resumen').textContent = rows.length + ' orden(es) retrasada(s)';
        var p = params();
        document.getElementById('btnImprimir').classList.toggle('disabled', !rows.length);
        document.getElementById('btnExcel').classList.toggle('disabled', !rows.length);
        document.getElementById('btnImprimir').href = 'print.php?' + qs(p);
        document.getElementById('btnExcel').href = 'export.php?' + qs(p);
    }

    document.getElementById('btnFiltrar').addEventListener('click', load);
    document.getElementById('btnReload').addEventListener('click', load);
    ['fDias', 'fOdp', 'fOcorte', 'fArt', 'fDesde', 'fHasta'].forEach(function (id) {
        document.getElementById(id).addEventListener('keydown', function (e) { if (e.key === 'Enter') load(); });
    });
    document.getElementById('btnLimpiar').addEventListener('click', function () {
        ['fOdp', 'fOcorte', 'fArt'].forEach(function (id) { document.getElementById(id).value = ''; });
        ['fCli', 'fMar', 'fPre'].forEach(function (id) { document.getElementById(id).value = ''; });
        load();
    });
    init();
})();
