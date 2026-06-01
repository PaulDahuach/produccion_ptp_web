<?php
/**
 * Query compartida de Movimientos de Lotes (api/print/export). Portada de las
 * action queries "wrk Movimientos Lotes 02 Ingresos / 03 Egresos" → temporal unificada.
 * Cada lote con FFPODP (fecha fin de proceso) genera:
 *   - INGRESO (+CANODP) al sector del proceso actual (L.ORDODP).
 *   - EGRESO  (−CANODP) del sector del proceso de origen (L.OPOODP).
 * Filtros: rango de fechas sobre FFPODP (serial Access), sector (CODETA) opcional.
 * 'nivel': detalle | produccion (etapa) | personal (sector personal) | planta.
 */

/** dd/mm/aaaa → serial Access (días desde 1899-12-30). '' → null. */
function _mov_serial($ddmmaaaa) {
    $s = trim((string)$ddmmaaaa);
    if ($s === '') return null;
    $iso = fecha_access($s); // usa el helper del kit → 'mm/dd/yyyy'
    $ts = strtotime(str_replace('/', '-', date('Y-m-d', strtotime($iso))));
    // recomputar robusto desde dd/mm/aaaa
    if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})#', $s, $m)) {
        $ts = mktime(0, 0, 0, (int)$m[2], (int)$m[1], (int)$m[3]);
    }
    $base = mktime(0, 0, 0, 12, 30, 1899);
    return (int) round(($ts - $base) / 86400);
}

function movimientos_rows() {
    $desde = _mov_serial((isset($_GET['desde']) ? $_GET['desde'] : ''));
    $hasta = _mov_serial((isset($_GET['hasta']) ? $_GET['hasta'] : ''));
    $sector = trim((isset($_GET['sector']) ? $_GET['sector'] : ''));   // CODETA (sector producción) opcional
    $nivel = strtolower(trim((isset($_GET['nivel']) ? $_GET['nivel'] : 'detalle')));

    $fIng = ['(L.FFPODP Is Not Null)'];
    $fEgr = ['(L.FFPODP Is Not Null)'];
    if ($desde !== null) { $fIng[] = "(L.FFPODP >= $desde)"; $fEgr[] = "(L.FFPODP >= $desde)"; }
    if ($hasta !== null) { $fIng[] = "(L.FFPODP <= $hasta)"; $fEgr[] = "(L.FFPODP <= $hasta)"; }
    if ($sector !== '') {
        $fIng[] = '(P.CODETA = ' . intval($sector) . ')';
        $fEgr[] = '(P.CODETA = ' . intval($sector) . ')';
    }
    $wIng = implode(' AND ', $fIng);
    $wEgr = implode(' AND ', $fEgr);

    // INGRESO: lote en su proceso actual (L.ORDODP). Sector = etapa del proceso.
    $ing = "SELECT 1 AS ORDMOV, L.NUMODP AS ODP, L.ORDODP AS ORDEN, L.LOTODP AS LOTE,
              L.CANODP AS INGMOV, 0 AS EGRMOV, L.FFPODP AS FFP, L.HFPODP AS HFP,
              E.DENETA AS SECTOR, Prc.DENPRC AS PROCESO, SP.DENSEC AS SECTORP, PP.DENPLA AS PLANTA
            FROM ((((([Tbl Ordenes De Proceso Lotes] AS L
              INNER JOIN [Tbl Ordenes De Proceso Procesos] AS OPP ON (L.NUMODP = OPP.NUMODP) AND (L.ORDODP = OPP.ORDODP))
              INNER JOIN [Tbl Procesos] AS Prc ON OPP.CODPRC = Prc.CODPRC)
              INNER JOIN [Tbl Etapas] AS E ON E.CODETA = Prc.CODETA)
              LEFT JOIN [Tbl Sectores Personal] AS SP ON E.CODSEC = SP.CODSEC)
              LEFT JOIN [Tbl Plantas Produccion] AS PP ON E.CODPLA = PP.CODPLA)
            WHERE $wIng";

    // EGRESO: lote desde su proceso de origen (L.OPOODP). Sector = etapa del proceso origen.
    $egr = "SELECT 2 AS ORDMOV, L.NUMODP AS ODP, L.OPOODP AS ORDEN, L.LPOODP AS LOTE,
              0 AS INGMOV, L.CANODP AS EGRMOV, L.FFPODP AS FFP, L.HFPODP AS HFP,
              E.DENETA AS SECTOR, Prc.DENPRC AS PROCESO, SP.DENSEC AS SECTORP, PP.DENPLA AS PLANTA
            FROM ((((([Tbl Ordenes De Proceso Lotes] AS L
              INNER JOIN [Tbl Ordenes De Proceso Procesos] AS OPP ON (L.NUMODP = OPP.NUMODP) AND (L.OPOODP = OPP.ORDODP))
              INNER JOIN [Tbl Procesos] AS Prc ON OPP.CODPRC = Prc.CODPRC)
              INNER JOIN [Tbl Etapas] AS E ON E.CODETA = Prc.CODETA)
              LEFT JOIN [Tbl Sectores Personal] AS SP ON E.CODSEC = SP.CODSEC)
              LEFT JOIN [Tbl Plantas Produccion] AS PP ON E.CODPLA = PP.CODPLA)
            WHERE $wEgr";

    // UNION a nivel tope (ACE no permite UNION dentro de un subquery de FROM).
    $rows = db_query("$ing UNION ALL $egr ORDER BY 7, 8, 1;");  // 7=FFP, 8=HFP, 1=ORDMOV

    if ($nivel === 'detalle' || $nivel === '') {
        foreach ($rows as &$r) {
            $r['FECHA'] = to_disp_date($r['FFP']);
            $r['HORA']  = _mov_hora($r['HFP']);
            $r['TIPO']  = ((int)$r['ORDMOV'] === 1) ? 'Ingreso' : 'Egreso';
        }
        unset($r);
        return ['nivel' => 'detalle', 'rows' => $rows];
    }

    // Niveles agrupados → se agregan en PHP (evita UNION en FROM).
    $campo = $nivel === 'personal' ? 'SECTORP' : ($nivel === 'planta' ? 'PLANTA' : 'SECTOR');
    $g = [];
    foreach ($rows as $r) {
        $k = (string)((isset($r[$campo]) ? $r[$campo] : ''));
        if (!isset($g[$k])) $g[$k] = ['GRUPO' => $k !== '' ? $k : '(sin asignar)', 'ING' => 0, 'EGR' => 0, 'MOVS' => 0];
        $g[$k]['ING']  += (float)$r['INGMOV'];
        $g[$k]['EGR']  += (float)$r['EGRMOV'];
        $g[$k]['MOVS'] += 1;
    }
    ksort($g);
    return ['nivel' => $nivel, 'campo' => $campo, 'rows' => array_values($g)];
}

/** HFPODP (serial Access, fracción = hora) → 'HH:MM'. */
function _mov_hora($v) {
    if ($v === null || $v === '') return '';
    if (!is_numeric($v)) return (string)$v;
    $frac = (float)$v - floor((float)$v);
    $mins = (int) round($frac * 24 * 60);
    return sprintf('%02d:%02d', ((int) ($mins / 60)) % 24, $mins % 60);
}
