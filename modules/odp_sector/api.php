<?php
/**
 * Consulta Órdenes de Proceso x Sector — API (solo lectura).
 * Portado de: Qry/Frm "Consulta Ordenes de Proceso x Sector" (Producción PTP).
 * Una fila por PROCESO (OPP) de las órdenes en un sector (etapa) dado.
 * El sector es obligatorio (la vista es inherentemente por-sector).
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_require_login();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
try {
    switch ($action) {
        case 'list':      listar(); break;
        case 'sectores':  sectores(); break;
        default: fail('Acción inválida: ' . $action);
    }
} catch (Exception $e) {
    fail($e->getMessage(), 500);
}

function listar() {
    $etapa = trim($_GET['etapa'] ?? '');
    if ($etapa === '') { ok([]); return; }   // sin sector: nada que mostrar
    $where = ['(O.CODETA = ' . intval($etapa) . ')'];

    $q = trim($_GET['q'] ?? '');
    if ($q !== '') {
        $e = db_esc($q);
        $where[] = "((OPP.NUMODP LIKE '%$e%') OR (M.DENMAR LIKE '%$e%') OR (Prc.DENPRC LIKE '%$e%'))";
    }

    $sql = "SELECT
      OPP.FPGODP AS PROGRAMA, OPP.OPGODP AS ORDENP, OPP.NUMODP AS ODP,
      M.DENMAR AS MARCA, O.NUMPTP AS PTP, Prc.DENPRC AS PROCESO,
      O.CANODP AS CANTIDAD, OPP.DSPODP AS PENDIENTE, O.FDDODP AS DEFINICION,
      DateDiff('d',O.FDRODP,Date()) AS DIAS_REC,
      DateDiff('d',O.FDDODP,Date()) AS DIAS_DEF
    FROM ((([Tbl Ordenes De Proceso] AS O
       INNER JOIN [Tbl Ordenes De Proceso Procesos] AS OPP ON (O.NUMODP = OPP.NUMODP) AND (O.ORDODP = OPP.ORDODP))
       INNER JOIN [Tbl Procesos] AS Prc ON OPP.CODPRC = Prc.CODPRC)
       INNER JOIN [Tbl Marcas] AS M ON O.CODMAR = M.CODMAR)
    WHERE " . implode(' AND ', $where) . "
    ORDER BY OPP.FPGODP, OPP.OPGODP;";
    ok(db_query($sql));
}

/** Sectores (etapas) que tienen órdenes, con su total — para el selector. */
function sectores() {
    $sql = "SELECT E.CODETA, E.DENETA, Count(O.NUMODP) AS TOTAL
    FROM [Tbl Etapas] AS E INNER JOIN [Tbl Ordenes De Proceso] AS O ON E.CODETA = O.CODETA
    WHERE (O.CODETA > 0)
    GROUP BY E.CODETA, E.DENETA ORDER BY Count(O.NUMODP) DESC;";
    ok(db_query($sql));
}
