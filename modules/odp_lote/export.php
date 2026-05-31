<?php
/**
 * Exportación a Excel — Consulta Órdenes de Proceso x Lote (reproduce cmdExl, que
 * exporta la "sql Exportacion Ordenes de Proceso x Lote" del sector elegido). Genera
 * un .xls (HTML-table que Excel abre nativamente) con los mismos filtros que la vista.
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
if ($sector === '') { http_response_code(400); echo 'Seleccione un sector.'; exit; }

$secExpr = "IIF(O.CODETA=30,'PROGRAMACION',E.DENETA)";
$where = implode(' AND ', filtros_where()) . " AND ($secExpr = '" . db_esc($sector) . "')";
$rows = db_query("SELECT $secExpr AS SECTOR, O.NUMODP AS ODP, C.DENCLI AS CLIENTE, Pre.DENPRE AS PRENDA,
          M.DENMAR AS MARCA, O.OCNODP AS OCORTE, O.CAXODP AS CARTICULO, O.NUMPTP AS PTP,
          Sum(L.DSPODP) AS CANTIDAD,
          DateDiff('d',O.FDRODP,Date()) AS DIAS_REC, DateDiff('d',O.FDDODP,Date()) AS DIAS_DEF,
          Min(L.ORDODP) AS ORDEN
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

$fname = preg_replace('/[^A-Za-z0-9_-]/', '_', $sector) . '_' . date('Y-m-d') . '.xls';
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $fname . '"');
echo "\xEF\xBB\xBF"; // BOM para que Excel lea UTF-8
$cols = ['ODP' => 'ODP N°', 'CLIENTE' => 'Cliente', 'PRENDA' => 'Prenda', 'MARCA' => 'Marca',
         'OCORTE' => 'O. Corte', 'CARTICULO' => 'C. Artículo', 'PTP' => 'PTP N°', 'CANTIDAD' => 'Cantidad',
         'DIAS_REC' => 'Días Rec', 'DIAS_DEF' => 'Días Def', 'ORDEN' => 'Orden'];
echo '<table border="1"><tr><th colspan="' . count($cols) . '">Órdenes de Proceso x Lote — ' . h($sector) . ' (' . date('d/m/Y') . ')</th></tr><tr>';
foreach ($cols as $lbl) echo '<th>' . h($lbl) . '</th>';
echo '</tr>';
$tot = 0;
foreach ($rows as $r) {
    echo '<tr>';
    foreach ($cols as $k => $lbl) echo '<td>' . h($r[$k]) . '</td>';
    echo '</tr>';
    $tot += (float)$r['CANTIDAD'];
}
echo '<tr><th colspan="7" style="text-align:right">TOTAL</th><th>' . h(number_format($tot, 0, ',', '.')) . '</th><th colspan="3"></th></tr>';
echo '</table>';
