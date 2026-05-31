# Producción PTP — Notas técnicas y estado del proyecto

Conocimiento de proyecto para quien retome el desarrollo. (Las credenciales/usuarios de prueba
**no** van acá; pedírselas a Paul.)

## Qué es

Front web de **Producción PTP** sobre la **misma `.mdb`** que usa el sistema de escritorio
(VB6/Access), vía **COM/ADODB** — patrón "B" (passthrough, sin migrar datos). Reproduce las
pantallas y la lógica del sistema viejo. Construido sobre el `inforemp-web-kit`.

- Fuente legacy de referencia: `_ProcesadoraTextilParque/VBA_Source_Produccion/` (forms exportados
  a texto) → es la **especificación**: cada pantalla web se portó leyendo su `Frm ...` y `SetData`.
- Repo del kit base: `github.com/PaulDahuach/inforemp-web-kit`.

## Restricciones y convenciones técnicas (IMPORTANTE)

- **PHP 7.4** estricto: NO usar `match`, `str_contains`, `str_starts_with`, enums, etc.
- **Solo Windows**: el driver `Microsoft.ACE.OLEDB.12.0` (Access Database Engine) no existe en Linux.
  Por eso corre en la PC del cliente, no en el hosting Linux común. Arquitectura ACE = arquitectura PHP.
- **Texto Access** viene Windows-1252 → se convierte a UTF-8 en `ado_val()` (includes/db.php).
- **Fechas Access** = serial OLE (base 1899-12-30). Helpers: `to_iso_date` / `to_disp_date` (leer),
  `fecha_access` (dd/mm/aaaa → `mm/dd/yyyy` para literal `#...#`). Algunas horas son fracción de día.
  La fecha de las operaciones sale de `Rec Control.FECAPE` (fecha de apertura), no del día calendario.
- **JOIN Access** anidados a la izquierda: *N* tablas requieren *N−1* paréntesis de apertura
  (`FROM ((((A JOIN B) JOIN C) ...)`). Es la causa #1 de "Error de sintaxis en la operación JOIN".
- **Correlativos**: `next_number($ultCol)` lee/incrementa el contador `ULT<ENT>` en `[Rec Control]`
  (replica `mdlGetNextNumber`). **Nunca `MAX+1`** (colisionaría con el escritorio).
- **No usar `Nz()`** en SQL (no confiable vía ACE) → leer-calcular-escribir en PHP.
- **`UNION` no va dentro de un subquery de FROM** en ACE → usar UNION a nivel tope y agregar en PHP
  (ej. Movimientos de Lotes).
- ACE **no soporta `Count(DISTINCT ...)`** → subconsulta agregada por entidad (ej. resumen x Lote).
- Escritura en transacción: `db_begin()` / `db_commit()` / `db_rollback()` (includes/db.php).
- Cache-bust: los `.js` de pantallas con lógica llevan `?v=N` en el include (subir N al cambiarlos).

## Circuito de Producción

`Recepción (etapa 20) → Definición (30) → Programación (sector del 1er proceso) → [Supervisores:
avance por sector] → Despacho (110) → Administración (120)`

- **Recepción** (`Frm Recepcion`, SetData "A"): inserta cabecera en `Tbl Ordenes De Proceso` (NUMODP
  desde `ULTODP`) + lote inicial. CODETA=20. Acción `PROCESA`(1)/`REPROCESA`(2): en reproceso copia
  los datos de la orden original (REPODP) — es solo auto-completado; la nueva orden es normal,
  marcada CODADO=2+REPODP. **Anular** (SetData "B"+DelData): CODETA=0, borra lotes/OEP, antepone nota a OBSODP.
- **Definición** (`Frm Definicion`, "M"): define la ruta de procesos; avanza a CODETA=30, genera
  `BARODP` (código de barras, módulo10 de `mdlModulo10`), inserta OPP + OEP por proceso, ajusta lotes.
  "Cargar PTP" trae una receta desde `Tbl PTP Procesos`.
- **Programación** (`Frm Programacion` ACTIVO; el optimizador `mdlProgramar` estaba DESHABILITADO en el
  legacy → NO se porta): pasa la orden al sector de su primer proceso. Marca OEP `WPXODP=True`.
- **Supervisores** (sistema aparte, `supervisores_ptp`): login sectorizado (operario+sector), avance
  de lotes al próximo proceso, y despacho final 110→120 (Frm Despacho). OJO: el estado final histórico
  es CODETA=120 a nivel **lote** (las órdenes casi no llegan a 110/120 a nivel orden en este install).

## Consultas (monitoreo)

- **x Lote** = la consulta CENTRAL (`Frm Consulta Ordenes de Proceso x Lote`): master-detail
  (sectores → lotes), filtros (período por `Rec Control`, ODP/O.Corte/C.Art/cliente/marca/prenda/
  "incluya proceso"), drill de procesos, reimpresión, Imprimir (con Próximo Sector + Obs como SQL_BASE)
  y Excel. Reglas: `DSPODP>0 AND CODETA>0 AND CODETA<>120`; CODETA=30 se muestra como 'PROGRAMACION'.
