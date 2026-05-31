/* Consulta Órdenes de Proceso x Lote — front. */
(function () {
    var table = null;

    function esc(s) {
        return (s === null || s === undefined) ? '' :
            String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
    function num(v) { return (v === null || v === '' || v === undefined) ? '' : v; }

    async function loadEtapas() {
        try {
            var r = await fetch('api.php?action=etapas');
            var j = await r.json();
            if (!j.ok) return;
            var sel = document.getElementById('fEtapa');
            j.data.forEach(function (e) {
                var o = document.createElement('option');
                o.value = e.CODETA; o.textContent = e.DENETA;
                sel.appendChild(o);
            });
        } catch (e) {}
    }

    async function load() {
        var p = new URLSearchParams({
            action: 'list',
            q: document.getElementById('fq').value.trim(),
            etapa: document.getElementById('fEtapa').value,
            desde: document.getElementById('fDesde').value.trim(),
            hasta: document.getElementById('fHasta').value.trim()
        });
        document.getElementById('resumen').textContent = 'Cargando...';
        var r = await fetch('api.php?' + p.toString());
        var j = await r.json();
        if (!j.ok) { document.getElementById('resumen').textContent = 'Error: ' + (j.error || ''); return; }
        render(j.data || []);
    }

    function render(rows) {
        var data = rows.map(function (x) {
            return [
                esc(x.SECTOR), num(x.ODP), esc(x.CLIENTE), esc(x.MARCA), esc(x.PRENDA),
                esc(x.PROCESO), esc(x.OCORTE), esc(x.CARTICULO), num(x.PTP),
                num(x.CANTIDAD), num(x.DIAS_REC), num(x.DIAS_DEF), num(x.ORDEN), num(x.LOTE)
            ];
        });
        if (table) { table.clear().rows.add(data).draw(); }
        else {
            table = new DataTable('#tbl', {
                data: data,
                columnDefs: [{ targets: [9, 10, 11, 12, 13], className: 'text-end' }],
                order: [[0, 'asc'], [1, 'asc']],
                pageLength: 50,
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-AR.json' }
            });
        }
        document.getElementById('resumen').textContent = rows.length + ' lote(s) en proceso';
        sincPrint();
    }

    function sincPrint() {
        var p = new URLSearchParams({
            q: document.getElementById('fq').value.trim(),
            etapa: document.getElementById('fEtapa').value,
            desde: document.getElementById('fDesde').value.trim(),
            hasta: document.getElementById('fHasta').value.trim()
        });
        var b = document.getElementById('btnImprimir');
        if (b) b.href = 'print.php?' + p.toString();
    }

    document.getElementById('btnFiltrar').addEventListener('click', load);
    document.getElementById('btnReload').addEventListener('click', load);
    document.getElementById('fq').addEventListener('keydown', function (e) { if (e.key === 'Enter') load(); });
    document.getElementById('btnLimpiar').addEventListener('click', function () {
        document.getElementById('fq').value = '';
        document.getElementById('fEtapa').value = '';
        document.getElementById('fDesde').value = '';
        document.getElementById('fHasta').value = '';
        load();
    });

    loadEtapas();
    load();
})();
