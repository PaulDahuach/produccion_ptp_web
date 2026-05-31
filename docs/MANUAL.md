# Producción PTP — Manual de uso

Front web de **Producción PTP** (Procesadora Textil Parque). Corre sobre la **misma base
Access** que el sistema de escritorio (no se migran datos): lo que se carga en la web
aparece en el escritorio y viceversa. Reproduce las pantallas y la lógica del sistema viejo.

> **Login**: usuario y clave de la tabla `Tbl Usuarios` del sistema (los mismos del escritorio).

El menú está agrupado igual que el sistema viejo: **Consultas · Procesos · Reimpresiones ·
Comercial · Listados · Maestros · Tablas**.

---

## 1. Circuito de Producción

Flujo de una orden de proceso (ODP):

```
Recepción (etapa 20) → Definición (30) → Programación (sector del 1er proceso)
   → [Supervisores: avance por sector] → Despacho (110) → Administración (120)
```

### Recepción de Órdenes  · *Procesos › Recepción*
Alta de órdenes de proceso. Formulario desplegado; **Nuevo / Guardar / Cancelar / Buscar /
Imprimir / Anular**.
- Elegí **Cliente** → se cargan sus **Marcas**. **Tela** autocompleta los colores.
- **Acción**: `PROCESA` (orden nueva) o `REPROCESA`. Si es **REPROCESA**, ingresá el
  **N° de la orden original** y el formulario se autocompleta con sus datos (remito, cliente,
  marca, taller, OC, prenda, cantidad, peso, PTP, etc.); la nueva orden queda marcada como
  reproceso de esa.
- Al guardar, la orden toma su número (correlativo del sistema), queda en **Recepción (20)**
  y crea su lote inicial. Después de guardar podés **Imprimir** la orden.
- **Anular**: marca la orden como anulada y elimina sus lotes (solo si no avanzó).

### Definición de Órdenes  · *Procesos › Definición*
Define la **ruta de procesos** de una orden recibida.
- **Buscar** una orden en Recepción (20), cargá la grilla de procesos (proceso + color + % +
  obs) o usá **Cargar PTP** para traer una receta existente.
- Al **Definir**: la orden avanza a **etapa 30**, se genera su **código de barras**, y se
  crean los registros de procesos y de seguimiento. Después se puede **imprimir** la orden.

### Programación  · *Procesos › Programación*
Libera las órdenes definidas (etapa 30) a producción: cada orden pasa al **sector de su primer
proceso** y queda disponible para la planta. (No incluye el viejo optimizador automático, que
estaba deshabilitado.)

### PTP (Alta/Modificación)  · *Procesos › PTP (Alta/Modif.)*
Crea y edita las **plantillas de ruta de procesos** ("recetas"). Cabecera (cliente, marca,
fecha, denominación) + grilla de procesos. Al guardar toma su número de PTP. Estas plantillas
se usan en **Definición** con "Cargar PTP". *Discontinuar* la marca como no usable (no borra).

---

## 2. Consultas (monitoreo de planta)

### Órdenes de Proceso x Lote  · *Consultas › x Lote* — **la consulta central**
Vista maestro-detalle:
- **Filtros**: período (por defecto el rango del sistema), N° ODP, O.Corte, C.Artículo,
  cliente, marca, prenda, y "que incluyan el proceso".
- **Panel izquierdo**: sectores con cantidad de órdenes y de prendas (Administración aparte).
- **Panel derecho**: al elegir un sector, sus lotes (ODP, cliente, prenda, cantidad, días en
  recepción/definición, próximo sector, observaciones).
- Por cada orden: **ver procesos** (drill con el avance de cada proceso) y **reimprimir la orden**.
- **Imprimir** (listado del sector, con próximo sector y observaciones) y **Excel**.
- Botón **Retrasadas** → ver abajo.

### x Etapa / x Sector  · *Consultas*
Variantes de la consulta: por etapa (una fila por orden, con resumen por etapa) y por sector
(los procesos de un sector elegido). Ambas con Imprimir.

### Órdenes Retrasadas  · *Consultas › Retrasadas*
Órdenes **definidas hace más de X días** y aún no terminadas, ordenadas por la más atrasada.
Semáforo de color (rojo ≥30 días, amarillo ≥15). Clic en una fila reimprime la orden. Imprimir + Excel.

