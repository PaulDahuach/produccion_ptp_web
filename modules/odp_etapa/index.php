<?php
/** Consulta Órdenes de Proceso x Etapa — Vista (solo lectura). */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
auth_require_login();

module_head('Órdenes de Proceso x Etapa', 'bi-diagram-3',
    '<a class="btn btn-outline-light btn-sm me-2" id="btnImprimir" target="_blank" href="#"><i class="bi bi-printer me-1"></i>Imprimir</a>' .
    '<button class="btn btn-outline-light btn-sm" id="btnReload"><i class="bi bi-arrow-clockwise me-1"></i>Refrescar</button>');
?>
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">

<div class="card mb-3">
  <div class="card-body py-2">
    <div class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label mb-1 small">Buscar</label>
        <input type="text" id="fq" class="form-control form-control-sm" placeholder="ODP, corte, artículo, cliente, marca...">
      </div>
      <div class="col-md-3">
        <label class="form-label mb-1 small">Etapa</label>
        <select id="fEtapa" class="form-select form-select-sm"><option value="">— Todas —</option></select>
      </div>
      <div class="col-md-2">
        <label class="form-label mb-1 small">Recibido desde</label>
        <input type="text" id="fDesde" class="form-control form-control-sm" placeholder="dd/mm/aaaa">
      </div>
      <div class="col-md-2">
        <label class="form-label mb-1 small">Hasta</label>
        <input type="text" id="fHasta" class="form-control form-control-sm" placeholder="dd/mm/aaaa">
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button id="btnFiltrar" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel me-1"></i>Filtrar</button>
        <button id="btnLimpiar" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></button>
      </div>
    </div>
  </div>
</div>

<!-- Resumen por etapa -->
<div class="row g-2 mb-3" id="resumenCards"></div>

<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between mb-2">
      <span class="text-muted small" id="resumen">—</span>
    </div>
    <table id="tbl" class="table table-sm table-striped table-hover w-100">
      <thead><tr>
        <th>Etapa</th><th>ODP N°</th><th>Cliente</th><th>Marca</th><th>Prenda</th>
        <th>O. Corte</th><th>C. Artículo</th><th>PTP N°</th>
        <th class="text-end">Cantidad</th><th class="text-end">Días Rec</th><th class="text-end">Días Def</th>
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
<script src="assets/js/odp_etapa.js?v=2"></script>
');
