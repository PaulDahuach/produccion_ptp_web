<?php
/** PTP — Alta / Modificación (paridad con Frm PTP de Access). */
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
$tb .= '<a id="btnImprimir" class="btn btn-outline-light btn-sm disabled" target="_blank" href="#"><i class="bi bi-printer me-1"></i>Imprimir</a>';
if (!$ro) $tb .= '<button id="btnEliminar" class="btn btn-outline-danger btn-sm" disabled><i class="bi bi-slash-circle me-1"></i>Discontinuar</button>';
$tb .= '</div>';

module_head('PTP — Alta / Modificación', 'bi-list-check', $tb);
?>
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="../abm/assets/css/abm.css" rel="stylesheet">

<form id="mainForm" class="fc-form mode-view" autocomplete="off" data-keynav data-keynav-submit="#btnGuardar">

  <!-- ===== Datos del PTP ===== -->
  <div class="card fc-card">
    <div class="card-header"><span><i class="bi bi-list-check me-1"></i>Datos del PTP
      <span class="text-muted ms-2" style="text-transform:none">N°: <span id="fNum">—</span></span></span></div>
    <div class="card-body">
      <div class="row g-2">
        <div class="col-md-2"><label class="form-label">Fecha Emisión</label><input type="text" id="f_FDEPTP" class="form-control" placeholder="dd/mm/aaaa"></div>
        <div class="col-md-6"><label class="form-label">Denominación</label><input type="text" id="f_DENPTP" class="form-control" placeholder="(por defecto PTP + número)"></div>
        <div class="col-md-2"><div class="form-check mt-4"><input type="checkbox" class="form-check-input" id="f_CNFPTP"><label class="form-check-label" for="f_CNFPTP">Confirmado</label></div></div>
        <div class="col-md-2"><div class="form-check mt-4"><input type="checkbox" class="form-check-input" id="d_DISPTP" disabled><label class="form-check-label text-muted" for="d_DISPTP">Discontinuado</label></div></div>
      </div>
      <div class="row g-2">
        <div class="col-md-6"><label class="form-label">Cliente <span class="text-danger">*</span></label><select id="f_CODCLI" class="form-select"><option value="">— Cliente —</option></select></div>
        <div class="col-md-6"><label class="form-label">Marca <span class="text-danger">*</span></label><select id="f_CODMAR" class="form-select"><option value="">— elegí cliente —</option></select></div>
      </div>
      <div class="row g-2">
        <div class="col-12"><label class="form-label">Observaciones</label><input type="text" id="f_OBSPTP" class="form-control"></div>
      </div>
      <div id="formErr" class="text-danger small mt-2"></div>
    </div>
  </div>

  <div class="row g-2">
    <!-- ===== Imágenes ===== -->
    <div class="col-md-8">
      <div class="card fc-card h-100">
        <div class="card-header" data-bs-toggle="collapse" data-bs-target="#cImg" role="button">
          <span><i class="bi bi-images me-1"></i>Imágenes</span><i class="bi bi-chevron-down collapse-icon"></i>
        </div>
        <div id="cImg" class="collapse show"><div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Imagen I</label>
              <div class="iwk-img-box" id="box_IM1"><span class="text-muted small">Sin imagen</span></div>
              <input type="hidden" id="f_IM1PTP">
              <div class="input-group input-group-sm mt-1">
                <input type="file" accept="image/*" class="form-control img-file" data-slot="IM1PTP">
                <button type="button" class="btn btn-outline-danger img-clear" data-slot="IM1PTP" title="Quitar"><i class="bi bi-x"></i></button>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Imagen II</label>
              <div class="iwk-img-box" id="box_IM2"><span class="text-muted small">Sin imagen</span></div>
              <input type="hidden" id="f_IM2PTP">
              <div class="input-group input-group-sm mt-1">
                <input type="file" accept="image/*" class="form-control img-file" data-slot="IM2PTP">
                <button type="button" class="btn btn-outline-danger img-clear" data-slot="IM2PTP" title="Quitar"><i class="bi bi-x"></i></button>
              </div>
            </div>
          </div>
        </div></div>
      </div>
    </div>
    <!-- ===== Órdenes de Muestra ligadas (read-only) ===== -->
    <div class="col-md-4">
      <div class="card fc-card h-100">
        <div class="card-header"><span><i class="bi bi-card-list me-1"></i>Órdenes de Muestra <span class="badge bg-secondary ms-1" id="badgeOdm">0</span></span></div>
        <div class="card-body p-0">
          <table class="table table-sm mb-0"><thead><tr><th>N°</th><th>Acción</th></tr></thead><tbody id="tbOdm"><tr><td colspan="2" class="text-muted text-center py-2">—</td></tr></tbody></table>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== Ruta de procesos (editable) ===== -->
  <div class="card fc-card">
    <div class="card-header" data-bs-toggle="collapse" data-bs-target="#cProc" role="button">
      <span><i class="bi bi-list-ol me-1"></i>Ruta de Procesos <span class="badge bg-secondary ms-1" id="badgeProc">0</span></span>
      <i class="bi bi-chevron-down collapse-icon"></i>
    </div>
    <div id="cProc" class="collapse show"><div class="card-body">
      <table class="fc-grid"><thead><tr>
        <th style="width:3rem">#</th><th>Proceso</th><th style="width:9rem">Sector</th><th>Color de Proceso</th>
        <th style="width:5rem">%</th><th>Observaciones</th><th style="width:2.5rem"></th>
      </tr></thead><tbody id="tbProc"></tbody></table>
      <button type="button" id="btnAddProc" class="btn btn-sm btn-outline-primary mt-2 hb-add"><i class="bi bi-plus-lg me-1"></i>Agregar proceso</button>
    </div></div>
  </div>
</form>

<!-- Buscar -->
<div class="modal fade" id="modalBuscar" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
  <div class="modal-header py-2"><h6 class="modal-title"><i class="bi bi-search me-2"></i>Buscar PTP</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="text" id="bq" class="form-control form-control-sm mb-2" placeholder="N°, cliente, marca, denominación...">
    <table id="grdBuscar" class="table table-sm table-hover w-100" style="cursor:pointer">
      <thead><tr><th>N° PTP</th><th>Fecha</th><th>Cliente</th><th>Marca</th><th>Denominación</th></tr></thead><tbody></tbody>
    </table>
  </div>
</div></div></div>

<!-- Confirm -->
<div class="modal fade" id="modalConfirm" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header py-2"><h6 class="modal-title">Confirmar</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body" id="confirmBody">—</div>
  <div class="modal-footer py-1"><button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" id="btnConfirmOk" class="btn btn-sm btn-danger">Confirmar</button></div>
</div></div></div>

<div class="fc-toast-container"><div id="toastMsg" class="toast align-items-center border-0"><div class="d-flex"><div class="toast-body" id="toastBody"></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div></div>

<template id="rowTpl">
  <tr>
    <td class="text-center ord"></td>
    <td><select class="form-select form-select-sm c-prc"></select></td>
    <td><input type="text" class="form-control form-control-sm c-sec bg-body-tertiary" readonly tabindex="-1"></td>
    <td><select class="form-select form-select-sm c-cdp"></select></td>
    <td><input type="number" step="any" class="form-control form-control-sm text-end c-por"></td>
    <td><input type="text" class="form-control form-control-sm c-obs"></td>
    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger c-del py-0 px-1"><i class="bi bi-trash"></i></button></td>
  </tr>
</template>

<?php
module_foot('
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/js/ptp_edit.js?v=2"></script>
');
