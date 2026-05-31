<?php
/** Exportación a Excel — Movimientos de Lotes (.xls HTML), según nivel. */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_require_login();
require __DIR__ . '/_query.php';

$res = movimientos_rows();
$isDet = ($res['nivel'] === 'detalle');
$rows = $res['rows'];
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="MovimientosLotes_' . date('Y-m-d') . '.xls"');
echo "\xEF\xBB\xBF";
echo '<table border="1">';
if ($isDet) {
    echo '<tr><th>Fecha</th><th>Hora</th><th>Tipo</th><th>ODP N°</th><th>Orden</th><th>Lote</th><th>Sector</th><th>Proceso</th><th>S. Personal</th><th>Planta</th><th>Ingreso</th><th>Egreso</th></tr>';
    foreach ($rows as $r) {
        echo '<tr><td>' . h($r['FECHA']) . '</td><td>' . h($r['HORA']) . '</td><td>' . h($r['TIPO']) . '</td><td>' . h($r['ODP']) . '</td><td>' . h($r['ORDEN']) . '</td><td>' . h($r['LOTE']) . '</td><td>' . h($r['SECTOR']) . '</td><td>' . h($r['PROCESO']) . '</td><td>' . h($r['SECTORP']) . '</td><td>' . h($r['PLANTA']) . '</td><td>' . h($r['INGMOV']) . '</td><td>' . h($r['EGRMOV']) . '</td></tr>';
    }
} else {
    $gl = $res['nivel'] === 'personal' ? 'Sector Personal' : ($res['nivel'] === 'planta' ? 'Planta' : 'Sector Producción');
    echo '<tr><th>' . h($gl) . '</th><th>Ingresos</th><th>Egresos</th><th>Neto</th><th>Movimientos</th></tr>';
    foreach ($rows as $r) {
        echo '<tr><td>' . h($r['GRUPO']) . '</td><td>' . h($r['ING']) . '</td><td>' . h($r['EGR']) . '</td><td>' . h((float)$r['ING'] - (float)$r['EGR']) . '</td><td>' . h($r['MOVS']) . '</td></tr>';
    }
}
echo '</table>';
