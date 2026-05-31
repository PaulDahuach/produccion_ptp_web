<?php
/** Impresión — Órdenes de Proceso Retrasadas (lista por más retrasada). Apaisado. */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_require_login();

require __DIR__ . '/_query.php';
$rows = retrasadas_rows();
$dias = intval($_GET['dias'] ?? 0);
$empresa = sys('tagline', sys('name'));
$tot = 0; foreach ($rows as $r) $tot += (float)$r['CANTIDAD'];
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
<title>Órdenes Retrasadas</title>
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
  <a class="btn btn-sm btn-outline-secondary" href="/produccion_ptp/modules/odp_retrasadas/">Cerrar</a>
</div>
<div class="hoja">
  <div class="titulo">ÓRDENES DE PROCESO RETRASADAS</div>
  <div class="empresa"><?= h($empresa) ?></div>
  <div class="meta">Definidas hace más de <?= h($dias) ?> días — <?= count($rows) ?> orden(es)</div>
  <?php if (!$rows): ?>
    <p class="text-center text-muted">Sin órdenes retrasadas para los filtros.</p>
  <?php else: ?>
    <table class="lst">
      <thead><tr>
        <th class="num">Días Def</th><th class="num">Días Rec</th><th>Sector</th><th>ODP</th><th>Cliente</th>
        <th>Prenda</th><th>Marca</th><th>O.Corte</th><th>Cód.Art.</th><th>PTP</th><th class="num">Cantidad</th>
      </tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td class="num"><?= h($r['DIAS_DEF']) ?></td><td class="num"><?= h($r['DIAS_REC']) ?></td>
          <td><?= h($r['SECTOR']) ?></td><td><?= h($r['ODP']) ?></td><td><?= h($r['CLIENTE']) ?></td>
          <td><?= h($r['PRENDA']) ?></td><td><?= h($r['MARCA']) ?></td><td><?= h($r['OCORTE']) ?></td>
          <td><?= h($r['CARTICULO']) ?></td><td><?= h($r['PTP']) ?></td>
          <td class="num"><?= h(number_format((float)$r['CANTIDAD'], 0, ',', '.')) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div class="totg">TOTAL: <?= h(number_format($tot, 0, ',', '.')) ?> prendas — <?= count($rows) ?> órdenes</div>
  <?php endif; ?>
</div></body></html>
