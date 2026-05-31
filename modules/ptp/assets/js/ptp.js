/* Consulta de PTP — lista + ficha (ruta de procesos), solo lectura. */
(function () {
    var table = null, modal = null;
    function esc(s) { return (s === null || s === undefined) ? '' : String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
    function set(id, v) { document.getElementById(id).textContent = (v === null || v === undefined || v === '') ? '—' : v; }
    function val(id) { return document.getElementById(id).value.trim(); }

    async function load() {
        document.getElementById('resumen').textContent = 'Cargando...';
        var p = new URLSearchParams({ action: 'list', q: val('fq'), estado: val('fEstado') });
        var j = await (await fetch('api.php?' + p.toString())).json();
        if (!j.ok) { document.getElementById('resumen').textContent = 'Error: ' + j.error; return; }
        var rows = j.data || [];
        var data = rows.map(function (r) { return [r.NPP, esc(r.FDEPTP), esc(r.CLIENTE), esc(r.MARCA), esc(r.ESTADO), esc(r.DENOM)]; });
        if (table) { table.clear().rows.add(data).draw(); }
        else {
            table = new DataTable('#tbl', { data: data, pageLength: 50, order: [[0, 'desc']], language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-AR.json' } });
            document.querySelector('#tbl tbody').addEventListener('click', function (e) { var tr = e.target.closest('tr'); if (!tr) return; var d = table.row(tr).data(); if (d) ver(d[0]); });
        }
        document.getElementById('resumen').textContent = rows.length + ' PTP';
    }

    async function ver(id) {
        var j = await (await fetch('api.php?action=get&id=' + encodeURIComponent(id))).json();
        if (!j.ok) { alert(j.error); return; }
        var c = j.data.cabecera;
        document.getElementById('fTit').textContent = 'PTP N° ' + c.NUMPTP;
        set('f_npp', c.NUMPTP); set('f_fec', c.FDEPTP); set('f_est', c.DENEDP); set('f_mar', c.DENMAR);
        set('f_cli', c.DENCLI); set('f_den', c.DENPTP);
        document.getElementById('btnImprimir').href = '../imprimir_ptp/?id=' + encodeURIComponent(c.NUMPTP);
        var items = j.data.items || [];
        document.getElementById('f_items').innerHTML = items.length ? items.map(function (it) {
            return '<tr><td>' + esc(it.ORDPTP) + '</td><td>' + esc(it.DENPRC) + '</td><td>' + esc(it.DENETA) + '</td><td>' + esc(it.DENCDP) + '</td><td class="text-end">' + esc(it.PORPTP) + '</td><td>' + esc(it.OBSPTP) + '</td></tr>';
        }).join('') : '<tr><td colspan="6" class="text-muted text-center">Sin procesos</td></tr>';
        if (!modal) modal = new bootstrap.Modal(document.getElementById('modalFicha'));
        modal.show();
    }

    document.getElementById('btnReload').addEventListener('click', load);
    document.getElementById('btnFiltrar').addEventListener('click', load);
    document.getElementById('fq').addEventListener('keydown', function (e) { if (e.key === 'Enter') load(); });
    document.getElementById('fEstado').addEventListener('change', load);
    load();
})();
