/* Órdenes de Muestra — Alta/Modificación: form-first + 3 subgrillas (prototipos/prendas/procesos). */
const M = {
    DEF: null, mode: 'idle', RO: false, dt: null, currentId: null, sectorMap: {}, estadoMap: {}, isConf: false, isEnt: false,
    el(id) { return document.getElementById(id); },
    esc(s) { if (s == null) return ''; var d = document.createElement('div'); d.textContent = String(s); return d.innerHTML; },

    async init() {
        var modo = this.el('mainForm').dataset.modo;
        this.isConf = (modo === 'confirmar');   // modo Confirmación
        this.isEnt = (modo === 'entregar');     // modo Entrega
        var j = await (await fetch('api.php?action=init')).json();
        if (!j.ok) { this.toast('Error: ' + j.error, 'danger'); return; }
        this.DEF = j.data; this.RO = j.data.readonly;
        (j.data.procesos || []).forEach(o => { this.sectorMap[o.id] = o.sector || ''; });
        (j.data.estados || []).forEach(o => { this.estadoMap[o.id] = o.den; });
        this.fillSel(this.el('f_CODCLI'), j.data.clientes, '— Cliente —');
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
            var on = (id, fn) => { var e = this.el(id); if (e) e.addEventListener('click', fn); };
            on('btnNuevo', () => this.nuevo());
            on('btnGuardar', () => this.guardar());
            on('btnEntregar', () => this.entregar());
            on('btnCancelar', () => this.cancelar());
            on('btnAnular', () => this.anular());
            on('btnAddProc', () => this.addProc());
            on('btnAddPre', () => this.addPre());
            on('btnAddProt', () => this.addProt());
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

    // --- grilla prototipos (marca + precinto) ---
    addProt(d) {
        var tr = this.el('rowProt').content.firstElementChild.cloneNode(true);
        this.fillSel(tr.querySelector('.c-mar'), this.DEF.marcas, '— Marca —');
        if (d) { tr.querySelector('.c-mar').value = d.CODMAR || ''; tr.querySelector('.c-pre').value = d.PREODM || ''; }
        tr.querySelector('.c-del').addEventListener('click', () => { tr.remove(); this.renum('tbProt', 'badgeProt'); });
        this.el('tbProt').appendChild(tr); this.renum('tbProt', 'badgeProt');
    },
    // --- grilla prendas ---
    addPre(d) {
        var tr = this.el('rowPre').content.firstElementChild.cloneNode(true);
        this.fillSel(tr.querySelector('.c-pre'), this.DEF.prendas, '— Prenda —');
        this.fillSel(tr.querySelector('.c-tel'), this.DEF.telas, '—');
        if (d) { tr.querySelector('.c-pre').value = d.CODPRE || ''; tr.querySelector('.c-tel').value = d.CODTEL || ''; }
        tr.querySelector('.c-del').addEventListener('click', () => { tr.remove(); this.renum('tbPre', 'badgePre'); });
        this.el('tbPre').appendChild(tr); this.renum('tbPre', 'badgePre');
    },
    // --- grilla procesos (con sector auto) ---
    addProc(d) {
        var tr = this.el('rowProc').content.firstElementChild.cloneNode(true);
        this.fillSel(tr.querySelector('.c-prc'), this.DEF.procesos, '— Proceso —');
        this.fillSel(tr.querySelector('.c-cdp'), this.DEF.colores, '—');
        var prc = tr.querySelector('.c-prc'), sec = tr.querySelector('.c-sec');
        prc.addEventListener('change', () => { sec.value = this.sectorMap[prc.value] || ''; });
        if (d) {
            prc.value = d.CODPRC || ''; sec.value = this.sectorMap[d.CODPRC] || '';
            tr.querySelector('.c-cdp').value = d.CODCDP || '';
            tr.querySelector('.c-por').value = (d.PORODM == null ? '' : d.PORODM);
            tr.querySelector('.c-obs').value = d.OBSODM || '';
        }
        tr.querySelector('.c-del').addEventListener('click', () => { tr.remove(); this.renum('tbProc', 'badgeProc'); });
        this.el('tbProc').appendChild(tr); this.renum('tbProc', 'badgeProc');
    },
    renum(tid, badge) { var i = 0; this.el(tid).querySelectorAll('tr').forEach(tr => tr.querySelector('.ord').textContent = ++i); this.el(badge).textContent = i; },
    clearGrids() { ['tbProt', 'tbPre', 'tbProc'].forEach(id => this.el(id).innerHTML = ''); this.el('badgeProt').textContent = 0; this.el('badgePre').textContent = 0; this.el('badgeProc').textContent = 0; },
    collectProt() { var out = []; this.el('tbProt').querySelectorAll('tr').forEach(tr => { var m = tr.querySelector('.c-mar').value; if (m) out.push({ CODMAR: m, PREODM: tr.querySelector('.c-pre').value }); }); return out; },
    collectPre() { var out = []; this.el('tbPre').querySelectorAll('tr').forEach(tr => { var p = tr.querySelector('.c-pre').value; if (p) out.push({ CODPRE: p, CODTEL: tr.querySelector('.c-tel').value }); }); return out; },
    collectProc() { var out = []; this.el('tbProc').querySelectorAll('tr').forEach(tr => { var p = tr.querySelector('.c-prc').value; if (p) out.push({ CODPRC: p, CODCDP: tr.querySelector('.c-cdp').value, PORODM: tr.querySelector('.c-por').value, OBSODM: tr.querySelector('.c-obs').value }); }); return out; },

    async loadList() {
        var j = await (await fetch('api.php?action=list' + (this.isConf ? '&pend=1' : (this.isEnt ? '&conf=1' : '')))).json();
        if (!j.ok) return;
        var data = (j.data || []).map(r => [r.ODM, r.FDEODM, r.CLIENTE, r.MARCA, (r.CANT || ''), r.PTP]);
        var self = this;
        if (this.dt) this.dt.clear().rows.add(data).draw();
        else this.dt = $('#grdBuscar').DataTable({ data, pageLength: 25, order: [[0, 'desc']], language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-AR.json' }, createdRow: (row, d) => row.addEventListener('click', () => { self.ver(d[0]); bootstrap.Modal.getInstance(self.el('modalBuscar')).hide(); }) });
    },

    async ver(id) {
        var j = await (await fetch('api.php?action=get&id=' + encodeURIComponent(id))).json();
        if (!j.ok) { this.toast(j.error, 'danger'); return; }
        var c = j.data.cabecera; this.currentId = c.NUMODM; this.fecha = c.FDEODM || '';
        this.el('fNum').textContent = c.NUMODM;
        this.el('d_NUMODM').textContent = c.NUMODM;
        this.el('d_FDEODM').textContent = c.FDEODM || '—';
        this.el('d_NUMPTP').textContent = c.NUMPTP || '—';
        this.el('f_CODEDM').value = (c.CODEDM == null ? '' : c.CODEDM);
        this.el('d_ESTADO').textContent = c.DENEDM || (this.estadoMap[c.CODEDM] || '—');
        ['CMXODM', 'CANODM', 'CPXODM', 'REMODM', 'AOCODM', 'NOPODM', 'CODODM', 'CODADP', 'CODPDP', 'DENPTP', 'OBSODM'].forEach(k => {
            var el = this.el('f_' + k); if (el) el.value = (c[k] == null ? '' : c[k]);
        });
        this.el('f_CODCLI').value = c.CODCLI || '';
        await this.onCliente(c.CODMAR);
        if (this.isEnt) {   // Entrega: Total / Disponible / A Remitir
            var total = Number(c.CANODM || 0), rem = Number(c.CRMODM || 0), disp = total - rem;
            this.el('d_CMXODM').textContent = c.CMXODM || '—';
            this.el('d_TOTAL').textContent = total;
            this.el('d_DISP').textContent = disp;
            this.el('f_AREMITIR').value = disp;
        }
        this.clearGrids();
        (j.data.prototipos || []).forEach(p => this.addProt(p));
        (j.data.prendas || []).forEach(p => this.addPre(p));
        (j.data.procesos || []).forEach(p => this.addProc(p));
        this.setMode(this.isConf ? 'edit' : 'view');   // confirmación: editable; entrega/alta: view
    },

    clear() {
        this.currentId = null; this.fecha = '';
        this.el('fNum').textContent = '—';
        this.el('d_NUMODM').textContent = '—'; this.el('d_FDEODM').textContent = '—'; this.el('d_NUMPTP').textContent = '(se crea al guardar)';
        ['CMXODM', 'CANODM', 'CPXODM', 'REMODM', 'AOCODM', 'NOPODM', 'DENPTP', 'OBSODM', 'CODCLI'].forEach(i => { var e = this.el('f_' + i); if (e) e.value = ''; });
        this.el('f_CODEDM').value = ''; this.el('d_ESTADO').textContent = '—';
        if (this.isEnt) { this.el('d_CMXODM').textContent = '—'; this.el('d_TOTAL').textContent = '—'; this.el('d_DISP').textContent = '—'; this.el('f_AREMITIR').value = ''; }
        this.el('f_CODODM').selectedIndex = 0; this.el('f_CODADP').selectedIndex = 0; this.el('f_CODPDP').selectedIndex = 0;
        this.fillSel(this.el('f_CODMAR'), [], '— elegí cliente —');
        this.clearGrids(); this.el('formErr').textContent = '';
    },
    nuevo() {
        this.clear();
        this.fecha = this.DEF.fechaDisp; this.el('d_FDEODM').textContent = this.DEF.fechaDisp;
        this.el('f_CODEDM').value = '1'; this.el('d_ESTADO').textContent = this.estadoMap['1'] || 'PENDIENTE';
        this.setMode('create'); this.addProc(); this.addPre();
        setTimeout(() => this.el('f_CODCLI').focus(), 100);
    },
    editar() { if (this.currentId) this.setMode('edit'); },
    cancelar() { this.clear(); this.setMode('idle'); },

    async guardar() {
        this.el('formErr').textContent = '';
        var fd = new FormData();
        fd.append('NUMODM', this.mode === 'edit' ? this.currentId : 0);
        fd.append('FDEODM', this.fecha || '');
        fd.append('CODEDM', this.el('f_CODEDM').value);
        ['CODCLI', 'CODMAR', 'CMXODM', 'CANODM', 'CPXODM', 'REMODM', 'AOCODM', 'NOPODM', 'CODODM', 'CODADP', 'CODPDP', 'DENPTP', 'OBSODM']
            .forEach(k => fd.append(k, this.el('f_' + k).value));
        fd.append('__prototipos', JSON.stringify(this.collectProt()));
        fd.append('__prendas', JSON.stringify(this.collectPre()));
        fd.append('__procesos', JSON.stringify(this.collectProc()));
        if (this.isConf) fd.append('__confirmar', '1');
        var j = await (await fetch('api.php?action=guardar', { method: 'POST', body: fd })).json();
        if (!j.ok) { this.el('formErr').textContent = j.error || 'Error'; this.toast(j.error || 'Error', 'danger'); return; }
        if (this.isConf) { this.toast('Muestra N° ' + j.data.numodm + ' CONFIRMADA', 'success'); this.clear(); this.setMode('idle'); return; }
        this.toast('Muestra N° ' + j.data.numodm + ' guardada (PTP ' + j.data.numptp + ', ' + j.data.procesos + ' procesos)', 'success');
        await this.ver(j.data.numodm);
    },
    async entregar() {
        if (!this.currentId) return;
        this.el('formErr').textContent = '';
        var fd = new FormData(); fd.append('__id', this.currentId); fd.append('cant', this.el('f_AREMITIR').value);
        var j = await (await fetch('api.php?action=entregar', { method: 'POST', body: fd })).json();
        if (!j.ok) { this.el('formErr').textContent = j.error || 'Error'; this.toast(j.error || 'Error', 'danger'); return; }
        this.toast('Muestra N° ' + j.data.numodm + ' — remito #' + j.data.ordodm + (j.data.completa ? ' (COMPLETA → Remitida)' : ' (parcial)'), 'success');
        this.clear(); this.setMode('idle');
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
            var set = (id, v) => { var e = this.el(id); if (e) e.disabled = v; };
            set('btnNuevo', creating);
            set('btnGuardar', !creating);
            set('btnEntregar', !(mode === 'view' && this.currentId));
            set('btnCancelar', mode === 'idle');
            set('btnAnular', mode !== 'view');
        }
        this.el('mainForm').classList.toggle('mode-view', !creating);
        ['btnAddProc', 'btnAddPre', 'btnAddProt'].forEach(b => this.el(b).style.display = creating ? '' : 'none');
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
