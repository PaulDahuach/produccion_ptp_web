<?php
/**
 * Órdenes de Muestra — Ciclo de vida: Confirmación y Entrega. Portado de
 * `Frm Confirmacion Ordenes De Muestra` y `Frm Entrega Ordenes De Muestra`.
 *   Confirmar (Pendiente CODEDM=1 → Confirmada 2): UPDATE CODEDM=2, FDCODM=hoy.
 *   Entregar (Confirmada 2 → Remitida 4): inserta fila en Tbl Ordenes De Muestra Remitos
 *     (ORDODM=Max+1, CANODM=cant a remitir, FDRODM=hoy), acumula CRMODM en la muestra y, si
 *     CRMODM alcanza CANODM, pone CODEDM=4. Soporta entregas PARCIALES (varios remitos).
 *   El "remito" es esa fila (NUMODM+ORDODM) → se imprime con remito.php.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_require_login();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
try {
    switch ($action) {
        case 'list':      listar(); break;
        case 'get':       ficha(); break;
        case 'confirmar': confirmar(); break;
        case 'entregar':  entregar(); break;
        default: fail('Acción inválida: ' . $action);
    }
} catch (Exception $e) {
    fail($e->getMessage(), 500);
}

/** fase=confirmar → CODEDM=1 ; fase=entregar → CODEDM=2 */
function listar() {
    $fase = trim($_GET['fase'] ?? 'confirmar');
    $estado = ($fase === 'entregar') ? 2 : 1;
    $w = ['(O.CODEDM = ' . $estado . ')'];
    $q = trim($_GET['q'] ?? '');
    if ($q !== '') { $e = db_esc($q); $w[] = "((O.NUMODM LIKE '%$e%') OR (C.DENCLI LIKE '%$e%') OR (M.DENMAR LIKE '%$e%') OR (O.NUMPTP LIKE '%$e%'))"; }
    $where = 'WHERE ' . implode(' AND ', $w);
    $rows = db_query("SELECT TOP 500 O.NUMODM AS ODM, O.FDEODM, C.DENCLI AS CLIENTE, M.DENMAR AS MARCA,
                        O.CANODM AS CANT, O.CRMODM AS REMIT, O.NUMPTP AS PTP
                      FROM (([Tbl Ordenes De Muestra] AS O
                        LEFT JOIN [Tbl Clientes] AS C ON O.CODCLI=C.CODCLI)
                        LEFT JOIN [Tbl Marcas] AS M ON O.CODMAR=M.CODMAR)
                      $where ORDER BY O.NUMODM DESC;");
    foreach ($rows as &$r) {
        $r['FDEODM'] = to_disp_date($r['FDEODM']);
        $r['REMIT'] = (float) ($r['REMIT'] ?? 0);
        $r['PEND'] = (float) $r['CANT'] - $r['REMIT'];
    }
    ok($rows);
}

function ficha() {
    $id = intval($_GET['id'] ?? 0);
    $h = db_row("SELECT O.NUMODM, O.FDEODM, O.CANODM, O.CRMODM, O.NUMPTP, O.CODEDM, C.DENCLI, M.DENMAR
                 FROM (([Tbl Ordenes De Muestra] AS O
                   LEFT JOIN [Tbl Clientes] AS C ON O.CODCLI=C.CODCLI)
                   LEFT JOIN [Tbl Marcas] AS M ON O.CODMAR=M.CODMAR)
                 WHERE O.NUMODM=$id;");
    if (!$h) { fail('Muestra no encontrada'); return; }
    $h['FDEODM'] = to_disp_date($h['FDEODM']);
    $h['CRMODM'] = (float) ($h['CRMODM'] ?? 0);
    $h['PEND'] = (float) $h['CANODM'] - $h['CRMODM'];
    $rem = db_query("SELECT ORDODM, FDRODM, CANODM FROM [Tbl Ordenes De Muestra Remitos] WHERE NUMODM=$id ORDER BY ORDODM;");
    foreach ($rem as &$x) $x['FDRODM'] = to_disp_date($x['FDRODM']);
    ok(['cabecera' => $h, 'remitos' => $rem]);
}

function confirmar() {
    if (db_readonly()) { fail('Sistema en modo solo lectura'); return; }
    $id = intval($_POST['__id'] ?? 0);
    $o = db_row("SELECT CODEDM FROM [Tbl Ordenes De Muestra] WHERE NUMODM=$id;");
    if (!$o) { fail('Muestra no encontrada'); return; }
    if ((int) $o['CODEDM'] !== 1) { fail('La muestra no está Pendiente (no se puede confirmar)'); return; }
    $hoy = '#' . db_esc(fecha_access(date('d/m/Y'))) . '#';
    db_exec("UPDATE [Tbl Ordenes De Muestra] SET CODEDM=2, FDCODM=$hoy WHERE NUMODM=$id;");
    ok(['numodm' => $id]);
}

function entregar() {
    if (db_readonly()) { fail('Sistema en modo solo lectura'); return; }
    $id = intval($_POST['__id'] ?? 0);
    $cant = (float) str_replace(',', '.', $_POST['cant'] ?? '0');
    $o = db_row("SELECT CANODM, CRMODM, CODEDM FROM [Tbl Ordenes De Muestra] WHERE NUMODM=$id;");
    if (!$o) { fail('Muestra no encontrada'); return; }
    if ((int) $o['CODEDM'] !== 2) { fail('La muestra no está Confirmada (no se puede entregar)'); return; }
    $can = (float) $o['CANODM']; $rem = (float) ($o['CRMODM'] ?? 0); $pend = $can - $rem;
    if ($cant <= 0) { fail('Cantidad a remitir inválida'); return; }
    if ($cant > $pend) { fail('La cantidad supera lo pendiente (' . rtrim(rtrim(number_format($pend, 2, '.', ''), '0'), '.') . ')'); return; }
    $hoy = '#' . db_esc(fecha_access(date('d/m/Y'))) . '#';
    $uid = intval($_SESSION['uid'] ?? 0);

    db_begin();
    try {
        $mx = db_row("SELECT Max(ORDODM) AS M FROM [Tbl Ordenes De Muestra Remitos] WHERE NUMODM=$id;");
        $ord = (int) ($mx['M'] ?? 0) + 1;
        db_exec("INSERT INTO [Tbl Ordenes De Muestra Remitos] ([NUMODM],[ORDODM],[FDRODM],[CANODM],[NUIODM],[NMIODM],[NOWODM])
                 VALUES ($id, $ord, $hoy, " . ($cant == (int) $cant ? (string) (int) $cant : (string) $cant) . ", $uid, 0, Now());");
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
