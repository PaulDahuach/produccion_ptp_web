<?php
/**
 * Impresión / Reimpresión de Orden de Proceso.
 * Reproduce el formato del legacy "Rpt Ordenes de Proceso" (título, N°, F.Definición,
 * cliente/marca/prenda/cantidad, OC, PTP, código de barras Code39, observaciones de
 * recepción y definición, y la tabla de PROCESOS). Print-friendly.
 * Uso: ?id=NUMODP  (o sin id → buscador para reimprimir).
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
auth_require_login();

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    // Buscador para reimprimir
    require_once __DIR__ . '/../../includes/layout.php';
    module_head('Reimprimir Orden de Proceso', 'bi-printer', '');
    ?>
    <div class="card"><div class="card-body">
      <label class="form-label">N° de Orden (ODP)</label>
      <div class="input-group" style="max-width:340px">
        <input type="number" id="num" class="form-control" placeholder="Ej. 25366" autofocus>
        <button class="btn btn-primary" id="go"><i class="bi bi-printer me-1"></i>Abrir</button>
      </div>
      <p class="text-muted small mt-2">Se abre la orden lista para imprimir.</p>
    </div></div>
    <?php
    module_foot('<script>
      function ir(){ var n=document.getElementById("num").value.trim(); if(n) location.href="?id="+encodeURIComponent(n); }
      document.getElementById("go").addEventListener("click", ir);
      document.getElementById("num").addEventListener("keydown", function(e){ if(e.key==="Enter") ir(); });
    </script>');
    exit;
}

// ---- Datos de la orden ----
$sql = "SELECT O.NUMODP, O.FDRODP, O.FDDODP, O.CANODP, O.OCNODP, O.CAXODP, O.NUMPTP, O.REPODP,
          O.PREODP, O.O10ODP, O.O20ODP, O.BARODP, O.CODETA,
          C.DENCLI, M.DENMAR, T.DENTAL, P1.DENPRE AS DENPR1, P2.DENPRE AS DENPR2, Tl.DENTEL
        FROM (((((([Tbl Ordenes De Proceso] AS O
          LEFT JOIN [Tbl Clientes] AS C ON O.CODCLI=C.CODCLI)
          LEFT JOIN [Tbl Marcas] AS M ON O.CODMAR=M.CODMAR)
          LEFT JOIN [Tbl Talleres] AS T ON O.CODTAL=T.CODTAL)
          LEFT JOIN [Tbl Prendas] AS P1 ON O.CODPR1=P1.CODPRE)
          LEFT JOIN [Tbl Prendas] AS P2 ON O.CODPR2=P2.CODPRE)
          LEFT JOIN [Tbl Telas] AS Tl ON O.CODTEL=Tl.CODTEL)
        WHERE O.NUMODP = $id;";
$o = db_row($sql);
if (!$o) { http_response_code(404); echo "Orden $id no encontrada."; exit; }

$procs = db_query("SELECT OPP.ORDODP, Pr.DENPRC, E.DENETA, CP.DENCDP, OPP.PORODP, OPP.OBSODP
                   FROM (([Tbl Ordenes De Proceso Procesos] AS OPP
                     LEFT JOIN [Tbl Procesos] AS Pr ON OPP.CODPRC=Pr.CODPRC)
                     LEFT JOIN [Tbl Etapas] AS E ON Pr.CODETA=E.CODETA)
                     LEFT JOIN [Tbl Colores De Proceso] AS CP ON OPP.CODCDP=CP.CODCDP
                   WHERE OPP.NUMODP=$id ORDER BY OPP.ORDODP;");

$empresa = sys('tagline', sys('name'));
$bar = $o['BARODP'] ?: ('OP' . str_pad((string)$id, 8, '0', STR_PAD_LEFT));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Orden de Proceso N° <?= h($id) ?></title>
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
  table.proc { width:100%; border-collapse:collapse; margin-top:.4rem; }
  table.proc th { background:#1e293b; color:#fff; font-size:.66rem; text-transform:uppercase; padding:.3rem .4rem; text-align:left; }
  table.proc td { border-bottom:1px solid #ccc; padding:.3rem .4rem; }
  .seccion { font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; background:#e2e8f0; padding:.25rem .5rem; margin:.8rem 0 .3rem; }
  .obs { border:1px solid #ccc; min-height:2.2rem; padding:.3rem .5rem; white-space:pre-wrap; font-size:.85rem; }
  .firma { margin-top:2.5rem; display:flex; gap:3rem; }
  .firma div { flex:1; border-top:1px solid #000; text-align:center; font-size:.7rem; padding-top:.2rem; }
  .barranum { font-family:monospace; text-align:center; font-size:.8rem; letter-spacing:.1em; }
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
  <div class="titulo">ORDEN DE PROCESO</div>
  <div class="empresa"><?= h($empresa) ?></div>

  <div class="row g-3">
    <div class="col-4">
      <div class="campo"><div class="lbl">Número</div><div class="val num-grande"><?= h($id) ?></div></div>
    </div>
    <div class="col-4">
      <div class="campo"><div class="lbl">F. Definición</div><div class="val"><?= h(to_disp_date($o['FDDODP']) ?: to_disp_date($o['FDRODP'])) ?></div></div>
    </div>
    <div class="col-4 text-end">
      <svg id="barcode"></svg>
      <div class="barranum"><?= h($bar) ?></div>
    </div>
  </div>

  <div class="row g-2 mt-1">
    <div class="col-7"><div class="campo"><div class="lbl">Cliente</div><div class="val"><?= h($o['DENCLI']) ?></div></div></div>
    <div class="col-5"><div class="campo"><div class="lbl">Marca</div><div class="val"><?= h($o['DENMAR']) ?></div></div></div>
    <div class="col-5"><div class="campo"><div class="lbl">Prenda</div><div class="val"><?= h($o['DENPR1']) ?></div></div></div>
    <div class="col-3"><div class="campo"><div class="lbl">Tipo</div><div class="val"><?= h($o['DENPR2']) ?></div></div></div>
    <div class="col-2"><div class="campo"><div class="lbl">Cantidad</div><div class="val"><?= h(number_format((float)$o['CANODP'],0,',','.')) ?></div></div></div>
    <div class="col-2"><div class="campo"><div class="lbl">Precinto</div><div class="val"><?= h($o['PREODP']) ?></div></div></div>
    <div class="col-3"><div class="campo"><div class="lbl">OC N°</div><div class="val"><?= h($o['OCNODP']) ?></div></div></div>
    <div class="col-3"><div class="campo"><div class="lbl">Cód. Artículo</div><div class="val"><?= h($o['CAXODP']) ?></div></div></div>
    <div class="col-3"><div class="campo"><div class="lbl">PTP N°</div><div class="val"><?= h($o['NUMPTP']) ?></div></div></div>
    <div class="col-3"><div class="campo"><div class="lbl">Reproceso</div><div class="val"><?= h($o['REPODP']) ?></div></div></div>
    <div class="col-6"><div class="campo"><div class="lbl">Taller</div><div class="val"><?= h($o['DENTAL']) ?></div></div></div>
    <div class="col-6"><div class="campo"><div class="lbl">Tela</div><div class="val"><?= h($o['DENTEL']) ?></div></div></div>
  </div>

  <div class="seccion">Procesos</div>
  <table class="proc">
    <thead><tr><th style="width:2.5rem">#</th><th>Proceso</th><th>Sector</th><th>Color</th><th style="width:4rem">%</th></tr></thead>
    <tbody>
      <?php if ($procs): foreach ($procs as $p): ?>
      <tr><td><?= h($p['ORDODP']) ?></td><td><?= h($p['DENPRC']) ?></td><td><?= h($p['DENETA']) ?></td><td><?= h($p['DENCDP']) ?></td><td><?= h($p['PORODP']) ?></td></tr>
      <?php endforeach; else: ?>
      <tr><td colspan="5" class="text-muted text-center">Sin procesos definidos</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <div class="seccion">Observaciones Recepción</div>
  <div class="obs"><?= h($o['O10ODP']) ?></div>
  <div class="seccion">Observaciones Definición</div>
  <div class="obs"><?= h($o['O20ODP']) ?></div>

  <div class="firma">
    <div>FIRMA TALLER</div>
    <div>FIRMA CONTROL</div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
  try { JsBarcode("#barcode", <?= json_encode($bar) ?>, {format:"CODE39", width:1.3, height:45, displayValue:false, margin:0}); } catch(e){}
</script>
</body>
</html>
