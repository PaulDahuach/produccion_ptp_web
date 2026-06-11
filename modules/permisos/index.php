<?php
/** Permisos de Usuario — vista (admin-only). Edita la lista blanca web por usuario. */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
auth_require_admin();

module_head('Permisos de Usuario', 'bi-shield-lock',
    '<button id="btnGuardar" class="btn btn-light btn-sm" disabled><i class="bi bi-check-lg me-1"></i>Guardar</button>');
?>
<div class="card">
  <div class="card-body">
    <div class="row g-2 align-items-end mb-2">
      <div class="col-md-5">
        <label class="form-label mb-1">Usuario</label>
        <select id="usr" class="form-select"><option value="">— elegí un usuario —</option></select>
      </div>
      <div class="col-md-7 d-flex align-items-end gap-2">
        <button id="btnTodos" class="btn btn-outline-secondary btn-sm" type="button" disabled>Marcar todo</button>
        <button id="btnNada" class="btn btn-outline-secondary btn-sm" type="button" disabled>Desmarcar todo</button>
        <span class="text-muted small ms-auto" id="resumen"></span>
      </div>
    </div>
    <p class="text-muted small mb-2">Tildá las opciones que el usuario puede ver/usar en la web. Los admins ven todo igual. (No afecta al sistema legacy.)</p>
    <div id="grupos" class="row g-3"></div>
  </div>
</div>

<?php
module_foot('<script src="assets/js/permisos.js?v=1"></script>');
