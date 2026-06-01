<?php
/**
 * Consulta Órdenes de Proceso x Lote — API (solo lectura).
 * Portado FIEL de `Frm Consulta Ordenes de Proceso x Lote` (+ _Detalle, _Procesos) de
 * Producción PTP. Es la consulta CENTRAL del sistema → master-detail:
 *   - resumen: una fila por SECTOR (Count órdenes + Sum prendas), estilo lstEta.
 *   - detalle: al elegir un sector, sus lotes (rutRequeryDetalle / tmpDetalle).
 *   - procesos: drill-down de los procesos de una orden (Frm ..._Procesos).
 *   - init: combos + rango de fechas por defecto (Rec Control DESFEC/HASFEC).
 *
 * Reglas legacy: L.DSPODP>0, O.CODETA>0 y O.CODETA<>120 (Administración aparte),
 * sector = E.DENETA del lote (L.CSDODP); CODETA=30 se muestra como 'PROGRAMACION'.
 * Filtros: período (FDRODP), NUMODP, OCNODP, CODCLI, CODMAR, CODPR1, CAXODP, e
 * "incluya proceso" (EXISTS sobre OPP.CODPRC).
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_require_login();

$action = (isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : ''));
try {
    switch ($action) {
        case 'init':     init();    break;
        case 'resumen':  resumen(); break;
        case 'detalle':  detalle(); break;
        case 'procesos': procesos(); break;
        // compat: 'list' = detalle plano (sin elegir sector) por si algo viejo lo llama
        case 'list':     detalle(); break;
        default: fail('Acción inválida: ' . $action);
    }
} catch (Exception $e) {
    fail($e->getMessage(), 500);
}

/** Condiciones WHERE comunes (sobre alias O = Tbl Ordenes De Proceso). */
function filtros_where() {
    $w = ['(L.DSPODP > 0)', '(O.CODETA > 0)', '(O.CODETA <> 120)'];
    // Período (obligatorio en el legacy; por defecto Rec Control)
    $desde = trim((isset($_GET['desde']) ? $_GET['desde'] : ''));
    $hasta = trim((isset($_GET['hasta']) ? $_GET['hasta'] : ''));
    if ($desde !== '') $w[] = "(O.FDRODP >= #" . db_esc(fecha_access($desde)) . "#)";
    if ($hasta !== '') $w[] = "(O.FDRODP <= #" . db_esc(fecha_access($hasta)) . "#)";
    // Filtros exactos del legacy
    $odp = trim((isset($_GET['odp']) ? $_GET['odp'] : ''));
    if ($odp !== '') $w[] = '(O.NUMODP = ' . intval($odp) . ')';
    $oc = trim((isset($_GET['ocorte']) ? $_GET['ocorte'] : ''));
    if ($oc !== '') $w[] = "(O.OCNODP = '" . db_esc($oc) . "')";
    $cli = trim((isset($_GET['cli']) ? $_GET['cli'] : ''));
    if ($cli !== '') $w[] = '(O.CODCLI = ' . intval($cli) . ')';
    $mar = trim((isset($_GET['mar']) ? $_GET['mar'] : ''));
    if ($mar !== '') $w[] = '(O.CODMAR = ' . intval($mar) . ')';
    $pre = trim((isset($_GET['pre']) ? $_GET['pre'] : ''));
    if ($pre !== '') $w[] = '(O.CODPR1 = ' . intval($pre) . ')';
    $art = trim((isset($_GET['art']) ? $_GET['art'] : ''));
    if ($art !== '') $w[] = "(O.CAXODP = '" . db_esc($art) . "')";
    // "Órdenes que incluyan el proceso" (cboCodPrc2 → EXISTS)
    $prc = trim((isset($_GET['prc']) ? $_GET['prc'] : ''));
    if ($prc !== '') {
        $w[] = "(EXISTS (SELECT 1 FROM [Tbl Ordenes De Proceso Procesos] AS OPPf "
             . "WHERE OPPf.NUMODP = O.NUMODP AND OPPf.CODPRC = " . intval($prc) . "))";
    }
    return $w;
}

/** Combos + rango de fechas por defecto. */
function init() {
    $rc = db_row("SELECT DESFEC, HASFEC FROM [Rec Control];");
    ok([
        'desde'    => $rc ? to_disp_date($rc['DESFEC']) : '',
        'hasta'    => $rc ? to_disp_date($rc['HASFEC']) : '',
        'clientes' => map_combo(db_query("SELECT CODCLI AS id, DENCLI AS den FROM [Tbl Clientes] ORDER BY DENCLI;")),
        'marcas'   => map_combo(db_query("SELECT CODMAR AS id, DENMAR AS den FROM [Tbl Marcas] ORDER BY DENMAR;")),
        'prendas'  => map_combo(db_query("SELECT CODPRE AS id, DENPRE AS den FROM [Tbl Prendas] ORDER BY DENPRE;")),
        'procesos' => map_combo(db_query("SELECT CODPRC AS id, DENPRC AS den FROM [Tbl Procesos] ORDER BY DENPRC;")),
    ]);
}
function map_combo($rows) {
    $out = [];
    foreach ($rows as $r) $out[] = ['id' => $r['id'], 'den' => $r['den']];
    return $out;
}

/**
 * Resumen por sector (lstEta). Cuenta órdenes y suma prendas por sector del lote.
 * Excluye ADMINISTRACION del listado principal (se ve con el botón aparte) y la
 * devuelve por separado para el contador del botón.
 */
