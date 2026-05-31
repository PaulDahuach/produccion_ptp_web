<?php
/** Cotización de Órdenes (Presupuestos PTP) — lista + detalle (solo lectura). */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
auth_require_login();

module_head('Cotización de Órdenes', 'bi-cash-coin',
    '<button id="btnReload" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-clockwise me-1"></i>Refrescar</button>');
?>
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="../abm/assets/css/abm.css" rel="stylesheet">

<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between mb-2">
      <span class="text-muted small" id="resumen">—</span>
      <span class="text-muted small"><i class="bi bi-info-circle me-1"></i>Clic en una fila para ver el detalle de precios (últimos 500)</span>
    </div>
    <table id="tbl" class="table table-sm table-striped table-hover w-100" style="cursor:pointer">
      <thead><tr><th>N° Presup.</th><th>Fecha</th><th>Cliente</th><th>PTP</th><th class="text-end">Total $</th></tr></thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<div class="modal modal-blur fade" id="modalFicha" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title"><i class="bi bi-cash-coin me-2"></i><span id="fTit">Presupuesto</span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3 mb-2">
          <div class="col-md-3"><label class="form-label small text-muted mb-0">N°</label><div class="fw-medium" id="f_npp">—</div></div>
          <div class="col-md-3"><label class="form-label small text-muted mb-0">Fecha</label><div id="f_fec">—</div></div>
          <div class="col-md-6"><label class="form-label small text-muted mb-0">Cliente</label><div id="f_cli">—</div></div>
          <div class="col-md-3"><label class="form-label small text-muted mb-0">PTP N°</label><div id="f_ptp">—</div></div>
          <div class="col-md-3"><label class="form-label small text-muted mb-0">Prenda</label><div id="f_pre">—</div></div>
          <div class="col-md-3"><label class="form-label small text-muted mb-0">Total $</label><div class="fw-bold" id="f_tot">—</div></div>
        </div>
        <h6 class="text-uppercase small text-muted"><i class="bi bi-list-ol me-1"></i>Procesos cotizados</h6>
        <table class="table table-sm table-bordered mb-0">
          <thead><tr><th>#</th><th>Proceso</th><th class="text-end">Cant.</th><th class="text-end">Precio</th><th class="text-end">Neto</th><th class="text-end">Total</th></tr></thead>
          <tbody id="f_items"><tr><td colspan="6" class="text-muted text-center">—</td></tr></tbody>
        </table>
      </div>
      <div class="modal-footer">
        <a id="btnImprimir" class="btn btn-primary" target="_blank" href="#"><i class="bi bi-printer me-1"></i>Imprimir</a>
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
<script src="assets/js/cotizacion.js"></script>
');
