<?php
/**
 * Estadísticas de uso — lee los logs de tracking (logs/usage-YYYY-MM.csv) y agrega
 * por módulo / usuario / máquina / día para medir adopción del sistema nuevo.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_require_login();

$action = (isset($_GET['action']) ? $_GET['action'] : '');
try {
    switch ($action) {
        case 'stats': stats(); break;
        default: fail('Acción inválida: ' . $action);
    }
} catch (Exception $e) {
    fail($e->getMessage(), 500);
}

/** Lee las filas del rango [desde, hasta] de los CSV mensuales. */
function leerFilas($desde, $hasta) {
    $dir = __DIR__ . '/../../logs';
    $filas = array();
    // iterar meses desde→hasta
    $y = (int) substr($desde, 0, 4); $m = (int) substr($desde, 5, 2);
    $yh = (int) substr($hasta, 0, 4); $mh = (int) substr($hasta, 5, 2);
    while ($y < $yh || ($y === $yh && $m <= $mh)) {
        $f = $dir . '/usage-' . sprintf('%04d-%02d', $y, $m) . '.csv';
        if (is_file($f) && ($fh = @fopen($f, 'r'))) {
            while (($r = fgetcsv($fh)) !== false) {
                if (count($r) < 7) continue;
                $ts = $r[0];
                $dia = substr($ts, 0, 10);
                if ($dia < $desde || $dia > $hasta) continue;
                $filas[] = array('ts' => $ts, 'dia' => $dia, 'uid' => $r[1], 'user' => $r[2],
                    'ip' => $r[3], 'host' => $r[4], 'mod' => $r[5], 'path' => $r[6]);
            }
            fclose($fh);
        }
        $m++; if ($m > 12) { $m = 1; $y++; }
    }
    return $filas;
}

function stats() {
    $desde = trim((isset($_GET['desde']) ? $_GET['desde'] : ''));
    $hasta = trim((isset($_GET['hasta']) ? $_GET['hasta'] : ''));
    if ($desde === '') $desde = date('Y-m-d', strtotime('-29 days'));
    if ($hasta === '') $hasta = date('Y-m-d');

    $filas = leerFilas($desde, $hasta);

    $porMod = array(); $porUsr = array(); $porMaq = array(); $porDia = array();
    $usuarios = array(); $maquinas = array();

    foreach ($filas as $f) {
        $usuarios[$f['user']] = 1;
        $maquinas[$f['ip']] = 1;

        // por módulo
        $k = $f['mod'];
        if (!isset($porMod[$k])) $porMod[$k] = array('modulo' => $k, 'hits' => 0, 'ultimo' => '');
        $porMod[$k]['hits']++; if ($f['ts'] > $porMod[$k]['ultimo']) $porMod[$k]['ultimo'] = $f['ts'];

        // por usuario
        $u = $f['user'] !== '' ? $f['user'] : ('uid ' . $f['uid']);
        if (!isset($porUsr[$u])) $porUsr[$u] = array('user' => $u, 'hits' => 0, 'ultimo' => '', '_maq' => array());
        $porUsr[$u]['hits']++; if ($f['ts'] > $porUsr[$u]['ultimo']) $porUsr[$u]['ultimo'] = $f['ts'];
        $porUsr[$u]['_maq'][$f['ip']] = 1;

        // por máquina
        $mq = $f['ip'];
        if (!isset($porMaq[$mq])) $porMaq[$mq] = array('ip' => $mq, 'host' => $f['host'], 'hits' => 0, 'ultimo' => '', '_usr' => array());
        $porMaq[$mq]['hits']++; if ($f['ts'] > $porMaq[$mq]['ultimo']) $porMaq[$mq]['ultimo'] = $f['ts'];
        if ($f['host'] !== '') $porMaq[$mq]['host'] = $f['host'];
        $porMaq[$mq]['_usr'][$u] = 1;

        // por día
        if (!isset($porDia[$f['dia']])) $porDia[$f['dia']] = 0;
        $porDia[$f['dia']]++;
    }

    // contar distinct máquinas/usuarios y limpiar internos
    foreach ($porUsr as &$u) { $u['maquinas'] = count($u['_maq']); unset($u['_maq']); } unset($u);
    foreach ($porMaq as &$m) { $m['usuarios'] = count($m['_usr']); unset($m['_usr']); } unset($m);

    // ordenar por hits desc
    $byHits = function ($a, $b) { return $b['hits'] - $a['hits']; };
    $vMod = array_values($porMod); usort($vMod, $byHits);
    $vUsr = array_values($porUsr); usort($vUsr, $byHits);
    $vMaq = array_values($porMaq); usort($vMaq, $byHits);
    ksort($porDia);
    $vDia = array();
    foreach ($porDia as $d => $n) $vDia[] = array('dia' => $d, 'hits' => $n);

    ok(array(
        'desde' => $desde, 'hasta' => $hasta,
        'kpis' => array(
            'hits' => count($filas),
            'usuarios' => count($usuarios),
            'maquinas' => count($maquinas),
            'dias' => count($porDia),
        ),
        'porModulo'  => $vMod,
        'porUsuario' => $vUsr,
        'porMaquina' => $vMaq,
        'porDia'     => $vDia,
    ));
}
