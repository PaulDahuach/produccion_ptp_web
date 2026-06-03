<?php
/**
 * Órdenes de Muestra — Alta / Modificación (transaccional). Portado de `Frm Ordenes De
 * Muestra` (SetData A/M/B). Crea la muestra y AUTO-CREA su PTP (la ruta de procesos),
 * escribiendo los procesos en AMBAS tablas (Ordenes De Muestra Procesos + PTP Procesos),
 * tal como el legacy (así "Cargar PTP" en Definición funciona). De esta muestra deriva
 * luego el Presupuesto PTP.
 *   Alta "A": NUMODM=next_number('ULTODM'); NUMPTP=next_number('ULTPTP') + inserta Tbl PTP;
 *             inserta cabecera ODM + procesos (ODM + PTP) + prendas.
 *   Modif "M": reescribe cabecera, actualiza el PTP, reemplaza procesos (ambas) y prendas.
 *   Baja "B": CODEDM=3 (ANULADA).
 * Catálogos: Origen=Tbl Origenes De Muestra, Acción=Tbl Acciones De PTP, Estado=Tbl Estados
 * De Muestra, Propiedad prototipo=Tbl Propiedades De Prototipo. Prototipos (PREODM) se omiten.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_require_login();

$action = (isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : ''));
try {
    switch ($action) {
        case 'init':           initData(); break;
        case 'marcas_cliente': marcasCliente(); break;
        case 'list':           listar(); break;
        case 'get':            obtener(); break;
        case 'guardar':        guardar(); break;
        case 'entregar':       entregar(); break;
        case 'anular':         anular(); break;
        default: fail('Acción inválida: ' . $action);
    }
} catch (Exception $e) {
    fail($e->getMessage(), 500);
}

function lk($t, $pk, $den, $where = '') { return db_query("SELECT $pk AS id, $den AS den FROM [$t] $where ORDER BY $den;"); }
function sqlInt($v) { $v = trim((string) $v); return $v === '' ? 'Null' : (string) intval($v); }
function sqlDec($v) { $v = trim((string) $v); return $v === '' ? 'Null' : (string) (float) str_replace(',', '.', $v); }
function sqlTxt($v) { $v = trim((string) $v); return $v === '' ? 'Null' : "'" . db_esc($v) . "'"; }

function initData() {
    ok([
        'readonly'    => db_readonly(),
        'clientes'    => lk('Tbl Clientes', 'CODCLI', 'DENCLI'),
        'marcas'      => lk('Tbl Marcas', 'CODMAR', 'DENMAR'),   // prototipos: marca libre (no por cliente)
        'procesos'    => db_query("SELECT P.CODPRC AS id, P.DENPRC AS den, E.DENETA AS sector
                                   FROM [Tbl Procesos] AS P LEFT JOIN [Tbl Etapas] AS E ON P.CODETA = E.CODETA
                                   ORDER BY P.DENPRC;"),
        'colores'     => lk('Tbl Colores De Proceso', 'CODCDP', 'DENCDP'),
        'prendas'     => lk('Tbl Prendas', 'CODPRE', 'DENPRE'),
        'telas'       => lk('Tbl Telas', 'CODTEL', 'DENTEL'),
        'estados'     => lk('Tbl Estados De Muestra', 'CODEDM', 'DENEDM'),
        'origenes'    => lk('Tbl Origenes De Muestra', 'CODODM', 'DENODM'),
        'acciones'    => lk('Tbl Acciones De PTP', 'CODADP', 'DENADP'),
        'propiedades' => lk('Tbl Propiedades De Prototipo', 'CODPDP', 'DENPDP'),
        'fechaDisp'   => date('d/m/Y'),
    ]);
}

function marcasCliente() {
    $cli = intval((isset($_GET['cli']) ? $_GET['cli'] : 0));
    ok(db_query("SELECT M.CODMAR AS id, M.DENMAR AS den
                 FROM [Tbl Clientes Marcas] AS CM INNER JOIN [Tbl Marcas] AS M ON CM.CODMAR = M.CODMAR
                 WHERE CM.CODCLI = $cli ORDER BY M.DENMAR;"));
}

function listar() {
    $w = ['(O.CODEDM NOT IN (3,5))'];
    if (!empty($_GET['pend'])) $w[] = '(O.CODEDM = 1)';   // modo Confirmación: solo pendientes
    if (!empty($_GET['conf'])) $w[] = '(O.CODEDM = 2)';   // modo Entrega: solo confirmadas
    $q = trim((isset($_GET['q']) ? $_GET['q'] : ''));
    if ($q !== '') {
        $e = db_esc($q);
        $w[] = "((O.NUMODM LIKE '%$e%') OR (C.DENCLI LIKE '%$e%') OR (M.DENMAR LIKE '%$e%') OR (O.NUMPTP LIKE '%$e%'))";
    }
    $where = 'WHERE ' . implode(' AND ', $w);
    $rows = db_query("SELECT TOP 500 O.NUMODM AS ODM, O.FDEODM, C.DENCLI AS CLIENTE, M.DENMAR AS MARCA, O.CANODM AS CANT, O.NUMPTP AS PTP
                      FROM (([Tbl Ordenes De Muestra] AS O
                        LEFT JOIN [Tbl Clientes] AS C ON O.CODCLI = C.CODCLI)
                        LEFT JOIN [Tbl Marcas] AS M ON O.CODMAR = M.CODMAR)
                      $where ORDER BY O.NUMODM DESC;");
    foreach ($rows as &$r) $r['FDEODM'] = to_disp_date($r['FDEODM']);
    ok($rows);
}

function obtener() {
    $id = intval((isset($_GET['id']) ? $_GET['id'] : 0));
    $h = db_row("SELECT O.*, P.DENPTP, E.DENEDM FROM (([Tbl Ordenes De Muestra] AS O
                   LEFT JOIN [Tbl PTP] AS P ON O.NUMPTP = P.NUMPTP)
                   LEFT JOIN [Tbl Estados De Muestra] AS E ON O.CODEDM = E.CODEDM)
                 WHERE O.NUMODM = $id;");
    if (!$h) { fail('Orden de Muestra no encontrada'); return; }
    $h['FDEODM'] = to_disp_date($h['FDEODM']);
    $procs = db_query("SELECT PP.ORDODM, PP.CODPRC, Prc.DENPRC, E.DENETA AS SECTOR, PP.CODCDP, CP.DENCDP, PP.PORODM, PP.OBSODM
                       FROM ((([Tbl Ordenes De Muestra Procesos] AS PP
                         LEFT JOIN [Tbl Procesos] AS Prc ON PP.CODPRC = Prc.CODPRC)
                         LEFT JOIN [Tbl Etapas] AS E ON Prc.CODETA = E.CODETA)
                         LEFT JOIN [Tbl Colores De Proceso] AS CP ON PP.CODCDP = CP.CODCDP)
                       WHERE PP.NUMODM = $id ORDER BY PP.ORDODM;");
    $prendas = db_query("SELECT PR.ORDODM, PR.CODPRE, Pre.DENPRE, PR.CODTEL, Tl.DENTEL
                         FROM (([Tbl Ordenes De Muestra Prendas] AS PR
                           LEFT JOIN [Tbl Prendas] AS Pre ON PR.CODPRE = Pre.CODPRE)
                           LEFT JOIN [Tbl Telas] AS Tl ON PR.CODTEL = Tl.CODTEL)
                         WHERE PR.NUMODM = $id ORDER BY PR.ORDODM;");
    $prototipos = db_query("SELECT PT.ORDODM, PT.CODMAR, M.DENMAR, PT.PREODM
                            FROM ([Tbl Ordenes de Muestra Prototipos] AS PT
                              LEFT JOIN [Tbl Marcas] AS M ON PT.CODMAR = M.CODMAR)
                            WHERE PT.NUMODM = $id ORDER BY PT.ORDODM;");
    ok(['cabecera' => $h, 'procesos' => $procs, 'prendas' => $prendas, 'prototipos' => $prototipos]);
}

function guardar() {
    if (db_readonly()) { fail('Sistema en modo solo lectura'); return; }
    $id   = intval((isset($_POST['NUMODM']) ? $_POST['NUMODM'] : 0));   // 0 = alta
    $cli  = intval((isset($_POST['CODCLI']) ? $_POST['CODCLI'] : 0));
    $mar  = intval((isset($_POST['CODMAR']) ? $_POST['CODMAR'] : 0));
    $fec  = trim((isset($_POST['FDEODM']) ? $_POST['FDEODM'] : ''));
    $est  = intval((isset($_POST['CODEDM']) ? $_POST['CODEDM'] : 1));
    $can  = trim((isset($_POST['CANODM']) ? $_POST['CANODM'] : ''));
    $ori  = intval((isset($_POST['CODODM']) ? $_POST['CODODM'] : 1));   // origen
    $acc  = intval((isset($_POST['CODADP']) ? $_POST['CODADP'] : 1));   // acción
    $prop = intval((isset($_POST['CODPDP']) ? $_POST['CODPDP'] : 0));   // propiedad prototipo (opcional)
    $den  = trim((isset($_POST['DENPTP']) ? $_POST['DENPTP'] : ''));
    $obs  = trim((isset($_POST['OBSODM']) ? $_POST['OBSODM'] : ''));
    $cmx  = trim((isset($_POST['CMXODM']) ? $_POST['CMXODM'] : ''));    // código muestra
    $cpx  = trim((isset($_POST['CPXODM']) ? $_POST['CPXODM'] : ''));    // cantidad prototipo
    $rem  = trim((isset($_POST['REMODM']) ? $_POST['REMODM'] : ''));    // remito
    $aoc  = trim((isset($_POST['AOCODM']) ? $_POST['AOCODM'] : ''));    // adelanto OC
    $nop  = trim((isset($_POST['NOPODM']) ? $_POST['NOPODM'] : ''));    // OP N°
    $procs = json_decode((isset($_POST['__procesos']) ? $_POST['__procesos'] : '[]'), true); if (!is_array($procs)) $procs = [];
    $procs = array_values(array_filter($procs, function ($p) { return intval((isset($p['CODPRC']) ? $p['CODPRC'] : 0)) > 0; }));
    $prendas = json_decode((isset($_POST['__prendas']) ? $_POST['__prendas'] : '[]'), true); if (!is_array($prendas)) $prendas = [];
    $prendas = array_values(array_filter($prendas, function ($p) { return intval((isset($p['CODPRE']) ? $p['CODPRE'] : 0)) > 0; }));
    $prots = json_decode((isset($_POST['__prototipos']) ? $_POST['__prototipos'] : '[]'), true); if (!is_array($prots)) $prots = [];
    $prots = array_values(array_filter($prots, function ($p) { return intval((isset($p['CODMAR']) ? $p['CODMAR'] : 0)) > 0; }));

    $conf = !empty($_POST['__confirmar']);              // modo Confirmación (Pendiente → Confirmada)
    if ($conf && $id <= 0) { fail('La confirmación requiere una muestra existente'); return; }
    if ($conf) $est = 2;                                // CONFIRMADA
    if ($cli <= 0) { fail('Elegí un cliente'); return; }
    if ($mar <= 0) { fail('Elegí una marca'); return; }
    if (!$procs)   { fail('Cargá al menos un proceso'); return; }
    $fecSql = '#' . db_esc(fecha_access($fec !== '' ? $fec : date('d/m/Y'))) . '#';
    $hoySql = '#' . db_esc(fecha_access(date('d/m/Y'))) . '#';
    $confSet = $conf ? ", FDCODM=$hoySql" : '';        // fecha de confirmación
    $uid = intval((isset($_SESSION['uid']) ? $_SESSION['uid'] : 0));

    db_begin();
    try {
        if ($id <= 0) {
            // ALTA: nueva ODM + nuevo PTP
            $id = next_number('ULTODM');
            $ptp = next_number('ULTPTP');
            db_exec("INSERT INTO [Tbl PTP] ([NUMPTP],[FDEPTP],[CODEDP],[DENPTP],[CNFPTP],[DISPTP],[CODCLI],[CODMAR])
                     VALUES ($ptp, $fecSql, 1, " . sqlTxt($den !== '' ? $den : ('PTP' . $ptp)) . ", True, False, $cli, $mar);");
            db_exec("INSERT INTO [Tbl Ordenes De Muestra]
                       ([NUMODM],[FDEODM],[CODEDM],[CODCLI],[CODMAR],[CMXODM],[CANODM],[CODODM],[CODADP],[CODPDP],[CPXODM],[REMODM],[AOCODM],[NOPODM],[NUMPTP],[OBSODM],[NUIODM],[NMIODM],[NOWODM])
                     VALUES ($id, $fecSql, $est, $cli, $mar, " . sqlTxt($cmx) . ", " . sqlDec($can) . ", $ori, $acc, " . ($prop > 0 ? $prop : 'Null') . ",
                       " . sqlDec($cpx) . ", " . sqlTxt($rem) . ", " . sqlTxt($aoc) . ", " . sqlTxt($nop) . ", $ptp, " . sqlTxt($obs) . ", $uid, 0, Now());");
        } else {
            // MODIF: mantiene NUMODM y su NUMPTP
            $ptpRow = db_row("SELECT NUMPTP FROM [Tbl Ordenes De Muestra] WHERE NUMODM=$id;");
            $ptp = (int) ((isset($ptpRow['NUMPTP']) ? $ptpRow['NUMPTP'] : 0));
            db_exec("UPDATE [Tbl Ordenes De Muestra] SET FDEODM=$fecSql, CODEDM=$est, CODCLI=$cli, CODMAR=$mar,
                       CMXODM=" . sqlTxt($cmx) . ", CANODM=" . sqlDec($can) . ", CODODM=$ori, CODADP=$acc, CODPDP=" . ($prop > 0 ? $prop : 'Null') . ",
                       CPXODM=" . sqlDec($cpx) . ", REMODM=" . sqlTxt($rem) . ", AOCODM=" . sqlTxt($aoc) . ", NOPODM=" . sqlTxt($nop) . ",
                       OBSODM=" . sqlTxt($obs) . "$confSet WHERE NUMODM=$id;");
            if ($ptp > 0) {
                db_exec("UPDATE [Tbl PTP] SET FDEPTP=$fecSql, DENPTP=" . sqlTxt($den) . ", CODCLI=$cli, CODMAR=$mar WHERE NUMPTP=$ptp;");
                db_exec("DELETE FROM [Tbl PTP Procesos] WHERE NUMPTP=$ptp;");
            }
            db_exec("DELETE FROM [Tbl Ordenes De Muestra Procesos] WHERE NUMODM=$id;");
            db_exec("DELETE FROM [Tbl Ordenes De Muestra Prendas] WHERE NUMODM=$id;");
            db_exec("DELETE FROM [Tbl Ordenes de Muestra Prototipos] WHERE NUMODM=$id;");
        }

        // Procesos → ODM Procesos + PTP Procesos (idénticos, como el legacy)
        $ord = 0;
        foreach ($procs as $p) {
            $ord++;
            $cp  = sqlInt((isset($p['CODCDP']) ? $p['CODCDP'] : ''));
            $por = sqlDec((isset($p['PORODM']) ? $p['PORODM'] : ''));
            $ob  = sqlTxt((isset($p['OBSODM']) ? $p['OBSODM'] : ''));
            $cpr = (string) intval($p['CODPRC']);
            db_exec("INSERT INTO [Tbl Ordenes De Muestra Procesos] ([NUMODM],[ORDODM],[CODPRC],[CODCDP],[PORODM],[OBSODM])
                     VALUES ($id, $ord, $cpr, $cp, $por, $ob);");
            if (!empty($ptp))
                db_exec("INSERT INTO [Tbl PTP Procesos] ([NUMPTP],[ORDPTP],[CODPRC],[CODCDP],[PORPTP],[OBSPTP])
                         VALUES ($ptp, $ord, $cpr, $cp, $por, $ob);");
        }
        // Prendas → ODM Prendas
        $ord = 0;
        foreach ($prendas as $pr) {
            $ord++;
            db_exec("INSERT INTO [Tbl Ordenes De Muestra Prendas] ([NUMODM],[ORDODM],[CODPRE],[CODTEL])
                     VALUES ($id, $ord, " . (string) intval($pr['CODPRE']) . ", " . sqlInt((isset($pr['CODTEL']) ? $pr['CODTEL'] : '')) . ");");
        }
        // Prototipos → ODM Prototipos (marca + precinto)
        $ord = 0;
        foreach ($prots as $pt) {
            $ord++;
            db_exec("INSERT INTO [Tbl Ordenes de Muestra Prototipos] ([NUMODM],[ORDODM],[CODMAR],[PREODM])
                     VALUES ($id, $ord, " . (string) intval($pt['CODMAR']) . ", " . sqlTxt((isset($pt['PREODM']) ? $pt['PREODM'] : '')) . ");");
        }
        db_commit();
    } catch (Exception $e) {
        db_rollback();
        fail('No se pudo guardar: ' . $e->getMessage());
        return;
    }
    ok(['numodm' => $id, 'numptp' => (isset($ptp) ? $ptp : null), 'procesos' => count($procs), 'prendas' => count($prendas)]);
}

/** Entrega (remito) — Confirmada (2) → Remitida (4) cuando se completa. Soporta parciales.
 *  Portado de Frm Entrega Ordenes De Muestra (mismo circuito que odm_ciclo). */
