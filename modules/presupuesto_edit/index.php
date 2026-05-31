<?php
/** Presupuestos PTP — Alta / Modificación (deriva de una Orden de Muestra). */
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
if (!$ro) $tb .= '<button id="btnAnular" class="btn btn-outline-danger btn-sm" disabled><i class="bi bi-slash-circle me-1"></i>Anular</button>';
$tb .= '</div>';

module_head('Presupuestos PTP — Alta / Modificación', 'bi-cash-coin', $tb);
?>
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="../abm/assets/css/abm.css" rel="stylesheet">
<style>#tblLin input{text-align:right} #tblLin td.calc{text-align:right;font-variant-numeric:tabular-nums}</style>

<form id="mainForm" class="mode-view" autocomplete="off">
  <div class="card mb-3"><div class="card-body">
    <div class="row g-3">
      <div class="col-md-2"><label class="form-label small">N° Presup.</label><div class="fs-5 fw-bold" id="fNum">—</div></div>
      <div class="col-md-2"><label class="form-label small">Fecha</label><input type="text" id="f_FEXPPP" class="form-control form-control-sm" placeholder="dd/mm/aaaa"></div>
      <div class="col-md-2"><label class="form-label small">Muestra (ODM)</label><div class="fw-medium pt-1" id="fOdm">—</div></div>
      <div class="col-md-2"><label class="form-label small">PTP N°</label><div class="fw-medium pt-1" id="fPtp">—</div></div>
      <div class="col-md-2"><label class="form-label small">% Pronto Pago</label><input type="number" step="0.01" id="f_PDPPPP" class="form-control form-control-sm" value="0"></div>
      <div class="col-md-2"><label class="form-label small">% Comercial</label><input type="number" step="0.01" id="f_PDCPPP" class="form-control form-control-sm" value="0"></div>
      <div class="col-md-6"><label class="form-label small">Cliente</label><div class="form-control form-control-sm bg-body-secondary" id="fCli">—</div></div>
      <div class="col-md-3"><label class="form-label small">Prenda</label><div class="form-control form-control-sm bg-body-secondary" id="fPre">—</div></div>
      <div class="col-md-3"><label class="form-label small">Observaciones</label><input type="text" id="f_OBSPPP" class="form-control form-control-sm"></div>
    </div>
    <div id="formErr" class="text-danger small mt-2"></div>
  </div></div>

  <div class="card"><div class="card-body">
    <div class="d-flex justify-content-between mb-2">
      <h6 class="mb-0 text-uppercase small text-muted"><i class="bi bi-list-ol me-1"></i>Procesos cotizados</h6>
      <span class="small text-muted">El precio sugerido inicial = precio de lista del proceso (NETPRC). Editá Sugerido y % bonif. por línea.</span>
    </div>
    <table class="table table-sm table-bordered align-middle mb-3" id="tblLin">
      <thead><tr>
        <th style="width:2.5rem">#</th><th>Proceso</th>
        <th class="text-end" style="width:7rem">P. Lista</th>
        <th class="text-end" style="width:8rem">Sugerido</th>
        <th class="text-end" style="width:6.5rem">Bonif. PP</th>
        <th class="text-end" style="width:8rem">Precio</th>
        <th class="text-end" style="width:6rem">% Bonif.</th>
        <th class="text-end" style="width:6.5rem">Bonif.</th>
        <th class="text-end" style="width:8rem">Neto</th>
      </tr></thead>
      <tbody></tbody>
    </table>
    <div class="row justify-content-end g-2 text-end">
      <div class="col-md-2"><div class="small text-muted">Bruto (ΣSug.)</div><div class="fw-medium" id="tNT0">0,00</div></div>
      <div class="col-md-2"><div class="small text-muted">Desc. P.Pago</div><div class="fw-medium" id="tIDP">0,00</div></div>
      <div class="col-md-2"><div class="small text-muted">Subtotal</div><div class="fw-medium" id="tNT1">0,00</div></div>
      <div class="col-md-2"><div class="small text-muted">Desc. Comercial</div><div class="fw-medium" id="tIDC">0,00</div></div>
      <div class="col-md-2"><div class="small text-muted">TOTAL</div><div class="fs-5 fw-bold text-success" id="tTOT">0,00</div></div>
    </div>
  </div></div>
</form>

<!-- Buscar Orden de Muestra (para nuevo) -->
<div class="modal modal-blur fade" id="modalODM" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title"><i class="bi bi-eyedropper me-2"></i>Elegí la Orden de Muestra a presupuestar</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="text" id="oq" class="form-control form-control-sm mb-2" placeholder="N° muestra, cliente, marca, PTP...">
    <table id="grdODM" class="table table-sm table-hover w-100" style="cursor:pointer">
      <thead><tr><th>N° Muestra</th><th>Fecha</th><th>Cliente</th><th>Marca</th><th>PTP N°</th></tr></thead><tbody></tbody>
    </table>
  </div>
</div></div></div>

<!-- Buscar Presupuesto (para editar) -->
<div class="modal modal-blur fade" id="modalBuscar" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title"><i class="bi bi-search me-2"></i>Buscar Presupuesto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="text" id="bq" class="form-control form-control-sm mb-2" placeholder="N° presup., cliente, PTP...">
    <table id="grdBuscar" class="table table-sm table-hover w-100" style="cursor:pointer">
      <thead><tr><th>N° Presup.</th><th>Fecha</th><th>Cliente</th><th>PTP N°</th><th class="text-end">Total</th></tr></thead><tbody></tbody>
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

<template id="rowLin">
  <tr>
    <td class="ord"></td>
    <td class="prc"></td>
    <td class="calc pdl"></td>
    <td><input type="text" class="form-control form-control-sm c-sug"></td>
    <td class="calc ibp"></td>
    <td class="calc pre"></td>
    <td><input type="text" class="form-control form-control-sm c-pbx"></td>
    <td class="calc ibx"></td>
    <td class="calc net fw-medium"></td>
  </tr>
</template>

<?php
module_foot('
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/js/presupuesto_edit.js?v=1"></script>
');
