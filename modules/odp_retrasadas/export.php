<?php
/** Exportación a Excel — Órdenes de Proceso Retrasadas (.xls HTML). */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_require_login();
require __DIR__ . '/_query.php';

$rows = retrasadas_rows();
$dias = intval($_GET['dias'] ?? 0);
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="Retrasadas_' . date('Y-m-d') . '.xls"');
echo "\xEF\xBB\xBF";
$cols = ['DIAS_DEF' => 'Días Def', 'DIAS_REC' => 'Días Rec', 'SECTOR' => 'Sector', 'ODP' => 'ODP N°',
         'CLIENTE' => 'Cliente', 'PRENDA' => 'Prenda', 'MARCA' => 'Marca', 'OCORTE' => 'O. Corte',
         'CARTICULO' => 'C. Artículo', 'PTP' => 'PTP N°', 'CANTIDAD' => 'Cantidad'];
echo '<table border="1"><tr><th colspan="' . count($cols) . '">Órdenes Retrasadas — más de ' . h($dias) . ' días definidas (' . date('d/m/Y') . ')</th></tr><tr>';
foreach ($cols as $lbl) echo '<th>' . h($lbl) . '</th>';
echo '</tr>';
$tot = 0;
foreach ($rows as $r) {
    echo '<tr>';
    foreach ($cols as $k => $lbl) echo '<td>' . h($r[$k]) . '</td>';
    echo '</tr>';
    $tot += (float)$r['CANTIDAD'];
}
echo '<tr><th colspan="10" style="text-align:right">TOTAL</th><th>' . h(number_format($tot, 0, ',', '.')) . '</th></tr></table>';
