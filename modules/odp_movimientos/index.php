<?php
/**
 * Movimientos de Lotes — Vista (solo lectura). Reproduce "Rpt Movimientos Lotes" (op.906):
 * ingresos/egresos de lotes por rango de fecha (FFPODP) y sector, nivel Detalle/Sector
 * Producción/Sector Personal/Planta. Imprimir + Excel.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
auth_require_login();

module_head('Movimientos de Lotes', 'bi-arrow-left-right',
    '<a class="btn btn-outline-light btn-sm me-2 disabled" id="btnImprimir" target="_blank" href="#"><i class="bi bi-printer me-1"></i>Imprimir</a>' .
    '<a class="btn btn-outline-light btn-sm me-2 disabled" id="btnExcel" href="#"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Excel</a>' .
    '<button class="btn btn-outline-light btn-sm" id="btnReload"><i class="bi bi-arrow-clockwise me-1"></i>Refrescar</button>');
?>
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">

<div class="card mb-3">
  <div class="card-body py-2">
    <div class="row g-2 align-items-end">
      <div class="col-6 col-md-2">
        <label class="form-label mb-1 small">Desde</label>
        <input type="text" id="fDesde" class="form-control form-control-sm" placeholder="dd/mm/aaaa">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label mb-1 small">Hasta</label>
        <input type="text" id="fHasta" class="form-control form-control-sm" placeholder="dd/mm/aaaa">
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label mb-1 small">Sector (producción)</label>
        <select id="fSector" class="form-select form-select-sm"><option value="">— Todos —</option></select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label mb-1 small">Nivel</label>
        <select id="fNivel" class="form-select form-select-sm">
          <option value="detalle">Detalle</option>
          <option value="produccion">Sector Producción</option>
          <option value="personal">Sector Personal</option>
          <option value="planta">Planta</option>
        </select>
      </div>
      <div class="col-6 col-md-2 d-flex gap-2">
        <button id="btnFiltrar" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel me-1"></i>Filtrar</button>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between mb-2">
      <span class="text-muted small" id="resumen">—</span>
      <span class="small" id="totales"></span>
    </div>
    <table id="tbl" class="table table-sm table-striped table-hover w-100">
      <thead id="thd"></thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<?php
module_foot('
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/js/odp_movimientos.js"></script>
');
