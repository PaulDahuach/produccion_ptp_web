<?php
/** Órdenes de Muestra — Alta / Modificación (form-first + grillas de procesos y prendas). */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
auth_require_login();

$ro = db_readonly();
$tb = '<div class="btn-group me-2">';
if (!$ro) $tb .=
    '<button id="btnNuevo" class="btn btn-success btn-sm"><i class="bi bi-plus-lg me-1"></i>Nuevo</button>' .
    '<button id="btnGuardar" class="btn btn-primary btn-sm" disabled><i class="bi bi-check-lg me-1"></i>Guardar</button>' .
    '<button id="btnCancelar" class="btn btn-outline-light btn-sm" disabled><i class="bi bi-x-lg me-1"></i>Cancelar</button>';
$tb .= '</div><div class="btn-group me-2"><button id="btnBuscar" class="btn btn-outline-light btn-sm"><i class="bi bi-search me-1"></i>Buscar</button>';
if (!$ro) $tb .= '<button id="btnAnular" class="btn btn-outline-danger btn-sm" disabled><i class="bi bi-slash-circle me-1"></i>Anular</button>';
$tb .= '</div>';

module_head('Órdenes de Muestra — Alta / Modificación', 'bi-eyedropper', $tb);
?>
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="../abm/assets/css/abm.css" rel="stylesheet">

<form id="mainForm" class="mode-view" autocomplete="off">
  <div class="card mb-3"><div class="card-body">
    <div class="row g-3">
      <div class="col-md-2"><label class="form-label small">N° Muestra</label><div class="fs-5 fw-bold" id="fNum">—</div></div>
      <div class="col-md-2"><label class="form-label small">Fecha</label><input type="text" id="f_FDEODM" class="form-control form-control-sm" placeholder="dd/mm/aaaa"></div>
      <div class="col-md-2"><label class="form-label small">Estado</label><select id="f_CODEDM" class="form-select form-select-sm"></select></div>
      <div class="col-md-2"><label class="form-label small">Cantidad</label><input type="number" id="f_CANODM" class="form-control form-control-sm"></div>
      <div class="col-md-2"><label class="form-label small">PTP N°</label><div class="fw-medium pt-1" id="fPtp">(se crea al guardar)</div></div>

      <div class="col-md-4"><label class="form-label small">Cliente</label><select id="f_CODCLI" class="form-select form-select-sm"><option value="">— Cliente —</option></select></div>
      <div class="col-md-4"><label class="form-label small">Marca</label><select id="f_CODMAR" class="form-select form-select-sm"><option value="">— elegí cliente —</option></select></div>
      <div class="col-md-2"><label class="form-label small">Origen</label><select id="f_CODODM" class="form-select form-select-sm"></select></div>
      <div class="col-md-2"><label class="form-label small">Acción PTP</label><select id="f_CODADP" class="form-select form-select-sm"></select></div>

      <div class="col-md-6"><label class="form-label small">Denominación (PTP)</label><input type="text" id="f_DENPTP" class="form-control form-control-sm" placeholder="(por defecto PTP + número)"></div>
      <div class="col-md-3"><label class="form-label small">Propiedad prototipo</label><select id="f_CODPDP" class="form-select form-select-sm"><option value="">—</option></select></div>
      <div class="col-md-3"><label class="form-label small">Observaciones</label><input type="text" id="f_OBSODM" class="form-control form-control-sm"></div>
    </div>
    <div id="formErr" class="text-danger small mt-2"></div>
  </div></div>

  <div class="row g-3">
    <div class="col-lg-7"><div class="card h-100"><div class="card-body">
      <div class="d-flex justify-content-between mb-2">
        <h6 class="mb-0 text-uppercase small text-muted"><i class="bi bi-list-ol me-1"></i>Procesos</h6>
        <button type="button" id="btnAddProc" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Agregar</button>
      </div>
      <table class="table table-sm table-bordered align-middle mb-0" id="tblProc">
        <thead><tr><th style="width:2.5rem">#</th><th>Proceso</th><th>Color</th><th style="width:5rem">%</th><th>Obs</th><th style="width:2.5rem"></th></tr></thead>
        <tbody></tbody>
      </table>
    </div></div></div>
    <div class="col-lg-5"><div class="card h-100"><div class="card-body">
      <div class="d-flex justify-content-between mb-2">
        <h6 class="mb-0 text-uppercase small text-muted"><i class="bi bi-bag me-1"></i>Prendas / Telas</h6>
        <button type="button" id="btnAddPre" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Agregar</button>
      </div>
      <table class="table table-sm table-bordered align-middle mb-0" id="tblPre">
        <thead><tr><th style="width:2.5rem">#</th><th>Prenda</th><th>Tela</th><th style="width:2.5rem"></th></tr></thead>
        <tbody></tbody>
      </table>
    </div></div></div>
  </div>
</form>

<!-- Buscar -->
<div class="modal modal-blur fade" id="modalBuscar" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title"><i class="bi bi-search me-2"></i>Buscar Orden de Muestra</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="text" id="bq" class="form-control form-control-sm mb-2" placeholder="N°, cliente, marca, PTP...">
    <table id="grdBuscar" class="table table-sm table-hover w-100" style="cursor:pointer">
      <thead><tr><th>N° Muestra</th><th>Fecha</th><th>Cliente</th><th>Marca</th><th>Cant.</th><th>PTP N°</th></tr></thead><tbody></tbody>
    </table>
  </div>
</div></div></div>

<!-- Confirm -->
<div class="modal modal-blur fade" id="modalConfirm" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Confirmar</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body" id="confirmBody">—</div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" id="btnConfirmOk" class="btn btn-danger">Confirmar</button></div>
</div></div></div>

<div class="toast-container position-fixed bottom-0 end-0 p-3"><div id="toastMsg" class="toast align-items-center text-bg-info border-0"><div class="d-flex"><div class="toast-body" id="toastBody"></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div></div>

<template id="rowProc">
  <tr><td class="ord"></td>
    <td><select class="form-select form-select-sm c-prc"></select></td>
    <td><select class="form-select form-select-sm c-cdp"></select></td>
    <td><input type="text" class="form-control form-control-sm c-por"></td>
    <td><input type="text" class="form-control form-control-sm c-obs"></td>
    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger c-del py-0 px-1"><i class="bi bi-trash"></i></button></td>
  </tr>
</template>
<template id="rowPre">
  <tr><td class="ord"></td>
    <td><select class="form-select form-select-sm c-pre"></select></td>
    <td><select class="form-select form-select-sm c-tel"></select></td>
    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger c-del py-0 px-1"><i class="bi bi-trash"></i></button></td>
  </tr>
</template>

<?php
module_foot('
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/js/odm_edit.js?v=2"></script>
');
