<?php
/**
 * Remito de Muestra — reproduce "Rpt Ordenes de Remision Muestra".
 * Una entrega (fila de Tbl Ordenes De Muestra Remitos, identificada por NUMODM+ORDODM):
 * cabecera de la muestra + cantidad de este remito + prendas. Print-friendly.
 * Uso: ?numodm=NUMODM&ordodm=ORDODM
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_require_login();

$numodm = intval((isset($_GET['numodm']) ? $_GET['numodm'] : 0));
$ordodm = intval((isset($_GET['ordodm']) ? $_GET['ordodm'] : 0));
if ($numodm <= 0 || $ordodm <= 0) { http_response_code(400); echo 'Faltan parámetros.'; exit; }

$r = db_row("SELECT R.NUMODM, R.ORDODM, R.FDRODM, R.CANODM AS CANREM,
               O.CANODM AS CANORI, O.NUMPTP, C.DENCLI, M.DENMAR, Org.DENODM AS ORIGEN
             FROM ((([Tbl Ordenes De Muestra Remitos] AS R
               INNER JOIN [Tbl Ordenes De Muestra] AS O ON R.NUMODM=O.NUMODM)
               LEFT JOIN [Tbl Clientes] AS C ON O.CODCLI=C.CODCLI)
               LEFT JOIN [Tbl Marcas] AS M ON O.CODMAR=M.CODMAR)
               LEFT JOIN [Tbl Origenes De Muestra] AS Org ON O.CODODM=Org.CODODM
             WHERE R.NUMODM=$numodm AND R.ORDODM=$ordodm;");
if (!$r) { http_response_code(404); echo "Remito no encontrado."; exit; }

$prendas = db_query("SELECT PR.ORDODM, Pre.DENPRE, Tl.DENTEL
                     FROM (([Tbl Ordenes De Muestra Prendas] AS PR
                       LEFT JOIN [Tbl Prendas] AS Pre ON PR.CODPRE=Pre.CODPRE)
                       LEFT JOIN [Tbl Telas] AS Tl ON PR.CODTEL=Tl.CODTEL)
                     WHERE PR.NUMODM=$numodm ORDER BY PR.ORDODM;");
$empresa = sys('tagline', sys('name'));
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
<title>Remito de Muestra <?= h($numodm) ?>/<?= h($ordodm) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body{background:#f1f5f9;color:#000;font-size:13px}
  .hoja{background:#fff;max-width:800px;margin:1rem auto;padding:1.5rem 2rem;box-shadow:0 2px 10px rgba(0,0,0,.15)}
  .titulo{text-align:center;font-size:1.5rem;font-weight:800;letter-spacing:.08em;border-bottom:3px solid #000;padding-bottom:.3rem;margin-bottom:.2rem}
  .empresa{text-align:center;font-size:.8rem;color:#555;margin-bottom:.8rem;text-transform:uppercase;letter-spacing:.05em}
  .campo{margin-bottom:.35rem}
  .campo .lbl{font-size:.66rem;font-weight:700;text-transform:uppercase;color:#555;letter-spacing:.04em}
  .campo .val{font-size:.95rem;font-weight:600;border-bottom:1px dotted #bbb;min-height:1.2rem}
  .num-grande{font-size:1.6rem;font-weight:800}
  .seccion{font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;background:#e2e8f0;padding:.25rem .5rem;margin:.8rem 0 .3rem}
  table.t{width:100%;border-collapse:collapse;margin-top:.3rem}
  table.t th{background:#1e293b;color:#fff;font-size:.66rem;text-transform:uppercase;padding:.3rem .4rem;text-align:left}
  table.t td{border-bottom:1px solid #ccc;padding:.3rem .4rem}
  .firma{margin-top:2.5rem;display:flex;gap:3rem}
  .firma div{flex:1;border-top:1px solid #000;text-align:center;font-size:.7rem;padding-top:.2rem}
  .toolbar{max-width:800px;margin:.5rem auto 0;text-align:right}
  @media print{body{background:#fff}.hoja{box-shadow:none;margin:0;max-width:100%}.toolbar{display:none}@page{margin:1cm}}
</style></head><body>
<div class="toolbar">
  <button class="btn btn-sm btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
  <a class="btn btn-sm btn-outline-secondary" href="/produccion_ptp/modules/odm_ciclo/">Cerrar</a>
</div>
<div class="hoja">
  <div class="titulo">REMITO DE MUESTRA</div>
  <div class="empresa"><?= h($empresa) ?></div>
  <div class="row g-3">
    <div class="col-4"><div class="campo"><div class="lbl">Muestra N°</div><div class="val num-grande"><?= h($r['NUMODM']) ?></div></div></div>
    <div class="col-4"><div class="campo"><div class="lbl">Remito N°</div><div class="val num-grande"><?= h($r['ORDODM']) ?></div></div></div>
    <div class="col-4"><div class="campo"><div class="lbl">Fecha</div><div class="val"><?= h(to_disp_date($r['FDRODM'])) ?></div></div></div>
  </div>
  <div class="row g-2 mt-1">
    <div class="col-8"><div class="campo"><div class="lbl">Cliente</div><div class="val"><?= h($r['DENCLI']) ?></div></div></div>
    <div class="col-4"><div class="campo"><div class="lbl">Marca</div><div class="val"><?= h($r['DENMAR']) ?></div></div></div>
    <div class="col-3"><div class="campo"><div class="lbl">PTP N°</div><div class="val"><?= h($r['NUMPTP']) ?></div></div></div>
    <div class="col-3"><div class="campo"><div class="lbl">Origen</div><div class="val"><?= h($r['ORIGEN']) ?></div></div></div>
    <div class="col-3"><div class="campo"><div class="lbl">Cant. Remitida</div><div class="val"><?= h(number_format((float)$r['CANREM'], 0, ',', '.')) ?></div></div></div>
    <div class="col-3"><div class="campo"><div class="lbl">Cant. Muestra</div><div class="val"><?= h(number_format((float)$r['CANORI'], 0, ',', '.')) ?></div></div></div>
  </div>
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
  <div class="firma"><div>ENTREGÓ</div><div>RECIBIÓ</div></div>
</div></body></html>
