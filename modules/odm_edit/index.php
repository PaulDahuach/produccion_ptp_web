<?php
/** Órdenes de Muestra — Alta / Modificación (paridad con Frm Ordenes De Muestra). */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
auth_require_login();

$ro = db_readonly();
$modo  = (isset($_GET['modo']) ? $_GET['modo'] : '');
$isConf = ($modo === 'confirmar');   // Confirmación: form completo, filtra pendientes
$isEnt  = ($modo === 'entregar');    // Entrega: form completo, filtra confirmadas, remito parcial

$tb = '<div class="btn-group me-2">';
if (!$ro) {
    if ($isEnt) {
        $tb .= '<button id="btnEntregar" class="btn btn-primary btn-sm" disabled><i class="bi bi-truck me-1"></i>Entregar</button>';
    } else {
        if (!$isConf) $tb .= '<button id="btnNuevo" class="btn btn-success btn-sm"><i class="bi bi-plus-lg me-1"></i>Nuevo</button>';
        $tb .= '<button id="btnGuardar" class="btn btn-primary btn-sm" disabled><i class="bi ' . ($isConf ? 'bi-check2-circle me-1"></i>Confirmar' : 'bi-check-lg me-1"></i>Guardar') . '</button>';
    }
    $tb .= '<button id="btnCancelar" class="btn btn-outline-light btn-sm" disabled><i class="bi bi-x-lg me-1"></i>Cancelar</button>';
}
$tb .= '</div><div class="btn-group me-2"><button id="btnBuscar" class="btn btn-outline-light btn-sm"><i class="bi bi-search me-1"></i>Buscar</button>';
if (!$ro && !$isConf && !$isEnt) $tb .= '<button id="btnAnular" class="btn btn-outline-danger btn-sm" disabled><i class="bi bi-slash-circle me-1"></i>Anular</button>';
$tb .= '</div>';

module_head($isConf ? 'Confirmación de Muestra' : ($isEnt ? 'Entrega de Muestra' : 'Órdenes de Muestra — Alta / Modificación'), 'bi-eyedropper', $tb);
?>
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="../abm/assets/css/abm.css" rel="stylesheet">

<form id="mainForm" class="fc-form mode-view" autocomplete="off" data-keynav data-keynav-submit="#btnGuardar" data-modo="<?= h($modo) ?>">

  <!-- ===== Datos de la Orden ===== -->
  <div class="card fc-card">
    <div class="card-header"><span><i class="bi bi-eyedropper me-1"></i>Datos de la Orden
      <span class="text-muted ms-2" style="text-transform:none">N°: <span id="fNum">—</span></span></span></div>
    <div class="card-body">
      <div class="row g-2">
        <div class="col-md-2"><label class="form-label">N°</label><div class="form-control bg-body-tertiary" id="d_NUMODM">—</div></div>
        <div class="col-md-2"><label class="form-label">Fecha Emisión</label><div class="form-control bg-body-tertiary" id="d_FDEODM">—</div></div>
        <div class="col-md-2"><label class="form-label">Estado</label><div class="form-control bg-body-tertiary" id="d_ESTADO">—</div><input type="hidden" id="f_CODEDM"></div>
<?php if ($isEnt): ?>
        <div class="col-md-2"><label class="form-label">Muestras · Código</label><div class="form-control bg-body-tertiary" id="d_CMXODM">—</div></div>
        <div class="col-md-1"><label class="form-label">Total</label><div class="form-control bg-body-tertiary text-end" id="d_TOTAL">—</div></div>
        <div class="col-md-1"><label class="form-label">Disponible</label><div class="form-control bg-body-tertiary text-end" id="d_DISP">—</div></div>
        <div class="col-md-2"><label class="form-label">A Remitir <span class="text-danger">*</span></label><input type="number" id="f_AREMITIR" class="form-control text-end keep-editable" min="0" step="1"></div>
<?php else: ?>
        <div class="col-md-2"><label class="form-label">Muestras · Código</label><input type="text" id="f_CMXODM" class="form-control" maxlength="20"></div>
        <div class="col-md-2"><label class="form-label">Muestras · Cantidad</label><input type="number" id="f_CANODM" class="form-control text-end"></div>
