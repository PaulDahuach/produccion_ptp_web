<?php
/**
 * Consulta Órdenes de Proceso Retrasadas — API (solo lectura).
 * Portado de `sql Consulta Ordenes de Proceso x Lote_Retrasos Detalle` (cmdRet del
 * Frm Consulta x Lote). Lista las órdenes DEFINIDAS hace más de X días y aún no
 * terminadas (CODETA>0 y <120), una fila por orden, ordenadas por más retrasada.
 * Retraso = DateDiff('d', O.FDDODP, hoy) > dias.  Mismos filtros que la consulta x Lote
 * (pero el período acá filtra por FDDODP = fecha de definición).
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_require_login();

$action = $_GET['action'] ?? '';
try {
    switch ($action) {
        case 'init': init(); break;
        case 'list': listar(); break;
        default: fail('Acción inválida: ' . $action);
    }
} catch (Exception $e) {
    fail($e->getMessage(), 500);
}

function init() {
    $rc = db_row("SELECT DESFEC, HASFEC FROM [Rec Control];");
    ok([
        'desde'    => $rc ? to_disp_date($rc['DESFEC']) : '',
        'hasta'    => $rc ? to_disp_date($rc['HASFEC']) : '',
        'clientes' => db_query("SELECT CODCLI AS id, DENCLI AS den FROM [Tbl Clientes] ORDER BY DENCLI;"),
        'marcas'   => db_query("SELECT CODMAR AS id, DENMAR AS den FROM [Tbl Marcas] ORDER BY DENMAR;"),
        'prendas'  => db_query("SELECT CODPRE AS id, DENPRE AS den FROM [Tbl Prendas] ORDER BY DENPRE;"),
    ]);
}

function listar() {
    require __DIR__ . '/_query.php';
    ok(retrasadas_rows());
}
