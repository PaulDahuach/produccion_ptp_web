/* Permisos de Usuario — checklist de la lista blanca web por usuario. */
(function () {
    function esc(s) { return (s == null) ? '' : String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
    function $(id) { return document.getElementById(id); }
    var KEYS = [];   // todas las claves del catálogo (para contar)

    async function cargarUsuarios() {
        var j = await (await fetch('api.php?action=usuarios')).json();
        if (!j.ok) { $('grupos').innerHTML = '<div class="alert alert-danger">' + esc(j.error) + '</div>'; return; }
        $('usr').innerHTML = '<option value="">— elegí un usuario —</option>' +
            (j.data || []).map(function (u) { return '<option value="' + u.CODUSR + '">' + esc(u.DENUSR) + ' (cat ' + esc(u.CATUSR) + ')</option>'; }).join('');
    }

    function checks() { return Array.prototype.slice.call(document.querySelectorAll('#grupos input[type=checkbox]')); }
    function actualizarResumen() {
        var m = checks().filter(function (c) { return c.checked; }).length;
        $('resumen').textContent = m + ' de ' + KEYS.length + ' opciones habilitadas';
    }

    async function cargarUsuario() {
        var uid = $('usr').value;
        var enable = uid !== '';
        $('btnGuardar').disabled = !enable; $('btnTodos').disabled = !enable; $('btnNada').disabled = !enable;
        if (!enable) { $('grupos').innerHTML = ''; $('resumen').textContent = ''; return; }
        $('grupos').innerHTML = '<div class="text-muted small">Cargando...</div>';
        var j = await (await fetch('api.php?action=cargar&uid=' + encodeURIComponent(uid))).json();
        if (!j.ok) { $('grupos').innerHTML = '<div class="alert alert-danger">' + esc(j.error) + '</div>'; return; }
        var activos = {}; (j.data.activos || []).forEach(function (k) { activos[k] = true; });
        KEYS = [];
        var html = '';
        Object.keys(j.data.grupos).forEach(function (sec) {
            html += '<div class="col-md-4"><div class="border rounded p-2 h-100">' +
                '<div class="fw-bold small text-uppercase text-muted mb-1">' + esc(sec) + '</div>';
            j.data.grupos[sec].forEach(function (o) {
                KEYS.push(o.key);
                html += '<div class="form-check"><input class="form-check-input" type="checkbox" value="' + esc(o.key) +
                    '" id="k_' + esc(o.key) + '"' + (activos[o.key] ? ' checked' : '') + '>' +
                    '<label class="form-check-label small" for="k_' + esc(o.key) + '">' + esc(o.label) + '</label></div>';
            });
            html += '</div></div>';
        });
        $('grupos').innerHTML = html;
        checks().forEach(function (c) { c.addEventListener('change', actualizarResumen); });
        actualizarResumen();
    }

    async function guardar() {
        var uid = $('usr').value; if (!uid) return;
        var body = new URLSearchParams(); body.append('action', 'guardar'); body.append('uid', uid);
        checks().filter(function (c) { return c.checked; }).forEach(function (c) { body.append('keys[]', c.value); });
        $('btnGuardar').disabled = true;
        try {
            var j = await (await fetch('api.php', { method: 'POST', body: body })).json();
            if (!j.ok) { alert('Error: ' + j.error); return; }
            $('resumen').textContent = '✓ Guardado (' + j.data.activas + ' opciones)';
        } finally { $('btnGuardar').disabled = false; }
    }

    function marcar(val) { checks().forEach(function (c) { c.checked = val; }); actualizarResumen(); }

    $('usr').addEventListener('change', cargarUsuario);
    $('btnGuardar').addEventListener('click', guardar);
    $('btnTodos').addEventListener('click', function () { marcar(true); });
    $('btnNada').addEventListener('click', function () { marcar(false); });
    cargarUsuarios();
})();
