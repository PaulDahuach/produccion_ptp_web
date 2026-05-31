<?php
/**
 * Cotización de Órdenes (Presupuestos PTP) — VISTA (solo lectura).
 * Lista presupuestos y su detalle de precios por proceso. El ALTA con la lógica de
 * precios (ORIPPP/PDLPPP/SUGPPP/PREPPP/NETPPP/TOTPPP…) queda pendiente de confirmar
 * las fórmulas con Paul antes de portarla.
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
    $sql = "SELECT TOP 500 P.NUMPPP AS NPP, P.FEXPPP, C.DENCLI AS CLIENTE, P.NUMPTP AS PTP, P.TOTPPP AS TOTAL
            FROM [Tbl Presupuestos PTP] AS P LEFT JOIN [Tbl Clientes] AS C ON P.CODCLI=C.CODCLI
            WHERE (P.ANUPPP=False OR P.ANUPPP Is Null)
            ORDER BY P.NUMPPP DESC;";
    $rows = db_query($sql);
    foreach ($rows as &$r) $r['FEXPPP'] = to_disp_date($r['FEXPPP']);
    ok($rows);
}

function ficha() {
    $id = intval($_GET['id'] ?? 0);
    $h = db_row("SELECT P.NUMPPP, P.FEXPPP, P.NUMPTP, P.TOTPPP, P.OBSPPP, C.DENCLI, Pre.DENPRE
                 FROM (([Tbl Presupuestos PTP] AS P
                   LEFT JOIN [Tbl Clientes] AS C ON P.CODCLI=C.CODCLI)
                   LEFT JOIN [Tbl Prendas] AS Pre ON P.CODPRE=Pre.CODPRE)
                 WHERE P.NUMPPP=$id;");
    if (!$h) { fail('Presupuesto no encontrado'); return; }
    $h['FEXPPP'] = to_disp_date($h['FEXPPP']);
    $items = db_query("SELECT PP.ORDPPP, Pr.DENPRC, PP.CANPPP, PP.PREPPP, PP.NETPPP, PP.PBXPPP, PP.TOTPPP, PP.OBSPPP
                       FROM [Tbl Presupuestos PTP Procesos] AS PP LEFT JOIN [Tbl Procesos] AS Pr ON PP.CODPRC=Pr.CODPRC
                       WHERE PP.NUMPPP=$id ORDER BY PP.ORDPPP;");
    ok(['cabecera' => $h, 'items' => $items]);
}
