# Producción PTP — contexto para Claude

Front web PHP del sistema de **Producción** de PTP (Procesadora Textil Parque, codcue=18).
Construido con `inforemp-web-kit` (patrón B: abre la `.mdb` legacy vía COM/ADODB, sin migrar
datos). El legacy de escritorio (VB6/Access) y esta web conviven sobre el mismo dato.

- **Repo:** github.com/PaulDahuach/produccion_ptp_web.git (rama `master`)
- **base_url:** `/produccion_ptp` · **mode:** `readwrite`
- **Backend dev (local):** `C:\_Inforemp\_dev_ptp\Produccion PTP_w2.mdb` (COPIA, ~77MB —
  seguro escribir). En prod corre en PC del cliente (LAN detrás de firewall, sin URL pública).
- **Login dev:** usuario `OPERADOR` / clave `SU1234PER` (auth contra `Tbl Usuarios`, col DENUSR).
- **Fuente VBA legacy:** `C:\_inforemp\_sistemas\_ProcesadoraTextilParque\VBA_Source_Produccion\`
  (forms exportados a texto — leerlos para portar lógica fiel).

## Qué está hecho (todo validado HTTP + visual)
- **Consultas:** x Lote (el CORAZÓN, master-detail fiel: resumen por sector + detalle de lotes,
  filtros legacy, drill de procesos, imprimir+Excel), x Etapa, x Sector, **Retrasadas**,
  **Movimientos de Lotes** (las 2 últimas "las que más usan").
- **Transaccional producción:** Recepción (→CODETA 20) → Definición (→30, genera BARODP) →
  Programación (→sector del 1er proceso). Anular en Recepción. Circuito validado vs órdenes reales.
- **Comercial:** Órdenes de Muestra (alta **auto-crea el PTP**) → Presupuestos PTP (cálculo de
  precios EN VIVO, fórmula validada) → Confirmación/Entrega con remito (parciales). Consultas:
  ODM, PTP, Cotización.
- **Maestros:** ABM genérico form-first (16 maestros CRUD, hijos inline). 
- **Reimpresiones:** Orden de Proceso (barcode Code39), PTP, Presupuesto, Remito Muestra, ODM.
- **Dashboard "todo a la vista" (2026-06-02):** el inicio (`app/index.php`) es un **panel
  multi-columna** que muestra TODOS los grupos/opciones de un vistazo (como el panel viejo de Access
  pero limpio) + **buscador central** que filtra al tipear (Ctrl+K / `/` enfocan, Enter abre el 1er
  resultado). KPIs compactos + Favoritos arriba. Reemplazó el sidebar angosto. Grupos: Consultas /
  Procesos / Reimpresiones / Comercial / Listados / Maestros / Tablas (ABM). El menú lo define
  `config/system.php → 'menu'`.
- **Botón "Portal" (topbar):** vuelve al selector de sistemas. Config `'portal_url'`, renderizado
  RAW (no por `bu()`) → **relativo al host**. En el inicio y en cada módulo (layout.php).

## Convenciones de datos PTP (campos 6 letras)
- `CODETA` = etapa/sector. Hitos: 20=recibida, 30=definida, 31-109=en producción (sector),
  110=despacho, **120=administración (estado final de orden NUEVA = +120)**, 0=anulada.
  (OJO: el histórico tiene órdenes en -120 = optimización retroactiva de Paul para que la
  Consulta x Lote no las liste; una orden NUEVA debe terminar en **+120**.)
- `NUMODP` orden, `DSPODP` pendiente, `CANODP` cant, `BARODP` código barra (algoritmo modulo10),
  `CODCLI`/`CODMAR`/`CODPR1`. Lotes en `Tbl Ordenes De Proceso Lotes` (`ORDODP`, `CSDODP` sector
  destino, `CSOODP` sector origen). Procesos en OPP (`Tbl Ordenes De Proceso Procesos`).
- `next_number('ULTODP'|'ULTPTP'|'ULTODM'|'ULTPPP'|...)` para PKs. NUNCA MAX+1.

## Reglas técnicas (ver también el CLAUDE.md del kit)
- **PHP 5.5** target (server cliente): NO `??`, `intdiv`, arrow fns, `match`. JS ES6 OK (Chrome).
- **CRLF** EOL (`.gitattributes` puesto).
- ACE: sin `UNION` en subquery, sin `Count(DISTINCT)`, sin `Nz()`. Fechas = serial OLE.
- Escrituras en **transacción** (`db_begin/commit/rollback`). Portar lógica fiel del VBA
  (SetData A/M/B), no inventar.
- `git -C C:\wamp64\www\produccion_ptp` para todo git. `config/system.php` NO se versiona.
- **NUNCA hardcodear `localhost`** en URLs entre sistemas: en prod entran por **server-ptp** (nombre
  del server) o IP. Usar rutas **relativas al host** (`/produccion_ptp/`, `portal_url='/'` en prod).

## Pendientes conocidos
Cotización ALTA quedó cubierta (presupuesto_edit). Secundarias sin uso/datos en este install
(Adelantos, Compensaciones Destajo, Producción x Operario/Planta, Recetas) NO portadas a
propósito. Verificar con Paul `CSOODP` del lote a 120 en una orden NUEVA real (histórico=null).

## Deploy — DEPLOYADO en server-ptp (2026-06-02)
- Corre en la PC del cliente (**server-ptp**), WAMP + PHP 5.5 + ACE 32-bit. Acceso por
  `http://server-ptp/produccion_ptp/` (NO localhost).
- **mdb en prod:** `C:\_Inforemp\Produccion PTP_d2.mdb` (el archivo de **DATOS** `_d2`, NO el front
  `_w2`). Verificado que Producción no usa saved queries y `Rec Control` está en el `_d2`, así que
  apunta directo a datos. `mode=readwrite`, `portal_url='/'` (selector en la raíz del server).
- **Paquete de deploy:** `C:\_Inforemp\_Sistemas\_ProcesadoraTextilParque\Produccion_PTP_Deploy.zip`
  (pre-configurado: mdb, portal_url=/, deploy_key=`prod_ptp_2026_9Kx7mP2qZ`). Para actualizar:
  re-armar el ZIP ahí (NO en el Desktop) o subir por `deploy.php` con la deploy_key.
- Hermanos en el portal: Administración (`administracion_ptp`) y Supervisores (`supervisores_ptp`),
  selector = `ptp_portal` (en la raíz del server). Admin maneja blanco/negro por categoría de usuario.

## Historia completa
Detalle exhaustivo (cada commit, decisión, validación) en la memoria de **inforemp_inside**:
`C:\Users\pauld\.claude\projects\C--wamp64-www-inforemp-inside\memory\inforemp_web_kit.md`.
