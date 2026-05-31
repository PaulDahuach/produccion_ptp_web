<?php
/**
 * Consulta de Órdenes de Muestra (ODM) — API (solo lectura).
 * Tbl Ordenes De Muestra = muestras/prototipos. De cada ODM se genera luego el
 * Presupuesto PTP (Tbl Presupuestos PTP.NUMODM). Lista + ficha (cabecera + procesos + prendas).
 * Estado CODEDM → Tbl Estados De Muestra (1=Pend,2=Confirmada,3=Anulada,4=Remitida,5=Anul.y Rem.).
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_require_login();

$action = $_GET['action'] ?? '';
try {
    switch ($action) {
        case 'list': listar(); break;
        case 'get':  ficha();  break;
        default: fail('Acción inválida: ' . $action);
    }
} catch (Exception $e) {
    fail($e->getMessage(), 500);
}

function listar() {
    $w = [];
    $estado = trim($_GET['estado'] ?? '');
    if ($estado !== '') $w[] = '(O.CODEDM = ' . intval($estado) . ')';
    else $w[] = '(O.CODEDM NOT IN (3,5))';   // por defecto, ocultar anuladas
    $q = trim($_GET['q'] ?? '');
    if ($q !== '') {
        $e = db_esc($q);
        $w[] = "((O.NUMODM LIKE '%$e%') OR (C.DENCLI LIKE '%$e%') OR (M.DENMAR LIKE '%$e%') OR (O.NUMPTP LIKE '%$e%'))";
    }
    $where = $w ? ('WHERE ' . implode(' AND ', $w)) : '';
    $rows = db_query("SELECT TOP 500 O.NUMODM AS ODM, O.FDEODM, C.DENCLI AS CLIENTE, M.DENMAR AS MARCA,
                        Pre.DENPRE AS PRENDA, O.CANODM AS CANTIDAD, O.NUMPTP AS PTP, Ed.DENEDM AS ESTADO
                      FROM (((([Tbl Ordenes De Muestra] AS O
                        LEFT JOIN [Tbl Clientes] AS C ON O.CODCLI = C.CODCLI)
                        LEFT JOIN [Tbl Marcas] AS M ON O.CODMAR = M.CODMAR)
                        LEFT JOIN [Tbl Prendas] AS Pre ON O.CODPRE = Pre.CODPRE)
                        LEFT JOIN [Tbl Estados De Muestra] AS Ed ON O.CODEDM = Ed.CODEDM)
                      $where ORDER BY O.NUMODM DESC;");
    foreach ($rows as &$r) $r['FDEODM'] = to_disp_date($r['FDEODM']);
    ok($rows);
}

function ficha() {
    $id = intval($_GET['id'] ?? 0);
    $h = db_row("SELECT O.NUMODM, O.FDEODM, O.CANODM, O.NUMPTP, O.OBSODM, Ed.DENEDM,
                   C.DENCLI, M.DENMAR, Pre.DENPRE, Tl.DENTEL
                 FROM ((((([Tbl Ordenes De Muestra] AS O
                   LEFT JOIN [Tbl Clientes] AS C ON O.CODCLI = C.CODCLI)
                   LEFT JOIN [Tbl Marcas] AS M ON O.CODMAR = M.CODMAR)
                   LEFT JOIN [Tbl Prendas] AS Pre ON O.CODPRE = Pre.CODPRE)
                   LEFT JOIN [Tbl Telas] AS Tl ON O.CODTEL = Tl.CODTEL)
                   LEFT JOIN [Tbl Estados De Muestra] AS Ed ON O.CODEDM = Ed.CODEDM)
                 WHERE O.NUMODM = $id;");
    if (!$h) { fail('Orden de Muestra no encontrada'); return; }
    $h['FDEODM'] = to_disp_date($h['FDEODM']);
    $procs = db_query("SELECT PP.ORDODM, Prc.DENPRC, E.DENETA, CP.DENCDP, PP.PORODM, PP.OBSODM
                       FROM ((([Tbl Ordenes De Muestra Procesos] AS PP
                         LEFT JOIN [Tbl Procesos] AS Prc ON PP.CODPRC = Prc.CODPRC)
                         LEFT JOIN [Tbl Etapas] AS E ON Prc.CODETA = E.CODETA)
                         LEFT JOIN [Tbl Colores De Proceso] AS CP ON PP.CODCDP = CP.CODCDP)
                       WHERE PP.NUMODM = $id ORDER BY PP.ORDODM;");
    $prendas = db_query("SELECT PR.ORDODM, Pre.DENPRE, Tl.DENTEL
                         FROM (([Tbl Ordenes De Muestra Prendas] AS PR
                           LEFT JOIN [Tbl Prendas] AS Pre ON PR.CODPRE = Pre.CODPRE)
                           LEFT JOIN [Tbl Telas] AS Tl ON PR.CODTEL = Tl.CODTEL)
                         WHERE PR.NUMODM = $id ORDER BY PR.ORDODM;");
    ok(['cabecera' => $h, 'procesos' => $procs, 'prendas' => $prendas]);
}
