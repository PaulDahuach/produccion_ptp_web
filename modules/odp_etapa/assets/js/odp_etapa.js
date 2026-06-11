/* Consulta Órdenes de Proceso x Etapa — front. */
(function () {
    var table = null;

    function esc(s) {
        return (s === null || s === undefined) ? '' :
            String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
    function n(v) { return (v === null || v === '' || v === undefined) ? '' : v; }
    function miles(v) { return (v === null || v === '' || v === undefined) ? '' : Number(v).toLocaleString('es-AR'); }

    function params() {
        return new URLSearchParams({
            q: document.getElementById('fq').value.trim(),
            etapa: document.getElementById('fEtapa').value,
            desde: document.getElementById('fDesde').value.trim(),
            hasta: document.getElementById('fHasta').value.trim()
        });
    }

    async function loadEtapas() {
        try {
            var j = await (await fetch('api.php?action=etapas')).json();
            if (!j.ok) return;
            var sel = document.getElementById('fEtapa');
            j.data.forEach(function (e) {
                var o = document.createElement('option');
                o.value = e.CODETA; o.textContent = e.DENETA; sel.appendChild(o);
            });
        } catch (e) {}
    }

    async function loadResumen() {
        var p = params(); p.set('action', 'resumen');
        var cont = document.getElementById('resumenCards');
        try {
            var j = await (await fetch('api.php?' + p.toString())).json();
            if (!j.ok) { cont.innerHTML = ''; return; }
            cont.innerHTML = j.data.map(function (r) {
                return '<div class="col-6 col-md-3 col-lg-2">' +
                    '<div class="card etapa-card" style="cursor:pointer" data-etapa="' + esc(r.CODETA) + '" title="Ver detalle de ' + esc(r.ETAPA) + '"><div class="card-body py-2 px-3">' +
                    '<div class="small text-muted text-truncate">' + esc(r.ETAPA) + '</div>' +
                    '<div class="fw-bold">' + n(r.TOTAL_ORDENES) + ' <span class="text-muted fw-normal small">órd.</span></div>' +
                    '<div class="small text-muted">' + miles(r.TOTAL_PRENDAS) + ' prendas</div>' +
                    '</div></div></div>';
            }).join('');
            // tarjeta clickeable → drill-down a esa etapa
            cont.querySelectorAll('.etapa-card').forEach(function (el) {
                el.addEventListener('click', function () {
                    document.getElementById('fEtapa').value = el.dataset.etapa;
                    load();
                });
            });
        } catch (e) { cont.innerHTML = ''; }
    }

    async function loadList() {
        var p = params(); p.set('action', 'list');
        document.getElementById('resumen').textContent = 'Cargando...';
        var j = await (await fetch('api.php?' + p.toString())).json();
        if (!j.ok) { document.getElementById('resumen').textContent = 'Error: ' + (j.error || ''); return; }
        var rows = j.data || [];
        var data = rows.map(function (x) {
            return [esc(x.ETAPA), n(x.ODP), esc(x.CLIENTE), esc(x.MARCA), esc(x.PRENDA),
                esc(x.OCORTE), esc(x.CARTICULO), n(x.PTP), miles(x.CANTIDAD), n(x.DIAS_REC), n(x.DIAS_DEF)];
        });
        if (table) { table.clear().rows.add(data).draw(); }
        else {
            table = new DataTable('#tbl', {
                data: data,
                columnDefs: [{ targets: [8, 9, 10], className: 'text-end' }],
                order: [[0, 'asc'], [1, 'asc']], pageLength: 50,
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-AR.json' }
            });
        }
        document.getElementById('resumen').textContent = rows.length + ' orden(es)';
    }

    function load() {
        loadResumen();
        var b = document.getElementById('btnImprimir');
        if (b) b.href = 'print.php?' + params().toString();
        // El detalle (todas las órdenes activas) son ~13k filas con DateDiff por fila → 45s en Access.
        // Sólo lo cargamos con un filtro (etapa / fecha / texto); sin filtro mostramos el resumen.
        var hasFilter = document.getElementById('fEtapa').value
            || document.getElementById('fDesde').value.trim()
            || document.getElementById('fHasta').value.trim()
            || document.getElementById('fq').value.trim();
        if (hasFilter) { loadList(); }
        else {
            if (table) { table.clear().draw(); }
            document.getElementById('resumen').textContent = 'Elegí una etapa (tarjeta de arriba) o aplicá un filtro para ver el detalle.';
        }
    }

    document.getElementById('btnFiltrar').addEventListener('click', load);
    document.getElementById('btnReload').addEventListener('click', load);
    document.getElementById('fq').addEventListener('keydown', function (e) { if (e.key === 'Enter') load(); });
    document.getElementById('btnLimpiar').addEventListener('click', function () {
        ['fq', 'fEtapa', 'fDesde', 'fHasta'].forEach(function (id) { document.getElementById(id).value = ''; });
        load();
    });

    loadEtapas();
    load();
})();
