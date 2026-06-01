<?php
/**
 * Impresión de Presupuesto PTP — reproduce el legacy "Rpt Presupuestos PTP".
 * Cabecera (N°, fecha, cliente, prenda, PTP) + detalle de procesos cotizados con
 * precios (cantidad, precio, neto, total) y total general. Print-friendly.
 * Uso: ?id=NUMPPP
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_require_login();

$id = intval((isset($_GET['id']) ? $_GET['id'] : 0));
if ($id <= 0) { http_response_code(400); echo 'Falta el N° de presupuesto.'; exit; }

$h = db_row("SELECT P.NUMPPP, P.FEXPPP, P.NUMPTP, P.TOTPPP, P.OBSPPP, C.DENCLI, Pre.DENPRE
             FROM (([Tbl Presupuestos PTP] AS P
               LEFT JOIN [Tbl Clientes] AS C ON P.CODCLI=C.CODCLI)
               LEFT JOIN [Tbl Prendas] AS Pre ON P.CODPRE=Pre.CODPRE)
             WHERE P.NUMPPP=$id;");
if (!$h) { http_response_code(404); echo "Presupuesto $id no encontrado."; exit; }

$items = db_query("SELECT PP.ORDPPP, Pr.DENPRC, PP.CANPPP, PP.PREPPP, PP.NETPPP, PP.PBXPPP, PP.TOTPPP, PP.OBSPPP
                   FROM [Tbl Presupuestos PTP Procesos] AS PP LEFT JOIN [Tbl Procesos] AS Pr ON PP.CODPRC=Pr.CODPRC
                   WHERE PP.NUMPPP=$id ORDER BY PP.ORDPPP;");

$empresa = sys('tagline', sys('name'));
function m($v) { return ($v === null || $v === '') ? '' : '$ ' . number_format((float)$v, 2, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Presupuesto PTP N° <?= h($id) ?></title>
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
  table.proc tfoot td { font-weight:800; border-top:2px solid #000; font-size:1rem; }
  .seccion { font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; background:#e2e8f0; padding:.25rem .5rem; margin:.8rem 0 .3rem; }
  .obs { border:1px solid #ccc; min-height:2.2rem; padding:.3rem .5rem; white-space:pre-wrap; font-size:.85rem; }
  .firma { margin-top:2.5rem; display:flex; gap:3rem; }
  .firma div { flex:1; border-top:1px solid #000; text-align:center; font-size:.7rem; padding-top:.2rem; }
  .toolbar { max-width:800px; margin:.5rem auto 0; text-align:right; }
  @media print { body{background:#fff;} .hoja{box-shadow:none; margin:0; max-width:100%;} .toolbar{display:none;} @page{margin:1cm;} }
</style>
</head>
<body>
<div class="toolbar">
  <button class="btn btn-sm btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
  <a class="btn btn-sm btn-outline-secondary" href="/produccion_ptp/modules/cotizacion/">Cerrar</a>
</div>
<div class="hoja">
  <div class="titulo">PRESUPUESTO</div>
  <div class="empresa"><?= h($empresa) ?></div>

  <div class="row g-3">
    <div class="col-4"><div class="campo"><div class="lbl">Número</div><div class="val num-grande"><?= h($id) ?></div></div></div>
    <div class="col-4"><div class="campo"><div class="lbl">Fecha</div><div class="val"><?= h(to_disp_date($h['FEXPPP'])) ?></div></div></div>
    <div class="col-4"><div class="campo"><div class="lbl">PTP N°</div><div class="val"><?= h($h['NUMPTP']) ?></div></div></div>
  </div>

  <div class="row g-2 mt-1">
    <div class="col-8"><div class="campo"><div class="lbl">Cliente</div><div class="val"><?= h($h['DENCLI']) ?></div></div></div>
    <div class="col-4"><div class="campo"><div class="lbl">Prenda</div><div class="val"><?= h($h['DENPRE']) ?></div></div></div>
  </div>

  <div class="seccion">Procesos cotizados</div>
  <table class="proc">
    <thead><tr><th style="width:2.5rem">#</th><th>Proceso</th><th class="text-end" style="width:5rem">Cantidad</th><th class="text-end" style="width:6rem">Precio</th><th class="text-end" style="width:6rem">Neto</th><th class="text-end" style="width:7rem">Total</th></tr></thead>
    <tbody>
      <?php if ($items): foreach ($items as $it): ?>
      <tr>
        <td><?= h($it['ORDPPP']) ?></td>
        <td><?= h($it['DENPRC']) ?><?php if (!empty($it['OBSPPP'])): ?><div class="text-muted small"><?= h($it['OBSPPP']) ?></div><?php endif; ?></td>
        <td class="text-end"><?= h($it['CANPPP'] !== null && $it['CANPPP'] !== '' ? number_format((float)$it['CANPPP'], 0, ',', '.') : '') ?></td>
        <td class="text-end"><?= h(m($it['PREPPP'])) ?></td>
        <td class="text-end"><?= h(m($it['NETPPP'])) ?></td>
        <td class="text-end"><?= h(m($it['TOTPPP'])) ?></td>
      </tr>
      <?php endforeach; else: ?>
      <tr><td colspan="6" class="text-muted text-center">Sin procesos cotizados</td></tr>
      <?php endif; ?>
    </tbody>
    <tfoot><tr><td colspan="5" class="text-end">TOTAL</td><td class="text-end"><?= h(m($h['TOTPPP'])) ?></td></tr></tfoot>
  </table>

  <?php if (!empty($h['OBSPPP'])): ?>
  <div class="seccion">Observaciones</div>
  <div class="obs"><?= h($h['OBSPPP']) ?></div>
  <?php endif; ?>

  <div class="firma">
    <div>FIRMA PTP</div>
    <div>CONFORME CLIENTE</div>
  </div>
</div>
</body>
</html>
