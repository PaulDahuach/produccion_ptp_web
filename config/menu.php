<?php
/**
 * Menú del dashboard — PORTABLE (versionado, viaja por git).
 * ============================================================================
 *  Es la parte de la config que NO cambia entre dev y producción. `config/system.php`
 *  (rutas/modo/admin_users por instalación) lo incluye con:
 *        'menu' => require __DIR__ . '/menu.php',
 *  Así el menú no hay que replicarlo a mano en cada deploy.
 *
 *  Cada clave = una sección; su valor = lista de tarjetas (en orden).
 *    ['label'=>..,'desc'=>..,'icon'=>..,'url'=>..]  → opción (link)
 *    [..., 'admin'=>true]                            → visible solo para admin_users
 *    [..., 'opt'=>CODMEN]                            → restricción por usuario (lista negra
 *        [Tbl Usuarios Menu], porta rutAccesoUsuario; 'opt' = CODMEN del legacy [Tbl Menu]).
 *        Sin 'opt' = módulo web sin equivalente legacy → nunca se restringe (ver includes/auth.php).
 *        OJO: varias Consultas/Listados web son NUEVAS (no existían como opción del menú legacy) →
 *        quedan sin 'opt' a propósito. Revisar/completar el mapeo con criterio del negocio.
 * ============================================================================
 */
