<?php
/** Reportes/listados (solo lectura). ?action=list&r=<clave> (ver defs.php). */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_require_login();
auth_release_session();   // solo lectura: libera el lock de sesión para no bloquear otras pestañas del usuario

$DEFS = require __DIR__ . '/defs.php';
$action = (isset($_GET['action']) ? $_GET['action'] : '');
$r = (isset($_GET['r']) ? $_GET['r'] : '');

try {
    if ($action === 'list') {
        if (!isset($DEFS[$r])) { fail('Reporte inválido: ' . $r); }
        else {
            $def = $DEFS[$r];
            // Filtros server-side (opcional, por reporte): arma las condiciones AND y las inyecta en {F}.
            $extra = ''; $faltaReq = false;
            if (!empty($def['filtros'])) {
                foreach ($def['filtros'] as $f) {
                    $v = isset($_GET[$f['param']]) ? trim($_GET[$f['param']]) : '';
                    if ($v === '') { if (!empty($f['req'])) $faltaReq = true; continue; }
                    $lit = ($f['tipo'] === 'int') ? (string) intval($v) : ("'%" . db_esc($v) . "%'");
                    $extra .= ' AND (' . str_replace('{V}', $lit, $f['sql']) . ')';
                }
            }
            $sql = str_replace('{F}', $extra, $def['sql']);
            $rows = $faltaReq ? array() : db_query($sql);   // falta un filtro requerido → vacío (el front avisa)
            if (!empty($def['fechas'])) {
                foreach ($rows as &$row) foreach ($def['fechas'] as $f) {
                    if (array_key_exists($f, $row)) $row[$f] = to_disp_date($row[$f]);
                }
            }
            ok($rows);
        }
    } else {
        fail('Acción inválida');
    }
} catch (Exception $e) {
    fail($e->getMessage(), 500);
}
