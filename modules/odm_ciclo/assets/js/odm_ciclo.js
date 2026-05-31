/* Muestras — Confirmación y Entrega (ciclo de vida). */
(function () {
    var table = null, selOdm = null, selPend = 0;
    function esc(s) { return (s === null || s === undefined) ? '' : String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
    function miles(v) { return (v === null || v === '' || v === undefined) ? '' : Number(v).toLocaleString('es-AR'); }
    function fase() { return document.querySelector('input[name=fase]:checked').value; }

    var HEAD = {
        confirmar: ['N° Muestra', 'Fecha', 'Cliente', 'Marca', 'Cantidad', 'PTP N°', ''],
        entregar: ['N° Muestra', 'Fecha', 'Cliente', 'Marca', 'Cantidad', 'Remitido', 'Pendiente', 'PTP N°', '']
    };

    async function load() {
        var f = fase();
        document.getElementById('hint').textContent = f === 'entregar' ? 'Entregá (total o parcial) y se imprime el remito' : 'Confirmá las muestras pendientes';
        document.getElementById('resumen').textContent = 'Cargando…';
        var p = new URLSearchParams({ action: 'list', fase: f, q: document.getElementById('fq').value.trim() });
        var j = await (await fetch('api.php?' + p.toString())).json();
        if (!j.ok) { document.getElementById('resumen').textContent = 'Error: ' + j.error; return; }
        var rows = j.data || [];
        // rebuild table on fase change
        if (table) { table.destroy(); document.querySelector('#tbl tbody').innerHTML = ''; table = null; }
        document.getElementById('thd').innerHTML = '<tr>' + HEAD[f].map((h, i) => {
            var cls = (f === 'confirmar' ? [4] : [4, 5, 6]).indexOf(i) >= 0 ? ' class="text-end"' : '';
            return '<th' + cls + '>' + esc(h) + '</th>';
        }).join('') + '</tr>';
        var data = rows.map(function (r) {
            var btn;
            if (f === 'entregar') btn = '<button class="btn btn-sm btn-primary py-0 px-2 act" data-odm="' + r.ODM + '" data-pend="' + r.PEND + '" data-can="' + r.CANT + '" data-rem="' + r.REMIT + '"><i class="bi bi-truck"></i> Entregar</button>';
            else btn = '<button class="btn btn-sm btn-success py-0 px-2 act" data-odm="' + r.ODM + '"><i class="bi bi-check2"></i> Confirmar</button>';
            if (f === 'entregar') return [r.ODM, esc(r.FDEODM), esc(r.CLIENTE), esc(r.MARCA), miles(r.CANT), miles(r.REMIT), miles(r.PEND), r.PTP, btn];
            return [r.ODM, esc(r.FDEODM), esc(r.CLIENTE), esc(r.MARCA), miles(r.CANT), r.PTP, btn];
        });
        var endCols = f === 'entregar' ? [4, 5, 6] : [4];
        table = new DataTable('#tbl', { data: data, pageLength: 50, order: [[0, 'desc']], columnDefs: [{ targets: endCols, className: 'text-end' }, { targets: [HEAD[f].length - 1], orderable: false }], language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-AR.json' } });
        document.querySelector('#tbl tbody').addEventListener('click', onAct);
        document.getElementById('resumen').textContent = rows.length + (f === 'entregar' ? ' muestra(s) por entregar' : ' muestra(s) por confirmar');
    }

    function onAct(e) {
        var b = e.target.closest('.act'); if (!b) return;
        selOdm = b.getAttribute('data-odm');
        if (fase() === 'confirmar') {
            document.getElementById('confBody').textContent = '¿Confirmar la Orden de Muestra N° ' + selOdm + '? Pasará a estado Confirmada.';
            new bootstrap.Modal(document.getElementById('modalConf')).show();
        } else {
            selPend = Number(b.getAttribute('data-pend')) || 0;
            document.getElementById('entInfo').innerHTML = 'Muestra N° <b>' + selOdm + '</b> · Cantidad ' + miles(b.getAttribute('data-can')) + ' · Remitido ' + miles(b.getAttribute('data-rem')) + ' · Pendiente <b>' + miles(selPend) + '</b>';
            document.getElementById('entCant').value = selPend;
            document.getElementById('entCant').max = selPend;
            document.getElementById('entErr').textContent = '';
            new bootstrap.Modal(document.getElementById('modalEnt')).show();
        }
    }

    async function doConfirmar() {
        var fd = new FormData(); fd.append('__id', selOdm);
        var j = await (await fetch('api.php?action=confirmar', { method: 'POST', body: fd })).json();
        bootstrap.Modal.getInstance(document.getElementById('modalConf')).hide();
        if (!j.ok) { toast(j.error || 'No se pudo', 'danger'); return; }
        toast('Muestra N° ' + j.data.numodm + ' confirmada', 'success');
        load();
    }

    async function doEntregar() {
        var cant = Number(document.getElementById('entCant').value);
        if (!(cant > 0)) { document.getElementById('entErr').textContent = 'Ingresá una cantidad'; return; }
        if (cant > selPend) { document.getElementById('entErr').textContent = 'Supera lo pendiente (' + miles(selPend) + ')'; return; }
        var fd = new FormData(); fd.append('__id', selOdm); fd.append('cant', cant);
        var j = await (await fetch('api.php?action=entregar', { method: 'POST', body: fd })).json();
        bootstrap.Modal.getInstance(document.getElementById('modalEnt')).hide();
        if (!j.ok) { toast(j.error || 'No se pudo', 'danger'); return; }
        toast('Remito N° ' + j.data.ordodm + ' de la muestra ' + j.data.numodm + (j.data.completa ? ' (entrega completa → Remitida)' : ' (entrega parcial)'), 'success');
        window.open('remito.php?numodm=' + encodeURIComponent(j.data.numodm) + '&ordodm=' + encodeURIComponent(j.data.ordodm), '_blank');
        load();
    }

    function toast(msg, type) {
        var t = document.getElementById('toastMsg'); document.getElementById('toastBody').textContent = msg;
        t.className = 'toast align-items-center border-0 text-bg-' + (type || 'info');
        bootstrap.Toast.getOrCreateInstance(t, { delay: type === 'danger' ? 7000 : 4500 }).show();
    }

    document.getElementById('btnFiltrar').addEventListener('click', load);
    document.getElementById('btnReload').addEventListener('click', load);
    document.getElementById('fq').addEventListener('keydown', function (e) { if (e.key === 'Enter') load(); });
    Array.prototype.forEach.call(document.querySelectorAll('input[name=fase]'), el => el.addEventListener('change', load));
    document.getElementById('btnConfOk').addEventListener('click', doConfirmar);
    document.getElementById('btnEntOk').addEventListener('click', doEntregar);
    load();
})();
