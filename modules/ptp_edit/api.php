<?php
/**
 * PTP — Alta / Modificación (transaccional). Portado de `Frm PTP` (SetData A/M/B).
 * Crea y edita las plantillas de ruta de procesos (Tbl PTP + Tbl PTP Procesos), que
 * luego se cargan en Definición ("Cargar PTP").
 *   Alta "A": NUMPTP = next_number('ULTPTP'); CODEDP=2 (confirmado), CNFPTP=True, DISPTP=False;
 *             inserta cabecera + líneas de proceso (ORDPTP secuencial).
 *   Modif "M": reescribe cabecera (CODEDP=2, DISPTP=False) y reemplaza las líneas.
 *   Baja "B": DISPTP=True (discontinúa, no borra).
 * Las imágenes IM1PTP/IM2PTP del legacy (rutas de archivo en el escritorio) se omiten.
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
        case 'discontinuar':   discontinuar(); break;
        case 'subir_imagen':   subirImagen(); break;
        default: fail('Acción inválida: ' . $action);
    }
} catch (Exception $e) {
    fail($e->getMessage(), 500);
}

function lk($t, $pk, $den, $where = '') {
    return db_query("SELECT $pk AS id, $den AS den FROM [$t] $where ORDER BY $den;");
}
function sqlInt($v) { $v = trim((string) $v); return $v === '' ? 'Null' : (string) intval($v); }
function sqlDec($v) { $v = trim((string) $v); return $v === '' ? 'Null' : (string) (float) str_replace(',', '.', $v); }
function sqlTxt($v) { $v = trim((string) $v); return $v === '' ? 'Null' : "'" . db_esc($v) . "'"; }

function initData() {
    ok([
        'readonly' => db_readonly(),
        'clientes' => lk('Tbl Clientes', 'CODCLI', 'DENCLI'),
        'procesos' => db_query("SELECT P.CODPRC AS id, P.DENPRC AS den, E.DENETA AS sector
                                FROM [Tbl Procesos] AS P LEFT JOIN [Tbl Etapas] AS E ON P.CODETA = E.CODETA
                                ORDER BY P.DENPRC;"),
        'colores'  => lk('Tbl Colores De Proceso', 'CODCDP', 'DENCDP'),
        'fechaDisp' => date('d/m/Y'),
    ]);
}

function marcasCliente() {
    $cli = intval((isset($_GET['cli']) ? $_GET['cli'] : 0));
    ok(db_query("SELECT M.CODMAR AS id, M.DENMAR AS den
                 FROM [Tbl Clientes Marcas] AS CM INNER JOIN [Tbl Marcas] AS M ON CM.CODMAR = M.CODMAR
                 WHERE CM.CODCLI = $cli ORDER BY M.DENMAR;"));
}

function listar() {
    $w = ['(P.DISPTP = False OR P.DISPTP Is Null)'];
    $q = trim((isset($_GET['q']) ? $_GET['q'] : ''));
    if ($q !== '') {
        $e = db_esc($q);
        $w[] = "((P.NUMPTP LIKE '%$e%') OR (C.DENCLI LIKE '%$e%') OR (M.DENMAR LIKE '%$e%') OR (P.DENPTP LIKE '%$e%'))";
    }
    $where = 'WHERE ' . implode(' AND ', $w);
    $rows = db_query("SELECT TOP 500 P.NUMPTP AS ODP, P.FDEPTP, C.DENCLI AS CLIENTE, M.DENMAR AS MARCA, P.DENPTP AS DENOM
                      FROM (([Tbl PTP] AS P
                        LEFT JOIN [Tbl Clientes] AS C ON P.CODCLI = C.CODCLI)
                        LEFT JOIN [Tbl Marcas] AS M ON P.CODMAR = M.CODMAR)
                      $where ORDER BY P.NUMPTP DESC;");
    foreach ($rows as &$r) $r['FDEPTP'] = to_disp_date($r['FDEPTP']);
    ok($rows);
}

function obtener() {
    $id = intval((isset($_GET['id']) ? $_GET['id'] : 0));
    $h = db_row("SELECT NUMPTP, FDEPTP, DENPTP, OBSPTP, CODCLI, CODMAR, CODEDP, CNFPTP, DISPTP, IM1PTP, IM2PTP FROM [Tbl PTP] WHERE NUMPTP = $id;");
    if (!$h) { fail('PTP no encontrado'); return; }
    $h['FDEPTP'] = to_disp_date($h['FDEPTP']);
    $procs = db_query("SELECT PP.ORDPTP, PP.CODPRC, Prc.DENPRC, E.DENETA AS SECTOR, PP.CODCDP, CP.DENCDP, PP.PORPTP, PP.OBSPTP
                       FROM ((([Tbl PTP Procesos] AS PP
                         LEFT JOIN [Tbl Procesos] AS Prc ON PP.CODPRC = Prc.CODPRC)
                         LEFT JOIN [Tbl Etapas] AS E ON Prc.CODETA = E.CODETA)
                         LEFT JOIN [Tbl Colores De Proceso] AS CP ON PP.CODCDP = CP.CODCDP)
                       WHERE PP.NUMPTP = $id ORDER BY PP.ORDPTP;");
    // Órdenes de Muestra ligadas (lstODM)
    $odms = db_query("SELECT O.NUMODM, A.DENADP AS ACCION
                      FROM [Tbl Acciones De PTP] AS A INNER JOIN [Tbl Ordenes De Muestra] AS O ON A.CODADP = O.CODADP
                      WHERE O.NUMPTP = $id ORDER BY O.NUMODM;");
    ok(['cabecera' => $h, 'procesos' => $procs, 'odms' => $odms]);
}

function guardar() {
    if (db_readonly()) { fail('Sistema en modo solo lectura'); return; }
    $id   = intval((isset($_POST['NUMPTP']) ? $_POST['NUMPTP'] : 0));   // 0 = alta
    $cli  = intval((isset($_POST['CODCLI']) ? $_POST['CODCLI'] : 0));
    $mar  = intval((isset($_POST['CODMAR']) ? $_POST['CODMAR'] : 0));
    $fec  = trim((isset($_POST['FDEPTP']) ? $_POST['FDEPTP'] : ''));
    $den  = trim((isset($_POST['DENPTP']) ? $_POST['DENPTP'] : ''));
    $obs  = trim((isset($_POST['OBSPTP']) ? $_POST['OBSPTP'] : ''));
    $cnf  = (!empty($_POST['CNFPTP']) && $_POST['CNFPTP'] !== '0') ? 'True' : 'False';
    $im1  = sqlTxt((isset($_POST['IM1PTP']) ? $_POST['IM1PTP'] : ''));
    $im2  = sqlTxt((isset($_POST['IM2PTP']) ? $_POST['IM2PTP'] : ''));
    $procs = json_decode((isset($_POST['__procesos']) ? $_POST['__procesos'] : '[]'), true);
    if (!is_array($procs)) $procs = [];
    $procs = array_values(array_filter($procs, function ($p) { return intval((isset($p['CODPRC']) ? $p['CODPRC'] : 0)) > 0; }));

    if ($cli <= 0) { fail('Elegí un cliente'); return; }
    if ($mar <= 0) { fail('Elegí una marca'); return; }
    if (!$procs)   { fail('Cargá al menos un proceso'); return; }
    $fecSql = '#' . db_esc(fecha_access($fec !== '' ? $fec : date('d/m/Y'))) . '#';

    db_begin();
    try {
        if ($id <= 0) {
            $id = next_number('ULTPTP');
            db_exec("INSERT INTO [Tbl PTP] ([NUMPTP],[FDEPTP],[CODEDP],[DENPTP],[CNFPTP],[DISPTP],[CODCLI],[CODMAR],[OBSPTP],[IM1PTP],[IM2PTP])
                     VALUES ($id, $fecSql, 2, " . sqlTxt($den !== '' ? $den : ('PTP' . $id)) . ", $cnf, False, $cli, $mar, " . sqlTxt($obs) . ", $im1, $im2);");
        } else {
            db_exec("UPDATE [Tbl PTP] SET FDEPTP=$fecSql, CODEDP=2, DENPTP=" . sqlTxt($den) . ", CNFPTP=$cnf, DISPTP=False,
                     CODCLI=$cli, CODMAR=$mar, OBSPTP=" . sqlTxt($obs) . ", IM1PTP=$im1, IM2PTP=$im2 WHERE NUMPTP=$id;");
            db_exec("DELETE FROM [Tbl PTP Procesos] WHERE NUMPTP=$id;");
        }
        $ord = 0;
        foreach ($procs as $p) {
            $ord++;
            $cols = ['NUMPTP', 'ORDPTP', 'CODPRC', 'CODCDP', 'PORPTP', 'OBSPTP'];
            $vals = [(string) $id, (string) $ord, (string) intval($p['CODPRC']),
                     sqlInt((isset($p['CODCDP']) ? $p['CODCDP'] : '')), sqlDec((isset($p['PORPTP']) ? $p['PORPTP'] : '')), sqlTxt((isset($p['OBSPTP']) ? $p['OBSPTP'] : ''))];
            db_exec("INSERT INTO [Tbl PTP Procesos] ([" . implode('],[', $cols) . "]) VALUES (" . implode(',', $vals) . ");");
        }
        db_commit();
    } catch (Exception $e) {
        db_rollback();
        fail('No se pudo guardar: ' . $e->getMessage());
        return;
    }
    ok(['numptp' => $id, 'procesos' => count($procs)]);
}

function discontinuar() {
    if (db_readonly()) { fail('Sistema en modo solo lectura'); return; }
    $id = intval((isset($_POST['__id']) ? $_POST['__id'] : 0));
    if ($id <= 0) { fail('Falta el PTP'); return; }
    db_exec("UPDATE [Tbl PTP] SET DISPTP=True WHERE NUMPTP=$id;");
    ok(['numptp' => $id]);
}

/** Subida de imagen (IMAGEN I / II). Devuelve la ruta web relativa para guardar en IM1PTP/IM2PTP. */
function subirImagen() {
    if (db_readonly()) { fail('Sistema en modo solo lectura', 403); return; }
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) { fail('Error al subir el archivo'); return; }
    $f = $_FILES['file'];
    if ($f['size'] > 8 * 1024 * 1024) { fail('Imagen demasiado grande (máx 8 MB)'); return; }
    $info = @getimagesize($f['tmp_name']);
    if (!$info) { fail('El archivo no es una imagen válida'); return; }
    $extMap = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif');
    if (!isset($extMap[$info[2]])) { fail('Formato no soportado (usá JPG, PNG o GIF)'); return; }
    $ext = $extMap[$info[2]];
    $dir = __DIR__ . '/../../uploads/ptp';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $name = 'ptp_' . date('Ymd_His') . '_' . mt_rand(1000, 9999) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) { fail('No se pudo guardar la imagen'); return; }
    ok(['path' => 'uploads/ptp/' . $name]);
}
