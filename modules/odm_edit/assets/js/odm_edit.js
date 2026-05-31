/* Órdenes de Muestra — Alta/Modificación: form-first + grillas procesos/prendas. */
const M = {
    DEF: null, mode: 'idle', RO: false, dt: null, currentId: null,
    el(id) { return document.getElementById(id); },
    esc(s) { if (s == null) return ''; var d = document.createElement('div'); d.textContent = String(s); return d.innerHTML; },

    async init() {
        var j = await (await fetch('api.php?action=init')).json();
        if (!j.ok) { this.toast('Error: ' + j.error, 'danger'); return; }
        this.DEF = j.data; this.RO = j.data.readonly;
        this.fillSel(this.el('f_CODCLI'), j.data.clientes, '— Cliente —');
        this.fillSel(this.el('f_CODEDM'), j.data.estados, null);
        this.fillSel(this.el('f_CODODM'), j.data.origenes, null);
        this.fillSel(this.el('f_CODADP'), j.data.acciones, null);
        this.fillSel(this.el('f_CODPDP'), j.data.propiedades, '—');
        this.bind();
        this.setMode('idle');
    },
    fillSel(sel, arr, ph) {
        sel.innerHTML = (ph !== null ? '<option value="">' + this.esc(ph) + '</option>' : '') +
            (arr || []).map(o => '<option value="' + this.esc(o.id) + '">' + this.esc(o.den) + '</option>').join('');
    },
    bind() {
        if (!this.RO) {
            this.el('btnNuevo').addEventListener('click', () => this.nuevo());
            this.el('btnGuardar').addEventListener('click', () => this.guardar());
            this.el('btnCancelar').addEventListener('click', () => this.cancelar());
            this.el('btnAnular').addEventListener('click', () => this.anular());
            this.el('btnAddProc').addEventListener('click', () => this.addProc());
            this.el('btnAddPre').addEventListener('click', () => this.addPre());
        }
        this.el('btnBuscar').addEventListener('click', () => new bootstrap.Modal(this.el('modalBuscar')).show());
        this.el('modalBuscar').addEventListener('shown.bs.modal', () => this.loadList());
        this.el('f_CODCLI').addEventListener('change', () => this.onCliente());
        this.el('bq').addEventListener('keyup', () => { if (this.dt) this.dt.search(this.el('bq').value).draw(); });
    },
    async onCliente(keep) {
        var cli = this.el('f_CODCLI').value;
        if (!cli) { this.fillSel(this.el('f_CODMAR'), [], '— elegí cliente —'); return; }
        var j = await (await fetch('api.php?action=marcas_cliente&cli=' + encodeURIComponent(cli))).json();
        this.fillSel(this.el('f_CODMAR'), j.ok ? j.data : [], '— Marca —');
        if (keep !== undefined) this.el('f_CODMAR').value = keep;
    },

    // grilla procesos
    addProc(d) {
        var tb = this.el('tblProc').querySelector('tbody');
        var tr = this.el('rowProc').content.firstElementChild.cloneNode(true);
        this.fillSel(tr.querySelector('.c-prc'), this.DEF.procesos, '— Proceso —');
        this.fillSel(tr.querySelector('.c-cdp'), this.DEF.colores, '—');
        if (d) { tr.querySelector('.c-prc').value = d.CODPRC || ''; tr.querySelector('.c-cdp').value = d.CODCDP || ''; tr.querySelector('.c-por').value = (d.PORODM == null ? '' : d.PORODM); tr.querySelector('.c-obs').value = d.OBSODM || ''; }
        tr.querySelector('.c-del').addEventListener('click', () => { tr.remove(); this.renum('tblProc'); });
        tb.appendChild(tr); this.renum('tblProc');
    },
    // grilla prendas
    addPre(d) {
        var tb = this.el('tblPre').querySelector('tbody');
        var tr = this.el('rowPre').content.firstElementChild.cloneNode(true);
        this.fillSel(tr.querySelector('.c-pre'), this.DEF.prendas, '— Prenda —');
        this.fillSel(tr.querySelector('.c-tel'), this.DEF.telas, '—');
        if (d) { tr.querySelector('.c-pre').value = d.CODPRE || ''; tr.querySelector('.c-tel').value = d.CODTEL || ''; }
        tr.querySelector('.c-del').addEventListener('click', () => { tr.remove(); this.renum('tblPre'); });
        tb.appendChild(tr); this.renum('tblPre');
    },
    renum(tid) { var i = 0; this.el(tid).querySelectorAll('tbody tr').forEach(tr => tr.querySelector('.ord').textContent = ++i); },
    clearGrids() { this.el('tblProc').querySelector('tbody').innerHTML = ''; this.el('tblPre').querySelector('tbody').innerHTML = ''; },
    collectProc() {
        var out = [];
        this.el('tblProc').querySelectorAll('tbody tr').forEach(tr => { var p = tr.querySelector('.c-prc').value; if (p) out.push({ CODPRC: p, CODCDP: tr.querySelector('.c-cdp').value, PORODM: tr.querySelector('.c-por').value, OBSODM: tr.querySelector('.c-obs').value }); });
        return out;
    },
    collectPre() {
        var out = [];
        this.el('tblPre').querySelectorAll('tbody tr').forEach(tr => { var p = tr.querySelector('.c-pre').value; if (p) out.push({ CODPRE: p, CODTEL: tr.querySelector('.c-tel').value }); });
        return out;
    },

    async loadList() {
        var j = await (await fetch('api.php?action=list')).json();
        if (!j.ok) return;
        var data = (j.data || []).map(r => [r.ODM, r.FDEODM, r.CLIENTE, r.MARCA, (r.CANT || ''), r.PTP]);
        var self = this;
        if (this.dt) this.dt.clear().rows.add(data).draw();
        else this.dt = $('#grdBuscar').DataTable({ data, pageLength: 25, order: [[0, 'desc']], language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-AR.json' }, createdRow: (row, d) => row.addEventListener('click', () => { self.ver(d[0]); bootstrap.Modal.getInstance(self.el('modalBuscar')).hide(); }) });
    },

    async ver(id) {
        var j = await (await fetch('api.php?action=get&id=' + encodeURIComponent(id))).json();
        if (!j.ok) { this.toast(j.error, 'danger'); return; }
        var c = j.data.cabecera; this.currentId = c.NUMODM;
        this.el('fNum').textContent = c.NUMODM;
        this.el('fPtp').textContent = c.NUMPTP || '—';
        this.el('f_FDEODM').value = c.FDEODM || '';
        this.el('f_CANODM').value = (c.CANODM == null ? '' : c.CANODM);
        this.el('f_CODEDM').value = c.CODEDM || '';
        this.el('f_CODODM').value = c.CODODM || '';
        this.el('f_CODADP').value = c.CODADP || '';
        this.el('f_CODPDP').value = c.CODPDP || '';
        this.el('f_DENPTP').value = c.DENPTP || '';
        this.el('f_OBSODM').value = c.OBSODM || '';
        this.el('f_CODCLI').value = c.CODCLI || '';
        await this.onCliente(c.CODMAR);
        this.clearGrids();
        (j.data.procesos || []).forEach(p => this.addProc(p));
        (j.data.prendas || []).forEach(p => this.addPre(p));
        this.setMode('view');
    },

    clear() {
        this.currentId = null; this.el('fNum').textContent = '—'; this.el('fPtp').textContent = '(se crea al guardar)';
        ['f_FDEODM', 'f_CANODM', 'f_DENPTP', 'f_OBSODM', 'f_CODCLI'].forEach(i => this.el(i).value = '');
        this.el('f_CODEDM').selectedIndex = 0; this.el('f_CODODM').selectedIndex = 0; this.el('f_CODADP').selectedIndex = 0; this.el('f_CODPDP').selectedIndex = 0;
        this.fillSel(this.el('f_CODMAR'), [], '— elegí cliente —');
        this.clearGrids(); this.el('formErr').textContent = '';
    },
    nuevo() { this.clear(); this.el('f_FDEODM').value = this.DEF.fechaDisp; this.setMode('create'); this.addProc(); this.addPre(); setTimeout(() => this.el('f_CODCLI').focus(), 100); },
    editar() { if (this.currentId) this.setMode('edit'); },
    cancelar() { this.clear(); this.setMode('idle'); },

    async guardar() {
        this.el('formErr').textContent = '';
        var fd = new FormData();
        fd.append('NUMODM', this.mode === 'edit' ? this.currentId : 0);
        ['CODCLI', 'CODMAR', 'FDEODM', 'CODEDM', 'CANODM', 'CODODM', 'CODADP', 'CODPDP', 'DENPTP', 'OBSODM'].forEach(k => fd.append(k, this.el('f_' + k).value));
        fd.append('__procesos', JSON.stringify(this.collectProc()));
        fd.append('__prendas', JSON.stringify(this.collectPre()));
        var j = await (await fetch('api.php?action=guardar', { method: 'POST', body: fd })).json();
        if (!j.ok) { this.el('formErr').textContent = j.error || 'Error'; this.toast(j.error || 'Error', 'danger'); return; }
        this.toast('Muestra N° ' + j.data.numodm + ' guardada (PTP ' + j.data.numptp + ', ' + j.data.procesos + ' procesos)', 'success');
        await this.ver(j.data.numodm);
    },
    async anular() {
        if (!this.currentId) return;
        if (!await this.confirm('¿Anular la Orden de Muestra N° ' + this.currentId + '?')) return;
        var fd = new FormData(); fd.append('__id', this.currentId);
        var j = await (await fetch('api.php?action=anular', { method: 'POST', body: fd })).json();
        if (!j.ok) { this.toast(j.error || 'No se pudo', 'danger'); return; }
        this.toast('Muestra N° ' + j.data.numodm + ' anulada', 'success');
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
        }
        this.el('mainForm').classList.toggle('mode-view', !creating);
        this.el('btnAddProc').style.display = creating ? '' : 'none';
        this.el('btnAddPre').style.display = creating ? '' : 'none';
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
document.addEventListener('DOMContentLoaded', () => M.init());
document.addEventListener('dblclick', e => { if (e.target.closest('#mainForm') && M.mode === 'view') M.editar(); });
