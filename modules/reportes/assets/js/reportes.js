/* Reportes/listados — columnas dinámicas desde los datos + filtros server-side opcionales. */
(function () {
    var table = null;
    var FILTROS = window.REP_FILTROS || [];
    function esc(s) { return (s === null || s === undefined) ? '' : String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }

    // Render de la barra de filtros (si el reporte declara 'filtros').
    function buildFiltros() {
        var cont = document.getElementById('repFiltros');
        if (!cont || !FILTROS.length) { if (cont) cont.style.display = 'none'; return; }
        cont.innerHTML = FILTROS.map(function (f) {
            return '<div><label class="form-label mb-0 small">' + esc(f.label) + (f.req ? ' *' : '') + '</label>' +
                '<input class="form-control form-control-sm rep-f" data-param="' + esc(f.param) +
                '" type="' + (f.tipo === 'int' ? 'number' : 'text') + '" style="max-width:14rem"></div>';
        }).join('') + '<button id="repVer" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Ver</button>';
        cont.querySelector('#repVer').addEventListener('click', load);
        cont.querySelectorAll('.rep-f').forEach(function (el) {
            el.addEventListener('keydown', function (e) { if (e.key === 'Enter') load(); });
        });
    }

    function params() {
        var p = 'action=list&r=' + encodeURIComponent(window.REP_R);
        document.querySelectorAll('#repFiltros .rep-f').forEach(function (el) {
            if (el.value.trim() !== '') p += '&' + encodeURIComponent(el.dataset.param) + '=' + encodeURIComponent(el.value.trim());
        });
        return p;
    }

    // ¿falta algún filtro requerido?
    function faltaReq() {
        for (var i = 0; i < FILTROS.length; i++) {
            if (FILTROS[i].req) {
                var el = document.querySelector('#repFiltros .rep-f[data-param="' + FILTROS[i].param + '"]');
                if (!el || el.value.trim() === '') return FILTROS[i].label;
            }
        }
        return null;
    }

    async function load() {
        var falta = faltaReq();
        if (falta) {
            if (table) { table.clear().draw(); }
            document.getElementById('resumen').textContent = 'Ingresá ' + falta + ' para ver el reporte.';
            return;
        }
        document.getElementById('resumen').textContent = 'Cargando...';
        var j = await (await fetch('api.php?' + params())).json();
        if (!j.ok) { document.getElementById('resumen').textContent = 'Error: ' + j.error; return; }
        var rows = j.data || [];
        var cols = rows.length ? Object.keys(rows[0]) : [];
        document.getElementById('thead').innerHTML = cols.map(function (c) { return '<th>' + esc(c) + '</th>'; }).join('');
        var data = rows.map(function (r) { return cols.map(function (c) { return esc(r[c]); }); });
        if (table) { table.destroy(); document.querySelector('#tbl tbody').innerHTML = ''; }
        table = new DataTable('#tbl', {
            data: data, columns: cols.map(function (c) { return { title: c }; }),
            pageLength: 50, language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-AR.json' }
        });
        document.getElementById('resumen').textContent = rows.length + ' registro(s)';
    }

    document.getElementById('btnReload').addEventListener('click', load);
    document.getElementById('btnPrint').addEventListener('click', function () { window.print(); });
    buildFiltros();
    load();   // si hay filtro requerido vacío, muestra el hint en vez de cargar todo
})();