### Movimientos de Lotes  · *Consultas › Movimientos de Lotes*
Ingresos y egresos de lotes en un **rango de fecha** (y sector opcional), con totales.
**Niveles**: Detalle, Sector Producción, Sector Personal, Planta. Imprimir + Excel.
> En el día a día se usa con el rango del turno (ayer→hoy).

---

## 3. Reimpresiones

- **Orden de Proceso** · *Reimpresiones › Orden de Proceso*: reimprime cualquier orden por su
  número (también se llega desde la consulta x Lote y desde Recepción).
- **PTP** · *Reimpresiones › PTP*: reimprime una plantilla de ruta de procesos por su número.

---

## 4. Circuito Comercial (muestras y presupuestos)

```
Orden de Muestra (alta → crea su PTP) → Confirmación → Presupuesto (precios) → Entrega (remito)
        Pendiente  ───────────────────→  Confirmada  ──────────────────────→  Remitida
```

### Muestra (Alta/Modificación)  · *Comercial › Muestra (Alta/Modif.)*
Crea/edita una **Orden de Muestra**: cabecera (cliente→marca, fecha, cantidad, origen, acción,
denominación) + grilla de **procesos** + grilla de **prendas/telas**. Al guardar:
- Toma su número de muestra y **auto-crea su PTP** (la ruta de procesos), de modo que la receta
  queda disponible para Definición y para el presupuesto.
- Estado inicial **Pendiente**. *Anular* la marca como anulada.

### Muestras: Confirmación y Entrega  · *Comercial › Muestras: Conf./Entrega*
El **ciclo de vida** de la muestra, en dos pestañas:
- **Por Confirmar** (Pendientes): botón **Confirmar** → la muestra pasa a **Confirmada**.
- **Por Entregar** (Confirmadas): **Entregar** (total o parcial). Cada entrega genera un
  **remito** (se imprime) y acumula lo remitido; cuando se completa la cantidad, la muestra
  pasa a **Remitida**.

### Presupuesto (Alta/Modificación)  · *Comercial › Presupuesto (Alta/Modif.)*
Cotiza una muestra. **Nuevo** → elegís la **Orden de Muestra** → carga cliente, prenda, PTP y
una línea por proceso, con el **precio sugerido inicial = precio de lista del proceso**.
- Ajustás **% Pronto Pago** y **% Comercial** (cabecera) y, si querés, el **sugerido** y el
  **% bonificación** por línea.
- El total se calcula **en vivo**: `Precio = Sugerido − %P.Pago`, `Neto = Precio − %Bonif`,
  `Total = Σ Neto`. Imprimir el presupuesto.

### Consultas comerciales (solo lectura)
- **Cotización de Órdenes**: lista de presupuestos con su detalle de precios.
- **Consulta de PTP**: las rutas de procesos / pedidos.
- **Órdenes de Muestra**: las muestras con su detalle (procesos + prendas).

---

## 5. Listados  · *Listados*
Reportes de solo lectura (con buscador, orden por columna e **Imprimir**):
Pendientes de Definición · Pendientes de Programación · En Producción · En Administración ·
**Órdenes por PTP** (las órdenes de cada pedido) · Resumen por Etapa · Últimas Recibidas · Anuladas.

---

## 6. Maestros y Tablas
- **Maestros**: Clientes, Operarios, Procesos (ficha + alta/baja/modificación).
- **Tablas (ABM)**: Marcas, Prendas, Unidades, Colores de Tela/Proceso, Proveedores, Máquinas,
  Supervisores, Sectores, Bases de Producto, Localidades, Provincias, Talleres.
- Diseño "formulario desplegado": **Nuevo / Guardar / Cancelar / Buscar**. Los maestros con
  hijos (composición de colores, sectores de supervisor, marcas de cliente) se editan en la
  misma pantalla.

---

## Notas importantes
- **Convivencia**: el escritorio y la web operan sobre la misma `.mdb`. Lo que se carga en una
  se ve en la otra. Los números (orden, PTP, presupuesto, muestra) salen del **mismo contador**
  del sistema, así que no se pisan.
- **Fechas**: la fecha de las operaciones sale de la "fecha de apertura" del sistema (`FECAPE`),
  igual que en el escritorio.
- **Permisos**: si el sistema está en modo *solo lectura*, las pantallas de alta/edición
  muestran los datos pero no permiten guardar.
