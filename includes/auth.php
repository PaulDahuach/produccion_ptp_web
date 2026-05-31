<?php
/**
 * inforemp-web-kit — Autenticación contra la tabla de usuarios del legacy.
 *
 * Replica el flujo de RDN (clave en texto plano en [Tbl Usuarios]), pero con
 * la tabla/columnas tomadas de config/system.php → 'auth'.
 *
 * NOTA de seguridad: el legacy guarda la clave en texto plano. Lo respetamos
 * para convivir, pero el acceso web SIEMPRE debe ir detrás de HTTPS (Certbot).
 */

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('IWKSESSID');
    session_start();
}

/** Redirige a login si no hay sesión. Llamar al tope de páginas protegidas. */
function auth_require_login($login_url = null) {
    if (empty($_SESSION['uid'])) {
        header('Location: ' . ($login_url ?: bu('/app/login.php')));
        exit;
    }
}

/** Nombre del usuario logueado. */
function auth_user() {
    return $_SESSION['uname'] ?? 'Usuario';
}

/** Busca un usuario por su contraseña (paso 1 del login, como RDN). */
function auth_lookup_by_pass($pass) {
    $a = sys('auth');
    $sql = "SELECT [{$a['col_id']}] AS id, [{$a['col_name']}] AS name "
         . "FROM [{$a['table']}] WHERE [{$a['col_pass']}]='" . db_esc($pass) . "';";
    return db_row($sql);
}

/** Valida id+nombre+clave y abre sesión (paso 2). */
function auth_login($id, $name, $pass) {
    $a = sys('auth');
    $sql = "SELECT [{$a['col_id']}] AS id FROM [{$a['table']}] WHERE "
         . "[{$a['col_id']}]=" . intval($id) . " AND "
         . "[{$a['col_name']}]='" . db_esc($name) . "' AND "
         . "[{$a['col_pass']}]='" . db_esc($pass) . "';";
    $row = db_row($sql);
    if ($row) {
        $_SESSION['uid']   = $id;
        $_SESSION['uname'] = $name;
        return true;
    }
    return false;
}

/** Cierra la sesión. */
function auth_logout() {
    $_SESSION = [];
    session_destroy();
}
