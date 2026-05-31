/* Definición de Órdenes — cargar orden recibida, definir ruta de procesos. */
const Def = {
    DEF: null, RO: false, currentId: null, optProc: '', optCol: '', dt: null,

    el(id) { return document.getElementById(id); },
    esc(s) { if (s == null) return ''; const d = document.createElement('div'); d.textContent = String(s); return d.innerHTML; },

    async init() {
        const j = await (await fetch('api.php?action=init')).json();
        if (!j.ok) { this.toast('Error: ' + j.error, 'danger'); return; }
        this.DEF = j.data; this.RO = j.data.readonly;
        this.optProc = '<option value="">— Proceso —</option>' + j.data.procesos.map(o => `<option value="${o.id}">${this.esc(o.den)}</option>`).join('');
        this.optCol = '<option value="">—</option>' + j.data.colores.map(o => `<option value="${o.id}">${this.esc(o.den)}</option>`).join('');
        this.bind();
        this.setLoaded(false);
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
        ['FDRODP', 'DENCLI', 'DENMAR', 'DENTAL', 'DENPR1', 'CANODP', 'REMODP', 'DENTEL'].forEach(c => {
            const el = this.el('d_' + c); if (el) el.textContent = (d[c] === null || d[c] === undefined || d[c] === '') ? '—' : d[c];
        });
        this.el('f_FDDODP').value = this.DEF.fechaDisp;
        this.el('f_NUMPTP').value = d.NUMPTP || '';
        this.el('f_O20ODP').value = d.O20ODP || '';
        this.el('tbProc').innerHTML = '';
        this.renum();
        this.setLoaded(true);
    },

    rowHtml() {
        return `<tr>
            <td class="text-center rownum"></td>
            <td><select class="form-select form-select-sm pr-cod">${this.optProc}</select></td>
            <td><select class="form-select form-select-sm pr-col">${this.optCol}</select></td>
            <td><input type="number" step="any" class="form-control form-control-sm text-end pr-por"></td>
            <td><input type="text" class="form-control form-control-sm pr-obs"></td>
            <td><button type="button" class="btn btn-outline-danger btn-sm btn-remove-row">&times;</button></td></tr>`;
    },

    addRow(data) {
        const tb = this.el('tbProc');
        tb.insertAdjacentHTML('beforeend', this.rowHtml());
        const tr = tb.lastElementChild;
        if (data) {
            tr.querySelector('.pr-cod').value = data.CODPRC || '';
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
        fd.append('O20ODP', this.el('f_O20ODP').value);
        fd.append('NUMPTP', this.el('f_NUMPTP').value);
        fd.append('__procesos', JSON.stringify(this.collect()));
        const j = await (await fetch('api.php?action=definir', { method: 'POST', body: fd })).json();
        if (!j.ok) { this.el('formErr').textContent = j.error || 'Error'; this.toast(j.error || 'Error', 'danger'); return; }
        this.toast('Orden N° ' + j.data.numodp + ' definida (' + j.data.procesos + ' procesos)', 'success');
        this.limpiar();
    },

    limpiar() {
        this.currentId = null;
        this.el('fNum').textContent = '—';
        ['FDRODP', 'DENCLI', 'DENMAR', 'DENTAL', 'DENPR1', 'CANODP', 'REMODP', 'DENTEL'].forEach(c => this.el('d_' + c).textContent = '—');
        this.el('f_FDDODP').value = ''; this.el('f_NUMPTP').value = ''; this.el('f_O20ODP').value = '';
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
