<?php
/** Consulta Órdenes de Proceso x Sector — Vista (solo lectura). */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
auth_require_login();

module_head('Órdenes de Proceso x Sector', 'bi-pin-map',
    '<button class="btn btn-outline-light btn-sm" id="btnReload"><i class="bi bi-arrow-clockwise me-1"></i>Refrescar</button>');
?>
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">

<div class="card mb-3">
  <div class="card-body py-2">
    <div class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label mb-1 small">Sector <span class="text-danger">*</span></label>
        <select id="fSector" class="form-select form-select-sm"></select>
      </div>
      <div class="col-md-4">
        <label class="form-label mb-1 small">Buscar</label>
        <input type="text" id="fq" class="form-control form-control-sm" placeholder="ODP, marca, proceso...">
      </div>
      <div class="col-md-4 d-flex gap-2">
        <button id="btnFiltrar" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Filtrar</button>
        <button id="btnLimpiar" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></button>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between mb-2">
      <span class="text-muted small" id="resumen">—</span>
    </div>
    <table id="tbl" class="table table-sm table-striped table-hover w-100">
      <thead><tr>
        <th>Programa</th><th class="text-end">Orden</th><th>ODP N°</th><th>Marca</th><th>PTP N°</th>
        <th>Proceso</th><th class="text-end">Cantidad</th><th class="text-end">Pendiente</th>
        <th>Definición</th><th class="text-end">Días Rec</th><th class="text-end">Días Def</th>
      </tr></thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<?php
module_foot('
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/js/odp_sector.js"></script>
');
