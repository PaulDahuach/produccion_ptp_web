/* Consulta de Órdenes de Muestra — lista + ficha (procesos + prendas). */
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
        var data = rows.map(function (r) { return [r.ODM, esc(r.FDEODM), esc(r.CLIENTE), esc(r.MARCA), esc(r.PRENDA), (r.CANTIDAD || ''), r.PTP, esc(r.ESTADO)]; });
        if (table) { table.clear().rows.add(data).draw(); }
        else {
            table = new DataTable('#tbl', { data: data, pageLength: 50, order: [[0, 'desc']], columnDefs: [{ targets: [5], className: 'text-end' }], language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-AR.json' } });
            document.querySelector('#tbl tbody').addEventListener('click', function (e) { var tr = e.target.closest('tr'); if (!tr) return; var d = table.row(tr).data(); if (d) ver(d[0]); });
        }
        document.getElementById('resumen').textContent = rows.length + ' orden(es) de muestra';
    }

    async function ver(id) {
        var j = await (await fetch('api.php?action=get&id=' + encodeURIComponent(id))).json();
        if (!j.ok) { alert(j.error); return; }
        var c = j.data.cabecera;
        document.getElementById('fTit').textContent = 'Orden de Muestra N° ' + c.NUMODM;
        set('f_num', c.NUMODM); set('f_fec', c.FDEODM); set('f_est', c.DENEDM); set('f_can', c.CANODM);
        set('f_cli', c.DENCLI); set('f_mar', c.DENMAR); set('f_ptp', c.NUMPTP); set('f_pre', c.DENPRE); set('f_tel', c.DENTEL);
        document.getElementById('btnImprimir').href = '../imprimir_odm/?id=' + encodeURIComponent(c.NUMODM);
        var ps = j.data.procesos || [];
        document.getElementById('f_procs').innerHTML = ps.length ? ps.map(function (p) {
            return '<tr><td>' + esc(p.ORDODM) + '</td><td>' + esc(p.DENPRC) + '</td><td>' + esc(p.DENETA) + '</td><td>' + esc(p.DENCDP) + '</td><td class="text-end">' + esc(p.PORODM) + '</td><td>' + esc(p.OBSODM) + '</td></tr>';
        }).join('') : '<tr><td colspan="6" class="text-muted text-center">Sin procesos</td></tr>';
        var pr = j.data.prendas || [];
        document.getElementById('f_prendas').innerHTML = pr.length ? pr.map(function (x) {
            return '<tr><td>' + esc(x.ORDODM) + '</td><td>' + esc(x.DENPRE) + '</td><td>' + esc(x.DENTEL) + '</td></tr>';
        }).join('') : '<tr><td colspan="3" class="text-muted text-center">Sin prendas</td></tr>';
        if (!modal) modal = new bootstrap.Modal(document.getElementById('modalFicha'));
        modal.show();
    }

    document.getElementById('btnReload').addEventListener('click', load);
    document.getElementById('btnFiltrar').addEventListener('click', load);
    document.getElementById('fq').addEventListener('keydown', function (e) { if (e.key === 'Enter') load(); });
    document.getElementById('fEstado').addEventListener('change', load);
    load();
})();