<?php endif; ?>
      </div>
      <div class="row g-2">
        <div class="col-md-6"><label class="form-label">Cliente <span class="text-danger">*</span></label><select id="f_CODCLI" class="form-select"><option value="">— Cliente —</option></select></div>
        <div class="col-md-6"><label class="form-label">Marca <span class="text-danger">*</span></label><select id="f_CODMAR" class="form-select"><option value="">— elegí cliente —</option></select></div>
      </div>
      <div class="row g-2">
        <div class="col-md-3"><label class="form-label">Remito N°</label><input type="text" id="f_REMODM" class="form-control" maxlength="10"></div>
        <div class="col-md-3"><label class="form-label">Adelanto OC N°</label><input type="text" id="f_AOCODM" class="form-control" maxlength="10"></div>
        <div class="col-md-3"><label class="form-label">OP N°</label><input type="text" id="f_NOPODM" class="form-control" maxlength="10"></div>
      </div>
      <div class="row g-2">
        <div class="col-12"><label class="form-label">Observaciones</label><input type="text" id="f_OBSODM" class="form-control"></div>
      </div>
      <div id="formErr" class="text-danger small mt-2"></div>
    </div>
  </div>

  <!-- ===== PTP (se crea/asocia al guardar) ===== -->
  <div class="card fc-card">
    <div class="card-header"><span><i class="bi bi-list-check me-1"></i>PTP</span></div>
    <div class="card-body">
      <div class="row g-2">
        <div class="col-md-3"><label class="form-label">Acción</label><select id="f_CODADP" class="form-select"></select></div>
        <div class="col-md-2"><label class="form-label">N°</label><div class="form-control bg-body-tertiary" id="d_NUMPTP">(se crea al guardar)</div></div>
        <div class="col-md-7"><label class="form-label">Denominación</label><input type="text" id="f_DENPTP" class="form-control" placeholder="(por defecto PTP + número)"></div>
      </div>
    </div>
  </div>

  <!-- ===== Prototipos ===== -->
  <div class="card fc-card">
    <div class="card-header" data-bs-toggle="collapse" data-bs-target="#cProto" role="button">
      <span><i class="bi bi-bounding-box me-1"></i>Prototipos <span class="badge bg-secondary ms-1" id="badgeProt">0</span></span>
      <i class="bi bi-chevron-down collapse-icon"></i>
    </div>
    <div id="cProto" class="collapse show"><div class="card-body">
      <div class="row g-2 mb-2">
        <div class="col-md-4"><label class="form-label">Origen</label><select id="f_CODODM" class="form-select"></select></div>
        <div class="col-md-4"><label class="form-label">Propiedad</label><select id="f_CODPDP" class="form-select"><option value="">—</option></select></div>
        <div class="col-md-2"><label class="form-label">Cantidad</label><input type="number" id="f_CPXODM" class="form-control text-end"></div>
      </div>
      <table class="fc-grid"><thead><tr>
        <th style="width:3rem">#</th><th>Marca</th><th>Precinto</th><th style="width:2.5rem"></th>
      </tr></thead><tbody id="tbProt"></tbody></table>
      <button type="button" id="btnAddProt" class="btn btn-sm btn-outline-primary mt-2 hb-add"><i class="bi bi-plus-lg me-1"></i>Agregar prototipo</button>
    </div></div>
  </div>

  <!-- ===== Prendas / Telas ===== -->
  <div class="card fc-card">
    <div class="card-header" data-bs-toggle="collapse" data-bs-target="#cPre" role="button">
      <span><i class="bi bi-bag me-1"></i>Prendas / Telas <span class="badge bg-secondary ms-1" id="badgePre">0</span></span>
      <i class="bi bi-chevron-down collapse-icon"></i>
    </div>
    <div id="cPre" class="collapse show"><div class="card-body">
      <table class="fc-grid"><thead><tr>
        <th style="width:3rem">#</th><th>Prenda</th><th>Tela</th><th style="width:2.5rem"></th>
      </tr></thead><tbody id="tbPre"></tbody></table>
      <button type="button" id="btnAddPre" class="btn btn-sm btn-outline-primary mt-2 hb-add"><i class="bi bi-plus-lg me-1"></i>Agregar prenda</button>
    </div></div>
  </div>

  <!-- ===== Procesos ===== -->
  <div class="card fc-card">
    <div class="card-header" data-bs-toggle="collapse" data-bs-target="#cProc" role="button">
      <span><i class="bi bi-list-ol me-1"></i>Procesos <span class="badge bg-secondary ms-1" id="badgeProc">0</span></span>
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
  <div class="modal-header py-2"><h6 class="modal-title"><i class="bi bi-search me-2"></i>Buscar Orden de Muestra</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="text" id="bq" class="form-control form-control-sm mb-2" placeholder="N°, cliente, marca, PTP...">
    <table id="grdBuscar" class="table table-sm table-hover w-100" style="cursor:pointer">
      <thead><tr><th>N° Muestra</th><th>Fecha</th><th>Cliente</th><th>Marca</th><th>Cant.</th><th>PTP N°</th></tr></thead><tbody></tbody>
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

<template id="rowProt">
  <tr><td class="text-center ord"></td>
    <td><select class="form-select form-select-sm c-mar"></select></td>
    <td><input type="text" class="form-control form-control-sm c-pre" maxlength="20"></td>
    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger c-del py-0 px-1"><i class="bi bi-trash"></i></button></td>
  </tr>
</template>
<template id="rowPre">
  <tr><td class="text-center ord"></td>
    <td><select class="form-select form-select-sm c-pre"></select></td>
    <td><select class="form-select form-select-sm c-tel"></select></td>
    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger c-del py-0 px-1"><i class="bi bi-trash"></i></button></td>
  </tr>
</template>
<template id="rowProc">
  <tr><td class="text-center ord"></td>
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
<script src="assets/js/odm_edit.js?v=5"></script>
');
