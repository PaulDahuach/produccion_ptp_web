<?php
/**
 * Definiciones de los maestros ABM (CRUD genérico).
 * 'ult' = contador en [Rec Control] (mdlGetNextNumber).
 * Campos: text|memo|number|decimal|bool|date|select.
 * 'hijos' (sub-tablas / subforms): cada uno con
 *    'fk'    = columna que linkea al pk del padre
 *    'clave' = ['tipo'=>'auto','col'=>'ORDxxx']  (línea autonumérica)
 *           o  ['tipo'=>'select','col'=>'CODxxx','label'=>..,'lookup'=>..] (relación)
 *    'campos'= campos extra de la fila (puede ser [])
 */
$LOC = ['tabla' => 'Tbl Localidades', 'pk' => 'CODLOC', 'den' => 'DENLOC'];
$PRV = ['tabla' => 'Tbl Provincias',  'pk' => 'CODPRV', 'den' => 'DENPRV'];
$BDP = ['tabla' => 'Tbl Bases De Producto', 'pk' => 'CODBDP', 'den' => 'DENBDP'];
$ETA = ['tabla' => 'Tbl Etapas', 'pk' => 'CODETA', 'den' => 'DENETA'];
$PRC = ['tabla' => 'Tbl Procesos', 'pk' => 'CODPRC', 'den' => 'DENPRC'];
$MAR = ['tabla' => 'Tbl Marcas', 'pk' => 'CODMAR', 'den' => 'DENMAR'];
$COP = ['tabla' => 'Tbl Categorias Operarios', 'pk' => 'CODCOP', 'den' => 'DENCOP'];