return [
    // (El "Portal de Sistemas" ahora es un botón en el topbar, vía 'portal_url' → host-relativo.)
    // Consultas (CONSULTAS del menú viejo) — primero: la x Lote es la consulta central.
    'Consultas' => [
        ['label' => 'Órdenes de Proceso x Lote',   'desc' => 'Consulta central: sectores → lotes', 'icon' => 'bi-box-seam',  'url' => '/modules/odp_lote/'],
        ['label' => 'Órdenes de Proceso x Etapa',  'desc' => 'Órdenes y resumen por etapa',   'icon' => 'bi-diagram-3', 'url' => '/modules/odp_etapa/'],
        ['label' => 'Órdenes de Proceso x Sector', 'desc' => 'Procesos de un sector',         'icon' => 'bi-pin-map',   'url' => '/modules/odp_sector/'],
        ['label' => 'Órdenes Retrasadas',          'desc' => 'Definidas hace + de X días',    'icon' => 'bi-alarm',     'url' => '/modules/odp_retrasadas/'],
        ['label' => 'Movimientos de Lotes',        'desc' => 'Ingresos/egresos por sector',   'icon' => 'bi-arrow-left-right', 'url' => '/modules/odp_movimientos/', 'opt' => 1317],
    ],
    // Procesos (transaccionales)
    'Procesos' => [
        ['label' => 'Recepción de Órdenes', 'desc' => 'Alta de órdenes de proceso',     'icon' => 'bi-box-arrow-in-down', 'url' => '/modules/recepcion/', 'opt' => 320],
        ['label' => 'Definición de Órdenes', 'desc' => 'Ruta de procesos de la orden', 'icon' => 'bi-diagram-3',        'url' => '/modules/definicion/', 'opt' => 410],
        ['label' => 'Programación',          'desc' => 'Liberar órdenes a producción', 'icon' => 'bi-calendar-week',   'url' => '/modules/programacion/', 'opt' => 1172],
        ['label' => 'PTP (Alta/Modif.)',     'desc' => 'Crear/editar rutas de procesos','icon' => 'bi-list-check',     'url' => '/modules/ptp_edit/', 'opt' => 300],
    ],
    // Reimpresiones (EMISIONES del menú viejo) — uso intensivo
    'Reimpresiones' => [
        ['label' => 'Orden de Proceso',     'desc' => 'Reimprimir orden (Rpt Ordenes de Proceso)', 'icon' => 'bi-printer',  'url' => '/modules/imprimir_orden/', 'opt' => 1170],
        ['label' => 'PTP',                  'desc' => 'Reimprimir PTP / ruta de procesos',          'icon' => 'bi-printer',  'url' => '/modules/imprimir_ptp/', 'opt' => 770],
        ['label' => 'Orden de Muestra',     'desc' => 'Reimprimir orden de muestra',                'icon' => 'bi-printer',  'url' => '/modules/imprimir_odm/'],
    ],
    // Comercial
    'Comercial' => [
        ['label' => 'Cotización de Órdenes', 'desc' => 'Presupuestos PTP', 'icon' => 'bi-cash-coin', 'url' => '/modules/cotizacion/', 'opt' => 1296],
        ['label' => 'Presupuesto (Alta/Modif.)', 'desc' => 'Cotizar una muestra (precios)', 'icon' => 'bi-cash-coin', 'url' => '/modules/presupuesto_edit/', 'opt' => 1296],
        ['label' => 'Consulta de PTP',       'desc' => 'Rutas de procesos / pedidos', 'icon' => 'bi-list-check', 'url' => '/modules/ptp/', 'opt' => 780],
        ['label' => 'Órdenes de Muestra',    'desc' => 'Muestras / prototipos',       'icon' => 'bi-eyedropper', 'url' => '/modules/odm/', 'opt' => 310],
        ['label' => 'Muestra (Alta/Modif.)', 'desc' => 'Crear/editar muestra + PTP',  'icon' => 'bi-eyedropper', 'url' => '/modules/odm_edit/', 'opt' => 310],
        ['label' => 'Confirmación de Muestra','desc' => 'Form completo, pasar a Confirmada', 'icon' => 'bi-check2-circle', 'url' => '/modules/odm_edit/?modo=confirmar', 'opt' => 1208],
        ['label' => 'Entrega de Muestra',    'desc' => 'Form completo, remito (parciales)', 'icon' => 'bi-truck', 'url' => '/modules/odm_edit/?modo=entregar', 'opt' => 1210],
    ],
    // Listados (LISTADOS del menú viejo)
    'Listados' => [
        ['label' => 'Pendientes de Definición',   'desc' => 'Cola de definición',     'icon' => 'bi-diagram-3',          'url' => '/modules/reportes/?r=pend_definicion', 'opt' => 1174],
        ['label' => 'Pendientes de Programación', 'desc' => 'Cola de programación',   'icon' => 'bi-calendar-week',      'url' => '/modules/reportes/?r=pend_programacion', 'opt' => 1176],
        ['label' => 'En Producción',              'desc' => 'Órdenes por sector',     'icon' => 'bi-gear-wide-connected','url' => '/modules/reportes/?r=en_produccion'],
        ['label' => 'En Administración',          'desc' => 'Pendientes de remito',   'icon' => 'bi-inboxes',            'url' => '/modules/reportes/?r=en_administracion'],
        ['label' => 'Órdenes por PTP',            'desc' => 'Órdenes de cada pedido',  'icon' => 'bi-list-check',         'url' => '/modules/reportes/?r=por_ptp'],
        ['label' => 'Resumen por Etapa',          'desc' => 'Totales por etapa',      'icon' => 'bi-bar-chart',          'url' => '/modules/reportes/?r=resumen_etapas'],
        ['label' => 'Últimas Recibidas',          'desc' => 'Órdenes recientes',      'icon' => 'bi-clock-history',      'url' => '/modules/reportes/?r=ultimas_recibidas'],
        ['label' => 'Órdenes Anuladas',           'desc' => 'Anuladas',               'icon' => 'bi-x-octagon',          'url' => '/modules/reportes/?r=anuladas'],
        ['label' => 'Estadísticas de Uso',        'desc' => 'Adopción: páginas, usuarios, máquinas', 'icon' => 'bi-graph-up-arrow', 'url' => '/modules/uso/', 'admin' => true],
        ['label' => 'Performance',                'desc' => 'Tiempos por módulo/query — detectar cuellos de botella y cuelgues', 'icon' => 'bi-speedometer2', 'url' => '/modules/perf/', 'admin' => true],
        ['label' => 'Clonar Usuario',             'desc' => 'Crear un usuario con el mismo acceso que otro', 'icon' => 'bi-person-plus', 'url' => '/modules/clonar_usuario/', 'admin' => true],
    ],
    'Maestros' => [
        ['label' => 'Clientes',  'desc' => 'Ficha + ABM',           'icon' => 'bi-people',        'url' => '/modules/abm/?m=clientes', 'opt' => 150],
        ['label' => 'Operarios', 'desc' => 'Ficha + ABM',           'icon' => 'bi-person-badge',  'url' => '/modules/abm/?m=operarios', 'opt' => 40],
        ['label' => 'Procesos',  'desc' => 'Ficha + ABM',           'icon' => 'bi-gear',          'url' => '/modules/abm/?m=procesos', 'opt' => 30],
    ],
    'Tablas (ABM)' => [
        ['label' => 'Marcas',             'desc' => 'Alta/baja/modif.', 'icon' => 'bi-tags',        'url' => '/modules/abm/?m=marcas', 'opt' => 140],
        ['label' => 'Prendas',            'desc' => 'Alta/baja/modif.', 'icon' => 'bi-bag',         'url' => '/modules/abm/?m=prendas', 'opt' => 80],
        ['label' => 'Unidades de Medida', 'desc' => 'Alta/baja/modif.', 'icon' => 'bi-rulers',      'url' => '/modules/abm/?m=unidades', 'opt' => 170],
        ['label' => 'Colores de Tela',    'desc' => 'Alta/baja/modif.', 'icon' => 'bi-palette',     'url' => '/modules/abm/?m=colores_tela', 'opt' => 100],
        ['label' => 'Colores de Proceso', 'desc' => 'Con composición',  'icon' => 'bi-droplet',     'url' => '/modules/abm/?m=colores_proceso', 'opt' => 130],
        ['label' => 'Proveedores de Tela','desc' => 'Alta/baja/modif.', 'icon' => 'bi-truck',       'url' => '/modules/abm/?m=proveedores_tela', 'opt' => 90],
        ['label' => 'Máquinas',           'desc' => 'Con procesos',     'icon' => 'bi-cpu',         'url' => '/modules/abm/?m=maquinas', 'opt' => 60],
        ['label' => 'Supervisores',       'desc' => 'Con sectores',     'icon' => 'bi-person-gear', 'url' => '/modules/abm/?m=supervisores'],
        ['label' => 'Sectores de Personal','desc'=> 'Alta/baja/modif.', 'icon' => 'bi-people-fill', 'url' => '/modules/abm/?m=sectores_personal', 'opt' => 50],
        ['label' => 'Bases de Producto',  'desc' => 'Alta/baja/modif.', 'icon' => 'bi-box',         'url' => '/modules/abm/?m=bases_producto', 'opt' => 120],
        ['label' => 'Localidades',        'desc' => 'Alta/baja/modif.', 'icon' => 'bi-geo-alt',     'url' => '/modules/abm/?m=localidades'],
        ['label' => 'Provincias',         'desc' => 'Alta/baja/modif.', 'icon' => 'bi-map',         'url' => '/modules/abm/?m=provincias'],
        ['label' => 'Talleres',           'desc' => 'Alta/baja/modif.', 'icon' => 'bi-buildings',   'url' => '/modules/abm/?m=talleres', 'opt' => 160],
    ],
];
