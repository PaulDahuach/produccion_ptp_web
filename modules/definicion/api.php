<?php
/**
 * Definición de Órdenes de Proceso — API.
 * Portado de: Frm Definicion (SetData acción "M").
 * Toma una orden recibida (CODETA=20), define su ruta de procesos y la avanza a
 * CODETA=30, generando: OPP (Ordenes De Proceso Procesos), OEP (Ordenes En Proceso),
 * código de barras, y el lote hacia Programación. Todo en transacción.
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
        case 'tela_colores':   telaColores(); break;
        case 'list':           listar(); break;
        case 'get':            obtener(); break;
        case 'ptp_procesos':   ptpProcesos(); break;
        case 'definir':        definir(); break;
        default: fail('Acción inválida: ' . $action);
    }
} catch (Exception $e) {
    fail($e->getMessage(), 500);
}

function lk($tabla, $pk, $den) {
    return db_query("SELECT [$pk] AS id, [$den] AS den FROM [$tabla] ORDER BY [$den];");
}

function initData() {
    $rc = db_row("SELECT FECAPE FROM [Rec Control];");
    ok([
        'fecha'      => to_iso_date($rc['FECAPE']),
        'fechaDisp'  => to_disp_date($rc['FECAPE']),
        // Cabecera (editable en Definición, como en Access)
        'acciones'   => lk('Tbl Acciones De ODP', 'CODADO', 'DENADO'),
        'destinos'   => lk('Tbl Destinos', 'CODDST', 'DENDST'),
        'clientes'   => lk('Tbl Clientes', 'CODCLI', 'DENCLI'),
        'talleres'   => lk('Tbl Talleres', 'CODTAL', 'DENTAL'),
        'prendas'    => lk('Tbl Prendas', 'CODPRE', 'DENPRE'),
        'telas'      => lk('Tbl Telas', 'CODTEL', 'DENTEL'),
        'colores'    => lk('Tbl Colores Tela', 'CODCT1', 'DENCT1'),   // Color de tela
        'cuerpos'    => lk('Tbl Cuerpos Tela', 'CODCT2', 'DENCT2'),   // Cuerpo de tela
        // Grilla de procesos
        'procesos'   => db_query("SELECT P.CODPRC AS id, P.DENPRC AS den, E.DENETA AS sector
                                  FROM [Tbl Procesos] AS P LEFT JOIN [Tbl Etapas] AS E ON P.CODETA = E.CODETA
                                  ORDER BY P.DENPRC;"),
        'coloresProc'=> lk('Tbl Colores De Proceso', 'CODCDP', 'DENCDP'),
        'readonly'   => db_readonly(),
    ]);
}

/** Marcas habilitadas para un cliente. */
function marcasCliente() {
    $cli = intval((isset($_GET['cli']) ? $_GET['cli'] : 0));
    ok(db_query("SELECT M.CODMAR AS id, M.DENMAR AS den
                 FROM [Tbl Clientes Marcas] AS CM INNER JOIN [Tbl Marcas] AS M ON CM.CODMAR = M.CODMAR
                 WHERE CM.CODCLI = $cli ORDER BY M.DENMAR;"));
}

/** Colores por defecto de una tela (CODTEL_AfterUpdate). */
function telaColores() {
    $tel = intval((isset($_GET['tel']) ? $_GET['tel'] : 0));
    $r = db_row("SELECT CODCT1, CODCT2 FROM [Tbl Telas] WHERE CODTEL = $tel;");
    ok($r ?: ['CODCT1' => null, 'CODCT2' => null]);
}

/** Órdenes pendientes de definición (CODETA=20). */
function listar() {
    $sql = "SELECT O.NUMODP AS ODP, O.FDRODP, C.DENCLI AS CLIENTE, M.DENMAR AS MARCA,
              Pre.DENPRE AS PRENDA, O.CANODP AS CANTIDAD, O.REMODP AS REMITO
            FROM ((([Tbl Ordenes De Proceso] AS O
              LEFT JOIN [Tbl Clientes] AS C ON O.CODCLI=C.CODCLI)
              LEFT JOIN [Tbl Marcas] AS M ON O.CODMAR=M.CODMAR)
              LEFT JOIN [Tbl Prendas] AS Pre ON O.CODPR1=Pre.CODPRE)
            WHERE O.CODETA = 20 ORDER BY O.NUMODP DESC;";
    $rows = db_query($sql);
    foreach ($rows as &$r) $r['FDRODP'] = to_disp_date($r['FDRODP']);
    ok($rows);
}

/** Orden recibida (datos completos, editables en Definición). */
function obtener() {
    $id = intval((isset($_GET['id']) ? $_GET['id'] : 0));
    $row = db_row("SELECT * FROM [Tbl Ordenes De Proceso] WHERE NUMODP = $id;");
    if (!$row) { fail('Orden no encontrada'); return; }
    if (intval($row['CODETA']) !== 20) { fail('La orden no está pendiente de definición (CODETA=' . $row['CODETA'] . ')'); return; }

    // Nombres display (lookups baratos)
    $eta = db_row("SELECT DENETA FROM [Tbl Etapas] WHERE CODETA = " . intval($row['CODETA']) . ";");
    $row['DENETA'] = $eta ? $eta['DENETA'] : '';
    if (intval((isset($row['NUMPTP']) ? $row['NUMPTP'] : 0)) > 0) {
        $ptp = db_row("SELECT DENPTP FROM [Tbl PTP] WHERE NUMPTP = " . intval($row['NUMPTP']) . ";");
        $row['DENPTP'] = $ptp ? $ptp['DENPTP'] : '';
    } else { $row['DENPTP'] = ''; }
    if (intval((isset($row['NUMPPP']) ? $row['NUMPPP'] : 0)) > 0) {
        $pp = db_row("SELECT FEXPPP FROM [Tbl Presupuestos PTP] WHERE NUMPPP = " . intval($row['NUMPPP']) . ";");
        $row['FEXPPP_disp'] = ($pp && $pp['FEXPPP'] !== null && $pp['FEXPPP'] !== '') ? to_disp_date($pp['FEXPPP']) : '';
    } else { $row['FEXPPP_disp'] = ''; }
    ok($row);
}

/** Procesos de un PTP (para "Cargar del PTP"). */
function ptpProcesos() {
    $ptp = intval((isset($_GET['ptp']) ? $_GET['ptp'] : 0));
    $sql = "SELECT PP.ORDPTP, PP.CODPRC, P.DENPRC, PP.CODCDP, CP.DENCDP, PP.PORPTP, PP.OBSPTP
            FROM (([Tbl PTP Procesos] AS PP
              LEFT JOIN [Tbl Procesos] AS P ON PP.CODPRC=P.CODPRC)
              LEFT JOIN [Tbl Colores De Proceso] AS CP ON PP.CODCDP=CP.CODCDP)
            WHERE PP.NUMPTP = $ptp ORDER BY PP.ORDPTP;";
    ok(db_query($sql));
}

/** Dígito verificador Módulo 10 (mdlModulo10 del legacy) para el código de barras. */
function modulo10($s) {
    $imp = 0; $n = strlen($s);
    for ($i = 1; $i <= $n; $i += 2)       $imp += (int) $s[$i - 1];
    $imp *= 3;
    $par = 0;
    for ($i = 2; $i <= $n - 1; $i += 2)   $par += (int) $s[$i - 1];
    return ($imp + $par) % 10;
}

function sqlInt($v) { $v = trim((string) $v); return $v === '' ? 'Null' : (string) intval($v); }
function sqlDec($v) { $v = trim((string) $v); return $v === '' ? '0' : (string) (float) str_replace(',', '.', $v); }
function sqlTxt($v) { $v = trim((string) $v); return $v === '' ? 'Null' : "'" . db_esc($v) . "'"; }

/** Define la orden: avanza a CODETA=30 + procesos + OEP + lote (transacción). */
function definir() {
    if (db_readonly()) { fail('Sistema en modo solo-lectura', 403); return; }
    $id = intval((isset($_POST['__id']) ? $_POST['__id'] : 0));
    if ($id <= 0) { fail('Falta la orden'); return; }

    $o = db_row("SELECT CODETA FROM [Tbl Ordenes De Proceso] WHERE NUMODP = $id;");
    if (!$o) { fail('Orden no encontrada'); return; }
    if (intval($o['CODETA']) !== 20) { fail('La orden no está pendiente de definición'); return; }

    // Cabecera editable (paridad con Frm Definicion): requeridos
    $req = ['REMODP' => 'Remito', 'CODCLI' => 'Cliente', 'CODMAR' => 'Marca', 'CODTAL' => 'Taller',
            'CODPR1' => 'Prenda', 'CANODP' => 'Cantidad', 'PESODP' => 'Peso'];
    foreach ($req as $k => $lbl) {
        if (trim((string) ((isset($_POST[$k]) ? $_POST[$k] : ''))) === '') { fail("Falta: $lbl"); return; }
    }
    $cant = intval($_POST['CANODP']);
    if ($cant <= 0) { fail('Cantidad inválida'); return; }

    $procs = json_decode((isset($_POST['__procesos']) ? $_POST['__procesos'] : '[]'), true);
    if (!is_array($procs)) $procs = [];
    $procs = array_values(array_filter($procs, function ($p) { return intval((isset($p['CODPRC']) ? $p['CODPRC'] : 0)) > 0; }));
    if (!count($procs)) { fail('Definí al menos un proceso para la orden'); return; }

    $rc = db_row("SELECT FECAPE FROM [Rec Control];");
    $iso = to_iso_date($rc['FECAPE']);
    $p = explode('-', $iso);
    $fdd = "#{$p[1]}/{$p[2]}/{$p[0]}#";
    $uid = intval((isset($_SESSION['uid']) ? $_SESSION['uid'] : 0));
    $cdo = function_exists('mt_rand') ? mt_rand(0, 39) : 0;
    $bar = 'OP' . str_pad((string) $id, 8, '0', STR_PAD_LEFT);
    $bar .= modulo10(str_pad((string) $id, 8, '0', STR_PAD_LEFT));
    $o20 = sqlTxt((isset($_POST['O20ODP']) ? $_POST['O20ODP'] : ''));
    $numptp = sqlInt((isset($_POST['NUMPTP']) ? $_POST['NUMPTP'] : ''));
    $numppp = sqlInt((isset($_POST['NUMPPP']) ? $_POST['NUMPPP'] : ''));

    // Cabecera editable
    $codado = intval((isset($_POST['CODADO']) ? $_POST['CODADO'] : 1)) ?: 1;
    $repodp = ($codado === 1) ? 'Null' : sqlInt((isset($_POST['REPODP']) ? $_POST['REPODP'] : ''));
    $coddst = sqlInt((isset($_POST['CODDST']) ? $_POST['CODDST'] : ''));  if ($coddst === 'Null') $coddst = '1';
    $prt    = (!empty($_POST['PRTODP']) && $_POST['PRTODP'] !== '0') ? 'True' : 'False';
    $remodp = sqlTxt($_POST['REMODP']);
    $codcli = sqlInt($_POST['CODCLI']);   $codmar = sqlInt($_POST['CODMAR']);   $codtal = sqlInt($_POST['CODTAL']);
    $ocnodp = sqlTxt((isset($_POST['OCNODP']) ? $_POST['OCNODP'] : ''));
    $caxodp = sqlTxt((isset($_POST['CAXODP']) ? $_POST['CAXODP'] : ''));
    $codpr1 = sqlInt($_POST['CODPR1']);   $pesodp = sqlDec($_POST['PESODP']);
    $preodp = sqlTxt((isset($_POST['PREODP']) ? $_POST['PREODP'] : ''));
    $codpr2 = sqlInt((isset($_POST['CODPR2']) ? $_POST['CODPR2'] : ''));
    $codtel = sqlInt((isset($_POST['CODTEL']) ? $_POST['CODTEL'] : ''));
    $codct1 = sqlInt((isset($_POST['CODCT1']) ? $_POST['CODCT1'] : ''));
    $codct2 = sqlInt((isset($_POST['CODCT2']) ? $_POST['CODCT2'] : ''));

    db_begin();
    try {
        // 1) Avanzar la cabecera a Definición (CODETA=30) — incluye la cabecera editable
        db_exec("UPDATE [Tbl Ordenes De Proceso] SET
                    CODETA=30, FDDODP=$fdd, FUPODP=$fdd, BARODP='" . db_esc($bar) . "', CODCDO=$cdo,
                    ORDODP=1, DEFODP=0, PRGODP=$cant, O20ODP=$o20, CODDST=$coddst,
                    CODADO=$codado, REPODP=$repodp, REMODP=$remodp, CODCLI=$codcli, CODMAR=$codmar,
                    CODTAL=$codtal, OCNODP=$ocnodp, CAXODP=$caxodp, CODPR1=$codpr1, CANODP=$cant, PESODP=$pesodp,
                    PRTODP=$prt, PREODP=$preodp, NUMPTP=$numptp, NUMPPP=$numppp,
                    CODPR2=$codpr2, CODTEL=$codtel, CODCT1=$codct1, CODCT2=$codct2,
                    NUIODP=$uid, NMIODP=0, NOWODP=Now()
                 WHERE NUMODP=$id;");

        // 2) Insertar procesos (OPP) + ordenes en proceso (OEP), en orden
        $ord = 0;
        foreach ($procs as $pr) {
            $ord++;
            $cp  = sqlInt((isset($pr['CODCDP']) ? $pr['CODCDP'] : ''));
            $por = (trim((string) ((isset($pr['PORODP']) ? $pr['PORODP'] : ''))) === '') ? 'Null' : sqlDec($pr['PORODP']);
            $obs = sqlTxt((isset($pr['OBSODP']) ? $pr['OBSODP'] : ''));
            $cols = ['NUMODP', 'ORDODP', 'CODPRC', 'CODCDP', 'PORODP', 'OBSODP'];
            $vals = [(string) $id, (string) $ord, (string) intval($pr['CODPRC']), $cp, $por, $obs];
            if ($ord === 1) { $cols[] = 'FEXODP'; $vals[] = $fdd; $cols[] = 'CANODP'; $vals[] = (string) $cant; }
            db_exec("INSERT INTO [Tbl Ordenes De Proceso Procesos] ([" . implode('],[', $cols) . "]) VALUES (" . implode(',', $vals) . ");");

            $oep = next_number('ULTOEP');
            db_exec("INSERT INTO [Tbl Ordenes En Proceso] ([NUMOEP],[NUMODP],[CODPRC],[ORDPTP],[WPXODP])
                     VALUES ($oep, $id, " . intval($pr['CODPRC']) . ", $ord, False);");
        }

        // 3) Lotes: descomprometer el de recepción y crear el de definición → programación
        db_exec("UPDATE [Tbl Ordenes De Proceso Lotes] SET DSPODP=0 WHERE NUMODP=$id AND ORDODP=-2 AND LOTODP=1;");
        $lc = ['NUMODP', 'ORDODP', 'LOTODP', 'FEXODP', 'FIPODP', 'HIPODP', 'FFPODP', 'HFPODP',
               'CIPODP', 'CANODP', 'REZODP', 'DSPODP', 'OBSODP', 'CSDODP', 'OPOODP', 'CSOODP', 'LPOODP'];
        $lv = [(string) $id, '-1', '1', $fdd, $fdd, 'Now()', $fdd, 'Now()',
               (string) $cant, (string) $cant, '0', (string) $cant, $o20, '30', '-2', '20', '1'];
        db_exec("INSERT INTO [Tbl Ordenes De Proceso Lotes] ([" . implode('],[', $lc) . "]) VALUES (" . implode(',', $lv) . ");");

        db_commit();
        ok(['numodp' => $id, 'procesos' => $ord]);
    } catch (Exception $e) {
        db_rollback();
        fail('No se pudo definir la orden: ' . $e->getMessage(), 500);
    }
}
