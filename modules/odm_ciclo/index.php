<?php
/** Órdenes de Muestra — Confirmación y Entrega (ciclo de vida). */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
auth_require_login();

module_head('Muestras — Confirmación y Entrega', 'bi-truck',
    '<button id="btnReload" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-clockwise me-1"></i>Refrescar</button>');
?>
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<style>#tbl tbody tr{cursor:default}</style>

<div class="card mb-3"><div class="card-body py-2">
  <div class="row g-2 align-items-end">
    <div class="col-md-4">
      <label class="form-label mb-1 small">Fase</label>
      <div class="btn-group w-100" role="group">
        <input type="radio" class="btn-check" name="fase" id="faseConf" value="confirmar" checked>
        <label class="btn btn-outline-primary btn-sm" for="faseConf"><i class="bi bi-check2-circle me-1"></i>Por Confirmar</label>
        <input type="radio" class="btn-check" name="fase" id="faseEnt" value="entregar">
        <label class="btn btn-outline-primary btn-sm" for="faseEnt"><i class="bi bi-truck me-1"></i>Por Entregar</label>
      </div>
    </div>
    <div class="col-md-5">
      <label class="form-label mb-1 small">Buscar</label>
      <input type="text" id="fq" class="form-control form-control-sm" placeholder="N° muestra, cliente, marca, PTP...">
    </div>
    <div class="col-md-3 d-flex gap-2">
      <button id="btnFiltrar" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel me-1"></i>Filtrar</button>
    </div>
  </div>
</div></div>

<div class="card"><div class="card-body">
  <div class="d-flex justify-content-between mb-2">
    <span class="text-muted small" id="resumen">—</span>
    <span class="text-muted small" id="hint"></span>
  </div>
  <table id="tbl" class="table table-sm table-striped table-hover w-100">
    <thead id="thd"></thead><tbody></tbody>
  </table>
</div></div>

<!-- Confirmar -->
<div class="modal modal-blur fade" id="modalConf" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title"><i class="bi bi-check2-circle me-2"></i>Confirmar muestra</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body" id="confBody">—</div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" id="btnConfOk" class="btn btn-success">Confirmar</button></div>
</div></div></div>

<!-- Entregar -->
<div class="modal modal-blur fade" id="modalEnt" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title"><i class="bi bi-truck me-2"></i>Entregar (remito)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="small text-muted mb-2" id="entInfo">—</div>
    <label class="form-label small">Cantidad a remitir</label>
    <input type="number" id="entCant" class="form-control form-control-sm" min="0" step="1">
    <div class="form-text">Podés remitir en partes; cuando se complete la cantidad, la muestra pasa a Remitida.</div>
    <div id="entErr" class="text-danger small mt-2"></div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" id="btnEntOk" class="btn btn-primary"><i class="bi bi-truck me-1"></i>Entregar e imprimir remito</button></div>
</div></div></div>

<div class="toast-container position-fixed bottom-0 end-0 p-3"><div id="toastMsg" class="toast align-items-center text-bg-info border-0"><div class="d-flex"><div class="toast-body" id="toastBody"></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div></div>

<?php
module_foot('
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/js/odm_ciclo.js"></script>
');
