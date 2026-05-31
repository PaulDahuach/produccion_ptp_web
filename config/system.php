<?php
/**
 * Configuración — Producción PTP (Procesadora Textil Parque).
 * DEV: apunta a la COPIA local del front-end. En prod cambiar mdb_path a la
 * ruta real del cliente (la .mdb que usa el sistema de escritorio).
 */
return [
    // Dev: la instancia corre en localhost/produccion_ptp/. En prod poner ''.
    'base_url'    => '/produccion_ptp',

    'name'        => 'Producción PTP',
    'short_name'  => 'Producción PTP',
    'tagline'     => 'Procesadora Textil Parque',

    'mdb_path'    => 'C:\\_Inforemp\\_dev_ptp\\Produccion PTP_w2.mdb',
    'mdb_provider'=> 'Microsoft.ACE.OLEDB.12.0',
    'mdb_pass'    => '',

    // DEV: escritura habilitada para probar el ABM contra la copia local.
    // En prod, evaluar volver a 'readonly' o dejar 'readwrite' por módulo.
    'mode'        => 'readwrite',

    // Tabla de usuarios del legacy (igual que RDN).
    'auth' => [
        'table'    => 'Tbl Usuarios',
        'col_id'   => 'CODUSR',
        'col_name' => 'DENUSR',
        'col_pass' => 'ACCUSR',
    ],

    'logo'        => '/assets/img/logo.png',
    'primary'     => '#0ea5e9',
    'theme'       => 'dark',

    'menu' => [
        // Consultas (CONSULTAS del menú viejo) — primero: la x Lote es la consulta central.
        'Consultas' => [
            ['label' => 'Órdenes de Proceso x Lote',   'desc' => 'Consulta central: sectores → lotes', 'icon' => 'bi-box-seam',  'url' => '/modules/odp_lote/'],
            ['label' => 'Órdenes de Proceso x Etapa',  'desc' => 'Órdenes y resumen por etapa',   'icon' => 'bi-diagram-3', 'url' => '/modules/odp_etapa/'],
            ['label' => 'Órdenes de Proceso x Sector', 'desc' => 'Procesos de un sector',         'icon' => 'bi-pin-map',   'url' => '/modules/odp_sector/'],
            ['label' => 'Órdenes Retrasadas',          'desc' => 'Definidas hace + de X días',    'icon' => 'bi-alarm',     'url' => '/modules/odp_retrasadas/'],
            ['label' => 'Movimientos de Lotes',        'desc' => 'Ingresos/egresos por sector',   'icon' => 'bi-arrow-left-right', 'url' => '/modules/odp_movimientos/'],
        ],
        // Procesos (transaccionales)
        'Procesos' => [
            ['label' => 'Recepción de Órdenes', 'desc' => 'Alta de órdenes de proceso',     'icon' => 'bi-box-arrow-in-down', 'url' => '/modules/recepcion/'],
            ['label' => 'Definición de Órdenes', 'desc' => 'Ruta de procesos de la orden', 'icon' => 'bi-diagram-3',        'url' => '/modules/definicion/'],
            ['label' => 'Programación',          'desc' => 'Liberar órdenes a producción', 'icon' => 'bi-calendar-week',   'url' => '/modules/programacion/'],
            ['label' => 'PTP (Alta/Modif.)',     'desc' => 'Crear/editar rutas de procesos','icon' => 'bi-list-check',     'url' => '/modules/ptp_edit/'],
        ],
        // Reimpresiones (EMISIONES del menú viejo) — uso intensivo
        'Reimpresiones' => [
            ['label' => 'Orden de Proceso',     'desc' => 'Reimprimir orden (Rpt Ordenes de Proceso)', 'icon' => 'bi-printer',  'url' => '/modules/imprimir_orden/'],
            ['label' => 'PTP',                  'desc' => 'Reimprimir PTP / ruta de procesos',          'icon' => 'bi-printer',  'url' => '/modules/imprimir_ptp/'],
        ],
        // Comercial
        'Comercial' => [
            ['label' => 'Cotización de Órdenes', 'desc' => 'Presupuestos PTP', 'icon' => 'bi-cash-coin', 'url' => '/modules/cotizacion/'],
            ['label' => 'Consulta de PTP',       'desc' => 'Rutas de procesos / pedidos', 'icon' => 'bi-list-check', 'url' => '/modules/ptp/'],
            ['label' => 'Órdenes de Muestra',    'desc' => 'Muestras / prototipos',       'icon' => 'bi-eyedropper', 'url' => '/modules/odm/'],
        ],
        // Listados (LISTADOS del menú viejo)
        'Listados' => [
            ['label' => 'Pendientes de Definición',   'desc' => 'Cola de definición',     'icon' => 'bi-diagram-3',          'url' => '/modules/reportes/?r=pend_definicion'],
            ['label' => 'Pendientes de Programación', 'desc' => 'Cola de programación',   'icon' => 'bi-calendar-week',      'url' => '/modules/reportes/?r=pend_programacion'],
            ['label' => 'En Producción',              'desc' => 'Órdenes por sector',     'icon' => 'bi-gear-wide-connected','url' => '/modules/reportes/?r=en_produccion'],
            ['label' => 'En Administración',          'desc' => 'Pendientes de remito',   'icon' => 'bi-inboxes',            'url' => '/modules/reportes/?r=en_administracion'],
            ['label' => 'Resumen por Etapa',          'desc' => 'Totales por etapa',      'icon' => 'bi-bar-chart',          'url' => '/modules/reportes/?r=resumen_etapas'],
            ['label' => 'Últimas Recibidas',          'desc' => 'Órdenes recientes',      'icon' => 'bi-clock-history',      'url' => '/modules/reportes/?r=ultimas_recibidas'],
            ['label' => 'Órdenes Anuladas',           'desc' => 'Anuladas',               'icon' => 'bi-x-octagon',          'url' => '/modules/reportes/?r=anuladas'],
        ],
        'Maestros' => [
            ['label' => 'Clientes',  'desc' => 'Ficha + ABM',           'icon' => 'bi-people',        'url' => '/modules/abm/?m=clientes'],
            ['label' => 'Operarios', 'desc' => 'Ficha + ABM',           'icon' => 'bi-person-badge',  'url' => '/modules/abm/?m=operarios'],
            ['label' => 'Procesos',  'desc' => 'Ficha + ABM',           'icon' => 'bi-gear',          'url' => '/modules/abm/?m=procesos'],
        ],
        'Tablas (ABM)' => [
            ['label' => 'Marcas',             'desc' => 'Alta/baja/modif.', 'icon' => 'bi-tags',        'url' => '/modules/abm/?m=marcas'],
            ['label' => 'Prendas',            'desc' => 'Alta/baja/modif.', 'icon' => 'bi-bag',         'url' => '/modules/abm/?m=prendas'],
            ['label' => 'Unidades de Medida', 'desc' => 'Alta/baja/modif.', 'icon' => 'bi-rulers',      'url' => '/modules/abm/?m=unidades'],
            ['label' => 'Colores de Tela',    'desc' => 'Alta/baja/modif.', 'icon' => 'bi-palette',     'url' => '/modules/abm/?m=colores_tela'],
            ['label' => 'Colores de Proceso', 'desc' => 'Con composición',  'icon' => 'bi-droplet',     'url' => '/modules/abm/?m=colores_proceso'],
            ['label' => 'Proveedores de Tela','desc' => 'Alta/baja/modif.', 'icon' => 'bi-truck',       'url' => '/modules/abm/?m=proveedores_tela'],
            ['label' => 'Máquinas',           'desc' => 'Con procesos',     'icon' => 'bi-cpu',         'url' => '/modules/abm/?m=maquinas'],
            ['label' => 'Supervisores',       'desc' => 'Con sectores',     'icon' => 'bi-person-gear', 'url' => '/modules/abm/?m=supervisores'],
            ['label' => 'Sectores de Personal','desc'=> 'Alta/baja/modif.', 'icon' => 'bi-people-fill', 'url' => '/modules/abm/?m=sectores_personal'],
            ['label' => 'Bases de Producto',  'desc' => 'Alta/baja/modif.', 'icon' => 'bi-box',         'url' => '/modules/abm/?m=bases_producto'],
            ['label' => 'Localidades',        'desc' => 'Alta/baja/modif.', 'icon' => 'bi-geo-alt',     'url' => '/modules/abm/?m=localidades'],
            ['label' => 'Provincias',         'desc' => 'Alta/baja/modif.', 'icon' => 'bi-map',         'url' => '/modules/abm/?m=provincias'],
            ['label' => 'Talleres',           'desc' => 'Alta/baja/modif.', 'icon' => 'bi-buildings',   'url' => '/modules/abm/?m=talleres'],
        ],
    ],

    'afip' => ['enabled' => false],

    'deploy_key'  => 'ptp_deploy_2026_cambiar',
];
