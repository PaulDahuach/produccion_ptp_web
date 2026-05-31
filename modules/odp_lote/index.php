<?php
/**
 * Consulta Órdenes de Proceso x Lote — Vista (solo lectura).
 * Reproduce `Frm Consulta Ordenes de Proceso x Lote`: master-detail (sectores →
 * lotes), filtros del legacy, drill de procesos, reimpresión de orden, salidas
 * Imprimir/Excel. Es la consulta CENTRAL del sistema.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
auth_require_login();

module_head('Órdenes de Proceso x Lote', 'bi-box-seam',
    '<a class="btn btn-outline-light btn-sm me-2 disabled" id="btnImprimir" target="_blank" href="#"><i class="bi bi-printer me-1"></i>Imprimir</a>' .
    '<a class="btn btn-outline-light btn-sm me-2 disabled" id="btnExcel" href="#"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Excel</a>' .
    '<a class="btn btn-outline-warning btn-sm me-2" href="../odp_retrasadas/"><i class="bi bi-alarm me-1"></i>Retrasadas</a>' .
    '<button class="btn btn-outline-light btn-sm" id="btnReload"><i class="bi bi-arrow-clockwise me-1"></i>Refrescar</button>');
?>
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<style>
  #panelSectores .list-group-item { cursor:pointer; padding:.4rem .6rem; }
  #panelSectores .list-group-item.active { background:var(--bs-primary); border-color:var(--bs-primary); }
  #panelSectores .list-group-item .badge { font-weight:600; }
  .sector-vacio { color:var(--bs-secondary); font-style:italic; }
  #tblDet tbody tr { cursor:pointer; }
</style>

<!-- Filtros -->
<div class="card mb-3">
  <div class="card-body py-2">
    <div class="row g-2 align-items-end">
      <div class="col-6 col-md-2">
        <label class="form-label mb-1 small">Recibido desde</label>
        <input type="text" id="fDesde" class="form-control form-control-sm" placeholder="dd/mm/aaaa">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label mb-1 small">Hasta</label>
        <input type="text" id="fHasta" class="form-control form-control-sm" placeholder="dd/mm/aaaa">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label mb-1 small">ODP N°</label>
        <input type="number" id="fOdp" class="form-control form-control-sm" placeholder="N°">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label mb-1 small">O. Corte N°</label>
        <input type="text" id="fOcorte" class="form-control form-control-sm">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label mb-1 small">C. Artículo</label>
        <input type="text" id="fArt" class="form-control form-control-sm">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label mb-1 small">Cliente</label>
        <select id="fCli" class="form-select form-select-sm"><option value="">— Todos —</option></select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label mb-1 small">Marca</label>
        <select id="fMar" class="form-select form-select-sm"><option value="">— Todas —</option></select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label mb-1 small">Prenda</label>
        <select id="fPre" class="form-select form-select-sm"><option value="">— Todas —</option></select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label mb-1 small">Incluya el proceso</label>
        <select id="fPrc" class="form-select form-select-sm"><option value="">— Cualquiera —</option></select>
      </div>
      <div class="col-6 col-md-3 d-flex gap-2">
        <button id="btnFiltrar" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel me-1"></i>Filtrar</button>
        <button id="btnLimpiar" class="btn btn-outline-secondary btn-sm" title="Limpiar filtros"><i class="bi bi-x-lg"></i></button>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- Panel sectores -->
  <div class="col-md-3">
    <div class="card">
      <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-list-task me-1"></i>Sectores</span>
        <span class="badge bg-secondary" id="totPrendas">—</span>
      </div>
      <div class="list-group list-group-flush" id="panelSectores">
        <div class="text-muted small p-3" id="sectoresMsg">Cargando…</div>
      </div>
      <div class="card-footer py-2" id="adminWrap" style="display:none">
        <button class="btn btn-outline-warning btn-sm w-100" id="btnAdmin"><i class="bi bi-inboxes me-1"></i>Administración <span class="badge bg-warning text-dark" id="adminCount"></span></button>
      </div>
    </div>
  </div>

  <!-- Panel detalle -->
  <div class="col-md-9">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between mb-2">
          <span class="fw-semibold" id="detTitulo">Elegí un sector a la izquierda</span>
          <span class="text-muted small" id="detResumen"></span>
        </div>
        <table id="tblDet" class="table table-sm table-striped table-hover w-100">
          <thead><tr>
            <th>ODP N°</th><th>Cliente</th><th>Prenda</th><th>Marca</th>
            <th>O. Corte</th><th>C. Artículo</th><th>PTP N°</th>
            <th class="text-end">Cantidad</th><th class="text-end">Días Rec</th>
            <th class="text-end">Días Def</th><th class="text-end">Orden</th><th class="text-center">Obs</th>
            <th class="text-center">Acciones</th>
          </tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal procesos (drill cmdPrc) -->
<div class="modal modal-blur fade" id="modalProc" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-diagram-3 me-2"></i><span id="pTit">Procesos de la orden</span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="small text-muted mb-2" id="pCab">—</div>
        <table class="table table-sm table-bordered mb-0">
          <thead><tr><th>#</th><th>Proceso</th><th>Sector</th><th class="text-end">Cantidad</th><th class="text-end">Pendiente</th><th>Obs</th></tr></thead>
          <tbody id="pBody"><tr><td colspan="6" class="text-center text-muted">—</td></tr></tbody>
        </table>
      </div>
      <div class="modal-footer">
        <a id="pReimp" class="btn btn-primary" target="_blank" href="#"><i class="bi bi-printer me-1"></i>Reimprimir orden</a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<?php
module_foot('
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/js/odp_lote.js"></script>
');
