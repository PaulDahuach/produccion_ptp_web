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

    'menu' => require __DIR__ . '/menu.php',

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