function resumen() {
    $where = implode(' AND ', filtros_where());
    // CODETA=30 → 'PROGRAMACION'. ACE no soporta Count(DISTINCT); se replica con
    // subconsulta agregada por orden (cuenta órdenes distintas + suma prendas por sector).
    $sql = "SELECT T.SECTOR, T.COD, Count(T.NUMODP) AS ORDENES, Int(Sum(T.PRD)) AS PRENDAS
            FROM (SELECT IIF(O.CODETA=30,'PROGRAMACION',E.DENETA) AS SECTOR, E.CODETA AS COD,
                    O.NUMODP, Sum(L.DSPODP) AS PRD
                  FROM (((([Tbl Ordenes De Proceso] AS O
                    INNER JOIN [Tbl Ordenes De Proceso Lotes] AS L ON O.NUMODP = L.NUMODP)
                    INNER JOIN [Tbl Etapas] AS E ON L.CSDODP = E.CODETA)
                    INNER JOIN [Tbl Clientes] AS C ON O.CODCLI = C.CODCLI)
                    INNER JOIN [Tbl Marcas] AS M ON O.CODMAR = M.CODMAR)
                  WHERE $where
                  GROUP BY IIF(O.CODETA=30,'PROGRAMACION',E.DENETA), E.CODETA, O.NUMODP) AS T
            GROUP BY T.SECTOR, T.COD
            ORDER BY T.COD;";
    $rows = db_query($sql);
    $sectores = [];
    $admin = null;
    $totOrd = 0; $totPrd = 0;
    foreach ($rows as $r) {
        if (strcasecmp((string)$r['SECTOR'], 'ADMINISTRACION') === 0) { $admin = $r; continue; }
        $sectores[] = $r;
        $totOrd += (int)$r['ORDENES'];
        $totPrd += (int)$r['PRENDAS'];
    }
    ok(['sectores' => $sectores, 'admin' => $admin, 'tot_ordenes' => $totOrd, 'tot_prendas' => $totPrd]);
}

/** Detalle de lotes de un sector (rutRequeryDetalle + tmpDetalle). */
function detalle() {
    $sector = trim((isset($_GET['sector']) ? $_GET['sector'] : ''));
    if ($sector === '') { ok([]); return; }
    $where = implode(' AND ', filtros_where());
    // CODETA=30 se muestra como PROGRAMACION → el sector pedido puede venir así.
    $secExpr = "IIF(O.CODETA=30,'PROGRAMACION',E.DENETA)";
    $where .= " AND ($secExpr = '" . db_esc($sector) . "')";

    $sql = "SELECT $secExpr AS SECTOR, O.NUMODP AS ODP, C.DENCLI AS CLIENTE, Pre.DENPRE AS PRENDA,
              M.DENMAR AS MARCA, O.OCNODP AS OCORTE, O.CAXODP AS CARTICULO, O.NUMPTP AS PTP,
              Sum(L.DSPODP) AS CANTIDAD,
              DateDiff('d',O.FDRODP,Date()) AS DIAS_REC,
              DateDiff('d',O.FDDODP,Date()) AS DIAS_DEF,
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
            ORDER BY O.NUMODP;";
    $rows = db_query($sql);

    // OBS: '*' si la orden tiene algún lote con observación (batch, barato).
    if ($rows) {
        $ids = [];
        foreach ($rows as $r) $ids[(int)$r['ODP']] = true;
        $lista = implode(',', array_keys($ids));
        $obs = [];
        if ($lista !== '') {
            foreach (db_query("SELECT NUMODP FROM [Tbl Ordenes De Proceso Lotes]
                               WHERE OBSODP Is Not Null AND NUMODP IN ($lista)
                               GROUP BY NUMODP;") as $o) $obs[(int)$o['NUMODP']] = true;
        }
        foreach ($rows as &$r) $r['OBS'] = isset($obs[(int)$r['ODP']]) ? '*' : '';
        unset($r);
    }
    ok($rows);
}

/** Procesos de una orden (drill-down cmdPrc → Frm ..._Procesos). */
function procesos() {
    $odp = intval((isset($_GET['odp']) ? $_GET['odp'] : 0));
    if ($odp <= 0) { fail('Falta la orden'); return; }
    $rows = db_query("SELECT OPP.ORDODP AS ORDEN, Prc.DENPRC AS PROCESO, E.DENETA AS SECTOR,
                        OPP.CANODP AS CANTIDAD, OPP.DSPODP AS PENDIENTE, OPP.OBSODP AS OBS
                      FROM (([Tbl Ordenes De Proceso Procesos] AS OPP
                        LEFT JOIN [Tbl Procesos] AS Prc ON OPP.CODPRC = Prc.CODPRC)
                        LEFT JOIN [Tbl Etapas] AS E ON Prc.CODETA = E.CODETA)
                      WHERE OPP.NUMODP = $odp ORDER BY OPP.ORDODP;");
    $cab = db_row("SELECT O.NUMODP, C.DENCLI, M.DENMAR, O.CANODP
                   FROM (([Tbl Ordenes De Proceso] AS O
                     LEFT JOIN [Tbl Clientes] AS C ON O.CODCLI=C.CODCLI)
                     LEFT JOIN [Tbl Marcas] AS M ON O.CODMAR=M.CODMAR)
                   WHERE O.NUMODP=$odp;");
    ok(['cabecera' => $cab, 'procesos' => $rows]);
}
