/* Definición de Órdenes — cargar orden recibida, editar cabecera y definir ruta. */
const Def = {
    DEF: null, RO: false, currentId: null, optProc: '', optCol: '', sectorMap: {}, dt: null,
    // Campos de cabecera EDITABLES (se cargan/guardan).
    CAMPOS: ['CODADO', 'REPODP', 'CODDST', 'REMODP', 'CODCLI', 'CODMAR', 'CODTAL', 'OCNODP', 'CAXODP',
        'CODPR1', 'CANODP', 'PESODP', 'O20ODP', 'PRTODP', 'PREODP', 'NUMPTP', 'NUMPPP', 'CODPR2', 'CODTEL', 'CODCT1', 'CODCT2'],

    el(id) { return document.getElementById(id); },
    esc(s) { if (s == null) return ''; const d = document.createElement('div'); d.textContent = String(s); return d.innerHTML; },

    async init() {
        const j = await (await fetch('api.php?action=init')).json();
        if (!j.ok) { this.toast('Error: ' + j.error, 'danger'); return; }
        this.DEF = j.data; this.RO = j.data.readonly;
        // Combos de cabecera
        this.fillSel('f_CODADO', j.data.acciones, null);
        this.fillSel('f_CODDST', j.data.destinos, null);
        this.fillSel('f_CODCLI', j.data.clientes, '— Cliente —');
        this.fillSel('f_CODTAL', j.data.talleres, '— Taller —');
        this.fillSel('f_CODPR1', j.data.prendas, '— Prenda —');
        this.fillSel('f_CODPR2', j.data.prendas, '—');
        this.fillSel('f_CODTEL', j.data.telas, '—');
        this.fillSel('f_CODCT1', j.data.colores, '—');
        this.fillSel('f_CODCT2', j.data.cuerpos, '—');
        this.fillSel('f_CODMAR', [], '— elegí cliente —');
        // Opciones para la grilla de procesos
        this.optProc = '<option value="">— Proceso —</option>' + j.data.procesos.map(o => `<option value="${o.id}">${this.esc(o.den)}</option>`).join('');
        this.optCol = '<option value="">—</option>' + j.data.coloresProc.map(o => `<option value="${o.id}">${this.esc(o.den)}</option>`).join('');
        j.data.procesos.forEach(o => { this.sectorMap[o.id] = o.sector || ''; });
        this.bind();
        this.setLoaded(false);
    },

    fillSel(id, arr, ph) {
        const s = this.el(id);
        s.innerHTML = (ph !== null ? `<option value="">${this.esc(ph)}</option>` : '') +
            (arr || []).map(o => `<option value="${this.esc(o.id)}">${this.esc(o.den)}</option>`).join('');
    },

    bind() {
        this.el('btnBuscar').addEventListener('click', () => new bootstrap.Modal(this.el('modalBuscar')).show());
        this.el('modalBuscar').addEventListener('shown.bs.modal', () => this.loadList());
        if (!this.RO) {
            this.el('btnDefinir').addEventListener('click', () => this.definir());
            this.el('btnCancelar').addEventListener('click', () => this.cancelar());
            this.el('btnAddProc').addEventListener('click', () => this.addRow());
            this.el('btnCargarPtp').addEventListener('click', () => this.cargarPtp());
        }
        this.el('f_CODADO').addEventListener('change', () => this.onAccion());
        this.el('f_CODCLI').addEventListener('change', () => this.onCliente());
        this.el('f_CODTEL').addEventListener('change', () => this.onTela());
    },

    onAccion() {
        const rep = this.el('f_REPODP');
        if (this.el('f_CODADO').value === '1') { rep.disabled = true; rep.value = ''; }
        else rep.disabled = !this.currentId;
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

    async loadList() {
        const j = await (await fetch('api.php?action=list')).json();
        if (!j.ok) return;
        const data = (j.data || []).map(r => [r.ODP, r.FDRODP, r.CLIENTE, r.MARCA, r.PRENDA, r.CANTIDAD, r.REMITO]);
        const self = this;
        if (this.dt) this.dt.clear().rows.add(data).draw();
        else this.dt = $('#grdBuscar').DataTable({
            data, pageLength: 25, order: [[0, 'desc']],
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-AR.json' },
            createdRow: (row, d) => row.addEventListener('click', () => { self.cargar(d[0]); bootstrap.Modal.getInstance(self.el('modalBuscar')).hide(); })
        });
        setTimeout(() => $('#modalBuscar .dataTables_filter input').trigger('focus'), 150);
    },

    async cargar(id) {
        const j = await (await fetch('api.php?action=get&id=' + encodeURIComponent(id))).json();
        if (!j.ok) { this.toast(j.error, 'danger'); return; }
        const d = j.data;
        this.currentId = d.NUMODP;
        this.el('fNum').textContent = d.NUMODP;
        this.CAMPOS.forEach(c => {
            const el = this.el('f_' + c); if (!el || c === 'CODMAR') return;
            if (el.type === 'checkbox') el.checked = !!d[c];
            else el.value = (d[c] === null || d[c] === undefined) ? '' : d[c];
        });
        await this.onCliente(d.CODMAR);   // tras setear CODCLI: carga marcas y setea la marca
        if (!this.el('f_CODDST').value) this.el('f_CODDST').value = '1';   // destino default (SetData "E")
        // Display
        this.el('d_NUMODP').textContent = d.NUMODP;
        this.el('d_FDDODP').textContent = this.DEF.fechaDisp;              // emisión = fecha definición (hoy)
        this.el('d_SECTOR').textContent = d.DENETA || '—';
        this.el('d_DENPTP').textContent = d.DENPTP || '—';
        this.el('d_FEXPPP').textContent = d.FEXPPP_disp || '—';
        this.el('d_SPMODP').checked = !!d.SPMODP;
        // La ruta se define acá: grilla vacía (o cargar del PTP)
        this.el('tbProc').innerHTML = '';
        this.renum();
        this.setLoaded(true);
        this.onAccion();
    },

    rowHtml() {
        return `<tr>
            <td class="text-center rownum"></td>
            <td><select class="form-select form-select-sm pr-cod">${this.optProc}</select></td>
            <td><input type="text" class="form-control form-control-sm pr-sec bg-body-tertiary" readonly tabindex="-1"></td>
            <td><select class="form-select form-select-sm pr-col">${this.optCol}</select></td>
            <td><input type="number" step="any" class="form-control form-control-sm text-end pr-por"></td>
            <td><input type="text" class="form-control form-control-sm pr-obs"></td>
            <td><button type="button" class="btn btn-outline-danger btn-sm btn-remove-row">&times;</button></td></tr>`;
    },

    addRow(data) {
        const tb = this.el('tbProc');
        tb.insertAdjacentHTML('beforeend', this.rowHtml());
        const tr = tb.lastElementChild;
        const cod = tr.querySelector('.pr-cod');
        const sec = tr.querySelector('.pr-sec');
        cod.addEventListener('change', () => { sec.value = this.sectorMap[cod.value] || ''; });
        if (data) {
            cod.value = data.CODPRC || '';
            sec.value = this.sectorMap[data.CODPRC] || '';
            tr.querySelector('.pr-col').value = data.CODCDP || '';
            tr.querySelector('.pr-por').value = (data.PORPTP != null ? data.PORPTP : '');
            tr.querySelector('.pr-obs').value = data.OBSPTP || '';
        }
        tr.querySelector('.btn-remove-row').addEventListener('click', () => { tr.remove(); this.renum(); });
        this.renum();
    },

    renum() {
        const rows = this.el('tbProc').querySelectorAll('tr');
        rows.forEach((tr, i) => tr.querySelector('.rownum').textContent = i + 1);
        this.el('badgeProc').textContent = rows.length;
    },

    async cargarPtp() {
        const ptp = this.el('f_NUMPTP').value.trim();
        if (!ptp) { this.toast('Ingresá un N° de PTP', 'warning'); return; }
        const j = await (await fetch('api.php?action=ptp_procesos&ptp=' + encodeURIComponent(ptp))).json();
        if (!j.ok) { this.toast(j.error, 'danger'); return; }
        if (!j.data.length) { this.toast('El PTP no tiene procesos', 'warning'); return; }
        this.el('tbProc').innerHTML = '';
        j.data.forEach(p => this.addRow(p));
        this.toast(j.data.length + ' proceso(s) cargados del PTP', 'success');
    },

    collect() {
        const rows = [];
        this.el('tbProc').querySelectorAll('tr').forEach(tr => {
            rows.push({
                CODPRC: tr.querySelector('.pr-cod').value,
                CODCDP: tr.querySelector('.pr-col').value,
                PORODP: tr.querySelector('.pr-por').value,
                OBSODP: tr.querySelector('.pr-obs').value,
            });
        });
        return rows;
    },

    async definir() {
        if (!this.currentId) return;
        this.el('formErr').textContent = '';
        const fd = new FormData();
        fd.append('__id', this.currentId);
        this.CAMPOS.forEach(c => { const el = this.el('f_' + c); if (!el) return; fd.append(c, el.type === 'checkbox' ? (el.checked ? '1' : '') : el.value); });
        fd.append('__procesos', JSON.stringify(this.collect()));
        const j = await (await fetch('api.php?action=definir', { method: 'POST', body: fd })).json();
        if (!j.ok) { this.el('formErr').textContent = j.error || 'Error'; this.toast(j.error || 'Error', 'danger'); return; }
        this.toast('Orden N° ' + j.data.numodp + ' definida (' + j.data.procesos + ' procesos)', 'success');
        this.limpiar();
    },

    limpiar() {
        this.currentId = null;
        this.el('fNum').textContent = '—';
        this.CAMPOS.forEach(c => { const el = this.el('f_' + c); if (!el) return; if (el.type === 'checkbox') el.checked = false; else el.value = ''; });
        this.fillSel('f_CODMAR', [], '— elegí cliente —');
        ['d_NUMODP', 'd_FDDODP', 'd_SECTOR', 'd_DENPTP', 'd_FEXPPP'].forEach(id => this.el(id).textContent = '—');
        this.el('d_SPMODP').checked = false;
        this.el('tbProc').innerHTML = ''; this.renum();
        this.el('formErr').textContent = '';
        this.setLoaded(false);
    },

    cancelar() { this.limpiar(); },

    setLoaded(on) {
        if (!this.RO) {
            this.el('btnDefinir').disabled = !on;
            this.el('btnCancelar').disabled = !on;
        }
        this.el('mainForm').classList.toggle('mode-view', !on);
    },

    toast(msg, type = 'info') {
        const t = this.el('toastMsg'); this.el('toastBody').textContent = msg;
        t.className = 'toast align-items-center border-0 text-bg-' + type;
        bootstrap.Toast.getOrCreateInstance(t, { delay: type === 'danger' ? 7000 : 4000 }).show();
    },
};
document.addEventListener('DOMContentLoaded', () => Def.init());
