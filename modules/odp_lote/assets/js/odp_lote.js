/* Consulta Órdenes de Proceso x Lote — master-detail (sectores → lotes) + drill. */
(function () {
    var table = null;
    var sectorSel = null;     // sector actualmente seleccionado
    var modalProc = null;

    function esc(s) {
        return (s === null || s === undefined) ? '' :
            String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
    function miles(v) { return (v === null || v === '' || v === undefined) ? '' : Number(v).toLocaleString('es-AR'); }
    function val(id) { return document.getElementById(id).value.trim(); }

    /** Querystring con los filtros actuales (sin sector). */
    function baseParams() {
        return {
            desde: val('fDesde'), hasta: val('fHasta'),
            odp: val('fOdp'), ocorte: val('fOcorte'), art: val('fArt'),
            cli: val('fCli'), mar: val('fMar'), pre: val('fPre'), prc: val('fPrc')
        };
    }
    function qs(obj) {
        var p = new URLSearchParams();
        Object.keys(obj).forEach(function (k) { if (obj[k] !== '' && obj[k] != null) p.set(k, obj[k]); });
        return p.toString();
    }

    function fillSel(id, arr) {
        var s = document.getElementById(id);
        var keep = s.firstElementChild ? s.firstElementChild.outerHTML : '';
        s.innerHTML = keep + arr.map(function (o) {
            return '<option value="' + esc(o.id) + '">' + esc(o.den) + '</option>';
        }).join('');
    }

    async function init() {
        var j = await (await fetch('api.php?action=init')).json();
        if (!j.ok) { document.getElementById('sectoresMsg').textContent = 'Error: ' + j.error; return; }
        if (!val('fDesde')) document.getElementById('fDesde').value = j.data.desde || '';
        if (!val('fHasta')) document.getElementById('fHasta').value = j.data.hasta || '';
        fillSel('fCli', j.data.clientes); fillSel('fMar', j.data.marcas);
        fillSel('fPre', j.data.prendas); fillSel('fPrc', j.data.procesos);
        await loadSectores();
    }

    async function loadSectores() {
        sectorSel = null;
        document.getElementById('detTitulo').textContent = 'Elegí un sector a la izquierda';
        document.getElementById('detResumen').textContent = '';
        if (table) table.clear().draw();
        syncSalidas();
        document.getElementById('sectoresMsg').textContent = 'Cargando…';
        var j = await (await fetch('api.php?action=resumen&' + qs(baseParams()))).json();
        var cont = document.getElementById('panelSectores');
        if (!j.ok) { cont.innerHTML = '<div class="text-danger small p-3">Error: ' + esc(j.error) + '</div>'; return; }
        var d = j.data, secs = d.sectores || [];
        if (!secs.length) {
            cont.innerHTML = '<div class="text-muted small p-3">Sin órdenes para los filtros.</div>';
        } else {
            cont.innerHTML = secs.map(function (s) {
                return '<a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-sector="' + esc(s.SECTOR) + '">' +
                    '<span>' + esc(s.SECTOR) + '<br><span class="text-muted small">' + miles(s.ORDENES) + ' órd.</span></span>' +
                    '<span class="badge bg-primary rounded-pill">' + miles(s.PRENDAS) + '</span></a>';
            }).join('');
            Array.prototype.forEach.call(cont.querySelectorAll('[data-sector]'), function (el) {
                el.addEventListener('click', function () { selSector(el.getAttribute('data-sector'), el); });
            });
        }
        document.getElementById('totPrendas').textContent = miles(d.tot_prendas) + ' prendas';
        // Botón Administración
        var aw = document.getElementById('adminWrap');
        if (d.admin) {
            aw.style.display = '';
            document.getElementById('adminCount').textContent = miles(d.admin.ORDENES);
        } else { aw.style.display = 'none'; }
    }

    function marcarActivo(el) {
        Array.prototype.forEach.call(document.querySelectorAll('#panelSectores .active'), function (e) { e.classList.remove('active'); });
        if (el) el.classList.add('active');
    }

    async function selSector(sector, el) {
        sectorSel = sector;
        marcarActivo(el);
        document.getElementById('detTitulo').textContent = sector;
        document.getElementById('detResumen').textContent = 'Cargando…';
        var p = baseParams(); p.sector = sector;
        var j = await (await fetch('api.php?action=detalle&' + qs(p))).json();
        if (!j.ok) { document.getElementById('detResumen').textContent = 'Error: ' + j.error; return; }
        render(j.data || []);
        syncSalidas();
    }

    function render(rows) {
        var data = rows.map(function (x) {
            var acc = '<button class="btn btn-sm btn-outline-info py-0 px-1 me-1 b-proc" data-odp="' + esc(x.ODP) + '" title="Ver procesos"><i class="bi bi-diagram-3"></i></button>' +
                '<a class="btn btn-sm btn-outline-primary py-0 px-1" target="_blank" href="../imprimir_orden/?id=' + encodeURIComponent(x.ODP) + '" title="Reimprimir orden"><i class="bi bi-printer"></i></a>';
            return [
                x.ODP, esc(x.CLIENTE), esc(x.PRENDA), esc(x.MARCA),
                esc(x.OCORTE), esc(x.CARTICULO), x.PTP,
                miles(x.CANTIDAD), x.DIAS_REC, x.DIAS_DEF, x.ORDEN,
                x.OBS ? '<span class="text-warning fw-bold" title="Tiene observaciones">★</span>' : '',
                acc
            ];
        });
        if (table) { table.clear().rows.add(data).draw(); }
        else {
            table = new DataTable('#tblDet', {
                data: data,
                columnDefs: [
                    { targets: [7, 8, 9, 10], className: 'text-end' },
                    { targets: [11, 12], className: 'text-center', orderable: false }
                ],
                order: [[0, 'asc']], pageLength: 50,
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-AR.json' }
            });
            document.querySelector('#tblDet tbody').addEventListener('click', function (e) {
                var b = e.target.closest('.b-proc');
                if (b) { e.stopPropagation(); verProcesos(b.getAttribute('data-odp')); }
            });
        }
        var tot = rows.reduce(function (a, r) { return a + (Number(r.CANTIDAD) || 0); }, 0);
        document.getElementById('detResumen').textContent = rows.length + ' orden(es) · ' + miles(tot) + ' prendas';
    }

    async function verProcesos(odp) {
        var j = await (await fetch('api.php?action=procesos&odp=' + encodeURIComponent(odp))).json();
        if (!j.ok) { alert(j.error); return; }
        var c = j.data.cabecera || {};
        document.getElementById('pTit').textContent = 'Procesos · Orden N° ' + odp;
        document.getElementById('pCab').textContent = (c.DENCLI || '') + (c.DENMAR ? ' — ' + c.DENMAR : '') + (c.CANODP ? ' · ' + miles(c.CANODP) + ' prendas' : '');
        document.getElementById('pReimp').href = '../imprimir_orden/?id=' + encodeURIComponent(odp);
        var ps = j.data.procesos || [];
        document.getElementById('pBody').innerHTML = ps.length ? ps.map(function (p) {
            return '<tr><td>' + esc(p.ORDEN) + '</td><td>' + esc(p.PROCESO) + '</td><td>' + esc(p.SECTOR) + '</td>' +
                '<td class="text-end">' + (p.CANTIDAD != null ? miles(p.CANTIDAD) : '') + '</td>' +
                '<td class="text-end">' + (p.PENDIENTE != null ? miles(p.PENDIENTE) : '') + '</td>' +
                '<td>' + esc(p.OBS) + '</td></tr>';
        }).join('') : '<tr><td colspan="6" class="text-center text-muted">La orden aún no tiene procesos definidos.</td></tr>';
        if (!modalProc) modalProc = new bootstrap.Modal(document.getElementById('modalProc'));
        modalProc.show();
    }

    /** Sincroniza los hrefs de Imprimir / Excel con sector + filtros actuales. */
    function syncSalidas() {
        var bi = document.getElementById('btnImprimir'), be = document.getElementById('btnExcel');
        if (!sectorSel) {
            bi.classList.add('disabled'); be.classList.add('disabled');
            bi.href = '#'; be.href = '#';
        } else {
            var p = baseParams(); p.sector = sectorSel;
            bi.classList.remove('disabled'); be.classList.remove('disabled');
            bi.href = 'print.php?' + qs(p);
            be.href = 'export.php?' + qs(p);
        }
    }

    document.getElementById('btnFiltrar').addEventListener('click', loadSectores);
    document.getElementById('btnReload').addEventListener('click', loadSectores);
    document.getElementById('btnAdmin').addEventListener('click', function () {
        var fake = document.createElement('span');
        selSector('ADMINISTRACION', null);
        marcarActivo(null);
        this.classList.add('active');
    });
    ['fOdp', 'fOcorte', 'fArt', 'fDesde', 'fHasta'].forEach(function (id) {
        document.getElementById(id).addEventListener('keydown', function (e) { if (e.key === 'Enter') loadSectores(); });
    });
    document.getElementById('btnLimpiar').addEventListener('click', function () {
        ['fOdp', 'fOcorte', 'fArt'].forEach(function (id) { document.getElementById(id).value = ''; });
        ['fCli', 'fMar', 'fPre', 'fPrc'].forEach(function (id) { document.getElementById(id).value = ''; });
        loadSectores();
    });

    init();
})();