- **x Etapa / x Sector**: variantes. **Retrasadas** (cmdRet): definidas hace > X días, semáforo.
- **Movimientos de Lotes** (`Rpt Movimientos Lotes`): ingresos/egresos por `FFPODP` (serial) y sector,
  niveles Detalle/Sector Producción/Sector Personal/Planta. Cada lote con FFPODP genera ingreso (a su
  sector actual) y egreso (de su sector origen).

## Circuito Comercial

`Orden de Muestra (alta → crea su PTP) → Confirmación → Presupuesto (precios) → Entrega (remito)`
`     Pendiente(1) ───────────────────→ Confirmada(2) ──────────────────→ Remitida(4)`

- **Orden de Muestra** (`Frm Ordenes De Muestra`): alta AUTO-CREA su PTP (NUMPTP desde `ULTPTP` +
  inserta `Tbl PTP`), y escribe los procesos en AMBAS tablas idénticas (`Ordenes De Muestra Procesos`
  + `PTP Procesos`) → así "Cargar PTP" y el presupuesto encuentran la receta. Estados: `Tbl Estados De
  Muestra` (1=Pend,2=Confirmada,3=Anulada,4=Remitida,5=Anul.y Rem.). Catálogos: Origen=`Tbl Origenes
  De Muestra`, Acción=`Tbl Acciones De PTP`, Propiedad=`Tbl Propiedades De Prototipo`.
- **PTP** (`Frm PTP`): plantilla de ruta de procesos. Alta NUMPTP desde `ULTPTP`, CODEDP=2. Baja=DISPTP=True.
- **Confirmación / Entrega** (`Frm Confirmacion` / `Frm Entrega`): confirmar → CODEDM=2 + FDCODM.
  Entregar (parcial soportado): inserta fila en `Tbl Ordenes De Muestra Remitos` (ORDODM=Max+1, cant),
  acumula `CRMODM`; al completar `CANODM` → CODEDM=4. Cada entrega = un remito (`Rpt Ordenes de
  Remision Muestra`).
- **Presupuesto** (`Frm Presupuestos PTP`): SIEMPRE deriva de una Orden de Muestra (NUMODM). Carga
  cliente/prenda/PTP + una línea por proceso con **SUG inicial = `Tbl Procesos.NETPRC`**.
  **Fórmula (validada contra datos reales)**: `IBP=SUG×PDP%`, `PRE=SUG−IBP`, `IBX=PRE×PBX%`
  (PBX por línea, default=PDC comercial), `NET=PRE−IBX`. Cabecera: NT0=ΣSUG, IDP=ΣIBP, NT1=ΣPRE,
  IDC=ΣIBX, TOT=NT1−IDC=ΣNET. **El servidor recalcula todo** (no confía en el cliente). Alta desde `ULTPPP`.

## Módulos (carpetas en `modules/`)

Producción: `recepcion`, `definicion`, `programacion`, `ptp_edit`. Consultas: `odp_lote`, `odp_etapa`,
`odp_sector`, `odp_retrasadas`, `odp_movimientos`. Reimpresiones: `imprimir_orden`, `imprimir_ptp`.
Comercial: `odm_edit`, `odm_ciclo`, `presupuesto_edit`, `cotizacion`, `ptp`, `odm`. Listados: `reportes`
(config en `defs.php`). Maestros/Tablas: `abm` (16 maestros en `defs.php`), `maestros` (template simple).

## NO portado (sin uso/datos en este install)

- **Adelantos** (`Tbl ...Adelantos` vacía, CODETA=35 sin órdenes).
- **Recetas de Proceso/Producto** (las tablas no existen en esta `.mdb`).
- **Compensaciones Destajo / Carga Diferida Operarios** (operarios/destajo vacíos).
- **Proyección** (el optimizador automático estaba deshabilitado en el propio legacy).

## Pendientes / a verificar con Paul

- Cotización/Presupuesto: columnas `CODCUE`/`CODCAT` quedan null (no afectan total/impresión).
- Prototipos de la muestra (`Tbl ...Prototipos`, PREODM): omitidos (opcionales).
- Imágenes IM1PTP/IM2PTP del PTP (rutas de archivo del escritorio): omitidas.
- Al instalar en la PC del cliente: pasar `mode` a `readwrite` tras validar en `readonly`; cambiar la
  URL `localhost` registrada en el módulo Sistemas del inside por el host de la LAN.
- Datos de prueba dejados en la copia dev para comparar con el Access viejo: Muestras 109438/109439,
  PTP 101153–101155, Presupuestos 23410/23411 (+ órdenes web previas de validación).
