<?php
/**
 * Consulta de PTP (plantillas de ruta de procesos / pedidos) — API (solo lectura).
 * Tbl PTP = la "receta" de procesos que se carga en Definición ("Cargar PTP"). Distinta
 * de Tbl Presupuestos PTP (cotización con precios). Lista + ficha (cabecera + procesos).
 * Filtros: búsqueda (N°/cliente/marca/denominación), estado, no-anulados por defecto.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_require_login();

$action = (isset($_GET['action']) ? $_GET['action'] : '');
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
    $estado = trim((isset($_GET['estado']) ? $_GET['estado'] : ''));
    if ($estado !== '') $w[] = '(P.CODEDP = ' . intval($estado) . ')';
    else $w[] = '(P.CODEDP <> 3)';  // por defecto, ocultar anulados
    $q = trim((isset($_GET['q']) ? $_GET['q'] : ''));
    if ($q !== '') {
        $e = db_esc($q);
        $w[] = "((P.NUMPTP LIKE '%$e%') OR (C.DENCLI LIKE '%$e%') OR (M.DENMAR LIKE '%$e%') OR (P.DENPTP LIKE '%$e%'))";
    }
    $where = $w ? ('WHERE ' . implode(' AND ', $w)) : '';
    $rows = db_query("SELECT TOP 500 P.NUMPTP AS NPP, P.FDEPTP, C.DENCLI AS CLIENTE, M.DENMAR AS MARCA,
                        Ed.DENEDP AS ESTADO, P.DENPTP AS DENOM
                      FROM ((([Tbl PTP] AS P
                        LEFT JOIN [Tbl Estados De PTP] AS Ed ON P.CODEDP = Ed.CODEDP)
                        LEFT JOIN [Tbl Clientes] AS C ON P.CODCLI = C.CODCLI)
                        LEFT JOIN [Tbl Marcas] AS M ON P.CODMAR = M.CODMAR)
                      $where
                      ORDER BY P.NUMPTP DESC;");
    foreach ($rows as &$r) $r['FDEPTP'] = to_disp_date($r['FDEPTP']);
    ok($rows);
}

function ficha() {
    $id = intval((isset($_GET['id']) ? $_GET['id'] : 0));
    $h = db_row("SELECT P.NUMPTP, P.FDEPTP, P.DENPTP, P.OBSPTP, Ed.DENEDP, C.DENCLI, M.DENMAR
                 FROM ((([Tbl PTP] AS P
                   LEFT JOIN [Tbl Estados De PTP] AS Ed ON P.CODEDP = Ed.CODEDP)
                   LEFT JOIN [Tbl Clientes] AS C ON P.CODCLI = C.CODCLI)
                   LEFT JOIN [Tbl Marcas] AS M ON P.CODMAR = M.CODMAR)
                 WHERE P.NUMPTP = $id;");
    if (!$h) { fail('PTP no encontrado'); return; }
    $h['FDEPTP'] = to_disp_date($h['FDEPTP']);
    $items = db_query("SELECT PP.ORDPTP, Prc.DENPRC, E.DENETA, CP.DENCDP, PP.PORPTP, PP.OBSPTP
                       FROM ((([Tbl PTP Procesos] AS PP
                         LEFT JOIN [Tbl Procesos] AS Prc ON PP.CODPRC = Prc.CODPRC)
                         LEFT JOIN [Tbl Etapas] AS E ON Prc.CODETA = E.CODETA)
                         LEFT JOIN [Tbl Colores De Proceso] AS CP ON PP.CODCDP = CP.CODCDP)
                       WHERE PP.NUMPTP = $id ORDER BY PP.ORDPTP;");
    ok(['cabecera' => $h, 'items' => $items]);
}
