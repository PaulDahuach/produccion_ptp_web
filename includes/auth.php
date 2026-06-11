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

/** True si este sistema usa login sectorizado (opt-in via config 'sector_login'). */
function auth_sector_login() {
    return !empty(sys('sector_login'));
}

/** True si hay sesión iniciada. Robusto ante uid=0 (operarios pueden tener CODOPR=0). */
function auth_logged_in() {
    return isset($_SESSION['uid']) && $_SESSION['uid'] !== '' && $_SESSION['uid'] !== null;
}

/** Exige sesión iniciada (sólo usuario). Para páginas que aún no requieren sector. */
function auth_require_user($login_url = null) {
    if (!auth_logged_in()) {
        header('Location: ' . ($login_url ?: bu('/app/login.php')));
        exit;
    }
}

/**
 * Redirige a login si no hay sesión. Llamar al tope de páginas protegidas.
 * Si el sistema usa sector_login y todavía no se eligió sector, manda a elegirlo.
 */
function auth_require_login($login_url = null) {
    auth_require_user($login_url);
    if (auth_sector_login() && empty($_SESSION['sector'])) {
        header('Location: ' . bu('/app/sector.php'));
        exit;
    }
}

/** Nombre del usuario logueado. */
function auth_user() {
    return (isset($_SESSION['uname']) ? $_SESSION['uname'] : 'Usuario');
}

/** ¿El usuario actual es administrador? Lista en config 'admin_users' (por CODUSR o por
 *  nombre/DENUSR). Si no está configurada, NADIE es admin (default seguro). */
function auth_is_admin() {
    $admins = sys('admin_users', array());
    if (!is_array($admins) || !count($admins)) return false;
    $uid = (isset($_SESSION['uid'])   ? (string) $_SESSION['uid']   : '');
    $un  = (isset($_SESSION['uname']) ? (string) $_SESSION['uname'] : '');
    foreach ($admins as $a) {
        $a = trim((string) $a);
        if ($a !== '' && ($a === $uid || strcasecmp($a, $un) === 0)) return true;
    }
    return false;
}

/** Exige sesión + permiso de admin (para páginas HTML). */
function auth_require_admin() {
    auth_require_login();
    if (!auth_is_admin()) {
        http_response_code(403);
        die('<!doctype html><meta charset="utf-8"><div style="font-family:system-ui;padding:2rem;color:#b91c1c">'
            . 'Acceso restringido. Esta secci&oacute;n es s&oacute;lo para administradores.</div>');
    }
}

/* ─────────────────────────── Restricciones de menú (LISTA BLANCA) ───────────────────────────
 * PRODUCCION = lista BLANCA (opuesto a administración). El rutAccesoUsuario del Menú legacy deshabilita
 * TODAS las opciones y luego HABILITA sólo los CODMEN que el usuario tiene en [Tbl Usuarios Menu]. O sea
 * las filas = lo PERMITIDO. Acá las opciones se identifican por CODMEN (entero; produccion no tiene
 * OPTMEN). Una entrada del menú con 'opt'=CODMEN se ve sólo si ese CODMEN está en la lista del usuario;
 * las entradas SIN 'opt' (consultas web nativas, sin equivalente legacy) SIEMPRE se ven. Admins exentos.
 * Flag 'menu_restrict' (default true) → en false desactiva todo (web permisiva). */

/** CODMEN PERMITIDOS del usuario actual (assoc codmen=>true), de [Tbl Usuarios Menu]. Cacheado por
 *  request. Sistemas sin las tablas → vacío. */
function auth_allowed_opts() {
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = array();
    if (!auth_logged_in()) return $cache;
    $uid = intval($_SESSION['uid']);
    try {
        foreach (db_query("SELECT CODMEN FROM [Tbl Usuarios Menu] WHERE CODUSR=$uid;") as $r) {
            $o = trim((string) $r['CODMEN']);
            if ($o !== '') $cache[$o] = true;
        }
    } catch (Exception $e) { $cache = array(); }
    return $cache;
}

/** ¿La opción (CODMEN) está restringida para el usuario actual? LISTA BLANCA: restringida si la entrada
 *  TIENE 'opt' y ese CODMEN NO está en los permitidos. Sin 'opt' (web nativo) = nunca. Admin/flag exentos. */
