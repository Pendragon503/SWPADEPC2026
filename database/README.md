# EquipRent – Base de datos

Motor: **MySQL 26.7** (servicio de Windows `MySQL267`, puerto 3306, `lower_case_table_names=1`). Mínimo MySQL 8.0.16 por los CHECK y triggers. La MariaDB de XAMPP no se usa.

Probado el 18/10/2026 en MySQL 26.7: las 8 pruebas y las 10 pruebas negativas de `03_pruebas.sql` dan el resultado esperado.

| Script | Qué hace |
|---|---|
| `01_schema.sql` | Borra y crea la base `equiprent`: 17 tablas (la última, `reporteGenerado`, se agregó el 26/10/2026), función de conflictos, procedimiento de disponibilidad, triggers y vistas. |
| `02_seed.sql` | Datos de prueba: 3 roles, 6 usuarios, 3 clientes, 11 equipos, 6 contratos en distintos estados. Las fechas se calculan desde el día de ejecución. |
| `03_pruebas.sql` | 8 pruebas con su resultado esperado + 10 pruebas negativas (deben fallar). Hace `SET NAMES utf8mb4` para que los acentos no se dañen si la consola usa cp850. |

## Cómo ejecutarlo

**Consola (`C:\Program Files\MySQL\MySQL Server 26.7\bin`)**
```
mysql -u root -p < 01_schema.sql
mysql -u root -p < 02_seed.sql
mysql -u root -p --table < 03_pruebas.sql
```

**phpMyAdmin / MySQL Workbench:** importar o abrir los tres archivos en ese orden. Ambos entienden `DELIMITER`.

## Usuarios de prueba (contraseña: `Equip2026!`)

| Rol | Correo |
|---|---|
| Administrador | admin@equiprent.com |
| Operador | william@equiprent.com, ana@equiprent.com |
| Cliente | contacto@constructora.com, carlos.mendoza@correo.com, compras@delvalle.com |

## Reglas que garantiza la propia base

- **Sin traslapes (RF-05):** un trigger impide meter un equipo en dos contratos Pendiente/Aprobado/Activo con fechas cruzadas, aunque lleguen dos solicitudes al mismo tiempo.
- Un contrato **Activo vencido y sin devolución** bloquea el equipo hasta que regrese.
- Solo se entregan equipos de contratos Aprobados/Activos, y solo se reciben equipos que se entregaron.
- `totalEstimado = subtotal + depositoGarantia`; un rechazo exige motivo.
- **Bitácora automática** de cambios de estado del equipo. Antes de tocar `equipo`, la aplicación debe hacer:
  ```sql
  SET @app_idUsuario = <usuario en sesión>;
  SET @app_motivo = 'texto opcional';
  ```

## Tabla añadida después de la Etapa 1

- `reporteGenerado` (al final de `01_schema.sql`): historial de los reportes descargados desde
  Configuración → Ver reportes. Guarda el tipo (`alquileres`, `inventario`, `fallas`, `salidas`), el rango
  de fechas, el formato (`PDF` o `Excel`), quién lo generó y cuándo. No guarda archivos: "volver a
  descargar" regenera el reporte con los mismos parámetros. **Si tu base es anterior al 26/10/2026,
  vuelve a correr `01_schema.sql`, `02_seed.sql` y `03_pruebas.sql`.**

## Columnas añadidas al diccionario de la Etapa 1

- `cliente.codigoCliente` – código único que exige RF-03 (`CLI-000001`).
- `cliente.observaciones` – pedida en E-03.
- `contrato.destino` – pedido en RF-06 y E-04.
- `fotografiaEquipo.principalDe` – columna calculada que impide dos fotos principales por equipo.

Los tipos `SERIAL` del documento (PostgreSQL) se tradujeron a `INT/SMALLINT/BIGINT AUTO_INCREMENT`.
