<?php
/** Consulta de PTP (rutas de procesos / pedidos) — grilla + ficha (solo lectura). */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
auth_require_login();

module_head('Consulta de PTP', 'bi-list-check',
    '<button id="btnReload" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-clockwise me-1"></i>Refrescar</button>');
?>
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="../abm/assets/css/abm.css" rel="stylesheet">

<div class="card mb-3">
  <div class="card-body py-2">
    <div class="row g-2 align-items-end">
      <div class="col-md-5">
        <label class="form-label mb-1 small">Buscar</label>
        <input type="text" id="fq" class="form-control form-control-sm" placeholder="N° PTP, cliente, marca, denominación...">
      </div>
      <div class="col-md-3">
        <label class="form-label mb-1 small">Estado</label>
        <select id="fEstado" class="form-select form-select-sm">
          <option value="">— No anulados —</option>
          <option value="1">Pendiente</option>
          <option value="2">Confirmado</option>
          <option value="3">Anulado</option>
        </select>
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button id="btnFiltrar" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel me-1"></i>Filtrar</button>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between mb-2">
      <span class="text-muted small" id="resumen">—</span>
      <span class="text-muted small"><i class="bi bi-info-circle me-1"></i>Clic en una fila para ver la ruta de procesos (últimos 500)</span>
    </div>
    <table id="tbl" class="table table-sm table-striped table-hover w-100" style="cursor:pointer">
      <thead><tr><th>N° PTP</th><th>Fecha</th><th>Cliente</th><th>Marca</th><th>Estado</th><th>Denominación</th></tr></thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<div class="modal modal-blur fade" id="modalFicha" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title"><i class="bi bi-list-check me-2"></i><span id="fTit">PTP</span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3 mb-2">
          <div class="col-md-3"><label class="form-label small text-muted mb-0">N°</label><div class="fw-medium" id="f_npp">—</div></div>
          <div class="col-md-3"><label class="form-label small text-muted mb-0">Fecha</label><div id="f_fec">—</div></div>
          <div class="col-md-3"><label class="form-label small text-muted mb-0">Estado</label><div id="f_est">—</div></div>
          <div class="col-md-3"><label class="form-label small text-muted mb-0">Marca</label><div id="f_mar">—</div></div>
          <div class="col-md-8"><label class="form-label small text-muted mb-0">Cliente</label><div id="f_cli">—</div></div>
          <div class="col-md-4"><label class="form-label small text-muted mb-0">Denominación</label><div id="f_den">—</div></div>
        </div>
        <h6 class="text-uppercase small text-muted"><i class="bi bi-list-ol me-1"></i>Ruta de procesos</h6>
        <table class="table table-sm table-bordered mb-0">
          <thead><tr><th>#</th><th>Proceso</th><th>Sector</th><th>Color</th><th class="text-end">%</th><th>Obs</th></tr></thead>
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
<script src="assets/js/ptp.js"></script>
');
