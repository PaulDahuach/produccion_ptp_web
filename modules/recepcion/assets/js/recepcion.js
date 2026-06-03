/* Recepción de Órdenes — máquina de estados (idle/create/view), paridad con Frm Recepcion. */
const Rec = {
    DEF: null, mode: 'idle', dt: null, RO: false, currentId: null,
    // Campos EDITABLES (se cargan/guardan). Los display (d_*) se manejan aparte.
    CAMPOS: ['CODADO', 'REPODP', 'REMODP', 'CODCLI', 'CODMAR', 'CODTAL', 'OCNODP', 'CAXODP',
        'CODPR1', 'CANODP', 'PESODP', 'O10ODP', 'PRTODP', 'PREODP', 'CODPR2', 'CODTEL', 'CODCT1', 'CODCT2'],

    el(id) { return document.getElementById(id); },
    esc(s) { if (s == null) return ''; const d = document.createElement('div'); d.textContent = String(s); return d.innerHTML; },

    async init() {
        const j = await (await fetch('api.php?action=init')).json();
        if (!j.ok) { this.toast('Error: ' + j.error, 'danger'); return; }
        this.DEF = j.data; this.RO = j.data.readonly;
        this.fillSel('f_CODADO', j.data.acciones, null);
        this.fillSel('f_CODCLI', j.data.clientes, '— Cliente —');
        this.fillSel('f_CODTAL', j.data.talleres, '— Taller —');
        this.fillSel('f_CODPR1', j.data.prendas, '— Prenda —');
        this.fillSel('f_CODPR2', j.data.prendas, '—');
        this.fillSel('f_CODTEL', j.data.telas, '—');
        this.fillSel('f_CODCT1', j.data.colores, '—');   // Color de tela
        this.fillSel('f_CODCT2', j.data.cuerpos, '—');   // Cuerpo de tela
        this.fillSel('f_CODMAR', [], '— elegí cliente —');
        this.bind();
        this.setMode('idle');
    },

    fillSel(id, arr, ph) {
        const s = this.el(id);
        s.innerHTML = (ph !== null ? `<option value="">${this.esc(ph)}</option>` : '') +
            (arr || []).map(o => `<option value="${this.esc(o.id)}">${this.esc(o.den)}</option>`).join('');
    },

    bind() {
        if (!this.RO) {
            this.el('btnNuevo').addEventListener('click', () => this.nuevo());
            this.el('btnGuardar').addEventListener('click', () => this.guardar());
            this.el('btnCancelar').addEventListener('click', () => this.cancelar());
            this.el('btnAnular').addEventListener('click', () => this.anular());
        }
        this.el('btnBuscar').addEventListener('click', () => new bootstrap.Modal(this.el('modalBuscar')).show());
        this.el('modalBuscar').addEventListener('shown.bs.modal', () => this.loadList());
        this.el('f_CODADO').addEventListener('change', () => this.onAccion());
        this.el('f_CODCLI').addEventListener('change', () => this.onCliente());
        this.el('f_CODTEL').addEventListener('change', () => this.onTela());
        this.el('f_REPODP').addEventListener('change', () => this.onReproceso());
    },

    /** REPROCESA: copia los datos de la orden original (REPODP_AfterUpdate del legacy). */
    async onReproceso() {
        const rep = this.el('f_REPODP').value.trim();
        if (this.el('f_CODADO').value === '1' || !rep) return;
        const j = await (await fetch('api.php?action=get&id=' + encodeURIComponent(rep))).json();
        if (!j.ok || !j.data) { this.toast('Orden a reprocesar no encontrada: ' + rep, 'danger'); this.el('f_REPODP').value = ''; return; }
        const d = j.data;
        this.el('f_REMODP').value = (d.REMODP == null ? '' : d.REMODP);
        this.el('f_CODCLI').value = (d.CODCLI == null ? '' : d.CODCLI);
        await this.onCliente(d.CODMAR);  // carga marcas del cliente y setea la marca
        ['CODTAL', 'OCNODP', 'CAXODP', 'CODPR1', 'CANODP', 'PESODP', 'PRTODP', 'PREODP', 'CODPR2', 'CODTEL', 'CODCT1', 'CODCT2', 'O10ODP']
            .forEach(c => { const el = this.el('f_' + c); if (el) el.value = (d[c] == null ? '' : d[c]); });
        // PTP se copia del original (display)
        this.numptp = (d.NUMPTP == null ? '' : d.NUMPTP);
        this.el('d_NUMPTP').textContent = this.numptp || '—';
        this.el('d_DENPTP').textContent = d.DENPTP || '—';
        // Legacy: tras reproceso, la Acción queda fija
        this.el('f_CODADO').disabled = true;
        this.el('f_REMODP').focus();
        this.toast('Datos copiados de la orden N° ' + rep, 'info');
    },

    onAccion() {
        const rep = this.el('f_REPODP');
        if (this.el('f_CODADO').value === '1') { rep.disabled = true; rep.value = ''; }
        else rep.disabled = (this.mode !== 'create');
    },

    async onCliente(keepVal) {
        const cli = this.el('f_CODCLI').value;
        if (!cli) { this.fillSel('f_CODMAR', [], '— elegí cliente —'); return; }
        const j = await (await fetch('api.php?action=marcas_cliente&cli=' + encodeURIComponent(cli))).json();
        this.fillSel('f_CODMAR', j.ok ? j.data : [], '— Marca —');
        if (keepVal !== undefined) this.el('f_CODMAR').value = keepVal;
    },

    async onTela() {
        const tel = this.el('f_CODTEL').value;
        if (!tel) return;
        const j = await (await fetch('api.php?action=tela_colores&tel=' + encodeURIComponent(tel))).json();
        if (j.ok) { if (j.data.CODCT1) this.el('f_CODCT1').value = j.data.CODCT1; if (j.data.CODCT2) this.el('f_CODCT2').value = j.data.CODCT2; }
    },

    // ---------- Buscar / ver ----------
    async loadList() {
        const j = await (await fetch('api.php?action=list')).json();
        if (!j.ok) return;
        const data = (j.data || []).map(r => [r.ODP, r.FDRODP, r.CLIENTE, r.MARCA, r.PRENDA, r.CANTIDAD, r.REMITO]);
        const self = this;
        if (this.dt) this.dt.clear().rows.add(data).draw();
        else this.dt = $('#grdBuscar').DataTable({
            data, pageLength: 25, order: [[0, 'desc']],
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-AR.json' },
            createdRow: (row, d) => row.addEventListener('click', () => { self.ver(d[0]); bootstrap.Modal.getInstance(self.el('modalBuscar')).hide(); })
        });
        setTimeout(() => $('#modalBuscar .dataTables_filter input').trigger('focus'), 150);
    },

    async ver(id) {
        const j = await (await fetch('api.php?action=get&id=' + encodeURIComponent(id))).json();
        if (!j.ok) { this.toast(j.error, 'danger'); return; }
        const d = j.data;
        this.currentId = d.NUMODP;
        // Editables
        this.CAMPOS.forEach(c => {
            const el = this.el('f_' + c); if (!el || c === 'CODMAR') return;
            if (el.type === 'checkbox') el.checked = !!d[c];
            else el.value = (d[c] === null || d[c] === undefined) ? '' : d[c];
        });
        await this.onCliente(d.CODMAR);   // tras setear CODCLI: carga marcas y setea la marca
        // Display
        this.numptp = (d.NUMPTP == null ? '' : d.NUMPTP);
        this.el('d_NUMODP').textContent = d.NUMODP;
        this.el('d_FDRODP').textContent = this.dispDate(d.FDRODP);
        this.el('d_SECTOR').textContent = d.DENETA || '—';
        this.el('d_FDEODP').textContent = d.FDEODP_disp || '—';
        this.el('d_NUMPTP').textContent = this.numptp || '—';
        this.el('d_DENPTP').textContent = d.DENPTP || '—';
        this.el('d_NUMPPP').textContent = (d.NUMPPP == null ? '—' : d.NUMPPP);
        this.el('d_FEXPPP').textContent = d.FEXPPP_disp || '—';
        this.el('d_O20ODP').value = (d.O20ODP == null ? '' : d.O20ODP);
        this.el('d_OBSODP').value = (d.OBSODP == null ? '' : d.OBSODP);
        this.el('d_SPMODP').checked = !!d.SPMODP;
        this.renderProcesos(d.procesos || []);
        this.setMode('view');
    },

    renderProcesos(rows) {
        const tb = this.el('tbProc');
        this.el('badgeProc').textContent = rows.length;
        if (!rows.length) {
            tb.innerHTML = '<tr><td colspan="6" class="text-muted text-center py-2">Sin procesos (se definen en Definición)</td></tr>';
            return;
        }
        tb.innerHTML = rows.map((r, i) =>
            `<tr><td>${i + 1}</td><td>${this.esc(r.DENPRC)}</td><td>${this.esc(r.SECTOR)}</td>` +
            `<td>${this.esc(r.COLOR)}</td><td class="text-end">${this.esc(r.PORODP == null ? '' : r.PORODP)}</td>` +
            `<td>${this.esc(r.OBSODP)}</td></tr>`).join('');
    },

    dispDate(iso) {
        if (!iso) return '—';
        const p = String(iso).split('-');
        return p.length === 3 ? (p[2] + '/' + p[1] + '/' + p[0]) : iso;
    },

    // ---------- acciones ----------
    clear() {
        this.currentId = null; this.numptp = '';
        this.CAMPOS.forEach(c => { const el = this.el('f_' + c); if (!el) return; if (el.type === 'checkbox') el.checked = false; else el.value = ''; });
        this.fillSel('f_CODMAR', [], '— elegí cliente —');
        this.el('f_CODADO').disabled = false;
        ['d_NUMODP', 'd_FDRODP', 'd_FDEODP', 'd_NUMPTP', 'd_DENPTP', 'd_NUMPPP', 'd_FEXPPP'].forEach(id => this.el(id).textContent = '—');
        this.el('d_SECTOR').textContent = '—';
        this.el('d_O20ODP').value = ''; this.el('d_OBSODP').value = ''; this.el('d_SPMODP').checked = false;
        this.renderProcesos([]);
        this.el('formErr').textContent = '';
    },

    nuevo() {
        this.clear();
        this.el('d_FDRODP').textContent = this.DEF.fechaDisp;
        this.el('d_SECTOR').textContent = this.DEF.sectorRecep || 'RECEPCION';
        this.el('f_CODADO').value = '1';
        this.setMode('create');
        this.onAccion();
        setTimeout(() => this.el('f_REMODP').focus(), 100);
    },

    cancelar() { this.clear(); this.setMode('idle'); },

    async guardar() {
        this.el('formErr').textContent = '';
        const fd = new FormData();
        this.CAMPOS.forEach(c => { const el = this.el('f_' + c); if (!el) return; fd.append(c, el.type === 'checkbox' ? (el.checked ? '1' : '') : el.value); });
        fd.append('NUMPTP', this.numptp || '');   // PTP sólo si vino por reproceso
        const j = await (await fetch('api.php?action=crear', { method: 'POST', body: fd })).json();
        if (!j.ok) { this.el('formErr').textContent = j.error || 'Error'; this.toast(j.error || 'Error', 'danger'); return; }
        this.toast('Orden N° ' + j.data.numodp + ' registrada', 'success');
        await this.ver(j.data.numodp);   // la deja en vista, lista para imprimir (como el legacy)
    },

    setMode(mode) {
        this.mode = mode;
        const creating = (mode === 'create');
        if (!this.RO) {
            this.el('btnNuevo').disabled = creating;
            this.el('btnGuardar').disabled = !creating;
            this.el('btnCancelar').disabled = (mode === 'idle');
            this.el('btnAnular').disabled = (mode !== 'view');
        }
        this.el('mainForm').classList.toggle('mode-view', !creating);
        const imp = this.el('btnImprimir');
        if (imp) {
            const on = (mode === 'view' && this.currentId);
            imp.classList.toggle('disabled', !on);
            imp.href = on ? ('../imprimir_orden/?id=' + encodeURIComponent(this.currentId)) : '#';
        }
        if (creating) this.onAccion();
    },

    async anular() {
        if (!this.currentId) return;
        if (!await this.confirm('¿Anular la orden N° ' + this.currentId + '? Esta acción la marca como anulada y elimina sus lotes.')) return;
        const fd = new FormData(); fd.append('__id', this.currentId);
        const j = await (await fetch('api.php?action=anular', { method: 'POST', body: fd })).json();
        if (!j.ok) { this.toast(j.error || 'No se pudo anular', 'danger'); return; }
        this.toast('Orden N° ' + j.data.numodp + ' anulada', 'success');
        this.clear(); this.setMode('idle');
    },

    confirm(message) {
        return new Promise(resolve => {
            const me = this.el('modalConfirm'); this.el('confirmBody').textContent = message;
            const modal = bootstrap.Modal.getOrCreateInstance(me); let done = false;
            const ok = this.el('btnConfirmOk');
            const clean = () => { ok.removeEventListener('click', okH); me.removeEventListener('hidden.bs.modal', hidH); };
            const okH = () => { if (done) return; done = true; clean(); modal.hide(); resolve(true); };
            const hidH = () => { if (done) return; done = true; clean(); resolve(false); };
            ok.addEventListener('click', okH); me.addEventListener('hidden.bs.modal', hidH); modal.show();
        });
    },

    toast(msg, type = 'info') {
        const t = this.el('toastMsg'); this.el('toastBody').textContent = msg;
        t.className = 'toast align-items-center border-0 text-bg-' + type;
        bootstrap.Toast.getOrCreateInstance(t, { delay: type === 'danger' ? 7000 : 4000 }).show();
    },
};
document.addEventListener('DOMContentLoaded', () => Rec.init());
