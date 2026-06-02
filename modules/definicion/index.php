<?php
/** Definición de Órdenes — vista (cabecera recibida + ruta de procesos). */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
auth_require_login();

$ro = db_readonly();
$toolbar = '<button id="btnBuscar" class="btn btn-outline-light btn-sm me-2"><i class="bi bi-search me-1"></i>Buscar orden</button>';
if (!$ro) $toolbar .= '<div class="btn-group">' .
    '<button id="btnDefinir" class="btn btn-primary btn-sm" disabled><i class="bi bi-check2-square me-1"></i>Definir</button>' .
    '<button id="btnCancelar" class="btn btn-outline-light btn-sm" disabled><i class="bi bi-x-lg me-1"></i>Cancelar</button></div>';

module_head('Definición de Órdenes', 'bi-diagram-3', $toolbar);
?>
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="../abm/assets/css/abm.css" rel="stylesheet">

<div class="fc-form mode-view" id="mainForm" data-keynav data-keynav-submit="#btnDefinir">
  <!-- Datos de la orden (recepción, solo lectura) -->
  <div class="card fc-card">
    <div class="card-header"><span><i class="bi bi-receipt me-1"></i>Orden recibida
      <span class="text-muted ms-2" style="text-transform:none">ODP N°: <span id="fNum">—</span></span></span></div>
    <div class="card-body">
      <div class="row g-2">
        <div class="col-md-2"><label class="form-label">Fecha Recep.</label><div class="form-control bg-body-tertiary" id="d_FDRODP">—</div></div>
        <div class="col-md-4"><label class="form-label">Cliente</label><div class="form-control bg-body-tertiary" id="d_DENCLI">—</div></div>
        <div class="col-md-3"><label class="form-label">Marca</label><div class="form-control bg-body-tertiary" id="d_DENMAR">—</div></div>
        <div class="col-md-3"><label class="form-label">Taller</label><div class="form-control bg-body-tertiary" id="d_DENTAL">—</div></div>
      </div>
      <div class="row g-2">
        <div class="col-md-4"><label class="form-label">Prenda</label><div class="form-control bg-body-tertiary" id="d_DENPR1">—</div></div>
        <div class="col-md-2"><label class="form-label">Cantidad</label><div class="form-control bg-body-tertiary text-end" id="d_CANODP">—</div></div>
        <div class="col-md-2"><label class="form-label">Remito</label><div class="form-control bg-body-tertiary" id="d_REMODP">—</div></div>
        <div class="col-md-4"><label class="form-label">Tela</label><div class="form-control bg-body-tertiary" id="d_DENTEL">—</div></div>
      </div>
    </div>
  </div>

  <!-- Datos de definición -->
  <div class="card fc-card">
    <div class="card-header"><span><i class="bi bi-pencil-square me-1"></i>Definición</span></div>
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-2"><label class="form-label">Fecha Definición</label><input type="text" id="f_FDDODP" class="form-control" readonly></div>
        <div class="col-md-2"><label class="form-label">N° PTP</label><input type="number" id="f_NUMPTP" class="form-control"></div>
        <div class="col-md-2"><button type="button" class="btn btn-outline-primary btn-sm" id="btnCargarPtp"><i class="bi bi-download me-1"></i>Cargar PTP</button></div>
        <div class="col-md-6"><label class="form-label">Observaciones</label><input type="text" id="f_O20ODP" class="form-control"></div>
      </div>
    </div>
  </div>

  <!-- Ruta de procesos -->
  <div class="card fc-card">
    <div class="card-header"><span><i class="bi bi-list-ol me-1"></i>Ruta de Procesos
      <span class="badge bg-secondary ms-1" id="badgeProc">0</span></span></div>
    <div class="card-body">
      <table class="fc-grid"><thead><tr>
        <th style="width:3rem">#</th><th>Proceso</th><th>Color de Proceso</th>
        <th style="width:6rem">%</th><th>Observaciones</th><th style="width:2.5rem"></th>
      </tr></thead><tbody id="tbProc"></tbody></table>
      <button type="button" class="btn btn-outline-primary btn-sm mt-2 hb-add" id="btnAddProc"><i class="bi bi-plus-lg me-1"></i>Agregar proceso</button>
      <div class="text-danger small mt-2" id="formErr"></div>
    </div>
  </div>
</div>

<!-- MODAL BUSCAR -->
<div class="modal fade" id="modalBuscar" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header py-2"><h6 class="modal-title"><i class="bi bi-search me-2"></i>Órdenes pendientes de definición</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <table class="table table-sm table-hover w-100" id="grdBuscar"><thead><tr>
          <th>ODP</th><th>Fecha</th><th>Cliente</th><th>Marca</th><th>Prenda</th><th>Cant.</th><th>Remito</th>
        </tr></thead></table>
      </div>
      <div class="modal-footer py-1"><button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
    </div>
  </div>
</div>

<div class="fc-toast-container">
  <div id="toastMsg" class="toast align-items-center border-0" role="alert">
    <div class="d-flex"><div class="toast-body" id="toastBody"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>
  </div>
</div>

<?php
module_foot('
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/js/definicion.js"></script>
');
