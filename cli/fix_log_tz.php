<?php
/**
 * fix_log_tz.php — Corrige las horas de los CSV de logs PREEXISTENTES (perf / perf-slow / usage) que se
 * escribieron en UTC, restándoles el offset para dejarlos en hora de Argentina (UTC-3).
 *
 * Sólo toca la 1ª columna de cada fila (el timestamp "YYYY-MM-DD HH:MM:SS"); el resto queda intacto.
 * Antes de tocar cada archivo hace un backup `<archivo>.bak`. IDEMPOTENTE: si ya existe el .bak, saltea
 * ese archivo (para no restar dos veces si lo corrés de nuevo).
 *
 * ⚠️ ADVERTENCIA: usar SÓLO sobre un archivo escrito ENTERAMENTE en UTC (todas sus filas anteriores al
 * deploy del fix de timezone en db.php). Si el archivo está MEZCLADO (filas viejas en UTC + filas nuevas
 * ya en ART, porque cruza el momento del deploy), este script les resta 3h también a las que ya estaban
 * bien → las rompe. Para un archivo mezclado, lo correcto es archivarlo y arrancar uno nuevo (que se
 * generará 100% en ART con el db.php ya deployado), NO correr este script.
 *
 * Uso (CLI):
 *   php fix_log_tz.php [carpeta_logs] [horas]
 *     carpeta_logs : default = ../logs respecto de este script (si existe), si no el dir actual.
 *     horas        : a restar; default 3 (UTC → Argentina UTC-3).
 *
 * Ejemplos:
 *   php cli/fix_log_tz.php                         (usa ./logs, -3h)
 *   php fix_log_tz.php C:\ruta\a\logs 3            (otra carpeta)
 */

date_default_timezone_set('UTC');   // parseo/formateo en UTC → el cálculo es un shift limpio de -N horas

$dir = isset($argv[1]) && $argv[1] !== '' ? rtrim($argv[1], "/\\") : (is_dir(__DIR__ . '/../logs') ? __DIR__ . '/../logs' : '.');
$hours = isset($argv[2]) ? (int) $argv[2] : 3;
$off = $hours * 3600;

if (!is_dir($dir)) { fwrite(STDERR, "No existe la carpeta: $dir\n"); exit(1); }

// perf-*.csv ya incluye perf-slow-*.csv (ambos empiezan con "perf-")
$files = array_merge(glob("$dir/perf-*.csv"), glob("$dir/usage-*.csv"));
$files = array_filter($files, function ($f) { return substr($f, -4) === '.csv'; });
if (!$files) { echo "Sin CSV de logs en: $dir\n"; exit(0); }

$re = '/^"(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})"/';
$totFilas = 0;

foreach ($files as $f) {
    $bak = $f . '.bak';
    if (file_exists($bak)) { echo basename($f) . ": ya tiene .bak → SALTEADO (no re-resta)\n"; continue; }

    $lines = file($f, FILE_IGNORE_NEW_LINES);
    if ($lines === false) { echo basename($f) . ": no se pudo leer\n"; continue; }

    $n = 0; $out = array();
    foreach ($lines as $ln) {
        $ln = rtrim($ln, "\r");
        $out[] = preg_replace_callback($re, function ($m) use ($off, &$n) {
            $ts = mktime((int) $m[4], (int) $m[5], (int) $m[6], (int) $m[2], (int) $m[3], (int) $m[1]) - $off;
            $n++;
            return '"' . date('Y-m-d H:i:s', $ts) . '"';
        }, $ln);
    }

    if (!copy($f, $bak)) { echo basename($f) . ": no se pudo crear el backup → SALTEADO\n"; continue; }
    file_put_contents($f, implode("\r\n", $out) . "\r\n");
    $totFilas += $n;
    echo basename($f) . ": $n filas ajustadas (-{$hours}h) · backup → " . basename($bak) . "\n";
}

echo "Listo. $totFilas filas corregidas en total.\n";
echo "(Si algo salió mal, restaurá con los archivos .bak.)\n";
