<?php
/**
 * Consulta Órdenes de Proceso x Etapa — API (solo lectura).
 * Portado de: Qry/Frm "Consulta Ordenes de Proceso x Etapa" (Producción PTP).
 * Una fila por ORDEN (no por lote), con resumen por etapa.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_require_login();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
try {
    switch ($action) {
        case 'list':    listar(); break;
        case 'resumen': resumen(); break;
        case 'etapas':  etapas(); break;
        default: fail('Acción inválida: ' . $action);
    }
} catch (Exception $e) {
    fail($e->getMessage(), 500);
}

/** Construye las condiciones WHERE comunes a partir del querystring. */
function filtros(&$where) {
    $where = ['(O.CODETA > 0)'];
    $etapa = trim($_GET['etapa'] ?? '');
    if ($etapa !== '') $where[] = '(O.CODETA = ' . intval($etapa) . ')';
    $desde = trim($_GET['desde'] ?? '');
    $hasta = trim($_GET['hasta'] ?? '');
    if ($desde !== '') $where[] = "(O.FDRODP >= #" . db_esc(fecha_access($desde)) . "#)";
    if ($hasta !== '') $where[] = "(O.FDRODP <= #" . db_esc(fecha_access($hasta)) . "#)";
    $q = trim($_GET['q'] ?? '');
    if ($q !== '') {
        $e = db_esc($q);
        $where[] = "((O.NUMODP LIKE '%$e%') OR (O.OCNODP LIKE '%$e%') OR (O.CAXODP LIKE '%$e%')"
                 . " OR (C.DENCLI LIKE '%$e%') OR (M.DENMAR LIKE '%$e%'))";
    }
}

function listar() {
    filtros($where);
    $sql = "SELECT
      E.DENETA AS ETAPA, O.NUMODP AS ODP, C.DENCLI AS CLIENTE, Pre.DENPRE AS PRENDA,
      M.DENMAR AS MARCA, O.OCNODP AS OCORTE, O.CAXODP AS CARTICULO, O.NUMPTP AS PTP,
      O.CANODP AS CANTIDAD,
      DateDiff('d',O.FDRODP,Date()) AS DIAS_REC,
      DateDiff('d',O.FDDODP,Date()) AS DIAS_DEF, O.CODETA
    FROM ((((
       [Tbl Ordenes De Proceso] AS O
       INNER JOIN [Tbl Clientes] AS C ON O.CODCLI = C.CODCLI)
       INNER JOIN [Tbl Marcas] AS M ON O.CODMAR = M.CODMAR)
       INNER JOIN [Tbl Etapas] AS E ON O.CODETA = E.CODETA)
       LEFT JOIN [Tbl Prendas] AS Pre ON O.CODPR1 = Pre.CODPRE)
    WHERE " . implode(' AND ', $where) . "
    ORDER BY O.CODETA, O.NUMODP;";
    ok(db_query($sql));
}

/** Resumen por etapa: total de órdenes y de prendas (respeta filtros). */
function resumen() {
    filtros($where);
    $sql = "SELECT E.DENETA AS ETAPA, O.CODETA, Count(O.NUMODP) AS TOTAL_ORDENES, Sum(O.CANODP) AS TOTAL_PRENDAS
    FROM ((([Tbl Ordenes De Proceso] AS O
       INNER JOIN [Tbl Clientes] AS C ON O.CODCLI = C.CODCLI)
       INNER JOIN [Tbl Marcas] AS M ON O.CODMAR = M.CODMAR)
       INNER JOIN [Tbl Etapas] AS E ON O.CODETA = E.CODETA)
    WHERE " . implode(' AND ', $where) . "
    GROUP BY E.DENETA, O.CODETA ORDER BY O.CODETA;";
    ok(db_query($sql));
}

function etapas() {
    ok(db_query("SELECT CODETA, DENETA FROM [Tbl Etapas] WHERE CODETA > 0 ORDER BY DENETA;"));
}
