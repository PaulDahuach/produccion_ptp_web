<?php
/**
 * Recepción de Órdenes de Proceso — API.
 * Portado de: Frm Recepcion (SetData, acción "A").
 * Alta = inserta header en [Tbl Ordenes De Proceso] (NUMODP desde ULTODP) +
 * lote inicial en [Tbl Ordenes De Proceso Lotes], en una transacción.
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
        case 'crear':          crear(); break;
        case 'anular':         anular(); break;
        default: fail('Acción inválida: ' . $action);
    }
} catch (Exception $e) {
    fail($e->getMessage(), 500);
}

function lk($tabla, $pk, $den, $where = '') {
    return db_query("SELECT [$pk] AS id, [$den] AS den FROM [$tabla]" . ($where ? " WHERE $where" : '') . " ORDER BY [$den];");
}

/** Combos + fecha de trabajo (FECAPE). */
function initData() {
    $rc = db_row("SELECT FECAPE FROM [Rec Control];");
    ok([
        'fecha'     => to_iso_date($rc['FECAPE']),
        'fechaDisp' => to_disp_date($rc['FECAPE']),
        'acciones'  => lk('Tbl Acciones De ODP', 'CODADO', 'DENADO'),
        'clientes'  => lk('Tbl Clientes', 'CODCLI', 'DENCLI'),
        'talleres'  => lk('Tbl Talleres', 'CODTAL', 'DENTAL'),
        'prendas'   => lk('Tbl Prendas', 'CODPRE', 'DENPRE'),
        'telas'     => lk('Tbl Telas', 'CODTEL', 'DENTEL'),
        'colores'   => lk('Tbl Colores Tela', 'CODCT1', 'DENCT1'),
        'readonly'  => db_readonly(),
    ]);
}

/** Marcas habilitadas para un cliente (subform Clientes_Marcas). */
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

/** Órdenes existentes (para Buscar). */
function listar() {
    $sql = "SELECT TOP 500 O.NUMODP AS ODP, O.FDRODP, C.DENCLI AS CLIENTE, M.DENMAR AS MARCA,
              Pre.DENPRE AS PRENDA, O.CANODP AS CANTIDAD, O.REMODP AS REMITO
            FROM ((([Tbl Ordenes De Proceso] AS O
              LEFT JOIN [Tbl Clientes] AS C ON O.CODCLI = C.CODCLI)
              LEFT JOIN [Tbl Marcas] AS M ON O.CODMAR = M.CODMAR)
              LEFT JOIN [Tbl Prendas] AS Pre ON O.CODPR1 = Pre.CODPRE)
            WHERE O.CODETA > 0 ORDER BY O.NUMODP DESC;";
    $rows = db_query($sql);
    foreach ($rows as &$r) $r['FDRODP'] = to_disp_date($r['FDRODP']);
    ok($rows);
}

/** Ficha de una orden (para ver). */
function obtener() {
    $id = intval((isset($_GET['id']) ? $_GET['id'] : 0));
    $sql = "SELECT O.*, C.DENCLI, M.DENMAR, T.DENTAL, P1.DENPRE AS DENPR1, P2.DENPRE AS DENPR2,
              Tl.DENTEL, A.DENADO
            FROM ((((((([Tbl Ordenes De Proceso] AS O
              LEFT JOIN [Tbl Clientes] AS C ON O.CODCLI=C.CODCLI)
              LEFT JOIN [Tbl Marcas] AS M ON O.CODMAR=M.CODMAR)
              LEFT JOIN [Tbl Talleres] AS T ON O.CODTAL=T.CODTAL)
              LEFT JOIN [Tbl Prendas] AS P1 ON O.CODPR1=P1.CODPRE)
              LEFT JOIN [Tbl Prendas] AS P2 ON O.CODPR2=P2.CODPRE)
              LEFT JOIN [Tbl Telas] AS Tl ON O.CODTEL=Tl.CODTEL)
              LEFT JOIN [Tbl Acciones De ODP] AS A ON O.CODADO=A.CODADO)
            WHERE O.NUMODP = $id;";
    $row = db_row($sql);
    if (!$row) { fail('Orden no encontrada'); return; }
    $row['FDRODP'] = to_iso_date($row['FDRODP']);
    ok($row);
}

