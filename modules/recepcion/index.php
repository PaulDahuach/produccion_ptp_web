<?php
/** Recepción de Órdenes de Proceso — vista (form desplegado + Buscar). */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
auth_require_login();

$ro = db_readonly();
$toolbar = '<div class="btn-group me-2">';
if (!$ro) $toolbar .=
    '<button id="btnNuevo" class="btn btn-success btn-sm"><i class="bi bi-plus-lg me-1"></i>Nuevo</button>' .
    '<button id="btnGuardar" class="btn btn-primary btn-sm" disabled><i class="bi bi-check-lg me-1"></i>Guardar</button>' .
    '<button id="btnCancelar" class="btn btn-outline-light btn-sm" disabled><i class="bi bi-x-lg me-1"></i>Cancelar</button>';
$toolbar .= '</div><div class="btn-group me-2"><button id="btnBuscar" class="btn btn-outline-light btn-sm"><i class="bi bi-search me-1"></i>Buscar</button>';
if (!$ro) $toolbar .= '<button id="btnAnular" class="btn btn-outline-danger btn-sm" disabled><i class="bi bi-slash-circle me-1"></i>Anular</button>';
$toolbar .= '</div>';

module_head('Recepción de Órdenes', 'bi-box-arrow-in-down', $toolbar);
?>
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="../abm/assets/css/abm.css" rel="stylesheet">

<div class="fc-form mode-view" id="mainForm">
  <div class="card fc-card">
    <div class="card-header">
      <span><i class="bi bi-box-arrow-in-down me-1"></i>Datos de la Recepción
        <span class="text-muted ms-2" style="text-transform:none">ODP N°: <span id="fNum">—</span></span></span>
    </div>
    <div class="card-body">
      <div class="row g-2">
        <div class="col-md-2"><label class="form-label">Fecha Recepción</label><input type="text" id="f_FDRODP" class="form-control" readonly></div>
        <div class="col-md-3"><label class="form-label">Acción</label><select id="f_CODADO" class="form-select"></select></div>
        <div class="col-md-2"><label class="form-label">Reproceso ODP</label><input type="number" id="f_REPODP" class="form-control" disabled></div>
        <div class="col-md-3"><label class="form-label">Remito N° <span class="text-danger">*</span></label><input type="text" id="f_REMODP" class="form-control" maxlength="10"></div>
      </div>
      <div class="row g-2">
        <div class="col-md-4"><label class="form-label">Cliente <span class="text-danger">*</span></label><select id="f_CODCLI" class="form-select"></select></div>
        <div class="col-md-4"><label class="form-label">Marca <span class="text-danger">*</span></label><select id="f_CODMAR" class="form-select"></select></div>
        <div class="col-md-4"><label class="form-label">Taller <span class="text-danger">*</span></label><select id="f_CODTAL" class="form-select"></select></div>
      </div>
      <div class="row g-2">
        <div class="col-md-3"><label class="form-label">Orden Corte Ext.</label><input type="text" id="f_OCNODP" class="form-control" maxlength="10"></div>
        <div class="col-md-3"><label class="form-label">Cód. Artículo Ext.</label><input type="text" id="f_CAXODP" class="form-control" maxlength="10"></div>
        <div class="col-md-3"><label class="form-label">N° PTP</label><input type="number" id="f_NUMPTP" class="form-control"></div>
      </div>
      <div class="row g-2">
        <div class="col-md-4"><label class="form-label">Prenda <span class="text-danger">*</span></label><select id="f_CODPR1" class="form-select"></select></div>
        <div class="col-md-2"><label class="form-label">Cantidad <span class="text-danger">*</span></label><input type="number" id="f_CANODP" class="form-control text-end"></div>
        <div class="col-md-2"><label class="form-label">Peso (Kg) <span class="text-danger">*</span></label><input type="number" step="any" id="f_PESODP" class="form-control text-end"></div>
        <div class="col-md-4"><label class="form-label">Tipo Prenda 2</label><select id="f_CODPR2" class="form-select"></select></div>
      </div>
      <div class="row g-2">
        <div class="col-md-4"><label class="form-label">Tela</label><select id="f_CODTEL" class="form-select"></select></div>
        <div class="col-md-3"><label class="form-label">Color 1</label><select id="f_CODCT1" class="form-select"></select></div>
        <div class="col-md-3"><label class="form-label">Color 2</label><select id="f_CODCT2" class="form-select"></select></div>
      </div>
      <div class="row g-2 align-items-center">
        <div class="col-md-3"><div class="form-check mt-3"><input type="checkbox" class="form-check-input" id="f_PRTODP"><label class="form-check-label" for="f_PRTODP">Lleva Precinto</label></div></div>
        <div class="col-md-3"><label class="form-label">N° Precinto</label><input type="text" id="f_PREODP" class="form-control" maxlength="10"></div>
      </div>
      <div class="row g-2">
        <div class="col-12"><label class="form-label">Observaciones</label><textarea id="f_O10ODP" class="form-control" rows="2"></textarea></div>
      </div>
      <div class="text-danger small mt-2" id="formErr"></div>
    </div>
  </div>
</div>

<!-- MODAL BUSCAR -->
<div class="modal fade" id="modalBuscar" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header py-2"><h6 class="modal-title"><i class="bi bi-search me-2"></i>Buscar Orden</h6>
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

<!-- MODAL CONFIRMAR -->
<div class="modal fade" id="modalConfirm" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-2"><h6 class="modal-title">Anular orden</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body" id="confirmBody"></div>
      <div class="modal-footer py-1">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-sm btn-danger" id="btnConfirmOk"><i class="bi bi-slash-circle me-1"></i>Anular</button>
      </div>
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
<script src="assets/js/recepcion.js"></script>
');
