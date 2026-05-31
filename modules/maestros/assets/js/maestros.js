/* Maestro de consulta genérico — construye columnas desde los datos. */
(function () {
    var table = null;
    var m = new URLSearchParams(location.search).get('m') || '';

    function fmtCell(v) {
        if (v === null || v === undefined) return '';
        var s = String(v);
        // fechas ISO → dd/mm/aaaa
        var d = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (d) return d[3] + '/' + d[2] + '/' + d[1];
        return s;
    }
    function esc(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    async function load() {
        document.getElementById('resumen').textContent = 'Cargando...';
        var j = await (await fetch('api.php?action=list&m=' + encodeURIComponent(m))).json();
        if (!j.ok) { document.getElementById('resumen').textContent = 'Error: ' + (j.error || ''); return; }
        render(j.data || []);
    }

    function render(rows) {
        var cols = rows.length ? Object.keys(rows[0]) : [];
        document.getElementById('thead').innerHTML = cols.map(function (c) { return '<th>' + esc(c) + '</th>'; }).join('');
        var data = rows.map(function (row) { return cols.map(function (c) { return fmtCell(row[c]); }); });
        if (table) { table.destroy(); document.querySelector('#tbl tbody').innerHTML = ''; }
        table = new DataTable('#tbl', {
            data: data,
            columns: cols.map(function (c) { return { title: c }; }),
            pageLength: 50,
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-AR.json' }
        });
        document.getElementById('resumen').textContent = rows.length + ' registro(s)';
    }

    document.getElementById('btnReload').addEventListener('click', load);
    load();
})();