return [
    // ── Núcleo con ficha completa (CRUD) ────────────────────────────────
    'clientes' => [
        'tabla' => 'Tbl Clientes', 'pk' => 'CODCLI', 'ult' => 'ULTCLI',
        'titulo' => 'Clientes', 'icono' => 'bi-people', 'orden' => 'DENCLI',
        'campos' => [
            ['col' => 'DENCLI', 'label' => 'Denominación', 'tipo' => 'text', 'req' => true, 'size' => 50, 'list' => true],
            ['col' => 'DOMCLI', 'label' => 'Domicilio', 'tipo' => 'text', 'req' => true, 'size' => 50],
            ['col' => 'CODLOC', 'label' => 'Localidad', 'tipo' => 'select', 'lookup' => $LOC, 'req' => true, 'list' => true],
            ['col' => 'TELCLI', 'label' => 'Teléfono', 'tipo' => 'text', 'size' => 50, 'list' => true],
            ['col' => 'FAXCLI', 'label' => 'Fax', 'tipo' => 'text', 'size' => 30],
            ['col' => 'DEMCLI', 'label' => 'e-Mail', 'tipo' => 'text', 'size' => 128],
            ['col' => 'CONCLI', 'label' => 'Contacto', 'tipo' => 'text', 'size' => 50, 'list' => true],
            ['col' => 'HMLCUE', 'label' => 'Cta. Corriente', 'tipo' => 'number'],
            ['col' => 'OBSCLI', 'label' => 'Observaciones', 'tipo' => 'memo'],
        ],
        'hijos' => [
            ['key' => 'marcas', 'titulo' => 'Marcas', 'tabla' => 'Tbl Clientes Marcas', 'fk' => 'CODCLI',
             'clave' => ['tipo' => 'select', 'col' => 'CODMAR', 'label' => 'Marca', 'lookup' => $MAR],
             'campos' => [['col' => 'OBSCMX', 'label' => 'Observaciones', 'tipo' => 'text', 'size' => 50, 'list' => true]]],
        ],
    ],
    'operarios' => [
        'tabla' => 'Tbl Operarios', 'pk' => 'CODOPR', 'ult' => 'ULTOPR',
        'titulo' => 'Operarios', 'icono' => 'bi-person-badge', 'orden' => 'DENOPR',
        'campos' => [
            ['col' => 'DENOPR', 'label' => 'Nombre y Apellido', 'tipo' => 'text', 'req' => true, 'size' => 30, 'list' => true],
            ['col' => 'LEGOPR', 'label' => 'Legajo N°', 'tipo' => 'number', 'req' => true, 'list' => true],
            ['col' => 'CODCOP', 'label' => 'Categoría', 'tipo' => 'select', 'lookup' => $COP, 'list' => true],
            ['col' => 'LIQOPR', 'label' => '% Liquidación', 'tipo' => 'decimal'],
            ['col' => 'CDAOPR', 'label' => 'Clave de Acceso', 'tipo' => 'text', 'size' => 30],
            ['col' => 'ALTOPR', 'label' => 'Alta', 'tipo' => 'date', 'list' => true],
            ['col' => 'BAJOPR', 'label' => 'Baja', 'tipo' => 'date'],
        ],
        'hijos' => [
            ['key' => 'sectores', 'titulo' => 'Sectores', 'tabla' => 'Tbl Operarios Sectores', 'fk' => 'CODOPR',
             'clave' => ['tipo' => 'auto', 'col' => 'ORDOPR'],
             'campos' => [['col' => 'CODETA', 'label' => 'Sector', 'tipo' => 'select', 'lookup' => $ETA, 'req' => true, 'list' => true]]],
        ],
    ],
    'procesos' => [
        'tabla' => 'Tbl Procesos', 'pk' => 'CODPRC', 'ult' => 'ULTPRC',
        'titulo' => 'Procesos', 'icono' => 'bi-gear', 'orden' => 'DENPRC',
        'campos' => [
            ['col' => 'DENPRC', 'label' => 'Denominación', 'tipo' => 'text', 'req' => true, 'size' => 30, 'list' => true],
            ['col' => 'CODETA', 'label' => 'Sector', 'tipo' => 'select', 'lookup' => $ETA, 'list' => true],
            ['col' => 'TITPRC', 'label' => 'Título', 'tipo' => 'text', 'size' => 30],
            ['col' => 'COLPRC', 'label' => 'Color', 'tipo' => 'bool'],
            ['col' => 'COSPRC', 'label' => 'Costo Destajo $', 'tipo' => 'decimal', 'req' => true, 'list' => true],
            ['col' => 'NETPRC', 'label' => 'Neto Lista $', 'tipo' => 'decimal', 'req' => true, 'list' => true],
            ['col' => 'HORPRC', 'label' => 'Horas Diarias', 'tipo' => 'decimal'],
            ['col' => 'TIEPRC', 'label' => 'Tiempo x Unidad (seg)', 'tipo' => 'number'],
            ['col' => 'PANPRC', 'label' => 'Producción Diaria', 'tipo' => 'number'],
            ['col' => 'HNOPRC', 'label' => 'Horno (min)', 'tipo' => 'number'],
            ['col' => 'CPSPRC', 'label' => 'Cap. Simultánea', 'tipo' => 'decimal'],
            ['col' => 'DISPRC', 'label' => 'Discontinuado', 'tipo' => 'bool'],
        ],
    ],

    // ── Simples (código + denominación) ─────────────────────────────────
    'marcas' => [
        'tabla' => 'Tbl Marcas', 'pk' => 'CODMAR', 'ult' => 'ULTMAR',
        'titulo' => 'Marcas', 'icono' => 'bi-tags', 'orden' => 'DENMAR',
        'campos' => [['col' => 'DENMAR', 'label' => 'Denominación', 'tipo' => 'text', 'req' => true, 'size' => 30, 'list' => true]],
    ],
    'prendas' => [
        'tabla' => 'Tbl Prendas', 'pk' => 'CODPRE', 'ult' => 'ULTPRE',
        'titulo' => 'Prendas', 'icono' => 'bi-bag', 'orden' => 'DENPRE',
        'campos' => [
            ['col' => 'DENPRE', 'label' => 'Denominación', 'tipo' => 'text', 'req' => true, 'size' => 30, 'list' => true],
            ['col' => 'DTOPRE', 'label' => '% Descuento Presup. PTP', 'tipo' => 'decimal', 'list' => true],
        ],
    ],
    'unidades' => [
        'tabla' => 'Tbl Unidades De Medida', 'pk' => 'CODUDM', 'ult' => 'ULTUDM',
        'titulo' => 'Unidades de Medida', 'icono' => 'bi-rulers', 'orden' => 'DENUDM',
        'campos' => [['col' => 'DENUDM', 'label' => 'Denominación', 'tipo' => 'text', 'req' => true, 'size' => 30, 'list' => true]],
    ],
    'colores_tela' => [
        'tabla' => 'Tbl Colores Tela', 'pk' => 'CODCT1', 'ult' => 'ULTCT1',
        'titulo' => 'Colores de Tela', 'icono' => 'bi-palette', 'orden' => 'DENCT1',
        'campos' => [['col' => 'DENCT1', 'label' => 'Denominación', 'tipo' => 'text', 'req' => true, 'size' => 30, 'list' => true]],
    ],
    'proveedores_tela' => [
        'tabla' => 'Tbl Proveedores De Tela', 'pk' => 'CODPDT', 'ult' => 'ULTPDT',
        'titulo' => 'Proveedores de Tela', 'icono' => 'bi-truck', 'orden' => 'DENPDT',
        'campos' => [['col' => 'DENPDT', 'label' => 'Denominación', 'tipo' => 'text', 'req' => true, 'size' => 30, 'list' => true]],
    ],
    'provincias' => [
        'tabla' => 'Tbl Provincias', 'pk' => 'CODPRV', 'ult' => 'ULTPRV',
        'titulo' => 'Provincias', 'icono' => 'bi-map', 'orden' => 'DENPRV',
        'campos' => [['col' => 'DENPRV', 'label' => 'Denominación', 'tipo' => 'text', 'req' => true, 'size' => 30, 'list' => true]],
    ],
    'sectores_personal' => [
        'tabla' => 'Tbl Sectores Personal', 'pk' => 'CODSEC', 'ult' => 'ULTSEC',
        'titulo' => 'Sectores de Personal', 'icono' => 'bi-people-fill', 'orden' => 'DENSEC',
        'campos' => [['col' => 'DENSEC', 'label' => 'Denominación', 'tipo' => 'text', 'req' => true, 'size' => 50, 'list' => true]],
    ],
    'bases_producto' => [
        'tabla' => 'Tbl Bases De Producto', 'pk' => 'CODBDP', 'ult' => 'ULTBDP',
        'titulo' => 'Bases de Producto', 'icono' => 'bi-box', 'orden' => 'DENBDP',
        'campos' => [['col' => 'DENBDP', 'label' => 'Denominación', 'tipo' => 'text', 'req' => true, 'size' => 30, 'list' => true]],
    ],

    // ── Con campos extra / FK ───────────────────────────────────────────
    'localidades' => [
        'tabla' => 'Tbl Localidades', 'pk' => 'CODLOC', 'ult' => 'ULTLOC',
        'titulo' => 'Localidades', 'icono' => 'bi-geo-alt', 'orden' => 'DENLOC',
        'campos' => [
            ['col' => 'DENLOC', 'label' => 'Denominación', 'tipo' => 'text', 'req' => true, 'size' => 50, 'list' => true],
            ['col' => 'CPXLOC', 'label' => 'Código Postal', 'tipo' => 'number', 'req' => true, 'list' => true],
            ['col' => 'PIULOC', 'label' => 'Prefijo InterUrbano', 'tipo' => 'text', 'req' => true, 'size' => 5],
            ['col' => 'CODPRV', 'label' => 'Provincia', 'tipo' => 'select', 'lookup' => $PRV, 'list' => true],
            ['col' => 'CDPLOC', 'label' => 'Cabecera de Partido', 'tipo' => 'bool'],
        ],
    ],

    // ── Con sub-tablas (hijos) ──────────────────────────────────────────
    'colores_proceso' => [
        'tabla' => 'Tbl Colores De Proceso', 'pk' => 'CODCDP', 'ult' => 'ULTCDP',
        'titulo' => 'Colores de Proceso', 'icono' => 'bi-droplet', 'orden' => 'DENCDP',
        'campos' => [
            ['col' => 'DENCDP', 'label' => 'Denominación', 'tipo' => 'text', 'req' => true, 'size' => 30, 'list' => true],
            ['col' => 'CATCDP', 'label' => 'Catálogo', 'tipo' => 'text', 'size' => 30, 'list' => true],
            ['col' => 'CODBDP', 'label' => 'Base', 'tipo' => 'select', 'lookup' => $BDP, 'req' => true, 'list' => true],
            ['col' => 'CODETA', 'label' => 'Sector', 'tipo' => 'select', 'lookup' => $ETA],
        ],
        'hijos' => [
            ['key' => 'composicion', 'titulo' => 'Composición', 'tabla' => 'Tbl Colores De Proceso Composicion', 'fk' => 'CODCDP',
             'clave' => ['tipo' => 'auto', 'col' => 'ORDCDP'],
             'campos' => [
                 ['col' => 'CODPRO', 'label' => 'Producto', 'tipo' => 'text', 'req' => true, 'size' => 10, 'list' => true],
                 ['col' => 'CONCDP', 'label' => 'Concentración', 'tipo' => 'decimal', 'req' => true, 'list' => true],
             ]],
        ],
    ],
    'maquinas' => [
        'tabla' => 'Tbl Maquinas', 'pk' => 'CODMAQ', 'ult' => 'ULTMAQ',
        'titulo' => 'Máquinas', 'icono' => 'bi-cpu', 'orden' => 'DENMAQ',
        'campos' => [
            ['col' => 'DENMAQ', 'label' => 'Nombre', 'tipo' => 'text', 'req' => true, 'size' => 30, 'list' => true],
            ['col' => 'CODETA', 'label' => 'Sector', 'tipo' => 'select', 'lookup' => $ETA, 'req' => true, 'list' => true],
            ['col' => 'CKDMAQ', 'label' => 'Carga Mínima (Kg)', 'tipo' => 'decimal'],
            ['col' => 'CKHMAQ', 'label' => 'Carga Máxima (Kg)', 'tipo' => 'decimal'],
            ['col' => 'COPMAQ', 'label' => 'Cant. Mín. Operarios', 'tipo' => 'number'],
        ],
        'hijos' => [
            ['key' => 'procesos', 'titulo' => 'Procesos', 'tabla' => 'Tbl Maquinas Procesos', 'fk' => 'CODMAQ',
             'clave' => ['tipo' => 'select', 'col' => 'CODPRC', 'label' => 'Proceso', 'lookup' => $PRC],
             'campos' => []],
        ],
    ],
    'supervisores' => [
        'tabla' => 'Tbl Supervisores', 'pk' => 'CODSUP', 'ult' => 'ULTSUP',
        'titulo' => 'Supervisores', 'icono' => 'bi-person-gear', 'orden' => 'DENSUP',
        'campos' => [
            ['col' => 'DENSUP', 'label' => 'Denominación', 'tipo' => 'text', 'req' => true, 'size' => 30, 'list' => true],
            ['col' => 'LEGSUP', 'label' => 'Legajo N°', 'tipo' => 'number', 'req' => true, 'list' => true],
            ['col' => 'LIQSUP', 'label' => '% Liquidación', 'tipo' => 'decimal', 'req' => true],
            ['col' => 'CDASUP', 'label' => 'Clave de Acceso', 'tipo' => 'text', 'req' => true, 'size' => 30],
        ],
        'hijos' => [
            ['key' => 'sectores', 'titulo' => 'Sectores', 'tabla' => 'Tbl Supervisores Sectores', 'fk' => 'CODSUP',
             'clave' => ['tipo' => 'auto', 'col' => 'ORDSUP'],
             'campos' => [['col' => 'CODETA', 'label' => 'Sector', 'tipo' => 'select', 'lookup' => $ETA, 'req' => true, 'list' => true]]],
        ],
    ],

    'talleres' => [
        'tabla' => 'Tbl Talleres', 'pk' => 'CODTAL', 'ult' => 'ULTTAL',
        'titulo' => 'Talleres', 'icono' => 'bi-buildings', 'orden' => 'DENTAL',
        'campos' => [
            ['col' => 'DENTAL', 'label' => 'Denominación', 'tipo' => 'text', 'req' => true, 'size' => 30, 'list' => true],
            ['col' => 'DOMTAL', 'label' => 'Domicilio', 'tipo' => 'text', 'size' => 50, 'list' => true],
            ['col' => 'CODLOC', 'label' => 'Localidad', 'tipo' => 'select', 'lookup' => $LOC, 'list' => true],
            ['col' => 'TELTAL', 'label' => 'Teléfono', 'tipo' => 'text', 'size' => 50, 'list' => true],
            ['col' => 'FAXTAL', 'label' => 'Fax', 'tipo' => 'text', 'size' => 50],
            ['col' => 'DEMTAL', 'label' => 'e-Mail', 'tipo' => 'text', 'size' => 128],
            ['col' => 'CONTAL', 'label' => 'Contacto', 'tipo' => 'text', 'size' => 50],
            ['col' => 'OBSTAL', 'label' => 'Observaciones', 'tipo' => 'memo'],
        ],
    ],
];
