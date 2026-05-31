<?php
/**
 * Consulta Órdenes de Proceso Retrasadas — Vista (solo lectura).
 * Reproduce el cmdRet del Frm Consulta x Lote: órdenes definidas hace más de X días
 * y aún no terminadas, ordenadas por más retrasada. Imprimir + Excel.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
auth_require_login();

module_head('Órdenes de Proceso Retrasadas', 'bi-alarm',
    '<a class="btn btn-outline-light btn-sm me-2 disabled" id="btnImprimir" target="_blank" href="#"><i class="bi bi-printer me-1"></i>Imprimir</a>' .
    '<a class="btn btn-outline-light btn-sm me-2 disabled" id="btnExcel" href="#"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Excel</a>' .
    '<button class="btn btn-outline-light btn-sm" id="btnReload"><i class="bi bi-arrow-clockwise me-1"></i>Refrescar</button>');
?>
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<style>#tblRet tbody tr{cursor:pointer}</style>

<div class="card mb-3">
  <div class="card-body py-2">
    <div class="row g-2 align-items-end">
      <div class="col-6 col-md-2">
        <label class="form-label mb-1 small fw-semibold text-warning">Más de (días definidas)</label>
        <input type="number" id="fDias" class="form-control form-control-sm" value="15" min="0">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label mb-1 small">Definido desde</label>
        <input type="text" id="fDesde" class="form-control form-control-sm" placeholder="dd/mm/aaaa">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label mb-1 small">Hasta</label>
        <input type="text" id="fHasta" class="form-control form-control-sm" placeholder="dd/mm/aaaa">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label mb-1 small">ODP N°</label>
        <input type="number" id="fOdp" class="form-control form-control-sm">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label mb-1 small">O. Corte</label>
        <input type="text" id="fOcorte" class="form-control form-control-sm">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label mb-1 small">C. Artículo</label>
        <input type="text" id="fArt" class="form-control form-control-sm">
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label mb-1 small">Cliente</label>
        <select id="fCli" class="form-select form-select-sm"><option value="">— Todos —</option></select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label mb-1 small">Marca</label>
        <select id="fMar" class="form-select form-select-sm"><option value="">— Todas —</option></select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label mb-1 small">Prenda</label>
        <select id="fPre" class="form-select form-select-sm"><option value="">— Todas —</option></select>
      </div>
      <div class="col-6 col-md-3 d-flex gap-2">
        <button id="btnFiltrar" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel me-1"></i>Filtrar</button>
        <button id="btnLimpiar" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></button>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between mb-2">
      <span class="text-muted small" id="resumen">—</span>
      <span class="text-muted small"><i class="bi bi-info-circle me-1"></i>Clic en una fila para reimprimir la orden</span>
    </div>
    <table id="tblRet" class="table table-sm table-striped table-hover w-100">
      <thead><tr>
        <th class="text-end">Días Def</th><th class="text-end">Días Rec</th><th>Sector</th>
        <th>ODP N°</th><th>Cliente</th><th>Prenda</th><th>Marca</th>
        <th>O. Corte</th><th>C. Artículo</th><th>PTP N°</th><th class="text-end">Cantidad</th>
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
<script src="assets/js/odp_retrasadas.js"></script>
');