function auth_opt_denied($opt) {
    if ($opt === null || $opt === '') return false;     // sin opt = consulta web nativa = siempre visible
    if (auth_is_admin()) return false;
    if (!sys('menu_restrict', true)) return false;       // escape hatch: web permisiva si se desactiva
    $allowed = auth_allowed_opts();
    return !isset($allowed[(string) $opt]);              // restringida si NO está en la lista blanca
}

/** Clave 'dir|disc' de una URL de módulo (disc = m|modo|r si está). Une el menú con el request. */
function _auth_url_key($path, $query) {
    if (!preg_match('#/modules/([^/?]+)#', (string) $path, $mm)) return '';
    $disc = '';
    if ($query) { parse_str($query, $q);
        foreach (array('m', 'modo', 'r') as $d) if (isset($q[$d]) && $q[$d] !== '') { $disc = $d . '=' . $q[$d]; break; } }
    return $mm[1] . '|' . $disc;
}

/** Mapa 'dir|disc' → CODMEN, derivado del menú (config). */
function _auth_opt_map() {
    static $map = null;
    if ($map !== null) return $map;
    $map = array();
    $menu = sys('menu', array());
    if (is_array($menu)) foreach ($menu as $cards) {
        if (!is_array($cards)) continue;
        foreach ($cards as $it) {
            if (empty($it['url']) || empty($it['opt'])) continue;
            $p = parse_url($it['url']);
            $k = _auth_url_key(isset($p['path']) ? $p['path'] : '', isset($p['query']) ? $p['query'] : '');
            if ($k !== '') $map[$k] = $it['opt'];
        }
    }
    return $map;
}

/** CODMEN del request actual (o '' si el módulo no mapea a una opción del legacy). */
function auth_current_opt() {
    $sn = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    if (strpos($sn, '/modules/') === false) return '';
    $q = '';
    foreach (array('m', 'modo', 'r') as $d) {   // discriminador (GET o POST)
        $v = isset($_GET[$d]) ? $_GET[$d] : (isset($_POST[$d]) ? $_POST[$d] : null);
        if ($v !== null && $v !== '') { $q = $d . '=' . $v; break; }
    }
    $map = _auth_opt_map();
    $key = _auth_url_key($sn, $q);
    if ($key !== '' && isset($map[$key])) return $map[$key];
    if (preg_match('#/modules/([^/?]+)#', $sn, $mm) && isset($map[$mm[1] . '|'])) return $map[$mm[1] . '|'];
    return '';
}

/** Gate automático de acceso por URL: bloquea si la opción del request está restringida. */
function auth_gate_url() {
    if (!auth_logged_in()) return;
    $opt = auth_current_opt();
    if ($opt === '' || !auth_opt_denied($opt)) return;
    http_response_code(403);
    $sn = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    if (strpos($sn, 'api') !== false) {
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode(array('ok' => false, 'error' => 'Acceso restringido a esta opción.')));
    }
    die('<!doctype html><meta charset="utf-8"><div style="font-family:system-ui;padding:2rem;color:#b91c1c">'
      . 'Acceso restringido. No ten&eacute;s permiso para esta opci&oacute;n.</div>');
}

/** Sectores que puede operar el usuario actual (config 'sector_login'). */
function auth_sectors() {
    $sl = sys('sector_login');
    if (!$sl) return [];
    $uid = intval((isset($_SESSION['uid']) ? $_SESSION['uid'] : 0));
    $sql = "SELECT DISTINCT S.[{$sl['sec_pk']}] AS id, S.[{$sl['sec_den']}] AS den
            FROM [{$sl['rel_tabla']}] AS R INNER JOIN [{$sl['sec_tabla']}] AS S
              ON R.[{$sl['rel_sector']}] = S.[{$sl['sec_pk']}]
            WHERE R.[{$sl['rel_fk']}] = $uid ORDER BY S.[{$sl['sec_den']}];";
    return db_query($sql);
}

/** Fija el sector activo en la sesión. */
function auth_set_sector($cod, $name) {
    $_SESSION['sector'] = $cod;
    $_SESSION['sector_name'] = $name;
}

function auth_sector()      { return (isset($_SESSION['sector']) ? $_SESSION['sector'] : null); }
function auth_sector_name() { return (isset($_SESSION['sector_name']) ? $_SESSION['sector_name'] : ''); }

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

// Gate de acceso por URL (lista negra Tbl Usuarios Menu, porta rutAccesoUsuario del Menú legacy).
// Sólo afecta /modules/*; no-op sin sesión o si la opción no está restringida.
auth_gate_url();
