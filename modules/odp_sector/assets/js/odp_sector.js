/* Consulta Órdenes de Proceso x Sector — front. */
(function () {
    var table = null;

    function esc(s) {
        return (s === null || s === undefined) ? '' :
            String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
    function n(v) { return (v === null || v === '' || v === undefined) ? '' : v; }
    function fecha(v) {
        if (!v) return '';
        var s = String(v);
        var m = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (m) return m[3] + '/' + m[2] + '/' + m[1];
        return s.split(' ')[0];
    }

    async function loadSectores() {
        var sel = document.getElementById('fSector');
        try {
            var j = await (await fetch('api.php?action=sectores')).json();
            if (!j.ok) return;
            sel.innerHTML = '';
            j.data.forEach(function (e) {
                var o = document.createElement('option');
                o.value = e.CODETA;
                o.textContent = e.DENETA + ' (' + e.TOTAL + ')';
                sel.appendChild(o);
            });
        } catch (e) {}
    }

    async function load() {
        var p = new URLSearchParams({
            action: 'list',
            etapa: document.getElementById('fSector').value,
            q: document.getElementById('fq').value.trim()
        });
        document.getElementById('resumen').textContent = 'Cargando...';
        var j = await (await fetch('api.php?' + p.toString())).json();
        if (!j.ok) { document.getElementById('resumen').textContent = 'Error: ' + (j.error || ''); return; }
        var rows = j.data || [];
        var data = rows.map(function (x) {
            return [fecha(x.PROGRAMA), n(x.ORDENP), n(x.ODP), esc(x.MARCA), n(x.PTP),
                esc(x.PROCESO), n(x.CANTIDAD), n(x.PENDIENTE), fecha(x.DEFINICION), n(x.DIAS_REC), n(x.DIAS_DEF)];
        });
        if (table) { table.clear().rows.add(data).draw(); }
        else {
            table = new DataTable('#tbl', {
                data: data,
                columnDefs: [{ targets: [1, 6, 7, 9, 10], className: 'text-end' }],
                order: [], pageLength: 50,
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-AR.json' }
            });
        }
        document.getElementById('resumen').textContent = rows.length + ' proceso(s) en el sector';
        var b = document.getElementById('btnImprimir');
        if (b) b.href = 'print.php?' + new URLSearchParams({
            etapa: document.getElementById('fSector').value,
            q: document.getElementById('fq').value.trim()
        }).toString();
    }

    document.getElementById('btnFiltrar').addEventListener('click', load);
    document.getElementById('btnReload').addEventListener('click', load);
    document.getElementById('fSector').addEventListener('change', load);
    document.getElementById('fq').addEventListener('keydown', function (e) { if (e.key === 'Enter') load(); });
    document.getElementById('btnLimpiar').addEventListener('click', function () {
        document.getElementById('fq').value = ''; load();
    });

    (async function () { await loadSectores(); load(); })();
})();
