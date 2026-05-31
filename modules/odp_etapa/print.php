<?php
/**
 * Impresión — Consulta Órdenes de Proceso x Etapa (reproduce "Rpt Consulta Ordenes
 * de Proceso x Etapa"). Una fila por orden, agrupada por etapa con subtotales.
 * Filtros: etapa, desde, hasta, q.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_require_login();

$where = ['(O.CODETA > 0)'];
$etapa = trim($_GET['etapa'] ?? '');
$desde = trim($_GET['desde'] ?? '');
$hasta = trim($_GET['hasta'] ?? '');
$q     = trim($_GET['q'] ?? '');
if ($etapa !== '') $where[] = '(O.CODETA = ' . intval($etapa) . ')';
if ($desde !== '') $where[] = "(O.FDRODP >= #" . db_esc(fecha_access($desde)) . "#)";
if ($hasta !== '') $where[] = "(O.FDRODP <= #" . db_esc(fecha_access($hasta)) . "#)";
if ($q !== '') {
    $e = db_esc($q);
    $where[] = "((O.NUMODP LIKE '%$e%') OR (O.OCNODP LIKE '%$e%') OR (O.CAXODP LIKE '%$e%')"
             . " OR (C.DENCLI LIKE '%$e%') OR (M.DENMAR LIKE '%$e%'))";
}
$sql = "SELECT
  E.DENETA AS ETAPA, O.NUMODP AS ODP, C.DENCLI AS CLIENTE, Pre.DENPRE AS PRENDA,
  M.DENMAR AS MARCA, O.OCNODP AS OCORTE, O.CAXODP AS CARTICULO, O.NUMPTP AS PTP,
  O.CANODP AS CANTIDAD,
  DateDiff('d',O.FDRODP,Date()) AS DIAS_REC,
  DateDiff('d',O.FDDODP,Date()) AS DIAS_DEF, O.CODETA
FROM ((((
   [Tbl Ordenes De Proceso] AS O
   INNER JOIN [Tbl Clientes] AS C ON O.CODCLI = C.CODCLI)
   INNER JOIN [Tbl Marcas] AS M ON O.CODMAR = M.CODMAR)
   INNER JOIN [Tbl Etapas] AS E ON O.CODETA = E.CODETA)
   LEFT JOIN [Tbl Prendas] AS Pre ON O.CODPR1 = Pre.CODPRE)
WHERE " . implode(' AND ', $where) . "
ORDER BY O.CODETA, O.NUMODP;";
$rows = db_query($sql);

$grupos = [];
foreach ($rows as $r) { $s = $r['ETAPA'] ?: '(sin etapa)'; $grupos[$s][] = $r; }
$totGral = 0; foreach ($rows as $r) $totGral += (float)$r['CANTIDAD'];

$empresa = sys('tagline', sys('name'));
$filtros = [];
if ($q !== '')     $filtros[] = 'Búsqueda: "' . $q . '"';
if ($desde !== '') $filtros[] = 'Desde: ' . $desde;
if ($hasta !== '') $filtros[] = 'Hasta: ' . $hasta;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Órdenes de Proceso x Etapa</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { background:#f1f5f9; color:#000; font-size:11px; }
  .hoja { background:#fff; max-width:1180px; margin:1rem auto; padding:1.2rem 1.5rem; box-shadow:0 2px 10px rgba(0,0,0,.15); }
  .titulo { text-align:center; font-size:1.25rem; font-weight:800; letter-spacing:.06em; border-bottom:2px solid #000; padding-bottom:.2rem; }
  .empresa { text-align:center; font-size:.72rem; color:#555; margin-bottom:.2rem; text-transform:uppercase; letter-spacing:.05em; }
  .meta { text-align:center; font-size:.7rem; color:#444; margin-bottom:.6rem; }
  .sector { background:#1e293b; color:#fff; font-weight:700; padding:.25rem .5rem; margin-top:.7rem; font-size:.78rem; text-transform:uppercase; }
  table.lst { width:100%; border-collapse:collapse; }
  table.lst th { background:#e2e8f0; font-size:.62rem; text-transform:uppercase; padding:.2rem .35rem; text-align:left; border-bottom:1px solid #94a3b8; }
  table.lst td { border-bottom:1px solid #ddd; padding:.18rem .35rem; }
  table.lst tr:nth-child(even) td { background:#f8fafc; }
  .subt td { font-weight:700; border-top:1px solid #64748b; background:#eef2f7 !important; }
  .totg { font-weight:800; font-size:.9rem; margin-top:.8rem; text-align:right; border-top:2px solid #000; padding-top:.3rem; }
  .num { text-align:right; }
  .toolbar { max-width:1180px; margin:.5rem auto 0; text-align:right; }
  @media print { body{background:#fff;} .hoja{box-shadow:none; margin:0; max-width:100%;} .toolbar{display:none;} @page{size:landscape; margin:.8cm;} }
</style>
</head>
<body>
<div class="toolbar">
  <button class="btn btn-sm btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
  <a class="btn btn-sm btn-outline-secondary" href="/produccion_ptp/modules/odp_etapa/">Cerrar</a>
</div>
<div class="hoja">
  <div class="titulo">ÓRDENES DE PROCESO POR ETAPA</div>
  <div class="empresa"><?= h($empresa) ?></div>
  <div class="meta"><?= count($rows) ?> orden(es)<?= $filtros ? ' — ' . h(implode('  |  ', $filtros)) : '' ?></div>

  <?php if (!$rows): ?>
    <p class="text-center text-muted">Sin registros para los filtros seleccionados.</p>
  <?php else: foreach ($grupos as $eta => $items): $sub = 0; foreach ($items as $i) $sub += (float)$i['CANTIDAD']; ?>
    <div class="sector"><?= h($eta) ?> — <?= count($items) ?> orden(es)</div>
    <table class="lst">
      <thead><tr>
        <th>ODP</th><th>Cliente</th><th>Marca</th><th>Prenda</th>
        <th>O.Corte</th><th>Cód.Art.</th><th>PTP</th><th class="num">Cant.</th>
        <th class="num">Días Rec.</th><th class="num">Días Def.</th>
      </tr></thead>
      <tbody>
        <?php foreach ($items as $r): ?>
        <tr>
          <td><?= h($r['ODP']) ?></td><td><?= h($r['CLIENTE']) ?></td><td><?= h($r['MARCA']) ?></td>
          <td><?= h($r['PRENDA']) ?></td><td><?= h($r['OCORTE']) ?></td><td><?= h($r['CARTICULO']) ?></td>
          <td><?= h($r['PTP']) ?></td>
          <td class="num"><?= h(number_format((float)$r['CANTIDAD'], 0, ',', '.')) ?></td>
          <td class="num"><?= h($r['DIAS_REC']) ?></td><td class="num"><?= h($r['DIAS_DEF']) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="subt"><td colspan="7" class="num">Subtotal <?= h($eta) ?></td><td class="num"><?= h(number_format($sub, 0, ',', '.')) ?></td><td colspan="2"></td></tr>
      </tbody>
    </table>
  <?php endforeach; ?>
    <div class="totg">TOTAL GENERAL: <?= h(number_format($totGral, 0, ',', '.')) ?> prendas — <?= count($rows) ?> órdenes</div>
  <?php endif; ?>
</div>
</body>
</html>
