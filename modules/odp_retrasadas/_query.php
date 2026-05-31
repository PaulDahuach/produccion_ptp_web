<?php
/** Query compartida de Retrasadas (api/print/export). Devuelve las filas según $_GET. */
function retrasadas_rows() {
    $dias = intval($_GET['dias'] ?? 0);
    $w = ['(O.CODETA > 0)', '(O.CODETA < 120)', "(DateDiff('d', O.FDDODP, Date()) > $dias)"];
    $desde = trim($_GET['desde'] ?? ''); $hasta = trim($_GET['hasta'] ?? '');
    if ($desde !== '') $w[] = "(O.FDDODP >= #" . db_esc(fecha_access($desde)) . "#)";
    if ($hasta !== '') $w[] = "(O.FDDODP <= #" . db_esc(fecha_access($hasta)) . "#)";
    $odp = trim($_GET['odp'] ?? ''); if ($odp !== '') $w[] = '(O.NUMODP = ' . intval($odp) . ')';
    $oc = trim($_GET['ocorte'] ?? ''); if ($oc !== '') $w[] = "(O.OCNODP = '" . db_esc($oc) . "')";
    $art = trim($_GET['art'] ?? ''); if ($art !== '') $w[] = "(O.CAXODP = '" . db_esc($art) . "')";
    $cli = trim($_GET['cli'] ?? ''); if ($cli !== '') $w[] = '(O.CODCLI = ' . intval($cli) . ')';
    $mar = trim($_GET['mar'] ?? ''); if ($mar !== '') $w[] = '(O.CODMAR = ' . intval($mar) . ')';
    $pre = trim($_GET['pre'] ?? ''); if ($pre !== '') $w[] = '(O.CODPR1 = ' . intval($pre) . ')';
    $where = implode(' AND ', $w);
    return db_query("SELECT DateDiff('d', O.FDDODP, Date()) AS DIAS_DEF,
              DateDiff('d', O.FDRODP, Date()) AS DIAS_REC,
              E.DENETA AS SECTOR, O.NUMODP AS ODP, C.DENCLI AS CLIENTE, Pre.DENPRE AS PRENDA,
              M.DENMAR AS MARCA, O.OCNODP AS OCORTE, O.CAXODP AS CARTICULO, O.NUMPTP AS PTP, O.CANODP AS CANTIDAD
            FROM (((([Tbl Ordenes De Proceso] AS O
              INNER JOIN [Tbl Etapas] AS E ON O.CODETA = E.CODETA)
              INNER JOIN [Tbl Clientes] AS C ON O.CODCLI = C.CODCLI)
              INNER JOIN [Tbl Marcas] AS M ON O.CODMAR = M.CODMAR)
              LEFT JOIN [Tbl Prendas] AS Pre ON O.CODPR1 = Pre.CODPRE)
            WHERE $where
            ORDER BY DateDiff('d', O.FDDODP, Date()) DESC, O.CODETA;");
}
