/* Clonar Usuario — carga usuarios, prefill de categoría/iniciales, submit. */
(function () {
    var users = [];
    function esc(s) { return (s == null) ? '' : String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
    function $(id) { return document.getElementById(id); }

    async function cargarUsuarios() {
        var j = await (await fetch('api.php?action=usuarios')).json();
        if (!j.ok) { $('resultado').innerHTML = '<div class="alert alert-danger">' + esc(j.error) + '</div>'; return; }
        users = j.data || [];
        $('src').innerHTML = '<option value="">— elegí el usuario de referencia —</option>' +
            users.map(function (u) {
                return '<option value="' + u.CODUSR + '">' + esc(u.DENUSR) + '  (' + u.PERMISOS + ' opciones · cat ' + esc(u.CATUSR) + ')</option>';
            }).join('');
    }

    // al elegir origen, prefill de categoría (si el admin no la tocó)
    function onSrcChange() {
        var u = users.filter(function (x) { return String(x.CODUSR) === $('src').value; })[0];
        if (u && !$('cat').dataset.touched) $('cat').value = u.CATUSR || '';
    }
    // iniciales automáticas desde el nombre (si no las tocaron)
    function onNombre() {
        if ($('inic').dataset.touched) return;
        var p = $('nombre').value.trim().split(/\s+/).filter(Boolean);
        var ini = p.length >= 2 ? (p[1].charAt(0) + p[0].charAt(0)) : (p[0] ? p[0].substring(0, 2) : '');
        $('inic').value = ini.toUpperCase();
    }

    async function clonar(ev) {
        ev.preventDefault();
        $('resultado').innerHTML = '';
        var body = new URLSearchParams({
            action: 'clonar', src: $('src').value, nombre: $('nombre').value.trim(),
            inic: $('inic').value.trim(), clave: $('clave').value.trim(), cat: $('cat').value.trim()
        });
        $('btnClonar').disabled = true;
        try {
            var j = await (await fetch('api.php', { method: 'POST', body: body })).json();
            if (!j.ok) { $('resultado').innerHTML = '<div class="alert alert-danger">' + esc(j.error) + '</div>'; return; }
            var d = j.data;
            $('resultado').innerHTML = '<div class="alert alert-success">' +
                '<strong>' + esc(d.nombre) + '</strong> creado (CODUSR ' + d.uid + ', categoría ' + esc(d.cat) + ') con <strong>' +
                d.permisos + ' opciones</strong> clonadas de <strong>' + esc(d.origen) + '</strong>.' +
                '<br>Ya puede entrar con su clave.</div>';
            $('frmClonar').reset(); $('inic').dataset.touched = ''; $('cat').dataset.touched = '';
            cargarUsuarios();
        } finally {
            $('btnClonar').disabled = false;
        }
    }

    $('src').addEventListener('change', onSrcChange);
    $('nombre').addEventListener('input', onNombre);
    $('inic').addEventListener('input', function () { this.dataset.touched = '1'; });
    $('cat').addEventListener('input', function () { this.dataset.touched = '1'; });
    $('frmClonar').addEventListener('submit', clonar);
    cargarUsuarios();
})();
