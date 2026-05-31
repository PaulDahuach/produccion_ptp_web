<?php
/** Impresión — Movimientos de Lotes (detalle o agrupado, según nivel). Apaisado. */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_require_login();
require __DIR__ . '/_query.php';

$res = movimientos_rows();
$nivel = $res['nivel'];
$rows = $res['rows'];
$isDet = ($nivel === 'detalle');
$empresa = sys('tagline', sys('name'));
$desde = trim($_GET['desde'] ?? ''); $hasta = trim($_GET['hasta'] ?? '');
$gl = $nivel === 'personal' ? 'Sector Personal' : ($nivel === 'planta' ? 'Planta' : 'Sector Producción');
$totIng = 0; $totEgr = 0;
foreach ($rows as $r) { $totIng += (float)($isDet ? $r['INGMOV'] : $r['ING']); $totEgr += (float)($isDet ? $r['EGRMOV'] : $r['EGR']); }
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Movimientos de Lotes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body{background:#f1f5f9;color:#000;font-size:11px}
  .hoja{background:#fff;max-width:1180px;margin:1rem auto;padding:1.2rem 1.5rem;box-shadow:0 2px 10px rgba(0,0,0,.15)}
  .titulo{text-align:center;font-size:1.25rem;font-weight:800;letter-spacing:.06em;border-bottom:2px solid #000;padding-bottom:.2rem}
  .empresa{text-align:center;font-size:.72rem;color:#555;text-transform:uppercase;letter-spacing:.05em}
  .meta{text-align:center;font-size:.7rem;color:#444;margin-bottom:.5rem}
  table.lst{width:100%;border-collapse:collapse}
  table.lst th{background:#e2e8f0;font-size:.62rem;text-transform:uppercase;padding:.2rem .35rem;text-align:left;border-bottom:1px solid #94a3b8}
  table.lst td{border-bottom:1px solid #ddd;padding:.18rem .35rem}
  table.lst tr:nth-child(even) td{background:#f8fafc}
  .num{text-align:right}
  .totg{font-weight:800;font-size:.9rem;margin-top:.8rem;text-align:right;border-top:2px solid #000;padding-top:.3rem}
  .toolbar{max-width:1180px;margin:.5rem auto 0;text-align:right}
  @media print{body{background:#fff}.hoja{box-shadow:none;margin:0;max-width:100%}.toolbar{display:none}@page{size:landscape;margin:.8cm}}
</style></head><body>
<div class="toolbar">
  <button class="btn btn-sm btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
  <a class="btn btn-sm btn-outline-secondary" href="/produccion_ptp/modules/odp_movimientos/">Cerrar</a>
</div>
<div class="hoja">
  <div class="titulo">MOVIMIENTOS DE LOTES</div>
  <div class="empresa"><?= h($empresa) ?></div>
  <div class="meta"><?= h($desde) ?> a <?= h($hasta) ?> — Nivel: <?= h($isDet ? 'Detalle' : $gl) ?> — <?= count($rows) ?> <?= $isDet ? 'movimiento(s)' : 'grupo(s)' ?></div>
  <?php if (!$rows): ?>
    <p class="text-center text-muted">Sin movimientos en el rango seleccionado.</p>
  <?php elseif ($isDet): ?>
    <table class="lst">
      <thead><tr><th>Fecha</th><th>Hora</th><th>Tipo</th><th>ODP</th><th>Orden</th><th>Lote</th><th>Sector</th><th>Proceso</th><th>S.Personal</th><th>Planta</th><th class="num">Ingreso</th><th class="num">Egreso</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= h($r['FECHA']) ?></td><td><?= h($r['HORA']) ?></td><td><?= h($r['TIPO']) ?></td>
          <td><?= h($r['ODP']) ?></td><td><?= h($r['ORDEN']) ?></td><td><?= h($r['LOTE']) ?></td>
          <td><?= h($r['SECTOR']) ?></td><td><?= h($r['PROCESO']) ?></td><td><?= h($r['SECTORP']) ?></td><td><?= h($r['PLANTA']) ?></td>
          <td class="num"><?= $r['INGMOV'] ? h(number_format((float)$r['INGMOV'], 0, ',', '.')) : '' ?></td>
          <td class="num"><?= $r['EGRMOV'] ? h(number_format((float)$r['EGRMOV'], 0, ',', '.')) : '' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <table class="lst">
      <thead><tr><th><?= h($gl) ?></th><th class="num">Ingresos</th><th class="num">Egresos</th><th class="num">Neto</th><th class="num">Movimientos</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= h($r['GRUPO']) ?></td>
          <td class="num"><?= h(number_format((float)$r['ING'], 0, ',', '.')) ?></td>
          <td class="num"><?= h(number_format((float)$r['EGR'], 0, ',', '.')) ?></td>
          <td class="num"><?= h(number_format((float)$r['ING'] - (float)$r['EGR'], 0, ',', '.')) ?></td>
          <td class="num"><?= h($r['MOVS']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
  <div class="totg">Ingresos: <?= h(number_format($totIng, 0, ',', '.')) ?> &nbsp;|&nbsp; Egresos: <?= h(number_format($totEgr, 0, ',', '.')) ?> &nbsp;|&nbsp; Neto: <?= h(number_format($totIng - $totEgr, 0, ',', '.')) ?></div>
</div></body></html>
