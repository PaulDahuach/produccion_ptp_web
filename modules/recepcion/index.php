<?php
/** Recepción de Órdenes de Proceso — vista (paridad con Frm Recepcion de Access). */
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
$toolbar .= '<a id="btnImprimir" class="btn btn-outline-light btn-sm disabled" target="_blank" href="#"><i class="bi bi-printer me-1"></i>Imprimir</a>';
if (!$ro) $toolbar .= '<button id="btnAnular" class="btn btn-outline-danger btn-sm" disabled><i class="bi bi-slash-circle me-1"></i>Anular</button>';
$toolbar .= '</div>';

module_head('Recepción de Órdenes', 'bi-box-arrow-in-down', $toolbar);
?>
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="../abm/assets/css/abm.css" rel="stylesheet">

<div class="fc-form mode-view" id="mainForm" data-keynav data-keynav-submit="#btnGuardar">

  <!-- ===== Datos de la Recepción ===== -->
  <div class="card fc-card">
    <div class="card-header"><span><i class="bi bi-box-arrow-in-down me-1"></i>Datos de la Recepción</span></div>
    <div class="card-body">
      <!-- Fila 1: Nº · Emisión · Sector · Acción · OP Nº · Fecha Entrega -->
      <div class="row g-2">
        <div class="col-md-2"><label class="form-label">N°</label><div class="form-control bg-body-tertiary" id="d_NUMODP">—</div></div>
        <div class="col-md-2"><label class="form-label">Emisión</label><div class="form-control bg-body-tertiary" id="d_FDRODP">—</div></div>
        <div class="col-md-2"><label class="form-label">Sector</label><div class="form-control bg-body-tertiary" id="d_SECTOR">—</div></div>
        <div class="col-md-2"><label class="form-label">Acción</label><select id="f_CODADO" class="form-select"></select></div>
        <div class="col-md-2"><label class="form-label">OP N° <span class="text-muted small">(reproc.)</span></label><input type="number" id="f_REPODP" class="form-control" disabled></div>
        <div class="col-md-2"><label class="form-label">Fecha Entrega Est.</label><div class="form-control bg-body-tertiary" id="d_FDEODP">—</div></div>
      </div>
      <!-- Fila 2: Cliente -->
      <div class="row g-2">
        <div class="col-md-2"><label class="form-label">Remito N° <span class="text-danger">*</span></label><input type="text" id="f_REMODP" class="form-control" maxlength="10"></div>
        <div class="col-md-6"><label class="form-label">Cliente <span class="text-danger">*</span></label><select id="f_CODCLI" class="form-select"></select></div>
        <div class="col-md-4"><label class="form-label">Marca <span class="text-danger">*</span></label><select id="f_CODMAR" class="form-select"></select></div>
      </div>
      <!-- Fila 3 -->
      <div class="row g-2">
        <div class="col-md-3"><label class="form-label">Taller <span class="text-danger">*</span></label><select id="f_CODTAL" class="form-select"></select></div>
        <div class="col-md-2"><label class="form-label">OC N°</label><input type="text" id="f_OCNODP" class="form-control" maxlength="10"></div>
        <div class="col-md-2"><label class="form-label">Cód. Artículo</label><input type="text" id="f_CAXODP" class="form-control" maxlength="10"></div>
        <div class="col-md-3"><label class="form-label">Prenda <span class="text-danger">*</span></label><select id="f_CODPR1" class="form-select"></select></div>
        <div class="col-md-1"><label class="form-label">Cantidad <span class="text-danger">*</span></label><input type="number" id="f_CANODP" class="form-control text-end"></div>
        <div class="col-md-1"><label class="form-label">Peso (Kg) <span class="text-danger">*</span></label><input type="number" step="any" id="f_PESODP" class="form-control text-end"></div>
      </div>
      <!-- Fila 4: las tres observaciones -->
      <div class="row g-2">
        <div class="col-md-4"><label class="form-label">Observaciones Recepción</label><textarea id="f_O10ODP" class="form-control" rows="2"></textarea></div>
        <div class="col-md-4"><label class="form-label">Observaciones Definición</label><textarea id="d_O20ODP" class="form-control bg-body-tertiary" rows="2" readonly></textarea></div>
        <div class="col-md-4"><label class="form-label">Observaciones Internas</label><textarea id="d_OBSODP" class="form-control bg-body-tertiary" rows="2" readonly></textarea></div>
      </div>
      <div class="text-danger small mt-2" id="formErr"></div>
    </div>
  </div>

  <!-- ===== Prototipo / PTP ===== -->
  <div class="card fc-card">
    <div class="card-header" data-bs-toggle="collapse" data-bs-target="#cProto" role="button">
      <span><i class="bi bi-tags me-1"></i>Prototipo / PTP</span>
      <i class="bi bi-chevron-down collapse-icon"></i>
    </div>
    <div id="cProto" class="collapse show"><div class="card-body">
      <!-- Precinto + bloque PTP (display) -->
      <div class="row g-2 align-items-end">
        <div class="col-md-2"><div class="form-check mt-1"><input type="checkbox" class="form-check-input" id="f_PRTODP"><label class="form-check-label" for="f_PRTODP">Lleva Precinto</label></div></div>
        <div class="col-md-2"><label class="form-label">Precinto N°</label><input type="text" id="f_PREODP" class="form-control" maxlength="10"></div>
        <div class="col-md-1"><label class="form-label">PTP N°</label><div class="form-control bg-body-tertiary" id="d_NUMPTP">—</div></div>
        <div class="col-md-3"><label class="form-label">PTP Denominación</label><div class="form-control bg-body-tertiary" id="d_DENPTP">—</div></div>
        <div class="col-md-2"><label class="form-label">Presupuesto N°</label><div class="form-control bg-body-tertiary" id="d_NUMPPP">—</div></div>
        <div class="col-md-2"><label class="form-label">Fecha Presup.</label><div class="form-control bg-body-tertiary" id="d_FEXPPP">—</div></div>
      </div>
      <div class="row g-2">
        <div class="col-md-3"><div class="form-check mt-1"><input type="checkbox" class="form-check-input" id="d_SPMODP" disabled><label class="form-check-label text-muted" for="d_SPMODP">Modificado</label></div></div>
      </div>
    </div></div>
  </div>

  <!-- ===== Procesos (solo lectura en Recepción) ===== -->
  <div class="card fc-card">
    <div class="card-header" data-bs-toggle="collapse" data-bs-target="#cProc" role="button">
      <span><i class="bi bi-list-ol me-1"></i>Procesos
      <span class="badge bg-secondary ms-1" id="badgeProc">0</span></span>
      <i class="bi bi-chevron-down collapse-icon"></i>
    </div>
    <div id="cProc" class="collapse show"><div class="card-body">
      <table class="fc-grid"><thead><tr>
        <th style="width:3rem">#</th><th>Proceso</th><th>Sector</th><th>Color</th>
        <th style="width:5rem">%</th><th>Observaciones</th>
      </tr></thead><tbody id="tbProc"><tr><td colspan="6" class="text-muted text-center py-2">Sin procesos (se definen en Definición)</td></tr></tbody></table>
    </div></div>
  </div>

  <!-- ===== Prenda / Tela (prototipo) — debajo de Procesos, como en el form de Access ===== -->
  <div class="card fc-card">
    <div class="card-header"><span><i class="bi bi-rulers me-1"></i>Prenda / Tela</span></div>
    <div class="card-body">
      <div class="row g-2">
        <div class="col-md-3"><label class="form-label">Prenda · Tipo</label><select id="f_CODPR2" class="form-select"></select></div>
        <div class="col-md-3"><label class="form-label">Tela · Tipo</label><select id="f_CODTEL" class="form-select"></select></div>
        <div class="col-md-3"><label class="form-label">Tela · Color</label><select id="f_CODCT1" class="form-select"></select></div>
        <div class="col-md-3"><label class="form-label">Tela · Cuerpo</label><select id="f_CODCT2" class="form-select"></select></div>
      </div>
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
<script src="assets/js/recepcion.js?v=4"></script>
');
