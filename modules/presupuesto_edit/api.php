<?php
/**
 * Presupuestos PTP — Alta / Modificación (transaccional, comercial). Portado de
 * `Frm Presupuestos PTP` (+ _Procesos). Un presupuesto SIEMPRE deriva de una Orden de
 * Muestra (NUMODM): de ella se toman cliente, prenda y la ruta de procesos; el precio
 * sugerido inicial de cada proceso = Tbl Procesos.NETPRC.
 *
 * Fórmula (VALIDADA contra datos reales, p.ej. presupuesto 23409):
 *   por línea: PDL=NETPRC ; SUG (editable, inicial=PDL)
 *              IBP = SUG × PDPPPP/100   (PDPPPP = % pronto pago, cabecera)
 *              PRE = SUG − IBP
 *              IBX = PRE × PBXPPP/100   (PBXPPP = % bonif. extra; por defecto = PDCPPP comercial)
 *              NET = PRE − IBX
 *   cabecera: NT0=ΣSUG ; IDP=ΣIBP ; NT1=ΣPRE ; IDC=ΣIBX ; TOT = NT1 − IDC = ΣNET
 * El servidor RECALCULA todo desde SUG/PBX/PDP (no confía en lo que manda el cliente).
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_require_login();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
try {
    switch ($action) {
        case 'init':       initData(); break;
        case 'buscar_odm': buscarOdm(); break;
        case 'cargar_odm': cargarOdm(); break;
        case 'list':       listar(); break;
        case 'get':        obtener(); break;
        case 'guardar':    guardar(); break;
        case 'anular':     anular(); break;
        default: fail('Acción inválida: ' . $action);
    }
} catch (Exception $e) {
    fail($e->getMessage(), 500);
}

function r4($v) { return round((float) $v, 4); }

function initData() { ok(['readonly' => db_readonly(), 'fechaDisp' => date('d/m/Y')]); }

/** ODM candidatas para presupuestar (no anuladas). */
function buscarOdm() {
    $w = ['(O.CODEDM NOT IN (3,5))', '(O.NUMPTP > 0)'];
    $q = trim($_GET['q'] ?? '');
    if ($q !== '') { $e = db_esc($q); $w[] = "((O.NUMODM LIKE '%$e%') OR (C.DENCLI LIKE '%$e%') OR (M.DENMAR LIKE '%$e%') OR (O.NUMPTP LIKE '%$e%'))"; }
    $where = 'WHERE ' . implode(' AND ', $w);
    $rows = db_query("SELECT TOP 300 O.NUMODM AS ODM, O.FDEODM, C.DENCLI AS CLIENTE, M.DENMAR AS MARCA, O.NUMPTP AS PTP
                      FROM (([Tbl Ordenes De Muestra] AS O
                        LEFT JOIN [Tbl Clientes] AS C ON O.CODCLI=C.CODCLI)
                        LEFT JOIN [Tbl Marcas] AS M ON O.CODMAR=M.CODMAR)
                      $where ORDER BY O.NUMODM DESC;");
    foreach ($rows as &$r) $r['FDEODM'] = to_disp_date($r['FDEODM']);
    ok($rows);
}

/** Carga la cabecera + líneas (con SUG=NETPRC) a partir de una Orden de Muestra. */
function cargarOdm() {
    $numodm = intval($_GET['numodm'] ?? 0);
    $o = db_row("SELECT O.NUMODM, O.NUMPTP, O.CODCLI, C.DENCLI, M.DENMAR
                 FROM (([Tbl Ordenes De Muestra] AS O
                   LEFT JOIN [Tbl Clientes] AS C ON O.CODCLI=C.CODCLI)
                   LEFT JOIN [Tbl Marcas] AS M ON O.CODMAR=M.CODMAR)
                 WHERE O.NUMODM=$numodm;");
    if (!$o) { fail('Orden de Muestra no encontrada'); return; }
    // Prenda de la muestra (ORDODM=1)
    $pr = db_row("SELECT PR.CODPRE, Pre.DENPRE FROM [Tbl Ordenes De Muestra Prendas] AS PR
                  LEFT JOIN [Tbl Prendas] AS Pre ON PR.CODPRE=Pre.CODPRE
                  WHERE PR.NUMODM=$numodm AND PR.ORDODM=1;");
    // Líneas = procesos de la muestra + NETPRC del proceso
    $lineas = db_query("SELECT OMP.ORDODM AS ORD, OMP.CODPRC, P.DENPRC, P.NETPRC, OMP.OBSODM AS OBS
                        FROM [Tbl Ordenes De Muestra Procesos] AS OMP
                          INNER JOIN [Tbl Procesos] AS P ON OMP.CODPRC=P.CODPRC
                        WHERE OMP.NUMODM=$numodm ORDER BY OMP.ORDODM;");
    foreach ($lineas as &$l) { $l['NETPRC'] = r4($l['NETPRC']); }
    ok([
        'NUMODM' => $o['NUMODM'], 'NUMPTP' => $o['NUMPTP'], 'CODCLI' => $o['CODCLI'],
        'DENCLI' => $o['DENCLI'], 'DENMAR' => $o['DENMAR'],
        'CODPRE' => $pr ? $pr['CODPRE'] : null, 'DENPRE' => $pr ? $pr['DENPRE'] : null,
        'lineas' => $lineas,
    ]);
}

/** Presupuestos existentes (para Buscar). */
function listar() {
    $w = ['(P.ANUPPP=False OR P.ANUPPP Is Null)'];
    $q = trim($_GET['q'] ?? '');
    if ($q !== '') { $e = db_esc($q); $w[] = "((P.NUMPPP LIKE '%$e%') OR (C.DENCLI LIKE '%$e%') OR (P.NUMPTP LIKE '%$e%'))"; }
    $where = 'WHERE ' . implode(' AND ', $w);
    $rows = db_query("SELECT TOP 500 P.NUMPPP AS PPP, P.FEXPPP, C.DENCLI AS CLIENTE, P.NUMPTP AS PTP, P.TOTPPP AS TOTAL
                      FROM [Tbl Presupuestos PTP] AS P LEFT JOIN [Tbl Clientes] AS C ON P.CODCLI=C.CODCLI
                      $where ORDER BY P.NUMPPP DESC;");
    foreach ($rows as &$r) $r['FEXPPP'] = to_disp_date($r['FEXPPP']);
    ok($rows);
}

/** Presupuesto existente para editar. */
function obtener() {
    $id = intval($_GET['id'] ?? 0);
    $h = db_row("SELECT P.NUMPPP, P.FEXPPP, P.NUMPTP, P.NUMODM, P.CODCLI, P.CODPRE, P.PDPPPP, P.PDCPPP,
                   P.NT0PPP, P.IDPPPP, P.NT1PPP, P.IDCPPP, P.TOTPPP, P.OBSPPP, C.DENCLI, Pre.DENPRE
                 FROM (([Tbl Presupuestos PTP] AS P
                   LEFT JOIN [Tbl Clientes] AS C ON P.CODCLI=C.CODCLI)
                   LEFT JOIN [Tbl Prendas] AS Pre ON P.CODPRE=Pre.CODPRE)
                 WHERE P.NUMPPP=$id;");
    if (!$h) { fail('Presupuesto no encontrado'); return; }
    $h['FEXPPP'] = to_disp_date($h['FEXPPP']);
    $lineas = db_query("SELECT PP.ORDPPP AS ORD, PP.CODPRC, Pr.DENPRC, PP.PDLPPP, PP.SUGPPP, PP.IBPPPP,
                          PP.PREPPP, PP.PBXPPP, PP.IBXPPP, PP.NETPPP, PP.OBSPPP AS OBS
                        FROM [Tbl Presupuestos PTP Procesos] AS PP
                          LEFT JOIN [Tbl Procesos] AS Pr ON PP.CODPRC=Pr.CODPRC
                        WHERE PP.NUMPPP=$id ORDER BY PP.ORDPPP;");
    ok(['cabecera' => $h, 'lineas' => $lineas]);
}

function guardar() {
    if (db_readonly()) { fail('Sistema en modo solo lectura'); return; }
    $id     = intval($_POST['NUMPPP'] ?? 0);   // 0 = alta
    $numodm = intval($_POST['NUMODM'] ?? 0);
    $numptp = intval($_POST['NUMPTP'] ?? 0);
    $cli    = intval($_POST['CODCLI'] ?? 0);
    $pre    = trim($_POST['CODPRE'] ?? '');
    $fec    = trim($_POST['FEXPPP'] ?? '');
    $pdp    = (float) str_replace(',', '.', $_POST['PDPPPP'] ?? '0');
    $pdc    = (float) str_replace(',', '.', $_POST['PDCPPP'] ?? '0');
    $obs    = trim($_POST['OBSPPP'] ?? '');
    $lineas = json_decode($_POST['__lineas'] ?? '[]', true); if (!is_array($lineas)) $lineas = [];
    $lineas = array_values(array_filter($lineas, function ($l) { return intval($l['CODPRC'] ?? 0) > 0; }));

    if ($cli <= 0)   { fail('Falta el cliente (cargá una Orden de Muestra)'); return; }
    if (!$lineas)    { fail('No hay procesos para presupuestar'); return; }
    $fecSql = '#' . db_esc(fecha_access($fec !== '' ? $fec : date('d/m/Y'))) . '#';
    $uid = intval($_SESSION['uid'] ?? 0);

    // Recalcular TODO en el servidor (autoritativo)
    $nt0 = 0; $idp = 0; $nt1 = 0; $idc = 0; $tot = 0;
    $calc = [];
    foreach ($lineas as $l) {
        $sug = (float) str_replace(',', '.', $l['SUG'] ?? '0');
        $pbx = isset($l['PBX']) && trim((string) $l['PBX']) !== '' ? (float) str_replace(',', '.', $l['PBX']) : $pdc;
        $ibp = r4($sug * $pdp / 100);
        $prec = r4($sug - $ibp);
        $ibx = r4($prec * $pbx / 100);
        $net = r4($prec - $ibx);
        $nt0 += $sug; $idp += $ibp; $nt1 += $prec; $idc += $ibx; $tot += $net;
        $calc[] = ['CODPRC' => intval($l['CODPRC']), 'PDL' => r4($l['PDL'] ?? $sug), 'SUG' => r4($sug),
                   'IBP' => $ibp, 'PRE' => $prec, 'PBX' => r4($pbx), 'IBX' => $ibx, 'NET' => $net,
                   'OBS' => trim((string) ($l['OBS'] ?? ''))];
    }
    $nt0 = r4($nt0); $idp = r4($idp); $nt1 = r4($nt1); $idc = r4($idc); $tot = r4($tot);

    db_begin();
    try {
        if ($id <= 0) {
            $id = next_number('ULTPPP');
            db_exec("INSERT INTO [Tbl Presupuestos PTP]
                       ([NUMPPP],[FEXPPP],[NUMPTP],[NUMODM],[CODCLI],[CODPRE],[PDPPPP],[IDPPPP],[NT0PPP],[NT1PPP],[PDCPPP],[IDCPPP],[TOTPPP],[OBSPPP],[ANUPPP],[NUIPPP],[NMIPPP],[NOWPPP])
                     VALUES ($id, $fecSql, " . ($numptp ?: 'Null') . ", " . ($numodm ?: 'Null') . ", $cli, " . ($pre !== '' ? intval($pre) : 'Null') . ",
                       $pdp, $idp, $nt0, $nt1, $pdc, $idc, $tot, " . sqlTxtP($obs) . ", False, $uid, 0, Now());");
        } else {
            db_exec("UPDATE [Tbl Presupuestos PTP] SET FEXPPP=$fecSql, PDPPPP=$pdp, IDPPPP=$idp, NT0PPP=$nt0, NT1PPP=$nt1,
                       PDCPPP=$pdc, IDCPPP=$idc, TOTPPP=$tot, OBSPPP=" . sqlTxtP($obs) . ", CODPRE=" . ($pre !== '' ? intval($pre) : 'Null') . " WHERE NUMPPP=$id;");
            db_exec("DELETE FROM [Tbl Presupuestos PTP Procesos] WHERE NUMPPP=$id;");
        }
        $ord = 0;
        foreach ($calc as $c) {
            $ord++;
            $cols = ['NUMPPP','ORDPPP','CODPRC','PDLPPP','SUGPPP','IBPPPP','PREPPP','PBXPPP','IBXPPP','NETPPP','OBSPPP'];
            $vals = [(string)$id,(string)$ord,(string)$c['CODPRC'],(string)$c['PDL'],(string)$c['SUG'],(string)$c['IBP'],
                     (string)$c['PRE'],(string)$c['PBX'],(string)$c['IBX'],(string)$c['NET'], sqlTxtP($c['OBS'])];
            db_exec("INSERT INTO [Tbl Presupuestos PTP Procesos] ([" . implode('],[', $cols) . "]) VALUES (" . implode(',', $vals) . ");");
        }
        db_commit();
    } catch (Exception $e) {
        db_rollback();
        fail('No se pudo guardar: ' . $e->getMessage());
        return;
    }
    ok(['numppp' => $id, 'lineas' => count($calc), 'total' => $tot]);
}

function sqlTxtP($v) { $v = trim((string) $v); return $v === '' ? 'Null' : "'" . db_esc($v) . "'"; }

function anular() {
    if (db_readonly()) { fail('Sistema en modo solo lectura'); return; }
    $id = intval($_POST['__id'] ?? 0);
    if ($id <= 0) { fail('Falta el presupuesto'); return; }
    db_exec("UPDATE [Tbl Presupuestos PTP] SET ANUPPP=True WHERE NUMPPP=$id;");
    ok(['numppp' => $id]);
}