/** Construye literal SQL. */
function sqlTxt($v)  { $v = trim((string) $v); return $v === '' ? 'Null' : "'" . db_esc($v) . "'"; }
function sqlInt($v)  { $v = trim((string) $v); return $v === '' ? 'Null' : (string) intval($v); }
function sqlDec($v)  { $v = trim((string) $v); return $v === '' ? '0' : (string) (float) str_replace(',', '.', $v); }

/** ALTA de una Orden de Proceso + lote inicial (transacción). */
function crear() {
    if (db_readonly()) { fail('Sistema en modo solo-lectura', 403); return; }

    // Requeridos (según Tbl Ordenes De Proceso)
    $req = ['REMODP' => 'Remito', 'CODCLI' => 'Cliente', 'CODMAR' => 'Marca', 'CODTAL' => 'Taller',
            'CODPR1' => 'Prenda', 'CANODP' => 'Cantidad', 'PESODP' => 'Peso'];
    foreach ($req as $k => $lbl) {
        if (trim((string) ((isset($_POST[$k]) ? $_POST[$k] : ''))) === '') { fail("Falta: $lbl"); return; }
    }

    $codado = intval((isset($_POST['CODADO']) ? $_POST['CODADO'] : 1)) ?: 1;
    $repodp = ($codado === 1) ? 'Null' : sqlInt((isset($_POST['REPODP']) ? $_POST['REPODP'] : ''));
    $cant   = sqlInt($_POST['CANODP']);
    $prt    = (!empty($_POST['PRTODP']) && $_POST['PRTODP'] !== '0') ? 'True' : 'False';
    $uid    = intval((isset($_SESSION['uid']) ? $_SESSION['uid'] : 0));

    // Fecha de trabajo (FECAPE) → literal Access #mm/dd/yyyy#
    $rc = db_row("SELECT FECAPE FROM [Rec Control];");
    $iso = to_iso_date($rc['FECAPE']);
    $p = explode('-', $iso);
    $fdr = "#{$p[1]}/{$p[2]}/{$p[0]}#";

    db_begin();
    try {
        $num = next_number('ULTODP');

        $cols = ['NUMODP', 'FDRODP', 'CODETA', 'CODADO', 'REPODP', 'REMODP', 'CODCLI', 'CODMAR', 'CODTAL',
                 'OCNODP', 'CAXODP', 'CODPR1', 'CANODP', 'RECODP', 'DEFODP', 'REZODP', 'PESODP', 'PRTODP',
                 'PREODP', 'NUMPTP', 'CODPR2', 'CODTEL', 'CODCT1', 'CODCT2', 'O10ODP', 'NUIODP', 'NMIODP', 'NOWODP'];
        $vals = [
            $num, $fdr, '20', (string) $codado, $repodp, sqlTxt($_POST['REMODP']),
            sqlInt($_POST['CODCLI']), sqlInt($_POST['CODMAR']), sqlInt($_POST['CODTAL']),
            sqlTxt((isset($_POST['OCNODP']) ? $_POST['OCNODP'] : '')), sqlTxt((isset($_POST['CAXODP']) ? $_POST['CAXODP'] : '')), sqlInt($_POST['CODPR1']),
            $cant, '0', $cant, '0', sqlDec($_POST['PESODP']), $prt,
            sqlTxt((isset($_POST['PREODP']) ? $_POST['PREODP'] : '')), sqlInt((isset($_POST['NUMPTP']) ? $_POST['NUMPTP'] : '')), sqlInt((isset($_POST['CODPR2']) ? $_POST['CODPR2'] : '')),
            sqlInt((isset($_POST['CODTEL']) ? $_POST['CODTEL'] : '')), sqlInt((isset($_POST['CODCT1']) ? $_POST['CODCT1'] : '')), sqlInt((isset($_POST['CODCT2']) ? $_POST['CODCT2'] : '')),
            sqlTxt((isset($_POST['O10ODP']) ? $_POST['O10ODP'] : '')), (string) $uid, '0', 'Now()',
        ];
        db_exec("INSERT INTO [Tbl Ordenes De Proceso] ([" . implode('],[', $cols) . "]) VALUES (" . implode(',', $vals) . ");");

        // Lote inicial (sector destino 20=Programación, origen 10=Recepción)
        $obs = sqlTxt((isset($_POST['O10ODP']) ? $_POST['O10ODP'] : ''));
        $lc = ['NUMODP', 'ORDODP', 'LOTODP', 'FEXODP', 'FIPODP', 'HIPODP', 'FFPODP', 'HFPODP',
               'CIPODP', 'CANODP', 'REZODP', 'DSPODP', 'OBSODP', 'CSDODP', 'OPOODP', 'CSOODP'];
        $lv = [$num, '-2', '1', $fdr, $fdr, 'Now()', $fdr, 'Now()',
               $cant, $cant, '0', $cant, $obs, '20', '-3', '10'];
        db_exec("INSERT INTO [Tbl Ordenes De Proceso Lotes] ([" . implode('],[', $lc) . "]) VALUES (" . implode(',', $lv) . ");");

        db_commit();
        ok(['numodp' => $num]);
    } catch (Exception $e) {
        db_rollback();
        fail('No se pudo registrar la recepción: ' . $e->getMessage(), 500);
    }
}

