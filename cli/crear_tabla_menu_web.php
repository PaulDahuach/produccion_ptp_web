<?php
/**
 * crear_tabla_menu_web.php — UN SOLO USO. Crea e inicializa [Tbl Usuarios Menu Web] si no existe.
 *
 * Esta tabla es el canal de restricciones WEB-ONLY: la lee SOLO la web (auth.php), el legacy NUNCA →
 * así podemos gatear opciones web-native (que no tienen control en el Menú legacy) SIN que el loop
 * rutAccesoUsuario explote (ese loop hace Controls("Opción"&CODMEN).Enabled=True sin On Error, así que
 * un CODMEN inexistente lo rompería — por eso NO van en [Tbl Usuarios Menu]).
 *
 * Modelo: WHITELIST (igual que [Tbl Usuarios Menu]): la fila (CODUSR, OPTWEB) = opción PERMITIDA.
 * Inicialización: otorga a TODOS los usuarios actuales las opciones gateadas → PRESERVA el acceso actual
 * (nadie pierde nada hoy). Después se deshabilita por usuario quitando su fila (o desde el editor).
 *
 * Idempotente: si la tabla ya existe, no hace nada.
 *
 * Uso CLI (en el server):
 *   php -d extension=php_com_dotnet.dll cli\crear_tabla_menu_web.php
 * O por navegador logueado como admin: http://.../produccion_ptp/cli/crear_tabla_menu_web.php
 * (borralo después de correrlo).
 */
require __DIR__ . '/../includes/db.php';

// Si se corre por navegador, exigir admin (por CLI no hay sesión → se permite).
if (PHP_SAPI !== 'cli') {
    require __DIR__ . '/../includes/auth.php';
    auth_require_admin();
    header('Content-Type: text/plain; charset=utf-8');
}

// Opciones web a gatear desde el arranque (se otorgan a todos para preservar el acceso actual).
$OPTWEB_INI = array('supervisores', 'localidades', 'provincias');

// ¿Ya existe la tabla?
$existe = true;
try {
    db_query("SELECT TOP 1 CODUSR FROM [Tbl Usuarios Menu Web];");
} catch (Exception $e) {
    $existe = false;
}

if ($existe) {
    echo "[Tbl Usuarios Menu Web] ya existe → no se hace nada (idempotente).\n";
    exit;
}

// Crear (CREATE TABLE no necesita lock exclusivo → seguro con la DB abierta).
db_exec("CREATE TABLE [Tbl Usuarios Menu Web] ([CODUSR] LONG, [OPTWEB] TEXT(50));");
echo "Tabla [Tbl Usuarios Menu Web] creada (CODUSR LONG, OPTWEB TEXT(50)).\n";

// Inicializar: otorgar a todos los usuarios actuales las opciones gateadas (preserva acceso actual).
$users = db_query("SELECT CODUSR FROM [Tbl Usuarios];");
$n = 0;
db_begin();
try {
    foreach ($users as $u) {
        $uid = (int) $u['CODUSR'];
        foreach ($OPTWEB_INI as $k) {
            db_exec("INSERT INTO [Tbl Usuarios Menu Web] (CODUSR, OPTWEB) VALUES ($uid, '" . db_esc($k) . "');");
            $n++;
        }
    }
    db_commit();
} catch (Exception $e) {
    db_rollback();
    throw $e;
}
echo "Inicializada: $n filas (" . count($users) . " usuarios × " . count($OPTWEB_INI) . " opciones). Acceso actual preservado.\n";
echo "Listo. Borrá este archivo. Para deshabilitar una opción a un usuario, quitá su fila (o usá el editor).\n";
