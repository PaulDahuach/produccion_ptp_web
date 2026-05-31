/* Programación de Órdenes — tablero + liberar a producción (WPXODP). */
const Prog = {
    table: null, RO: false,

    el(id) { return document.getElementById(id); },
    esc(s) { if (s == null) return ''; const d = document.createElement('div'); d.textContent = String(s); return d.innerHTML; },

    async init() {
        this.el('btnReload').addEventListener('click', () => this.load());
        await this.load();
    },

    async load() {
        this.el('resumen').textContent = 'Cargando...';
        const j = await (await fetch('api.php?action=list')).json();
        if (!j.ok) { this.el('resumen').textContent = 'Error: ' + j.error; return; }
        const rows = j.data || [];
        const self = this;
        const data = rows.map(r => {
            const dst = (Number(r.CODDST) === 2) ? ' <span class="badge bg-info-subtle text-info">Adelantos</span>' : '';
            const btn = '<button class="btn btn-sm btn-success btn-prog" data-id="' + r.ODP + '"><i class="bi bi-play-fill me-1"></i>Programar</button>';
            return [self.esc(r.SECTOR), r.ODP + dst, self.esc(r.CLIENTE), self.esc(r.MARCA), self.esc(r.PRENDA), self.esc(r.PROCESO), r.CANTIDAD, btn];
        });
        if (this.table) { this.table.clear().rows.add(data).draw(); }
        else {
            this.table = new DataTable('#tbl', {
                data, pageLength: 50, order: [[0, 'asc'], [1, 'asc']],
                columnDefs: [{ targets: [6], className: 'text-end' }, { targets: [7], orderable: false, searchable: false }],
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-AR.json' }
            });
            this.el('tbl').querySelector('tbody').addEventListener('click', e => {
                const b = e.target.closest('.btn-prog'); if (b) self.programar(b.dataset.id);
            });
        }
        this.el('resumen').textContent = rows.length + ' orden(es) en cola de programación';
    },

    async programar(id) {
        if (!await this.confirm('¿Programar la orden N° ' + id + '? Se liberará al primer sector de su ruta de procesos.')) return;
        const fd = new FormData(); fd.append('__id', id);
        const j = await (await fetch('api.php?action=programar', { method: 'POST', body: fd })).json();
        if (!j.ok) { this.toast(j.error || 'No se pudo programar', 'danger'); return; }
        this.toast('Orden N° ' + j.data.numodp + (j.data.adelantos ? ' enviada a Adelantos' : ' liberada a producción'), 'success');
        this.load();
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
document.addEventListener('DOMContentLoaded', () => Prog.init());