/**
 * ANULA una orden (SetData "B"): soft-annul CODETA=0 + nota en OBSODP, borra lotes y
 * "ordenes en proceso", pone procesos en cero. No borra el header (queda para auditoría).
 */
function anular() {
    if (db_readonly()) { fail('Sistema en modo solo-lectura', 403); return; }
    $id = intval((isset($_POST['__id']) ? $_POST['__id'] : (isset($_GET['id']) ? $_GET['id'] : 0)));
    if ($id <= 0) { fail('Falta la orden'); return; }

    $o = db_row("SELECT CODETA, NUIODP, NMIODP, OBSODP FROM [Tbl Ordenes De Proceso] WHERE NUMODP = $id;");
    if (!$o) { fail('Orden no encontrada'); return; }
    $codeta = intval($o['CODETA']);
    // Validaciones (DelData del legacy)
    if ($codeta === 0)   { fail('La orden ya está anulada'); return; }
    if ($codeta === 130) { fail('La orden está cerrada; no se puede anular'); return; }
    if ($codeta > 30)    { fail('La orden está en una etapa avanzada de producción; no se puede anular desde Recepción'); return; }

    $rc = db_row("SELECT FECAPE FROM [Rec Control];");
    $fecha = to_iso_date($rc['FECAPE']);   // yyyy-mm-dd
    $uid = intval((isset($_SESSION['uid']) ? $_SESSION['uid'] : 0));
    $nota = "[ANULADO $fecha, E:$codeta, U:" . intval($o['NUIODP']) . ", M:" . intval($o['NMIODP']) . "] \r\n";
    $obs = $nota . (string) ((isset($o['OBSODP']) ? $o['OBSODP'] : ''));

    db_begin();
    try {
        db_exec("DELETE FROM [Tbl Ordenes En Proceso] WHERE NUMODP = $id;");
        db_exec("DELETE FROM [Tbl Ordenes De Proceso Lotes] WHERE NUMODP = $id;");
        db_exec("UPDATE [Tbl Ordenes De Proceso Procesos] SET DSPODP=0, REZODP=0 WHERE NUMODP = $id;");
        db_exec("UPDATE [Tbl Ordenes De Proceso] SET CODETA=0, NUIODP=$uid, NMIODP=0, NOWODP=Now(), OBSODP='" . db_esc($obs) . "' WHERE NUMODP = $id;");
        db_commit();
        ok(['numodp' => $id]);
    } catch (Exception $e) {
        db_rollback();
        fail('No se pudo anular: ' . $e->getMessage(), 500);
    }
}
