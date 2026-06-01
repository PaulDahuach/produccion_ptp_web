<?php
/**
 * Programación de Órdenes — API.
 * Portado de: Frm Programacion / Frm Programacion_Procesos (Form_BeforeUpdate al
 * marcar WPXODP). Tablero de órdenes definidas (CODETA=30) esperando ser liberadas
 * a producción. "Programar" = marca WPXODP y despacha la orden al primer sector de
 * su ruta (o a Adelantos si CODDST=2), gestionando el lote. Todo en transacción.
 * (El optimizador automático mdlProgramar/PreProgramar quedó deshabilitado en el
 * legacy y NO se porta.)
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_require_login();

$action = (isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : ''));
try {
    switch ($action) {
        case 'list':     listar(); break;
        case 'programar': programar(); break;
        default: fail('Acción inválida: ' . $action);
    }
} catch (Exception $e) {
    fail($e->getMessage(), 500);
}

/** Cola de programación: órdenes CODETA=30, primer proceso (ORDPTP=1), por sector. */
function listar() {
    $sql = "SELECT O.NUMODP AS ODP, E.DENETA AS SECTOR, Prc.DENPRC AS PROCESO,
              C.DENCLI AS CLIENTE, M.DENMAR AS MARCA, Pre.DENPRE AS PRENDA,
              O.CANODP AS CANTIDAD, O.CODDST AS CODDST
            FROM ((((([Tbl Ordenes En Proceso] AS OEP
              INNER JOIN [Tbl Ordenes De Proceso] AS O ON OEP.NUMODP = O.NUMODP)
              LEFT JOIN [Tbl Procesos] AS Prc ON OEP.CODPRC = Prc.CODPRC)
              LEFT JOIN [Tbl Etapas] AS E ON Prc.CODETA = E.CODETA)
              LEFT JOIN [Tbl Clientes] AS C ON O.CODCLI = C.CODCLI)
              LEFT JOIN [Tbl Marcas] AS M ON O.CODMAR = M.CODMAR)
              LEFT JOIN [Tbl Prendas] AS Pre ON O.CODPR1 = Pre.CODPRE
            WHERE OEP.ORDPTP = 1 AND O.CODETA = 30
            ORDER BY E.CODETA, O.NUMODP;";
    ok(db_query($sql));
}

/** Libera la orden a producción (marca WPXODP + despacho). */
function programar() {
    if (db_readonly()) { fail('Sistema en modo solo-lectura', 403); return; }
    $id = intval((isset($_POST['__id']) ? $_POST['__id'] : 0));
    if ($id <= 0) { fail('Falta la orden'); return; }

    $o = db_row("SELECT CODETA, CODDST, CANODP FROM [Tbl Ordenes De Proceso] WHERE NUMODP = $id;");
    if (!$o) { fail('Orden no encontrada'); return; }
    if (intval($o['CODETA']) !== 30) { fail('La orden no está en programación (CODETA=' . $o['CODETA'] . ')'); return; }
    $cant   = intval($o['CANODP']);
    $coddst = intval($o['CODDST']);

    // Primer proceso (ORDPTP=1) y su sector destino
    $oep = db_row("SELECT CODPRC FROM [Tbl Ordenes En Proceso] WHERE NUMODP = $id AND ORDPTP = 1;");
    if (!$oep) { fail('La orden no tiene procesos definidos'); return; }
    $prc = db_row("SELECT CODETA FROM [Tbl Procesos] WHERE CODPRC = " . intval($oep['CODPRC']) . ";");
    $sectorDst = intval((isset($prc['CODETA']) ? $prc['CODETA'] : 0));

    $rc = db_row("SELECT FECAPE FROM [Rec Control];");
    $iso = to_iso_date($rc['FECAPE']);
    $p = explode('-', $iso);
    $fa = "#{$p[1]}/{$p[2]}/{$p[0]}#";

    db_begin();
    try {
        if ($coddst === 2) {
            // Adelantos
            db_exec("UPDATE [Tbl Ordenes De Proceso] SET PRGODP=0, CODETA=35 WHERE NUMODP=$id;");
            $ex = db_row("SELECT COUNT(*) AS n FROM [Tbl Ordenes De Proceso Adelantos] WHERE NUMODP=$id;");
            if (!$ex || (int) $ex['n'] === 0) db_exec("INSERT INTO [Tbl Ordenes De Proceso Adelantos] ([NUMODP]) VALUES ($id);");
            $ordD = '0'; $csd = '35';
        } else {
            // Normal: pasa al sector del primer proceso
            db_exec("UPDATE [Tbl Ordenes De Proceso] SET PRGODP=0, CODETA=$sectorDst WHERE NUMODP=$id;");
            db_exec("UPDATE [Tbl Ordenes De Proceso Procesos] SET CANODP=$cant, DSPODP=$cant WHERE NUMODP=$id AND ORDODP=1;");
            $ordD = '1'; $csd = (string) $sectorDst;
        }

        // Descomprometer el lote de programación y crear el lote hacia el primer sector
        db_exec("UPDATE [Tbl Ordenes De Proceso Lotes] SET DSPODP=0 WHERE NUMODP=$id AND ORDODP=-1 AND LOTODP=1;");
        $lc = ['NUMODP', 'LOTODP', 'FEXODP', 'FIPODP', 'HIPODP', 'FFPODP', 'HFPODP',
               'CANODP', 'REZODP', 'DSPODP', 'ORDODP', 'CSDODP', 'OPOODP', 'CSOODP', 'LPOODP'];
        $lv = [(string) $id, '1', $fa, $fa, 'Now()', $fa, 'Now()',
               (string) $cant, '0', (string) $cant, $ordD, $csd, '-1', '30', '1'];
        db_exec("INSERT INTO [Tbl Ordenes De Proceso Lotes] ([" . implode('],[', $lc) . "]) VALUES (" . implode(',', $lv) . ");");

        // Marcar el OEP del primer proceso como procesado/programado
        db_exec("UPDATE [Tbl Ordenes En Proceso] SET WPXODP=True WHERE NUMODP=$id AND ORDPTP=1;");

        db_commit();
        ok(['numodp' => $id, 'sector' => $sectorDst, 'adelantos' => ($coddst === 2)]);
    } catch (Exception $e) {
        db_rollback();
        fail('No se pudo programar: ' . $e->getMessage(), 500);
    }
}