function entregar() {
    if (db_readonly()) { fail('Sistema en modo solo lectura'); return; }
    $id = intval((isset($_POST['__id']) ? $_POST['__id'] : 0));
    $cant = (float) str_replace(',', '.', (isset($_POST['cant']) ? $_POST['cant'] : '0'));
    $o = db_row("SELECT CANODM, CRMODM, CODEDM FROM [Tbl Ordenes De Muestra] WHERE NUMODM=$id;");
    if (!$o) { fail('Muestra no encontrada'); return; }
    if ((int) $o['CODEDM'] !== 2) { fail('La muestra no está Confirmada (no se puede entregar)'); return; }
    $can = (float) $o['CANODM']; $rem = (float) ((isset($o['CRMODM']) ? $o['CRMODM'] : 0)); $pend = $can - $rem;
    if ($cant <= 0) { fail('Cantidad a remitir inválida'); return; }
    if ($cant > $pend) { fail('La cantidad supera lo pendiente (' . rtrim(rtrim(number_format($pend, 2, '.', ''), '0'), '.') . ')'); return; }
    $hoy = '#' . db_esc(fecha_access(date('d/m/Y'))) . '#';
    $uid = intval((isset($_SESSION['uid']) ? $_SESSION['uid'] : 0));

    db_begin();
    try {
        $mx = db_row("SELECT Max(ORDODM) AS M FROM [Tbl Ordenes De Muestra Remitos] WHERE NUMODM=$id;");
        $ord = (int) ((isset($mx['M']) ? $mx['M'] : 0)) + 1;
        $cantSql = ($cant == (int) $cant ? (string) (int) $cant : (string) $cant);
        db_exec("INSERT INTO [Tbl Ordenes De Muestra Remitos] ([NUMODM],[ORDODM],[FDRODM],[CANODM],[NUIODM],[NMIODM],[NOWODM])
                 VALUES ($id, $ord, $hoy, $cantSql, $uid, 0, Now());");
        $nuevoRem = $rem + $cant;
        $setEstado = ($nuevoRem >= $can) ? ', CODEDM=4' : '';
        db_exec("UPDATE [Tbl Ordenes De Muestra] SET CRMODM=" . ($nuevoRem == (int) $nuevoRem ? (string) (int) $nuevoRem : (string) $nuevoRem) . "$setEstado WHERE NUMODM=$id;");
        db_commit();
    } catch (Exception $e) {
        db_rollback();
        fail('No se pudo entregar: ' . $e->getMessage());
        return;
    }
    ok(['numodm' => $id, 'ordodm' => $ord, 'remitido' => $rem + $cant, 'completa' => ($rem + $cant >= $can)]);
}

function anular() {
    if (db_readonly()) { fail('Sistema en modo solo lectura'); return; }
    $id = intval((isset($_POST['__id']) ? $_POST['__id'] : 0));
    if ($id <= 0) { fail('Falta la orden de muestra'); return; }
    db_exec("UPDATE [Tbl Ordenes De Muestra] SET CODEDM=3 WHERE NUMODM=$id;");
    ok(['numodm' => $id]);
}
