<?php
/**
 * Clonar Usuario — crea un usuario nuevo con el MISMO acceso que uno existente (copia su lista blanca
 * de [Tbl Usuarios Menu] + su categoría). Admin-only. Resuelve el caso de varios que comparten una clave
 * por el nivel de acceso: se les da un usuario propio clonando al de referencia.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_require_admin();   // SOLO admin (config admin_users)

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');
try {
    switch ($action) {
        case 'usuarios': usuarios(); break;
        case 'clonar':   clonar();   break;
        default: fail('Acción inválida: ' . $action);
    }
} catch (Exception $e) {
    fail($e->getMessage(), 500);
}

/** Lista de usuarios (para elegir el origen) con su cantidad de permisos y categoría. */
function usuarios() {
    $cnt = array();
    foreach (db_query("SELECT CODUSR, COUNT(*) AS N FROM [Tbl Usuarios Menu] GROUP BY CODUSR;") as $c) {
        $cnt[(int) $c['CODUSR']] = (int) $c['N'];
    }
    $rows = db_query("SELECT CODUSR, DENUSR, CATUSR FROM [Tbl Usuarios] ORDER BY DENUSR;");
    foreach ($rows as &$r) {
        $r['CODUSR'] = (int) $r['CODUSR'];
        $r['PERMISOS'] = isset($cnt[$r['CODUSR']]) ? $cnt[$r['CODUSR']] : 0;
    }
    ok($rows);
}

/** Crea el usuario nuevo y le clona los permisos del origen (en transacción). */
function clonar() {
    if (db_readonly()) { fail('El sistema está en solo lectura (readonly).'); return; }
    $src    = intval(isset($_POST['src']) ? $_POST['src'] : 0);
    $nombre = trim((string) (isset($_POST['nombre']) ? $_POST['nombre'] : ''));
    $inic   = trim((string) (isset($_POST['inic']) ? $_POST['inic'] : ''));
    $clave  = trim((string) (isset($_POST['clave']) ? $_POST['clave'] : ''));
    $cat    = trim((string) (isset($_POST['cat']) ? $_POST['cat'] : ''));

    if ($src <= 0)         { fail('Elegí el usuario a clonar.'); return; }
    if ($nombre === '')    { fail('Falta el nombre del nuevo usuario.'); return; }
    if ($clave === '')     { fail('Falta la clave.'); return; }

    $s = db_row("SELECT CODUSR, DENUSR, CATUSR FROM [Tbl Usuarios] WHERE CODUSR = $src;");
    if (!$s) { fail('El usuario origen no existe.'); return; }
    if ($cat === '') $cat = (string) $s['CATUSR'];           // por defecto, misma categoría que el origen
    if ($inic === '') $inic = strtoupper(substr($nombre, 0, 2));

    $dup = db_row("SELECT COUNT(*) AS N FROM [Tbl Usuarios] WHERE DENUSR = '" . db_esc($nombre) . "';");
    if ($dup && (int) $dup['N'] > 0) { fail('Ya existe un usuario llamado "' . $nombre . '".'); return; }

    db_begin();
    try {
        $uid = next_number('ULTUSR');   // PK manual vía contador (NO max+1)
        db_exec("INSERT INTO [Tbl Usuarios] (CODUSR, DENUSR, INIUSR, ACCUSR, CATUSR) VALUES "
            . "($uid, '" . db_esc($nombre) . "', '" . db_esc($inic) . "', '" . db_esc($clave) . "', '" . db_esc($cat) . "');");
        db_exec("INSERT INTO [Tbl Usuarios Menu] (CODUSR, CODMEN) "
            . "SELECT $uid, CODMEN FROM [Tbl Usuarios Menu] WHERE CODUSR = $src;");
        db_commit();
    } catch (Exception $e) {
        db_rollback();
        throw $e;
    }

    $n = db_row("SELECT COUNT(*) AS N FROM [Tbl Usuarios Menu] WHERE CODUSR = $uid;");
    ok(array(
        'uid'      => $uid,
        'nombre'   => $nombre,
        'cat'      => $cat,
        'permisos' => $n ? (int) $n['N'] : 0,
        'origen'   => $s['DENUSR'],
    ));
}
