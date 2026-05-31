<?php
/**
 * Movimientos de Lotes — API (solo lectura). Reproduce "Rpt Movimientos Lotes" (opción
 * 906 del menú): ingresos/egresos de lotes por rango de fecha (FFPODP) y sector, con
 * nivel Detalle / Sector Producción / Sector Personal / Planta.
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
    ok([
        'sectores' => db_query("SELECT CODETA AS id, DENETA AS den FROM [Tbl Etapas] WHERE CODETA > 0 ORDER BY DENETA;"),
    ]);
}

function listar() {
    require __DIR__ . '/_query.php';
    ok(movimientos_rows());
}
