# Producción PTP — Front web

Interfaz web de **Producción PTP** (Procesadora Textil Parque), construida sobre el
inforemp-web-kit. Da una versión web a las pantallas del sistema de escritorio (VB6/Access)
**leyendo y escribiendo la misma `.mdb`** vía COM/ADODB — sin migrar datos. El escritorio y la
web conviven sobre el dato vivo.

```
Navegador ──HTTPS──> Apache+PHP (Windows) ──COM/ADODB──> "Produccion PTP_w2.mdb" <── App escritorio
```

## Documentación

- **[docs/MANUAL.md](docs/MANUAL.md)** — Manual de uso (todas las pantallas y los circuitos).
- **[docs/DEPLOY.md](docs/DEPLOY.md)** — Instalación y puesta en producción en la PC del cliente.

## Estado — pantallas implementadas

**Producción**: Recepción (con reproceso) · Definición · Programación · PTP (alta/modif).
**Consultas**: x Lote (central, maestro-detalle) · x Etapa · x Sector · Retrasadas · Movimientos de Lotes.
**Reimpresiones**: Orden de Proceso · PTP.
**Comercial**: Muestra (alta/modif) · Muestras Confirmación/Entrega (remito) · Presupuesto (alta/modif con precios) · Consultas (Cotización, PTP, Órdenes de Muestra).
**Listados**: Pendientes de Definición/Programación · En Producción · En Administración · Órdenes por PTP · Resumen por Etapa · Últimas Recibidas · Anuladas.
**Maestros**: Clientes · Operarios · Procesos. **Tablas (ABM)**: 13 maestros.

Circuitos completos de punta a punta:
- **Producción**: Recepción → Definición → Programación → (Supervisores) → Despacho → Administración.
- **Comercial**: Muestra (crea PTP) → Confirmación → Presupuesto → Entrega (remito) → Remitida.

> Pantallas del menú viejo **no portadas por estar sin uso/datos en este install**: Adelantos
> (tabla vacía), Recetas (tablas inexistentes), Compensaciones/Carga Diferida (destajo/operarios
> vacíos), Proyección (optimizador deshabilitado en el propio legacy).

## Tecnología

PHP 7.4 + COM/ADODB (Microsoft.ACE.OLEDB.12.0) sobre Access · Bootstrap 5 + DataTables (CDN) ·
sin build ni dependencias a instalar.

## Configuración

Un único archivo: `config/system.php` (ver `config/system.example.php` y **docs/DEPLOY.md**).
En este repo `system.php` lleva la config de **desarrollo** (apunta a una copia local de la `.mdb`).
