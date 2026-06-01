<?php
/**
 * Maestros de consulta (solo lectura) — genérico.
 * ?m=clientes|operarios|procesos  → listado con nombres resueltos por join.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_require_login();

$MAESTROS = [
    'clientes' => [
        'titulo' => 'Clientes',
        'sql' => "SELECT C.CODCLI AS [Cód], C.DENCLI AS [Cliente], C.DOMCLI AS [Domicilio],
                    L.DENLOC AS [Localidad], C.TELCLI AS [Teléfono], C.CONCLI AS [Contacto]
                  FROM [Tbl Clientes] AS C LEFT JOIN [Tbl Localidades] AS L ON C.CODLOC = L.CODLOC
                  ORDER BY C.DENCLI;",
    ],
    'operarios' => [
        'titulo' => 'Operarios',
        'sql' => "SELECT O.CODOPR AS [Cód], O.DENOPR AS [Operario], O.LEGOPR AS [Legajo],
                    CO.DENCOP AS [Categoría], O.ALTOPR AS [Alta], O.BAJOPR AS [Baja]
                  FROM [Tbl Operarios] AS O LEFT JOIN [Tbl Categorias Operarios] AS CO ON O.CODCOP = CO.CODCOP
                  ORDER BY O.DENOPR;",
    ],
    'procesos' => [
        'titulo' => 'Procesos',
        'sql' => "SELECT P.CODPRC AS [Cód], P.DENPRC AS [Proceso], E.DENETA AS [Etapa],
                    P.COSPRC AS [Costo], P.TIEPRC AS [Tiempo]
                  FROM [Tbl Procesos] AS P LEFT JOIN [Tbl Etapas] AS E ON P.CODETA = E.CODETA
                  ORDER BY P.DENPRC;",
    ],
];

$action = (isset($_GET['action']) ? $_GET['action'] : '');
$m = (isset($_GET['m']) ? $_GET['m'] : '');

try {
    if ($action === 'list') {
        if (!isset($MAESTROS[$m])) { fail('Maestro inválido: ' . $m); }
        else { ok(db_query($MAESTROS[$m]['sql'])); }
    } else {
        fail('Acción inválida: ' . $action);
    }
} catch (Exception $e) {
    fail($e->getMessage(), 500);
}
