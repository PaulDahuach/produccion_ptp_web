<?php
/**
 * Impresión — Consulta Órdenes de Proceso x Sector (reproduce "Rpt Consulta Ordenes
 * de Proceso x Sector"). Una fila por proceso (OPP) del sector. Requiere ?etapa=CODETA.
 * Filtro adicional: q. Ordenado por programa / orden de programa.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_require_login();

$etapa = trim((isset($_GET['etapa']) ? $_GET['etapa'] : ''));
$q     = trim((isset($_GET['q']) ? $_GET['q'] : ''));
$rows = [];
$nomSector = '';
if ($etapa !== '') {
    $rowSec = db_row("SELECT DENETA FROM [Tbl Etapas] WHERE CODETA = " . intval($etapa) . ";");
    $nomSector = (isset($rowSec['DENETA']) ? $rowSec['DENETA'] : '');
    $where = ['(O.CODETA = ' . intval($etapa) . ')'];
    if ($q !== '') {
        $e = db_esc($q);
        $where[] = "((OPP.NUMODP LIKE '%$e%') OR (M.DENMAR LIKE '%$e%') OR (Prc.DENPRC LIKE '%$e%'))";
    }
    $sql = "SELECT
      OPP.FPGODP AS PROGRAMA, OPP.OPGODP AS ORDENP, OPP.NUMODP AS ODP,
      M.DENMAR AS MARCA, O.NUMPTP AS PTP, Prc.DENPRC AS PROCESO,
      O.CANODP AS CANTIDAD, OPP.DSPODP AS PENDIENTE, O.FDDODP AS DEFINICION,
      DateDiff('d',O.FDRODP,Date()) AS DIAS_REC,
      DateDiff('d',O.FDDODP,Date()) AS DIAS_DEF
    FROM ((([Tbl Ordenes De Proceso] AS O
       INNER JOIN [Tbl Ordenes De Proceso Procesos] AS OPP ON (O.NUMODP = OPP.NUMODP) AND (O.ORDODP = OPP.ORDODP))
       INNER JOIN [Tbl Procesos] AS Prc ON OPP.CODPRC = Prc.CODPRC)
       INNER JOIN [Tbl Marcas] AS M ON O.CODMAR = M.CODMAR)
    WHERE " . implode(' AND ', $where) . "
    ORDER BY OPP.FPGODP, OPP.OPGODP;";
    $rows = db_query($sql);
    foreach ($rows as &$r) $r['DEFINICION'] = to_disp_date($r['DEFINICION']);
    unset($r);
}
$totPend = 0; foreach ($rows as $r) $totPend += (float)$r['PENDIENTE'];
$empresa = sys('tagline', sys('name'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Órdenes de Proceso x Sector</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { background:#f1f5f9; color:#000; font-size:11px; }
  .hoja { background:#fff; max-width:1180px; margin:1rem auto; padding:1.2rem 1.5rem; box-shadow:0 2px 10px rgba(0,0,0,.15); }
  .titulo { text-align:center; font-size:1.25rem; font-weight:800; letter-spacing:.06em; border-bottom:2px solid #000; padding-bottom:.2rem; }
  .empresa { text-align:center; font-size:.72rem; color:#555; margin-bottom:.2rem; text-transform:uppercase; letter-spacing:.05em; }
  .meta { text-align:center; font-size:.78rem; color:#222; font-weight:600; margin-bottom:.6rem; }
  table.lst { width:100%; border-collapse:collapse; margin-top:.3rem; }
  table.lst th { background:#1e293b; color:#fff; font-size:.62rem; text-transform:uppercase; padding:.25rem .35rem; text-align:left; }
  table.lst td { border-bottom:1px solid #ddd; padding:.18rem .35rem; }
  table.lst tr:nth-child(even) td { background:#f8fafc; }
  .totg { font-weight:800; font-size:.9rem; margin-top:.8rem; text-align:right; border-top:2px solid #000; padding-top:.3rem; }
  .num { text-align:right; }
  .toolbar { max-width:1180px; margin:.5rem auto 0; text-align:right; }
  @media print { body{background:#fff;} .hoja{box-shadow:none; margin:0; max-width:100%;} .toolbar{display:none;} @page{size:landscape; margin:.8cm;} }
</style>
</head>
<body>
<div class="toolbar">
  <button class="btn btn-sm btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
  <a class="btn btn-sm btn-outline-secondary" href="/produccion_ptp/modules/odp_sector/">Cerrar</a>
</div>
<div class="hoja">
  <div class="titulo">ÓRDENES DE PROCESO POR SECTOR</div>
  <div class="empresa"><?= h($empresa) ?></div>
  <div class="meta"><?= $etapa === '' ? 'Seleccione un sector en la consulta antes de imprimir.' : 'Sector: ' . h($nomSector) . ' — ' . count($rows) . ' proceso(s)' . ($q !== '' ? '  |  Búsqueda: "' . h($q) . '"' : '') ?></div>

  <?php if ($etapa === '' || !$rows): ?>
    <p class="text-center text-muted"><?= $etapa === '' ? 'Sin sector seleccionado.' : 'Sin procesos para los filtros seleccionados.' ?></p>
  <?php else: ?>
    <table class="lst">
      <thead><tr>
        <th class="num">Prog.</th><th class="num">Ord.P</th><th>ODP</th><th>Marca</th><th>PTP</th><th>Proceso</th>
        <th class="num">Cant.</th><th class="num">Pendiente</th><th>Definición</th>
        <th class="num">Días Rec.</th><th class="num">Días Def.</th>
      </tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td class="num"><?= h($r['PROGRAMA']) ?></td><td class="num"><?= h($r['ORDENP']) ?></td>
          <td><?= h($r['ODP']) ?></td><td><?= h($r['MARCA']) ?></td><td><?= h($r['PTP']) ?></td>
          <td><?= h($r['PROCESO']) ?></td>
          <td class="num"><?= h(number_format((float)$r['CANTIDAD'], 0, ',', '.')) ?></td>
          <td class="num"><?= h(number_format((float)$r['PENDIENTE'], 0, ',', '.')) ?></td>
          <td><?= h($r['DEFINICION']) ?></td>
          <td class="num"><?= h($r['DIAS_REC']) ?></td><td class="num"><?= h($r['DIAS_DEF']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div class="totg">TOTAL PENDIENTE: <?= h(number_format($totPend, 0, ',', '.')) ?> prendas — <?= count($rows) ?> procesos</div>
  <?php endif; ?>
</div>
</body>
</html>
