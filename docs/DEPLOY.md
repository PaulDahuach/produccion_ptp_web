# Producción PTP — Instalación y puesta en producción

Este front web corre **en una PC Windows** (la del cliente o un servidor en su red) y abre la
**misma `.mdb`** que usa el sistema de escritorio, vía COM/ADODB. No migra datos: ambos
conviven sobre el mismo archivo. (Patrón ya probado en RDN → `rdnoroeste2.ddns.net`.)

```
Navegador ──HTTPS──> Apache+PHP (Windows) ──COM/ADODB──> "Produccion PTP_w2.mdb" <── App escritorio
```

> **Importante**: es **solo Windows**. El driver `Microsoft.ACE.OLEDB.12.0` no existe en Linux,
> por eso no se puede hostear en el servidor Linux común (ferozo). Va en una PC con la `.mdb`.

---

## 1. Requisitos en la PC

1. **WAMP** (Apache + PHP **7.4**) — o Apache + PHP 7.4 sueltos.
2. Extensión **COM** habilitada en `php.ini`:
   ```ini
   extension=php_com_dotnet
   ```
3. **Access Database Engine** (provee `Microsoft.ACE.OLEDB.12.0`):
   instalar *"Microsoft Access Database Engine 2016 Redistributable"*.
   ⚠️ La **arquitectura (x86/x64) debe coincidir con la de PHP** (PHP x64 → ACE x64).
4. Acceso de **lectura y escritura** al archivo `.mdb` desde la cuenta que corre Apache.

---

## 2. Instalación

```powershell
# 1. Copiar el proyecto a la carpeta web (ej. raíz del host)
git clone <repo>  C:\wamp64\www\produccion_ptp
#   o copiar la carpeta tal cual.

# 2. Configurar la instalación
#    Editar config/system.php (ver sección 3).
```

No hay paso de build ni dependencias externas: es PHP + las librerías por CDN.

---

## 3. Configuración (`config/system.php`)

Ajustar para producción (los valores entre ⟨⟩):

```php
return [
    'base_url'    => '',                                  // '' si vive en la raíz del host
    'name'        => 'Producción PTP',
    'tagline'     => 'Procesadora Textil Parque',

    'mdb_path'    => 'C:\\ruta\\real\\Produccion PTP_w2.mdb',   // ⟨ruta REAL de la .mdb del cliente⟩
    'mdb_provider'=> 'Microsoft.ACE.OLEDB.12.0',
    'mdb_pass'    => '',                                  // si la .mdb tiene contraseña

    'mode'        => 'readwrite',                         // 'readonly' para arrancar sin riesgo

    'auth' => ['table'=>'Tbl Usuarios','col_id'=>'CODUSR','col_name'=>'DENUSR','col_pass'=>'ACCUSR'],

    'deploy_key'  => '⟨clave-larga-única⟩',               // cambiar SIEMPRE en producción
    // ...
];
```

Recomendaciones:
- **Arrancar en `readonly`** unos días (las consultas y reimpresiones funcionan; las altas se
  ven pero no guardan) para validar contra el escritorio. Luego pasar a `readwrite`.
- `base_url=''` si el sistema está en la raíz del host; `'/subcarpeta'` si va en un subdirectorio.
- Cambiar **`deploy_key`** por una clave propia (controla `deploy.php`).

---

## 4. Publicación segura (HTTPS + acceso remoto)

Igual que RDN:
1. **DDNS** (ej. `produccionptp.ddns.net`) apuntando a la IP de la conexión del cliente, con
   el puerto 443 redirigido a la PC.
2. **Certbot** para el certificado HTTPS (`c:\Certbot`), renovación automática.
3. En Apache, virtual host del dominio apuntando a `C:\wamp64\www\produccion_ptp`.

---

## 5. Actualizaciones (deploy de cambios)

`deploy.php` permite subir archivos por `curl` usando la `deploy_key` (sin tocar la PC a mano):

```bash
# subir un archivo
curl -X POST https://<host>/deploy.php -F "key=CLAVE" -F "path=modules/odp_lote/api.php" -F "file=@api.php"
# subir un zip (se descomprime en destino)
curl -X POST https://<host>/deploy.php -F "key=CLAVE" -F "zipfile=@deploy.zip"
```

> No sube `config/system.php` ni `log/`. Mantener la `deploy_key` en secreto.

---

## 6. Checklist de puesta en marcha

- [ ] PHP 7.4 con `php_com_dotnet` habilitado (`php -m` lista `com_dotnet`).
- [ ] Access Database Engine instalado, **misma arquitectura** que PHP.
- [ ] `config/system.php` con `mdb_path` real y `mode` correcto.
- [ ] Login OK con un usuario real (tabla `Tbl Usuarios`).
- [ ] Una **consulta x Lote** trae datos (valida la conexión a la `.mdb`).
- [ ] En `readwrite`: un alta de prueba (recepción) genera el **mismo correlativo** que vería
      el escritorio, y aparece en ambos. Borrar/anular la prueba si corresponde.
- [ ] HTTPS + DDNS funcionando desde fuera de la red.
- [ ] `deploy_key` cambiada.

---

## Notas técnicas
- **PHP 7.4** estricto: no usar `match`, `str_contains`, `str_starts_with`, enums, etc.
- Las **fechas** Access se guardan/leen como serial OLE (base 1899-12-30); los helpers del kit
  (`to_iso_date`/`to_disp_date`/`fecha_access`) las convierten.
- Los **JOIN** Access van anidados a la izquierda: *N* tablas requieren *N−1* paréntesis de apertura.
- Los **correlativos** (orden, PTP, presupuesto, muestra) se toman del contador `Rec Control`
  (`next_number`), igual que el escritorio — nunca `MAX+1`.
- La cookie de sesión es `IWKSESSID`. En producción cada sistema vive en su host → sin conflicto.
