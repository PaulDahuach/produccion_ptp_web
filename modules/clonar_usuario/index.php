<?php
/** Clonar Usuario — vista (admin-only). Crea un usuario nuevo con el acceso de otro. */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
auth_require_admin();

module_head('Clonar Usuario', 'bi-person-plus');
?>
<div class="card" style="max-width: 640px;">
  <div class="card-body">
    <p class="text-muted small mb-3">
      Crea un usuario nuevo con el <strong>mismo acceso</strong> que uno existente (copia su lista de
      opciones permitidas y su categoría). Útil cuando varios comparten una clave por el nivel de acceso.
    </p>
    <form id="frmClonar" data-keynav data-keynav-submit="#btnClonar">
      <div class="mb-3">
        <label class="form-label">Clonar el acceso de</label>
        <select id="src" class="form-select" required><option value="">— elegí el usuario de referencia —</option></select>
      </div>
      <div class="row g-2">
        <div class="col-8">
          <label class="form-label">Nombre del nuevo usuario</label>
          <input type="text" id="nombre" class="form-control" placeholder="APELLIDO NOMBRE" autocomplete="off" required>
        </div>
        <div class="col-4">
          <label class="form-label">Iniciales</label>
          <input type="text" id="inic" class="form-control" maxlength="4" placeholder="auto">
        </div>
      </div>
      <div class="row g-2 mt-1">
        <div class="col-8">
          <label class="form-label">Clave</label>
          <input type="text" id="clave" class="form-control" autocomplete="off" required>
        </div>
        <div class="col-4">
          <label class="form-label">Categoría</label>
          <input type="text" id="cat" class="form-control" maxlength="1" placeholder="(del origen)">
        </div>
      </div>
      <div class="mt-3">
        <button type="submit" id="btnClonar" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Crear usuario</button>
      </div>
    </form>
    <div id="resultado" class="mt-3"></div>
  </div>
</div>

<?php
module_foot('<script src="assets/js/clonar_usuario.js?v=1"></script>');
