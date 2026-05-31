<?php
/**
 * Reimpresión de Orden de Muestra — reproduce el legacy "Rpt Ordenes de Muestra"
 * (cabecera + procesos + prendas/telas). Print-friendly.
 * Uso: ?id=NUMODM  (o sin id → buscador).
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
auth_require_login();

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    require_once __DIR__ . '/../../includes/layout.php';
    module_head('Reimprimir Orden de Muestra', 'bi-printer', '');
    ?>
    <div class="card"><div class="card-body">
      <label class="form-label">N° de Orden de Muestra</label>
      <div class="input-group" style="max-width:340px">
        <input type="number" id="num" class="form-control" placeholder="Ej. 109437" autofocus>
        <button class="btn btn-primary" id="go"><i class="bi bi-printer me-1"></i>Abrir</button>
      </div>
      <p class="text-muted small mt-2">Se abre la muestra lista para imprimir.</p>
    </div></div>
    <?php
    module_foot('<script>
      function ir(){ var n=document.getElementById("num").value.trim(); if(n) location.href="?id="+encodeURIComponent(n); }
      document.getElementById("go").addEventListener("click", ir);
      document.getElementById("num").addEventListener("keydown", function(e){ if(e.key==="Enter") ir(); });
    </script>');
    exit;
}

$h = db_row("SELECT O.NUMODM, O.FDEODM, O.CANODM, O.NUMPTP, O.OBSODM, Ed.DENEDM, C.DENCLI, M.DENMAR,
               Org.DENODM AS ORIGEN, Acc.DENADP AS ACCION
             FROM ((((([Tbl Ordenes De Muestra] AS O
               LEFT JOIN [Tbl Clientes] AS C ON O.CODCLI = C.CODCLI)
               LEFT JOIN [Tbl Marcas] AS M ON O.CODMAR = M.CODMAR)
               LEFT JOIN [Tbl Estados De Muestra] AS Ed ON O.CODEDM = Ed.CODEDM)
               LEFT JOIN [Tbl Origenes De Muestra] AS Org ON O.CODODM = Org.CODODM)
               LEFT JOIN [Tbl Acciones De PTP] AS Acc ON O.CODADP = Acc.CODADP)
             WHERE O.NUMODM = $id;");
if (!$h) { http_response_code(404); echo "Orden de Muestra $id no encontrada."; exit; }

$procs = db_query("SELECT PP.ORDODM, Prc.DENPRC, E.DENETA, CP.DENCDP, PP.PORODM, PP.OBSODM
                   FROM ((([Tbl Ordenes De Muestra Procesos] AS PP
                     LEFT JOIN [Tbl Procesos] AS Prc ON PP.CODPRC = Prc.CODPRC)
                     LEFT JOIN [Tbl Etapas] AS E ON Prc.CODETA = E.CODETA)
                     LEFT JOIN [Tbl Colores De Proceso] AS CP ON PP.CODCDP = CP.CODCDP)
                   WHERE PP.NUMODM = $id ORDER BY PP.ORDODM;");
$prendas = db_query("SELECT PR.ORDODM, Pre.DENPRE, Tl.DENTEL
                     FROM (([Tbl Ordenes De Muestra Prendas] AS PR
                       LEFT JOIN [Tbl Prendas] AS Pre ON PR.CODPRE = Pre.CODPRE)
                       LEFT JOIN [Tbl Telas] AS Tl ON PR.CODTEL = Tl.CODTEL)
                     WHERE PR.NUMODM = $id ORDER BY PR.ORDODM;");

$empresa = sys('tagline', sys('name'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Orden de Muestra N° <?= h($id) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { background:#f1f5f9; color:#000; font-size:13px; }
  .hoja { background:#fff; max-width:800px; margin:1rem auto; padding:1.5rem 2rem; box-shadow:0 2px 10px rgba(0,0,0,.15); }
  .titulo { text-align:center; font-size:1.5rem; font-weight:800; letter-spacing:.08em; border-bottom:3px solid #000; padding-bottom:.3rem; margin-bottom:.2rem; }
  .empresa { text-align:center; font-size:.8rem; color:#555; margin-bottom:.8rem; text-transform:uppercase; letter-spacing:.05em; }
  .campo { margin-bottom:.35rem; }
  .campo .lbl { font-size:.66rem; font-weight:700; text-transform:uppercase; color:#555; letter-spacing:.04em; }
  .campo .val { font-size:.95rem; font-weight:600; border-bottom:1px dotted #bbb; min-height:1.2rem; }
  .num-grande { font-size:1.8rem; font-weight:800; }
  table.t { width:100%; border-collapse:collapse; margin-top:.4rem; }
  table.t th { background:#1e293b; color:#fff; font-size:.66rem; text-transform:uppercase; padding:.3rem .4rem; text-align:left; }
  table.t td { border-bottom:1px solid #ccc; padding:.3rem .4rem; }
  .seccion { font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; background:#e2e8f0; padding:.25rem .5rem; margin:.8rem 0 .3rem; }
  .obs { border:1px solid #ccc; min-height:2rem; padding:.3rem .5rem; white-space:pre-wrap; font-size:.85rem; }
  .firma { margin-top:2.5rem; display:flex; gap:3rem; }
  .firma div { flex:1; border-top:1px solid #000; text-align:center; font-size:.7rem; padding-top:.2rem; }
  .toolbar { max-width:800px; margin:.5rem auto 0; text-align:right; }
  @media print { body{background:#fff;} .hoja{box-shadow:none; margin:0; max-width:100%;} .toolbar{display:none;} @page{margin:1cm;} }
</style>
</head>
<body>
<div class="toolbar">
  <button class="btn btn-sm btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
  <a class="btn btn-sm btn-outline-secondary" href="/produccion_ptp/app/index.php">Cerrar</a>
</div>
<div class="hoja">
  <div class="titulo">ORDEN DE MUESTRA</div>
  <div class="empresa"><?= h($empresa) ?></div>

  <div class="row g-3">
    <div class="col-4"><div class="campo"><div class="lbl">Número</div><div class="val num-grande"><?= h($id) ?></div></div></div>
    <div class="col-4"><div class="campo"><div class="lbl">Fecha</div><div class="val"><?= h(to_disp_date($h['FDEODM'])) ?></div></div></div>
    <div class="col-4"><div class="campo"><div class="lbl">Estado</div><div class="val"><?= h($h['DENEDM']) ?></div></div></div>
  </div>
  <div class="row g-2 mt-1">
    <div class="col-7"><div class="campo"><div class="lbl">Cliente</div><div class="val"><?= h($h['DENCLI']) ?></div></div></div>
    <div class="col-5"><div class="campo"><div class="lbl">Marca</div><div class="val"><?= h($h['DENMAR']) ?></div></div></div>
    <div class="col-3"><div class="campo"><div class="lbl">Cantidad</div><div class="val"><?= h(number_format((float)$h['CANODM'], 0, ',', '.')) ?></div></div></div>
    <div class="col-3"><div class="campo"><div class="lbl">PTP N°</div><div class="val"><?= h($h['NUMPTP']) ?></div></div></div>
    <div class="col-3"><div class="campo"><div class="lbl">Origen</div><div class="val"><?= h($h['ORIGEN']) ?></div></div></div>
    <div class="col-3"><div class="campo"><div class="lbl">Acción PTP</div><div class="val"><?= h($h['ACCION']) ?></div></div></div>
  </div>

  <div class="seccion">Procesos</div>
  <table class="t">
    <thead><tr><th style="width:2.5rem">#</th><th>Proceso</th><th>Sector</th><th>Color</th><th style="width:4rem">%</th><th>Obs</th></tr></thead>
    <tbody>
      <?php if ($procs): foreach ($procs as $p): ?>
      <tr><td><?= h($p['ORDODM']) ?></td><td><?= h($p['DENPRC']) ?></td><td><?= h($p['DENETA']) ?></td><td><?= h($p['DENCDP']) ?></td><td><?= h($p['PORODM']) ?></td><td><?= h($p['OBSODM']) ?></td></tr>
      <?php endforeach; else: ?>
      <tr><td colspan="6" class="text-muted text-center">Sin procesos</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <div class="seccion">Prendas / Telas</div>
  <table class="t">
    <thead><tr><th style="width:2.5rem">#</th><th>Prenda</th><th>Tela</th></tr></thead>
    <tbody>
      <?php if ($prendas): foreach ($prendas as $p): ?>
      <tr><td><?= h($p['ORDODM']) ?></td><td><?= h($p['DENPRE']) ?></td><td><?= h($p['DENTEL']) ?></td></tr>
      <?php endforeach; else: ?>
      <tr><td colspan="3" class="text-muted text-center">Sin prendas</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <?php if (!empty($h['OBSODM'])): ?>
  <div class="seccion">Observaciones</div>
  <div class="obs"><?= h($h['OBSODM']) ?></div>
  <?php endif; ?>

  <div class="firma"><div>SOLICITÓ</div><div>RECIBIÓ</div></div>
</div>
</body>
</html>
