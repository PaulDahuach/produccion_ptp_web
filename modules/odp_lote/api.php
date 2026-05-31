<?php
/**
 * Consulta Órdenes de Proceso x Lote — API (solo lectura).
 * Portado de: Frm/Qry "Consulta Ordenes de Proceso x Lote" (Producción PTP).
 *
 * Reconstruye la SQL del legacy quitando los parámetros de form; los filtros
 * llegan por querystring y se arman en el servidor.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_require_login();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'list':     listar(); break;
        case 'etapas':   etapas(); break;  // para el combo de sector
        default: fail('Acción inválida: ' . $action);
    }
} catch (Exception $e) {
    fail($e->getMessage(), 500);
}

/** SQL base (sin filtros). Joins anidados a la izquierda al estilo Access. */
function sql_base() {
    return "SELECT
      E.DENETA AS SECTOR, O.NUMODP AS ODP, C.DENCLI AS CLIENTE, M.DENMAR AS MARCA,
      Pre.DENPRE AS PRENDA, Prc.DENPRC AS PROCESO,
      O.OCNODP AS OCORTE, O.CAXODP AS CARTICULO, O.NUMPTP AS PTP,
      L.DSPODP AS CANTIDAD,
      DateDiff('d',O.FDRODP,Date()) AS DIAS_REC,
      DateDiff('d',O.FDDODP,Date()) AS DIAS_DEF,
      L.ORDODP AS ORDEN, L.LOTODP AS LOTE, O.CODETA
    FROM ((((((
       [Tbl Ordenes De Proceso] AS O
       INNER JOIN [Tbl Ordenes De Proceso Lotes] AS L ON O.NUMODP = L.NUMODP)
       INNER JOIN [Tbl Etapas] AS E ON L.CSDODP = E.CODETA)
       INNER JOIN [Tbl Clientes] AS C ON O.CODCLI = C.CODCLI)
       INNER JOIN [Tbl Marcas] AS M ON O.CODMAR = M.CODMAR)
       LEFT JOIN [Tbl Prendas] AS Pre ON O.CODPR1 = Pre.CODPRE)
       LEFT JOIN [sql Ordenes de Proceso x Etapa_Proceso Actual] AS bufCurrentPrc ON O.NUMODP = bufCurrentPrc.NUMODP)
       LEFT JOIN [Tbl Procesos] AS Prc ON bufCurrentPrc.CODPRC = Prc.CODPRC";
}

function listar() {
    $where = ['(L.DSPODP > 0)', '(O.CODETA > 0)'];

    // Filtro por sector / etapa (CODETA exacto)
    $etapa = trim($_GET['etapa'] ?? '');
    if ($etapa !== '') $where[] = '(O.CODETA = ' . intval($etapa) . ')';

    // Rango de fechas de recepción (dd/mm/aaaa)
    $desde = trim($_GET['desde'] ?? '');
    $hasta = trim($_GET['hasta'] ?? '');
    if ($desde !== '') $where[] = "(O.FDRODP >= #" . db_esc(fecha_access($desde)) . "#)";
    if ($hasta !== '') $where[] = "(O.FDRODP <= #" . db_esc(fecha_access($hasta)) . "#)";

    // Búsqueda libre: ODP, O corte, C artículo, cliente, marca
    $q = trim($_GET['q'] ?? '');
    if ($q !== '') {
        $e = db_esc($q);
        $where[] = "((O.NUMODP LIKE '%$e%') OR (O.OCNODP LIKE '%$e%') OR (O.CAXODP LIKE '%$e%')"
                 . " OR (C.DENCLI LIKE '%$e%') OR (M.DENMAR LIKE '%$e%'))";
    }

    $sql = sql_base() . "\nWHERE " . implode(' AND ', $where) . "\nORDER BY E.CODETA, O.NUMODP;";
    ok(db_query($sql));
}

/** Sectores (etapas) presentes, para poblar el combo de filtro. */
function etapas() {
    $rows = db_query("SELECT CODETA, DENETA FROM [Tbl Etapas] WHERE CODETA > 0 ORDER BY DENETA;");
    ok($rows);
}
