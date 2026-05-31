<?php
/**
 * Impresión — Consulta Órdenes de Proceso x Lote (reproduce "Rpt Consulta Ordenes de
 * Proceso x Lote", cmdImp). Imprime el DETALLE del sector elegido con los mismos filtros
 * que la vista, incluyendo PRÓXIMO SECTOR y OBS (como SQL_BASE del legacy). Apaisado.
 * Params: sector (requerido) + desde/hasta/odp/ocorte/art/cli/mar/pre/prc.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_require_login();

function filtros_where() {
    $w = ['(L.DSPODP > 0)', '(O.CODETA > 0)', '(O.CODETA <> 120)'];
    $desde = trim($_GET['desde'] ?? ''); $hasta = trim($_GET['hasta'] ?? '');
    if ($desde !== '') $w[] = "(O.FDRODP >= #" . db_esc(fecha_access($desde)) . "#)";
    if ($hasta !== '') $w[] = "(O.FDRODP <= #" . db_esc(fecha_access($hasta)) . "#)";
    $odp = trim($_GET['odp'] ?? ''); if ($odp !== '') $w[] = '(O.NUMODP = ' . intval($odp) . ')';
    $oc = trim($_GET['ocorte'] ?? ''); if ($oc !== '') $w[] = "(O.OCNODP = '" . db_esc($oc) . "')";
    $cli = trim($_GET['cli'] ?? ''); if ($cli !== '') $w[] = '(O.CODCLI = ' . intval($cli) . ')';
    $mar = trim($_GET['mar'] ?? ''); if ($mar !== '') $w[] = '(O.CODMAR = ' . intval($mar) . ')';
    $pre = trim($_GET['pre'] ?? ''); if ($pre !== '') $w[] = '(O.CODPR1 = ' . intval($pre) . ')';
    $art = trim($_GET['art'] ?? ''); if ($art !== '') $w[] = "(O.CAXODP = '" . db_esc($art) . "')";
    $prc = trim($_GET['prc'] ?? '');
    if ($prc !== '') $w[] = "(EXISTS (SELECT 1 FROM [Tbl Ordenes De Proceso Procesos] AS OPPf WHERE OPPf.NUMODP = O.NUMODP AND OPPf.CODPRC = " . intval($prc) . "))";
    return $w;
}

$sector = trim($_GET['sector'] ?? '');
$rows = [];
if ($sector !== '') {
    $secExpr = "IIF(O.CODETA=30,'PROGRAMACION',E.DENETA)";
    $where = implode(' AND ', filtros_where()) . " AND ($secExpr = '" . db_esc($sector) . "')";
    $rows = db_query("SELECT $secExpr AS SECTOR, O.NUMODP AS ODP, C.DENCLI AS CLIENTE, Pre.DENPRE AS PRENDA,
              M.DENMAR AS MARCA, O.OCNODP AS OCORTE, O.CAXODP AS CARTICULO, O.NUMPTP AS PTP,
              Sum(L.DSPODP) AS CANTIDAD,
              DateDiff('d',O.FDRODP,Date()) AS DIAS_REC, DateDiff('d',O.FDDODP,Date()) AS DIAS_DEF,
              Min(L.ORDODP) AS ORDEN, E.CODETA AS COD_SECTOR
            FROM (((([Tbl Ordenes De Proceso] AS O
              INNER JOIN [Tbl Ordenes De Proceso Lotes] AS L ON O.NUMODP = L.NUMODP)
              INNER JOIN [Tbl Etapas] AS E ON L.CSDODP = E.CODETA)
              INNER JOIN [Tbl Clientes] AS C ON O.CODCLI = C.CODCLI)
              INNER JOIN [Tbl Marcas] AS M ON O.CODMAR = M.CODMAR)
              LEFT JOIN [Tbl Prendas] AS Pre ON O.CODPR1 = Pre.CODPRE
            WHERE $where
            GROUP BY $secExpr, O.NUMODP, C.DENCLI, Pre.DENPRE, M.DENMAR, O.OCNODP, O.CAXODP, O.NUMPTP,
                     O.FDRODP, O.FDDODP, E.CODETA
            ORDER BY O.NUMODP;");
    // PRÓXIMO SECTOR + OBS por orden (como SQL_BASE). Cache para no repetir consultas.
    $cacheProx = []; $cacheObs = [];
    foreach ($rows as &$r) {
        $odp = (int)$r['ODP']; $cod = (int)$r['COD_SECTOR']; $ord = (int)$r['ORDEN'];
        $kProx = $odp . ':' . $cod . ':' . $ord;
        if (!array_key_exists($kProx, $cacheProx)) {
            $px = db_row("SELECT TOP 1 S2.DENETA AS PROX
                          FROM (([Tbl Ordenes De Proceso Procesos] AS OPP2
                            INNER JOIN [Tbl Procesos] AS P2 ON OPP2.CODPRC = P2.CODPRC)
                            INNER JOIN [Tbl Etapas] AS S2 ON P2.CODETA = S2.CODETA)
                          WHERE OPP2.NUMODP = $odp AND P2.CODETA <> $cod AND OPP2.ORDODP > $ord
                          ORDER BY OPP2.ORDODP;");
            $cacheProx[$kProx] = $px ? $px['PROX'] : '';
        }
        if (!array_key_exists($odp, $cacheObs)) {
            $o = db_row("SELECT TOP 1 1 AS X FROM [Tbl Ordenes De Proceso Lotes] WHERE NUMODP = $odp AND OBSODP Is Not Null;");
            $cacheObs[$odp] = $o ? '*' : '';
        }
        $r['PROXIMO'] = $cacheProx[$kProx];
        $r['OBS'] = $cacheObs[$odp];
    }
    unset($r);
}
$tot = 0; foreach ($rows as $r) $tot += (float)$r['CANTIDAD'];
$empresa = sys('tagline', sys('name'));
$filtros = [];
foreach (['desde' => 'Desde', 'hasta' => 'Hasta', 'odp' => 'ODP', 'ocorte' => 'O.Corte', 'art' => 'C.Art'] as $k => $lbl) {
    $v = trim($_GET[$k] ?? ''); if ($v !== '') $filtros[] = "$lbl: $v";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Órdenes de Proceso x Lote<?= $sector !== '' ? ' — ' . h($sector) : '' ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { background:#f1f5f9; color:#000; font-size:11px; }
  .hoja { background:#fff; max-width:1180px; margin:1rem auto; padding:1.2rem 1.5rem; box-shadow:0 2px 10px rgba(0,0,0,.15); }
  .titulo { text-align:center; font-size:1.25rem; font-weight:800; letter-spacing:.06em; border-bottom:2px solid #000; padding-bottom:.2rem; }
  .empresa { text-align:center; font-size:.72rem; color:#555; text-transform:uppercase; letter-spacing:.05em; }
  .sector { background:#1e293b; color:#fff; font-weight:700; padding:.3rem .6rem; margin:.6rem 0 .3rem; font-size:.85rem; text-transform:uppercase; }
  .meta { text-align:center; font-size:.7rem; color:#444; margin-bottom:.3rem; }
  table.lst { width:100%; border-collapse:collapse; }
  table.lst th { background:#e2e8f0; font-size:.62rem; text-transform:uppercase; padding:.2rem .35rem; text-align:left; border-bottom:1px solid #94a3b8; }
  table.lst td { border-bottom:1px solid #ddd; padding:.18rem .35rem; }
  table.lst tr:nth-child(even) td { background:#f8fafc; }
  .totg { font-weight:800; font-size:.9rem; margin-top:.8rem; text-align:right; border-top:2px solid #000; padding-top:.3rem; }
  .num { text-align:right; } .ctr { text-align:center; }
  .toolbar { max-width:1180px; margin:.5rem auto 0; text-align:right; }
  @media print { body{background:#fff;} .hoja{box-shadow:none; margin:0; max-width:100%;} .toolbar{display:none;} @page{size:landscape; margin:.8cm;} }
</style>
</head>
<body>
<div class="toolbar">
  <button class="btn btn-sm btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
  <a class="btn btn-sm btn-outline-secondary" href="/produccion_ptp/modules/odp_lote/">Cerrar</a>
</div>
<div class="hoja">
  <div class="titulo">ÓRDENES DE PROCESO POR LOTE</div>
  <div class="empresa"><?= h($empresa) ?></div>
  <div class="sector"><?= $sector !== '' ? h($sector) : 'Sin sector seleccionado' ?></div>
  <div class="meta"><?= count($rows) ?> orden(es)<?= $filtros ? ' — ' . h(implode('  |  ', $filtros)) : '' ?></div>

  <?php if ($sector === ''): ?>
    <p class="text-center text-muted">Seleccione un sector en la consulta antes de imprimir.</p>
  <?php elseif (!$rows): ?>
    <p class="text-center text-muted">Sin órdenes para los filtros seleccionados.</p>
  <?php else: ?>
    <table class="lst">
      <thead><tr>
        <th>ODP</th><th>Cliente</th><th>Prenda</th><th>Marca</th>
        <th>O.Corte</th><th>Cód.Art.</th><th>PTP</th><th class="num">Cant.</th>
        <th class="num">Días Rec.</th><th class="num">Días Def.</th><th class="num">Orden</th>
        <th>Próx. Sector</th><th class="ctr">Obs</th>
      </tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= h($r['ODP']) ?></td><td><?= h($r['CLIENTE']) ?></td><td><?= h($r['PRENDA']) ?></td>
          <td><?= h($r['MARCA']) ?></td><td><?= h($r['OCORTE']) ?></td><td><?= h($r['CARTICULO']) ?></td>
          <td><?= h($r['PTP']) ?></td>
          <td class="num"><?= h(number_format((float)$r['CANTIDAD'], 0, ',', '.')) ?></td>
          <td class="num"><?= h($r['DIAS_REC']) ?></td><td class="num"><?= h($r['DIAS_DEF']) ?></td>
          <td class="num"><?= h($r['ORDEN']) ?></td>
          <td><?= h($r['PROXIMO']) ?></td><td class="ctr"><?= h($r['OBS']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div class="totg">TOTAL: <?= h(number_format($tot, 0, ',', '.')) ?> prendas — <?= count($rows) ?> órdenes</div>
  <?php endif; ?>
</div>
</body>
</html>
