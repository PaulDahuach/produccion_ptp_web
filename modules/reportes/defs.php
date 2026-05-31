<?php
/**
 * Definición de reportes/listados (solo lectura). Cada uno: titulo, icono, sql,
 * y 'fechas' = columnas (alias) a convertir de serial Access a dd/mm/aaaa.
 * Agregar un reporte = una entrada acá.
 */
$JOIN = "FROM ((([Tbl Ordenes De Proceso] AS O
           LEFT JOIN [Tbl Clientes] AS C ON O.CODCLI=C.CODCLI)
           LEFT JOIN [Tbl Marcas] AS M ON O.CODMAR=M.CODMAR)
           LEFT JOIN [Tbl Prendas] AS Pre ON O.CODPR1=Pre.CODPRE)";

return [
    'pend_definicion' => [
        'titulo' => 'Pendientes de Definición', 'icono' => 'bi-diagram-3',
        'sql' => "SELECT O.NUMODP AS [ODP], O.FDRODP AS [Recibido], C.DENCLI AS [Cliente],
                    M.DENMAR AS [Marca], Pre.DENPRE AS [Prenda], O.CANODP AS [Cantidad], O.REMODP AS [Remito]
                  $JOIN WHERE O.CODETA=20 ORDER BY O.NUMODP DESC;",
        'fechas' => ['Recibido'],
    ],
    'pend_programacion' => [
        'titulo' => 'Pendientes de Programación', 'icono' => 'bi-calendar-week',
        'sql' => "SELECT O.NUMODP AS [ODP], O.FDRODP AS [Recibido], O.FDDODP AS [Definido], C.DENCLI AS [Cliente],
                    M.DENMAR AS [Marca], Pre.DENPRE AS [Prenda], O.CANODP AS [Cantidad]
                  $JOIN WHERE O.CODETA=30 ORDER BY O.NUMODP DESC;",
        'fechas' => ['Recibido', 'Definido'],
    ],
    'en_produccion' => [
        'titulo' => 'En Producción (por sector)', 'icono' => 'bi-gear-wide-connected',
        'sql' => "SELECT E.DENETA AS [Sector], O.NUMODP AS [ODP], C.DENCLI AS [Cliente], M.DENMAR AS [Marca],
                    Pre.DENPRE AS [Prenda], O.CANODP AS [Cantidad], O.FDDODP AS [Definido]
                  FROM (((([Tbl Ordenes De Proceso] AS O
                    LEFT JOIN [Tbl Clientes] AS C ON O.CODCLI=C.CODCLI)
                    LEFT JOIN [Tbl Marcas] AS M ON O.CODMAR=M.CODMAR)
                    LEFT JOIN [Tbl Prendas] AS Pre ON O.CODPR1=Pre.CODPRE)
                    LEFT JOIN [Tbl Etapas] AS E ON O.CODETA=E.CODETA)
                  WHERE O.CODETA BETWEEN 31 AND 109 ORDER BY O.CODETA, O.NUMODP;",
        'fechas' => ['Definido'],
    ],
    'en_administracion' => [
        'titulo' => 'En Administración (pendientes de remito)', 'icono' => 'bi-inboxes',
        'sql' => "SELECT O.NUMODP AS [ODP], O.FDDODP AS [Definido], C.DENCLI AS [Cliente], M.DENMAR AS [Marca],
                    Pre.DENPRE AS [Prenda], O.CANODP AS [Cantidad], O.CFDODP AS [Despachado]
                  $JOIN WHERE O.CODETA=120 ORDER BY O.NUMODP DESC;",
        'fechas' => ['Definido'],
    ],
    'resumen_etapas' => [
        'titulo' => 'Resumen por Etapa', 'icono' => 'bi-bar-chart',
        'sql' => "SELECT E.DENETA AS [Etapa], O.CODETA AS [Cód], Count(O.NUMODP) AS [Órdenes], Sum(O.CANODP) AS [Prendas]
                  FROM [Tbl Etapas] AS E INNER JOIN [Tbl Ordenes De Proceso] AS O ON E.CODETA=O.CODETA
                  WHERE O.CODETA>0 GROUP BY E.DENETA, O.CODETA ORDER BY Count(O.NUMODP) DESC;",
        'fechas' => [],
    ],
];
