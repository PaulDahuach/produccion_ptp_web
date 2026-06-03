/* Presupuestos PTP — Alta/Modificación (deriva de Orden de Muestra). Cálculo en vivo. */
const Q = {
    DEF: null, mode: 'idle', RO: false, dtO: null, dtB: null, currentId: null,
    ctx: { NUMODM: null, NUMPTP: null, CODCLI: null, CODPRE: null },

    el(id) { return document.getElementById(id); },
    esc(s) { if (s == null) return ''; var d = document.createElement('div'); d.textContent = String(s); return d.innerHTML; },
    money(v) { return (v === null || v === '' || v === undefined || isNaN(v)) ? '0,00' : Number(v).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    , num(v) { var s = String(v == null ? '' : v).replace(',', '.').trim(); return s === '' ? 0 : (parseFloat(s) || 0); }
    , r4(v) { return Math.round(v * 10000) / 10000; },

    async init() {
        var j = await (await fetch('api.php?action=init')).json();
        if (!j.ok) { this.toast('Error: ' + j.error, 'danger'); return; }
        this.DEF = j.data; this.RO = j.data.readonly;
        this.bind(); this.setMode('idle');
    },
    bind() {
        if (!this.RO) {
            this.el('btnNuevo').addEventListener('click', () => new bootstrap.Modal(this.el('modalODM')).show());
            this.el('btnGuardar').addEventListener('click', () => this.guardar());
            this.el('btnCancelar').addEventListener('click', () => this.cancelar());
            this.el('btnAnular').addEventListener('click', () => this.anular());
            this.el('modalODM').addEventListener('shown.bs.modal', () => this.loadODM());
            this.el('oq').addEventListener('keyup', () => { if (this.dtO) this.dtO.search(this.el('oq').value).draw(); });
            ['f_PDPPPP', 'f_PDCPPP'].forEach(i => this.el(i).addEventListener('input', () => this.recalc()));
            this.el('btnCopiar').addEventListener('click', () => new bootstrap.Modal(this.el('modalCopiar')).show());
            this.el('modalCopiar').addEventListener('shown.bs.modal', () => this.loadCopiar());
            this.el('cq').addEventListener('keyup', () => { if (this.dtC) this.dtC.search(this.el('cq').value).draw(); });
        }
        this.el('btnBuscar').addEventListener('click', () => new bootstrap.Modal(this.el('modalBuscar')).show());
        this.el('modalBuscar').addEventListener('shown.bs.modal', () => this.loadList());
        this.el('bq').addEventListener('keyup', () => { if (this.dtB) this.dtB.search(this.el('bq').value).draw(); });
    },

    // ---- elegir ODM (nuevo) ----
    async loadODM() {
        var j = await (await fetch('api.php?action=buscar_odm')).json();
        if (!j.ok) return;
        var data = (j.data || []).map(r => [r.ODM, r.FDEODM, r.CLIENTE, r.MARCA, r.PTP]);
        var self = this;
        if (this.dtO) this.dtO.clear().rows.add(data).draw();
        else this.dtO = $('#grdODM').DataTable({ data, pageLength: 25, order: [[0, 'desc']], language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-AR.json' }, createdRow: (row, d) => row.addEventListener('click', () => { self.cargarOdm(d[0]); bootstrap.Modal.getInstance(self.el('modalODM')).hide(); }) });
    },
    async cargarOdm(numodm) {
        var j = await (await fetch('api.php?action=cargar_odm&numodm=' + encodeURIComponent(numodm))).json();
        if (!j.ok) { this.toast(j.error, 'danger'); return; }
        var d = j.data;
        this.currentId = null;
        this.ctx = { NUMODM: d.NUMODM, NUMPTP: d.NUMPTP, CODCLI: d.CODCLI, CODPRE: d.CODPRE };
        this.el('fNum').textContent = '(nuevo)';
        this.el('f_FEXPPP').value = this.DEF.fechaDisp;
        this.el('fOdm').textContent = d.NUMODM; this.el('fPtp').textContent = d.NUMPTP || '—';
        this.el('fCli').textContent = d.DENCLI || '—'; this.el('fPre').textContent = d.DENPRE || '—';
        this.el('f_PDPPPP').value = 0; this.el('f_PDCPPP').value = 0; this.el('f_OBSPPP').value = '';
        this.clearRows();
        (d.lineas || []).forEach(l => this.addRow({ CODPRC: l.CODPRC, DENPRC: l.DENPRC, SECTOR: l.SECTOR, PDL: l.NETPRC, SUG: l.NETPRC, PBX: '', OBS: l.OBS }));
        this.setMode('create');
        this.recalc();
    },

    // ---- copiar precios de un presupuesto existente (feature nuevo) ----
    async loadCopiar() {
        var j = await (await fetch('api.php?action=list')).json();
        if (!j.ok) return;
        var data = (j.data || []).map(r => [r.PPP, r.FEXPPP, r.CLIENTE, r.PTP, this.money(r.TOTAL)]);
        var self = this;
        if (this.dtC) this.dtC.clear().rows.add(data).draw();
        else this.dtC = $('#grdCopiar').DataTable({ data, pageLength: 25, order: [[0, 'desc']], columnDefs: [{ targets: [4], className: 'text-end' }], language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-AR.json' }, createdRow: (row, d) => row.addEventListener('click', () => { self.copiarDe(d[0]); bootstrap.Modal.getInstance(self.el('modalCopiar')).hide(); }) });
    },
    async copiarDe(id) {
        var j = await (await fetch('api.php?action=get&id=' + encodeURIComponent(id))).json();
        if (!j.ok) { this.toast(j.error, 'danger'); return; }
        var c = j.data.cabecera;
        // % del encabezado
        this.el('f_PDPPPP').value = (c.PDPPPP == null ? 0 : c.PDPPPP);
        this.el('f_PDCPPP').value = (c.PDCPPP == null ? 0 : c.PDCPPP);
        // mapa proceso → precios del presupuesto origen
        var map = {};
        (j.data.lineas || []).forEach(l => { map[String(l.CODPRC)] = { SUG: l.SUGPPP, PBX: l.PBXPPP, OBS: l.OBS }; });
        var ok = 0, miss = 0;
        this.el('tblLin').querySelectorAll('tbody tr').forEach(tr => {
            var src = map[String(tr.dataset.codprc)];
            if (src) {
                tr.querySelector('.c-sug').value = (src.SUG == null ? '' : src.SUG);
                tr.querySelector('.c-pbx').value = (src.PBX == null ? '' : src.PBX);
                if (src.OBS) tr.dataset.obs = src.OBS;
                ok++;
            } else { miss++; }
        });
        this.recalc();
        this.toast('Copiado del presupuesto N° ' + id + ' — ' + ok + ' proceso(s) coincidieron' + (miss ? ', ' + miss + ' sin coincidencia (sin cambios)' : ''), ok ? 'success' : 'warning');
    },

    // ---- filas ----
    addRow(d) {
        var tb = this.el('tblLin').querySelector('tbody');
        var tr = this.el('rowLin').content.firstElementChild.cloneNode(true);
        tr.querySelector('.prc').textContent = d.DENPRC || ('#' + d.CODPRC);
        tr.querySelector('.sec').textContent = d.SECTOR || '';
        tr.dataset.codprc = d.CODPRC;
        tr.dataset.pdl = (d.PDL == null ? '' : d.PDL);
        tr.querySelector('.c-sug').value = (d.SUG == null ? '' : d.SUG);
        tr.querySelector('.c-pbx').value = (d.PBX == null ? '' : d.PBX);
        tr.querySelector('.c-obs').textContent = (d.OBS == null ? '' : d.OBS);   // OBS read-only (como el legacy)
        tr.dataset.obs = (d.OBS == null ? '' : d.OBS);
        tr.querySelector('.c-sug').addEventListener('input', () => this.recalc());
        tr.querySelector('.c-pbx').addEventListener('input', () => this.recalc());
        tb.appendChild(tr);
    },
    clearRows() { this.el('tblLin').querySelector('tbody').innerHTML = ''; },

    recalc() {
        var pdp = this.num(this.el('f_PDPPPP').value), pdc = this.num(this.el('f_PDCPPP').value);
        var nt0 = 0, idp = 0, nt1 = 0, idc = 0, tot = 0;
        this.el('tblLin').querySelectorAll('tbody tr').forEach((tr, i) => {
            tr.querySelector('.ord').textContent = i + 1;
            var sug = this.num(tr.querySelector('.c-sug').value);
            var pbxRaw = tr.querySelector('.c-pbx').value.trim();
            var pbx = pbxRaw === '' ? pdc : this.num(pbxRaw);
            var ibp = this.r4(sug * pdp / 100), pre = this.r4(sug - ibp);
            var ibx = this.r4(pre * pbx / 100), net = this.r4(pre - ibx);
            tr.querySelector('.pdl').textContent = this.money(tr.dataset.pdl);
            tr.querySelector('.ibp').textContent = this.money(ibp);
            tr.querySelector('.pre').textContent = this.money(pre);
            tr.querySelector('.ibx').textContent = this.money(ibx);
            tr.querySelector('.net').textContent = this.money(net);
            nt0 += sug; idp += ibp; nt1 += pre; idc += ibx; tot += net;
        });
        this.el('tNT0').textContent = this.money(nt0);
        this.el('tIDP').textContent = this.money(idp);
        this.el('tNT1').textContent = this.money(nt1);
        this.el('tIDC').textContent = this.money(idc);
        this.el('tTOT').textContent = this.money(tot);
        // Neto Original (guardado): resaltar si el recálculo en vivo difiere
        var ori = this.el('tORI');
        if (this.origTot == null) { ori.textContent = '—'; ori.classList.remove('text-danger', 'fw-bold'); }
        else {
            ori.textContent = this.money(this.origTot);
            var difiere = Math.abs(Number(this.origTot) - tot) > 0.005;
            ori.classList.toggle('text-danger', difiere);
            ori.classList.toggle('fw-bold', difiere);
        }
    },

    collect() {
        var out = [];
        this.el('tblLin').querySelectorAll('tbody tr').forEach(tr => {
            out.push({ CODPRC: tr.dataset.codprc, PDL: tr.dataset.pdl, SUG: tr.querySelector('.c-sug').value, PBX: tr.querySelector('.c-pbx').value, OBS: tr.dataset.obs });
        });
        return out;
    },

    // ---- buscar presupuesto existente ----
    async loadList() {
        var j = await (await fetch('api.php?action=list')).json();
        if (!j.ok) return;
        var data = (j.data || []).map(r => [r.PPP, r.FEXPPP, r.CLIENTE, r.PTP, this.money(r.TOTAL)]);
        var self = this;
        if (this.dtB) this.dtB.clear().rows.add(data).draw();
        else this.dtB = $('#grdBuscar').DataTable({ data, pageLength: 25, order: [[0, 'desc']], columnDefs: [{ targets: [4], className: 'text-end' }], language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-AR.json' }, createdRow: (row, d) => row.addEventListener('click', () => { self.ver(d[0]); bootstrap.Modal.getInstance(self.el('modalBuscar')).hide(); }) });
    },
    async ver(id) {
        var j = await (await fetch('api.php?action=get&id=' + encodeURIComponent(id))).json();
        if (!j.ok) { this.toast(j.error, 'danger'); return; }
        var c = j.data.cabecera; this.currentId = c.NUMPPP;
        this.origTot = (c.TOTPPP == null ? null : Number(c.TOTPPP));   // Neto Original guardado
        this.ctx = { NUMODM: c.NUMODM, NUMPTP: c.NUMPTP, CODCLI: c.CODCLI, CODPRE: c.CODPRE };
        this.el('fNum').textContent = c.NUMPPP;
        this.el('f_FEXPPP').value = c.FEXPPP || '';
        this.el('fOdm').textContent = c.NUMODM || '—'; this.el('fPtp').textContent = c.NUMPTP || '—';
        this.el('fCli').textContent = c.DENCLI || '—'; this.el('fPre').textContent = c.DENPRE || '—';
        this.el('f_PDPPPP').value = c.PDPPPP || 0; this.el('f_PDCPPP').value = c.PDCPPP || 0;
        this.el('f_OBSPPP').value = c.OBSPPP || '';
        this.clearRows();
        (j.data.lineas || []).forEach(l => this.addRow({ CODPRC: l.CODPRC, DENPRC: l.DENPRC, SECTOR: l.SECTOR, PDL: l.PDLPPP, SUG: l.SUGPPP, PBX: l.PBXPPP, OBS: l.OBS }));
        this.setMode('view'); this.recalc();
    },

    clear() {
        this.currentId = null; this.origTot = null; this.ctx = { NUMODM: null, NUMPTP: null, CODCLI: null, CODPRE: null };
        this.el('fNum').textContent = '—'; this.el('fOdm').textContent = '—'; this.el('fPtp').textContent = '—';
        this.el('fCli').textContent = '—'; this.el('fPre').textContent = '—';
        ['f_FEXPPP', 'f_OBSPPP'].forEach(i => this.el(i).value = ''); this.el('f_PDPPPP').value = 0; this.el('f_PDCPPP').value = 0;
        this.clearRows(); this.recalc(); this.el('formErr').textContent = '';
    },
    editar() { if (this.currentId) this.setMode('edit'); },
    cancelar() { this.clear(); this.setMode('idle'); },

    async guardar() {
        this.el('formErr').textContent = '';
        if (!this.ctx.CODCLI) { this.toast('Cargá una Orden de Muestra primero (Nuevo)', 'danger'); return; }
        var fd = new FormData();
        fd.append('NUMPPP', this.mode === 'edit' ? this.currentId : 0);
        fd.append('NUMODM', this.ctx.NUMODM || ''); fd.append('NUMPTP', this.ctx.NUMPTP || '');
        fd.append('CODCLI', this.ctx.CODCLI || ''); fd.append('CODPRE', this.ctx.CODPRE || '');
        fd.append('FEXPPP', this.el('f_FEXPPP').value);
        fd.append('PDPPPP', this.el('f_PDPPPP').value); fd.append('PDCPPP', this.el('f_PDCPPP').value);
        fd.append('OBSPPP', this.el('f_OBSPPP').value);
        fd.append('__lineas', JSON.stringify(this.collect()));
        var j = await (await fetch('api.php?action=guardar', { method: 'POST', body: fd })).json();
        if (!j.ok) { this.el('formErr').textContent = j.error || 'Error'; this.toast(j.error || 'Error', 'danger'); return; }
        this.toast('Presupuesto N° ' + j.data.numppp + ' guardado — Total ' + this.money(j.data.total), 'success');
        await this.ver(j.data.numppp);
    },
    async anular() {
        if (!this.currentId) return;
        if (!await this.confirm('¿Anular el Presupuesto N° ' + this.currentId + '?')) return;
        var fd = new FormData(); fd.append('__id', this.currentId);
        var j = await (await fetch('api.php?action=anular', { method: 'POST', body: fd })).json();
        if (!j.ok) { this.toast(j.error || 'No se pudo', 'danger'); return; }
        this.toast('Presupuesto N° ' + j.data.numppp + ' anulado', 'success');
        this.clear(); this.setMode('idle');
    },

    setMode(mode) {
        this.mode = mode;
        var creating = (mode === 'create' || mode === 'edit');
        if (!this.RO) {
            this.el('btnNuevo').disabled = creating;
            this.el('btnGuardar').disabled = !creating;
            this.el('btnCancelar').disabled = (mode === 'idle');
            this.el('btnAnular').disabled = (mode !== 'view');
            this.el('btnCopiar').disabled = !creating;   // copiar precios solo al crear/editar
        }
        this.el('mainForm').classList.toggle('mode-view', !creating);
        var imp = this.el('btnImprimir'), on = (mode === 'view' && this.currentId);
        imp.classList.toggle('disabled', !on);
        imp.href = on ? ('../cotizacion/print.php?id=' + encodeURIComponent(this.currentId)) : '#';
    },
    confirm(message) {
        return new Promise(resolve => {
            var me = this.el('modalConfirm'); this.el('confirmBody').textContent = message;
            var modal = bootstrap.Modal.getOrCreateInstance(me); var done = false; var ok = this.el('btnConfirmOk');
            var clean = () => { ok.removeEventListener('click', okH); me.removeEventListener('hidden.bs.modal', hidH); };
            var okH = () => { if (done) return; done = true; clean(); modal.hide(); resolve(true); };
            var hidH = () => { if (done) return; done = true; clean(); resolve(false); };
            ok.addEventListener('click', okH); me.addEventListener('hidden.bs.modal', hidH); modal.show();
        });
    },
    toast(msg, type = 'info') {
        var t = this.el('toastMsg'); this.el('toastBody').textContent = msg;
        t.className = 'toast align-items-center border-0 text-bg-' + type;
        bootstrap.Toast.getOrCreateInstance(t, { delay: type === 'danger' ? 7000 : 4000 }).show();
    },
};
document.addEventListener('DOMContentLoaded', () => Q.init());
document.addEventListener('dblclick', e => { if (e.target.closest('#mainForm') && Q.mode === 'view') Q.editar(); });
