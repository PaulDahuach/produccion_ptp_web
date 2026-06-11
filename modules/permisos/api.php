<?php
/**
 * Permisos de Usuario — editor de la lista blanca WEB ([Tbl Usuarios Menu Web]). Admin-only.
 * Muestra el menú (opciones gateadas: 'opt' legacy = CODMEN string, y 'optweb' web-native) con checkboxes
 * y guarda borra-reinserta (sólo las claves del catálogo → preserva los CODMEN legacy sin entrada de menú).
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_require_admin();

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');
try {
    switch ($action) {
        case 'usuarios': usuarios(); break;
        case 'cargar':   cargar();   break;
        case 'guardar':  guardar();  break;
        default: fail('Acción inválida: ' . $action);
    }
} catch (Exception $e) {
    fail($e->getMessage(), 500);
}

/** Catálogo de opciones gateables del menú: [ [section, key, label], ... ] (dedup por key). */
function catalogo() {
    $menu = sys('menu', array());
    $out = array(); $seen = array();
    if (is_array($menu)) foreach ($menu as $section => $cards) {
        if (!is_array($cards)) continue;
        foreach ($cards as $c) {
            if (!isset($c['label'])) continue;
            $key = !empty($c['optweb']) ? (string) $c['optweb'] : (!empty($c['opt']) ? (string) $c['opt'] : '');
            if ($key === '' || isset($seen[$key])) continue;   // sin gate (siempre visible) o repetida
            $seen[$key] = true;
            $out[] = array('section' => $section, 'key' => $key, 'label' => $c['label']);
        }
    }
    return $out;
}

/** Claves del catálogo (set). */
function catalogo_keys() {
    $k = array();
    foreach (catalogo() as $c) $k[$c['key']] = true;
    return $k;
}

function usuarios() {
    $rows = db_query("SELECT CODUSR, DENUSR, CATUSR FROM [Tbl Usuarios] ORDER BY DENUSR;");
    foreach ($rows as &$r) $r['CODUSR'] = (int) $r['CODUSR'];
    ok($rows);
}

/** Catálogo (agrupado por sección) + las claves activas del usuario. */
function cargar() {
    $uid = intval(isset($_GET['uid']) ? $_GET['uid'] : 0);
    if ($uid <= 0) { fail('Elegí un usuario.'); return; }
    // activos del usuario (sólo los que están en el catálogo)
    $cat = catalogo_keys();
    $activos = array();
    try {
        foreach (db_query("SELECT OPTWEB FROM [Tbl Usuarios Menu Web] WHERE CODUSR = $uid;") as $r) {
            $k = trim((string) $r['OPTWEB']);
            if ($k !== '' && isset($cat[$k])) $activos[] = $k;
        }
    } catch (Exception $e) {
        fail('La tabla [Tbl Usuarios Menu Web] no existe todavía. Corré cli/crear_tabla_menu_web.php primero.');
        return;
    }
    // agrupar el catálogo por sección
    $grupos = array();
    foreach (catalogo() as $c) $grupos[$c['section']][] = array('key' => $c['key'], 'label' => $c['label']);
    ok(array('grupos' => $grupos, 'activos' => $activos));
}

/** Guarda: borra-reinserta SÓLO las claves del catálogo (preserva otros CODMEN legacy del usuario). */
function guardar() {
    if (db_readonly()) { fail('Sistema en solo lectura.'); return; }
    $uid = intval(isset($_POST['uid']) ? $_POST['uid'] : 0);
    if ($uid <= 0) { fail('Falta el usuario.'); return; }
    $u = db_row("SELECT CODUSR FROM [Tbl Usuarios] WHERE CODUSR = $uid;");
    if (!$u) { fail('El usuario no existe.'); return; }

    $cat = catalogo_keys();
    $keys = isset($_POST['keys']) && is_array($_POST['keys']) ? $_POST['keys'] : array();
    // sólo claves válidas del catálogo (seguridad)
    $sel = array();
    foreach ($keys as $k) { $k = (string) $k; if (isset($cat[$k])) $sel[$k] = true; }

    $catList = "'" . implode("','", array_map('db_esc', array_keys($cat))) . "'";
    db_begin();
    try {
        db_exec("DELETE FROM [Tbl Usuarios Menu Web] WHERE CODUSR = $uid AND OPTWEB IN ($catList);");
        foreach (array_keys($sel) as $k) {
            db_exec("INSERT INTO [Tbl Usuarios Menu Web] (CODUSR, OPTWEB) VALUES ($uid, '" . db_esc($k) . "');");
        }
        db_commit();
    } catch (Exception $e) {
        db_rollback();
        throw $e;
    }
    ok(array('uid' => $uid, 'activas' => count($sel)));
}
