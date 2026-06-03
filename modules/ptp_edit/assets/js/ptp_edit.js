/* PTP — Alta/Modificación: form-first + grilla de procesos (idle/create/edit/view). */
const P = {
    DEF: null, mode: 'idle', RO: false, dt: null, currentId: null, sectorMap: {}, base: (window.IWK_BASE || ''),

    el(id) { return document.getElementById(id); },
    esc(s) { if (s == null) return ''; var d = document.createElement('div'); d.textContent = String(s); return d.innerHTML; },

    async init() {
        var j = await (await fetch('api.php?action=init')).json();
        if (!j.ok) { this.toast('Error: ' + j.error, 'danger'); return; }
        this.DEF = j.data; this.RO = j.data.readonly;
        (j.data.procesos || []).forEach(o => { this.sectorMap[o.id] = o.sector || ''; });
        this.fillSel(this.el('f_CODCLI'), j.data.clientes, '— Cliente —');
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
            this.el('btnEliminar').addEventListener('click', () => this.discontinuar());
            this.el('btnAddProc').addEventListener('click', () => this.addRow());
            document.querySelectorAll('.img-file').forEach(inp => inp.addEventListener('change', e => this.uploadImg(e.target)));
            document.querySelectorAll('.img-clear').forEach(btn => btn.addEventListener('click', () => this.setImg(btn.dataset.slot, '')));
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

    // ---- imágenes ----
    boxId(slot) { return slot === 'IM1PTP' ? 'box_IM1' : 'box_IM2'; },
    setImg(slot, path) {
        this.el('f_' + slot).value = path || '';
        var box = this.el(this.boxId(slot));
        if (path) box.innerHTML = '<img src="' + this.esc(this.base + '/' + path) + '" alt="">';
        else box.innerHTML = '<span class="text-muted small">Sin imagen</span>';
    },
    async uploadImg(input) {
        if (!input.files || !input.files[0]) return;
        var slot = input.dataset.slot;
        var fd = new FormData(); fd.append('file', input.files[0]);
        this.el(this.boxId(slot)).innerHTML = '<span class="text-muted small"><span class="spinner-border spinner-border-sm me-1"></span>Subiendo…</span>';
        var j = await (await fetch('api.php?action=subir_imagen', { method: 'POST', body: fd })).json();
        input.value = '';
        if (!j.ok) { this.setImg(slot, this.el('f_' + slot).value); this.toast(j.error || 'No se pudo subir', 'danger'); return; }
        this.setImg(slot, j.data.path);
    },

    // ---- grilla de procesos ----
    addRow(data) {
        var tb = this.el('tbProc');
        var tr = this.el('rowTpl').content.firstElementChild.cloneNode(true);
        this.fillSel(tr.querySelector('.c-prc'), this.DEF.procesos, '— Proceso —');
        this.fillSel(tr.querySelector('.c-cdp'), this.DEF.colores, '—');
        var prc = tr.querySelector('.c-prc'), sec = tr.querySelector('.c-sec');
        prc.addEventListener('change', () => { sec.value = this.sectorMap[prc.value] || ''; });
        if (data) {
            prc.value = data.CODPRC || '';
            sec.value = this.sectorMap[data.CODPRC] || '';
            tr.querySelector('.c-cdp').value = data.CODCDP || '';
            tr.querySelector('.c-por').value = (data.PORPTP == null ? '' : data.PORPTP);
            tr.querySelector('.c-obs').value = data.OBSPTP || '';
        }
        tr.querySelector('.c-del').addEventListener('click', () => { tr.remove(); this.renumber(); });
        tb.appendChild(tr);
        this.renumber();
    },
    renumber() { var i = 0; this.el('tbProc').querySelectorAll('tr').forEach(tr => { tr.querySelector('.ord').textContent = ++i; }); this.el('badgeProc').textContent = i; },
    clearRows() { this.el('tbProc').innerHTML = ''; this.renumber(); },
    collectRows() {
        var out = [];
        this.el('tbProc').querySelectorAll('tr').forEach(tr => {
            var prc = tr.querySelector('.c-prc').value;
            if (!prc) return;
            out.push({ CODPRC: prc, CODCDP: tr.querySelector('.c-cdp').value, PORPTP: tr.querySelector('.c-por').value, OBSPTP: tr.querySelector('.c-obs').value });
        });
        return out;
    },

    renderOdms(rows) {
        this.el('badgeOdm').textContent = rows.length;
        var tb = this.el('tbOdm');
        if (!rows.length) { tb.innerHTML = '<tr><td colspan="2" class="text-muted text-center py-2">—</td></tr>'; return; }
        tb.innerHTML = rows.map(r => '<tr><td>' + this.esc(r.NUMODM) + '</td><td>' + this.esc(r.ACCION) + '</td></tr>').join('');
    },

    // ---- buscar / cargar ----
    async loadList() {
        var j = await (await fetch('api.php?action=list')).json();
        if (!j.ok) return;
        var data = (j.data || []).map(r => [r.ODP, r.FDEPTP, r.CLIENTE, r.MARCA, r.DENOM]);
        var self = this;
        if (this.dt) this.dt.clear().rows.add(data).draw();
        else this.dt = $('#grdBuscar').DataTable({
            data, pageLength: 25, order: [[0, 'desc']],
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-AR.json' },
            createdRow: (row, d) => row.addEventListener('click', () => { self.ver(d[0]); bootstrap.Modal.getInstance(self.el('modalBuscar')).hide(); })
        });
    },

    async ver(id) {
        var j = await (await fetch('api.php?action=get&id=' + encodeURIComponent(id))).json();
        if (!j.ok) { this.toast(j.error, 'danger'); return; }
        var c = j.data.cabecera;
        this.currentId = c.NUMPTP;
        this.el('fNum').textContent = c.NUMPTP;
        this.el('f_FDEPTP').value = c.FDEPTP || '';
        this.el('f_DENPTP').value = c.DENPTP || '';
        this.el('f_OBSPTP').value = c.OBSPTP || '';
        this.el('f_CNFPTP').checked = !!c.CNFPTP;
        this.el('d_DISPTP').checked = !!c.DISPTP;
        this.el('f_CODCLI').value = c.CODCLI || '';
        await this.onCliente(c.CODMAR);
        this.setImg('IM1PTP', c.IM1PTP || '');
        this.setImg('IM2PTP', c.IM2PTP || '');
        this.renderOdms(j.data.odms || []);
        this.clearRows();
        (j.data.procesos || []).forEach(p => this.addRow(p));
        this.setMode('view');
    },

    // ---- acciones ----
    clear() {
        this.currentId = null;
        this.el('fNum').textContent = '—';
        ['f_FDEPTP', 'f_DENPTP', 'f_OBSPTP', 'f_CODCLI'].forEach(i => this.el(i).value = '');
        this.el('f_CNFPTP').checked = false; this.el('d_DISPTP').checked = false;
        this.setImg('IM1PTP', ''); this.setImg('IM2PTP', '');
        this.fillSel(this.el('f_CODMAR'), [], '— elegí cliente —');
        this.renderOdms([]);
        this.clearRows();
        this.el('formErr').textContent = '';
    },

    nuevo() {
        this.clear();
        this.el('f_FDEPTP').value = this.DEF.fechaDisp;
        this.setMode('create');
        this.addRow();
        setTimeout(() => this.el('f_CODCLI').focus(), 100);
    },

    editar() { if (this.currentId) this.setMode('edit'); },
    cancelar() { this.clear(); this.setMode('idle'); },

    async guardar() {
        this.el('formErr').textContent = '';
        var fd = new FormData();
        fd.append('NUMPTP', this.mode === 'edit' ? this.currentId : 0);
        fd.append('CODCLI', this.el('f_CODCLI').value);
        fd.append('CODMAR', this.el('f_CODMAR').value);
        fd.append('FDEPTP', this.el('f_FDEPTP').value);
        fd.append('DENPTP', this.el('f_DENPTP').value);
        fd.append('OBSPTP', this.el('f_OBSPTP').value);
        fd.append('CNFPTP', this.el('f_CNFPTP').checked ? '1' : '');
        fd.append('IM1PTP', this.el('f_IM1PTP').value);
        fd.append('IM2PTP', this.el('f_IM2PTP').value);
        fd.append('__procesos', JSON.stringify(this.collectRows()));
        var j = await (await fetch('api.php?action=guardar', { method: 'POST', body: fd })).json();
        if (!j.ok) { this.el('formErr').textContent = j.error || 'Error'; this.toast(j.error || 'Error', 'danger'); return; }
        this.toast('PTP N° ' + j.data.numptp + ' guardado (' + j.data.procesos + ' procesos)', 'success');
        await this.ver(j.data.numptp);
    },

    async discontinuar() {
        if (!this.currentId) return;
        if (!await this.confirm('¿Discontinuar el PTP N° ' + this.currentId + '? No se borra, queda marcado como discontinuado.')) return;
        var fd = new FormData(); fd.append('__id', this.currentId);
        var j = await (await fetch('api.php?action=discontinuar', { method: 'POST', body: fd })).json();
        if (!j.ok) { this.toast(j.error || 'No se pudo', 'danger'); return; }
        this.toast('PTP N° ' + j.data.numptp + ' discontinuado', 'success');
        this.clear(); this.setMode('idle');
    },

    setMode(mode) {
        this.mode = mode;
        var creating = (mode === 'create' || mode === 'edit');
        if (!this.RO) {
            this.el('btnNuevo').disabled = creating;
            this.el('btnGuardar').disabled = !creating;
            this.el('btnCancelar').disabled = (mode === 'idle');
            this.el('btnEliminar').disabled = (mode !== 'view');
        }
        this.el('mainForm').classList.toggle('mode-view', !creating);
        var imp = this.el('btnImprimir');
        var on = (mode === 'view' && this.currentId);
        imp.classList.toggle('disabled', !on);
        imp.href = on ? ('../imprimir_ptp/?id=' + encodeURIComponent(this.currentId)) : '#';
        this.el('btnAddProc').style.display = creating ? '' : 'none';
    },

    confirm(message) {
        return new Promise(resolve => {
            var me = this.el('modalConfirm'); this.el('confirmBody').textContent = message;
            var modal = bootstrap.Modal.getOrCreateInstance(me); var done = false;
            var ok = this.el('btnConfirmOk');
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
document.addEventListener('DOMContentLoaded', () => P.init());
// Doble clic en el form (modo vista) para pasar a edición
document.addEventListener('dblclick', e => { if (e.target.closest('#mainForm') && P.mode === 'view') P.editar(); });
