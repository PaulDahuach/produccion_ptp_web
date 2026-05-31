/* Cotización (Presupuestos PTP) — lista + detalle (solo lectura). */
(function () {
    var table = null, modal = null;
    function esc(s) { return (s === null || s === undefined) ? '' : String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
    function money(v) { return (v === null || v === '' || v === undefined) ? '' : Number(v).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function set(id, v) { document.getElementById(id).textContent = (v === null || v === undefined || v === '') ? '—' : v; }

    async function load() {
        document.getElementById('resumen').textContent = 'Cargando...';
        var j = await (await fetch('api.php?action=list')).json();
        if (!j.ok) { document.getElementById('resumen').textContent = 'Error: ' + j.error; return; }
        var rows = j.data || [];
        var data = rows.map(function (r) { return [r.NPP, esc(r.FEXPPP), esc(r.CLIENTE), r.PTP, money(r.TOTAL)]; });
        if (table) { table.clear().rows.add(data).draw(); }
        else {
            table = new DataTable('#tbl', { data: data, pageLength: 50, order: [[0, 'desc']], columnDefs: [{ targets: [4], className: 'text-end' }], language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-AR.json' } });
            document.querySelector('#tbl tbody').addEventListener('click', function (e) { var tr = e.target.closest('tr'); if (!tr) return; var d = table.row(tr).data(); if (d) ver(d[0]); });
        }
        document.getElementById('resumen').textContent = rows.length + ' presupuesto(s)';
    }

    async function ver(id) {
        var j = await (await fetch('api.php?action=get&id=' + encodeURIComponent(id))).json();
        if (!j.ok) { alert(j.error); return; }
        var c = j.data.cabecera;
        document.getElementById('fTit').textContent = 'Presupuesto N° ' + c.NUMPPP;
        set('f_npp', c.NUMPPP); set('f_fec', c.FEXPPP); set('f_cli', c.DENCLI); set('f_ptp', c.NUMPTP); set('f_pre', c.DENPRE); set('f_tot', money(c.TOTPPP));
        var items = j.data.items || [];
        document.getElementById('f_items').innerHTML = items.length ? items.map(function (it) {
            return '<tr><td>' + it.ORDPPP + '</td><td>' + esc(it.DENPRC) + '</td><td class="text-end">' + (it.CANPPP || '') + '</td><td class="text-end">' + money(it.PREPPP) + '</td><td class="text-end">' + money(it.NETPPP) + '</td><td class="text-end">' + money(it.TOTPPP) + '</td></tr>';
        }).join('') : '<tr><td colspan="6" class="text-muted text-center">Sin procesos</td></tr>';
        document.getElementById('btnImprimir').href = 'print.php?id=' + encodeURIComponent(c.NUMPPP);
        if (!modal) modal = new bootstrap.Modal(document.getElementById('modalFicha'));
        modal.show();
    }

    document.getElementById('btnReload').addEventListener('click', load);
    load();
})();
