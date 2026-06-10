<?php
/**
 * Configuración — Producción PTP (Procesadora Textil Parque) — ARCHIVO PARA PROD.
 * Copiar TODO este contenido al config/system.php del SERVER (server-ptp).
 * Difiere del de DEV solo en: portal_url ('/'), mdb_path (_d2 real) y deploy_key.
 * ⚠️ Verificá que 'deploy_key' coincida con el que ya tenías en prod.
 */
return [
    'base_url'    => '/produccion_ptp',
    'portal_url'  => '/',   // PROD: el selector de sistemas está en la raíz del server

    'name'        => 'Producción PTP',
    'short_name'  => 'Producción PTP',
    'tagline'     => 'Procesadora Textil Parque',

    // Usuarios admin (ven "Estadísticas de Uso" y demás secciones marcadas 'admin').
    // Por CODUSR (número) o por nombre/DENUSR.
    'admin_users' => ['PAUL'],

    'mdb_path'    => 'C:\\_Inforemp\\Produccion PTP_d2.mdb',   // PROD: base de datos real (_d2)
    'mdb_provider'=> 'Microsoft.ACE.OLEDB.12.0',
    'mdb_pass'    => '',

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
        // (El "Portal de Sistemas" ahora es un botón en el topbar, vía 'portal_url' → host-relativo.)
        'Consultas' => [
            ['label' => 'Órdenes de Proceso x Lote',   'desc' => 'Consulta central: sectores → lotes', 'icon' => 'bi-box-seam',  'url' => '/modules/odp_lote/'],
            ['label' => 'Órdenes de Proceso x Etapa',  'desc' => 'Órdenes y resumen por etapa',   'icon' => 'bi-diagram-3', 'url' => '/modules/odp_etapa/'],
            ['label' => 'Órdenes de Proceso x Sector', 'desc' => 'Procesos de un sector',         'icon' => 'bi-pin-map',   'url' => '/modules/odp_sector/'],
            ['label' => 'Órdenes Retrasadas',          'desc' => 'Definidas hace + de X días',    'icon' => 'bi-alarm',     'url' => '/modules/odp_retrasadas/'],
            ['label' => 'Movimientos de Lotes',        'desc' => 'Ingresos/egresos por sector',   'icon' => 'bi-arrow-left-right', 'url' => '/modules/odp_movimientos/'],
        ],
        'Procesos' => [
            ['label' => 'Recepción de Órdenes', 'desc' => 'Alta de órdenes de proceso',     'icon' => 'bi-box-arrow-in-down', 'url' => '/modules/recepcion/'],
            ['label' => 'Definición de Órdenes', 'desc' => 'Ruta de procesos de la orden', 'icon' => 'bi-diagram-3',        'url' => '/modules/definicion/'],
            ['label' => 'Programación',          'desc' => 'Liberar órdenes a producción', 'icon' => 'bi-calendar-week',   'url' => '/modules/programacion/'],
            ['label' => 'PTP (Alta/Modif.)',     'desc' => 'Crear/editar rutas de procesos','icon' => 'bi-list-check',     'url' => '/modules/ptp_edit/'],
        ],
        'Reimpresiones' => [
            ['label' => 'Orden de Proceso',     'desc' => 'Reimprimir orden (Rpt Ordenes de Proceso)', 'icon' => 'bi-printer',  'url' => '/modules/imprimir_orden/'],
            ['label' => 'PTP',                  'desc' => 'Reimprimir PTP / ruta de procesos',          'icon' => 'bi-printer',  'url' => '/modules/imprimir_ptp/'],
            ['label' => 'Orden de Muestra',     'desc' => 'Reimprimir orden de muestra',                'icon' => 'bi-printer',  'url' => '/modules/imprimir_odm/'],
        ],
        'Comercial' => [
            ['label' => 'Cotización de Órdenes', 'desc' => 'Presupuestos PTP', 'icon' => 'bi-cash-coin', 'url' => '/modules/cotizacion/'],
            ['label' => 'Presupuesto (Alta/Modif.)', 'desc' => 'Cotizar una muestra (precios)', 'icon' => 'bi-cash-coin', 'url' => '/modules/presupuesto_edit/'],
            ['label' => 'Consulta de PTP',       'desc' => 'Rutas de procesos / pedidos', 'icon' => 'bi-list-check', 'url' => '/modules/ptp/'],
            ['label' => 'Órdenes de Muestra',    'desc' => 'Muestras / prototipos',       'icon' => 'bi-eyedropper', 'url' => '/modules/odm/'],
            ['label' => 'Muestra (Alta/Modif.)', 'desc' => 'Crear/editar muestra + PTP',  'icon' => 'bi-eyedropper', 'url' => '/modules/odm_edit/'],
            ['label' => 'Confirmación de Muestra','desc' => 'Form completo, pasar a Confirmada', 'icon' => 'bi-check2-circle', 'url' => '/modules/odm_edit/?modo=confirmar'],
            ['label' => 'Entrega de Muestra',    'desc' => 'Form completo, remito (parciales)', 'icon' => 'bi-truck', 'url' => '/modules/odm_edit/?modo=entregar'],
        ],
        'Listados' => [
            ['label' => 'Pendientes de Definición',   'desc' => 'Cola de definición',     'icon' => 'bi-diagram-3',          'url' => '/modules/reportes/?r=pend_definicion'],
            ['label' => 'Pendientes de Programación', 'desc' => 'Cola de programación',   'icon' => 'bi-calendar-week',      'url' => '/modules/reportes/?r=pend_programacion'],
            ['label' => 'En Producción',              'desc' => 'Órdenes por sector',     'icon' => 'bi-gear-wide-connected','url' => '/modules/reportes/?r=en_produccion'],
            ['label' => 'En Administración',          'desc' => 'Pendientes de remito',   'icon' => 'bi-inboxes',            'url' => '/modules/reportes/?r=en_administracion'],
            ['label' => 'Órdenes por PTP',            'desc' => 'Órdenes de cada pedido',  'icon' => 'bi-list-check',         'url' => '/modules/reportes/?r=por_ptp'],
            ['label' => 'Resumen por Etapa',          'desc' => 'Totales por etapa',      'icon' => 'bi-bar-chart',          'url' => '/modules/reportes/?r=resumen_etapas'],
            ['label' => 'Últimas Recibidas',          'desc' => 'Órdenes recientes',      'icon' => 'bi-clock-history',      'url' => '/modules/reportes/?r=ultimas_recibidas'],
            ['label' => 'Órdenes Anuladas',           'desc' => 'Anuladas',               'icon' => 'bi-x-octagon',          'url' => '/modules/reportes/?r=anuladas'],
            ['label' => 'Estadísticas de Uso',        'desc' => 'Adopción: páginas, usuarios, máquinas', 'icon' => 'bi-graph-up-arrow', 'url' => '/modules/uso/', 'admin' => true],
            ['label' => 'Performance',                'desc' => 'Tiempos por módulo/query — detectar cuellos de botella y cuelgues', 'icon' => 'bi-speedometer2', 'url' => '/modules/perf/', 'admin' => true],
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

    // Tablero del dashboard: indicadores (Count/Sum por SQL) + accesos rápidos.
    'dashboard' => [
        'kpis' => [
            ['label' => 'Pendientes de Definición', 'icon' => 'bi-diagram-3',           'color' => '#0ea5e9', 'url' => '/modules/reportes/?r=pend_definicion',
             'sql' => "SELECT Count(*) AS N FROM [Tbl Ordenes De Proceso] WHERE CODETA=20;"],
            ['label' => 'En Producción',            'icon' => 'bi-gear-wide-connected', 'color' => '#10b981', 'url' => '/modules/reportes/?r=en_produccion',
             'sql' => "SELECT Count(*) AS N FROM [Tbl Ordenes De Proceso] WHERE CODETA BETWEEN 31 AND 109;"],
            ['label' => 'Órdenes activas',          'icon' => 'bi-box-seam',            'color' => '#6366f1', 'url' => '/modules/odp_lote/',
             'sql' => "SELECT Count(*) AS N FROM [Tbl Ordenes De Proceso] WHERE CODETA>0;"],
            ['label' => 'Muestras por confirmar',   'icon' => 'bi-eyedropper',          'color' => '#f59e0b', 'url' => '/modules/odm_ciclo/',
             'sql' => "SELECT Count(*) AS N FROM [Tbl Ordenes De Muestra] WHERE CODEDM=1;"],
        ],
        'quick' => [
            ['label' => 'Recepción',   'icon' => 'bi-box-arrow-in-down', 'url' => '/modules/recepcion/'],
            ['label' => 'x Lote',      'icon' => 'bi-box-seam',          'url' => '/modules/odp_lote/'],
            ['label' => 'Definición',  'icon' => 'bi-diagram-3',         'url' => '/modules/definicion/'],
            ['label' => 'Retrasadas',  'icon' => 'bi-alarm',             'url' => '/modules/odp_retrasadas/'],
            ['label' => 'Presupuesto', 'icon' => 'bi-cash-coin',         'url' => '/modules/presupuesto_edit/'],
        ],
    ],

    'afip' => ['enabled' => false],

    // ⚠️ Usá el deploy_key que YA tenías en prod (no este placeholder).
    'deploy_key'  => 'prod_ptp_2026_9Kx7mP2qZ',
];
