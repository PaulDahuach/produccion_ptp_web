<?php
/**
 * crear_tabla_menu_web.php — UN SOLO USO. Crea e inicializa [Tbl Usuarios Menu Web] si no existe.
 *
 * Esta tabla pasa a ser la ÚNICA fuente de restricciones de la WEB (independencia total del legacy):
 * - Copia TODOS los permisos de [Tbl Usuarios Menu] (cada CODMEN como clave string, ej. '410').
 * - Agrega las opciones WEB-native (Supervisores/Localidades/Provincias) a todos (preserva acceso actual).
 * Después de correrlo, la web lee SOLO esta tabla; [Tbl Usuarios Menu] queda para el legacy (que la sigue
 * usando en su rutAccesoUsuario, intacto). Así el menú web es 100% independiente: agregás/sacás opciones
 * sin tocar el legacy ni arriesgar su loop (que explota con un CODMEN sin control).
 *
 * Modelo: WHITELIST (la fila (CODUSR, OPTWEB) = opción PERMITIDA). Idempotente: si la tabla ya existe, no
 * hace nada. CREATE TABLE no necesita lock exclusivo → seguro con la DB abierta.
 *
 * Uso CLI:  php -d extension=php_com_dotnet.dll cli\crear_tabla_menu_web.php
 * O por navegador logueado como admin:  http://.../produccion_ptp/cli/crear_tabla_menu_web.php  (borralo después).
 */
require __DIR__ . '/../includes/db.php';

if (PHP_SAPI !== 'cli') {
    require __DIR__ . '/../includes/auth.php';
    auth_require_admin();
    header('Content-Type: text/plain; charset=utf-8');
}

// Opciones WEB-native a otorgar a todos desde el arranque (preservar acceso actual).
$OPTWEB_INI = array('supervisores', 'localidades', 'provincias');

// ¿Ya existe la tabla?
$existe = true;
try {
    db_query("SELECT TOP 1 CODUSR FROM [Tbl Usuarios Menu Web];");
} catch (Exception $e) {
    $existe = false;
}
if ($existe) {
    echo "[Tbl Usuarios Menu Web] ya existe -> no se hace nada (idempotente).\n";
    exit;
}

// Crear.
db_exec("CREATE TABLE [Tbl Usuarios Menu Web] ([CODUSR] LONG, [OPTWEB] TEXT(50));");
echo "Tabla [Tbl Usuarios Menu Web] creada (CODUSR LONG, OPTWEB TEXT(50)).\n";

// Inicializar.
$nl = 0; $nw = 0;
db_begin();
try {
    // 1) Copiar TODOS los permisos legacy: CODMEN -> clave string. La web se vuelve independiente.
    foreach (db_query("SELECT CODUSR, CODMEN FROM [Tbl Usuarios Menu];") as $r) {
        $uid = (int) $r['CODUSR'];
        $k = trim((string) $r['CODMEN']);
        if ($k === '') continue;
        db_exec("INSERT INTO [Tbl Usuarios Menu Web] (CODUSR, OPTWEB) VALUES ($uid, '" . db_esc($k) . "');");
        $nl++;
    }
    // 2) Opciones web-native a todos los usuarios (preservar acceso actual).
    foreach (db_query("SELECT CODUSR FROM [Tbl Usuarios];") as $u) {
        $uid = (int) $u['CODUSR'];
        foreach ($OPTWEB_INI as $k) {
            db_exec("INSERT INTO [Tbl Usuarios Menu Web] (CODUSR, OPTWEB) VALUES ($uid, '" . db_esc($k) . "');");
            $nw++;
        }
    }
    db_commit();
} catch (Exception $e) {
    db_rollback();
    throw $e;
}
echo "Inicializada: $nl permisos legacy copiados + $nw web-native = " . ($nl + $nw) . " filas.\n";
echo "Independencia total: la web ya lee SOLO [Tbl Usuarios Menu Web]. El legacy sigue con [Tbl Usuarios Menu].\n";
echo "Listo. Borra este archivo. Para habilitar/deshabilitar una opcion a un usuario, agrega/quita su fila.\n";
