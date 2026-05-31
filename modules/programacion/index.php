<?php
/** Programación de Órdenes — tablero (cola CODETA=30 + liberar a producción). */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
auth_require_login();

module_head('Programación de Órdenes', 'bi-calendar-week',
    '<button id="btnReload" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-clockwise me-1"></i>Refrescar</button>');
?>
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="../abm/assets/css/abm.css" rel="stylesheet">

<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between mb-2">
      <span class="text-muted small" id="resumen">—</span>
      <span class="text-muted small"><i class="bi bi-info-circle me-1"></i>"Programar" libera la orden al primer sector de su ruta</span>
    </div>
    <table id="tbl" class="table table-sm table-striped table-hover w-100">
      <thead><tr>
        <th>Sector</th><th>ODP N°</th><th>Cliente</th><th>Marca</th><th>Prenda</th>
        <th>Proceso</th><th class="text-end">Cantidad</th><th class="w-1"></th>
      </tr></thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<!-- MODAL CONFIRMAR -->
<div class="modal fade" id="modalConfirm" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-2"><h6 class="modal-title">Programar orden</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body" id="confirmBody"></div>
      <div class="modal-footer py-1">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-sm btn-success" id="btnConfirmOk"><i class="bi bi-play-fill me-1"></i>Programar</button>
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
<script src="assets/js/programacion.js"></script>
');
