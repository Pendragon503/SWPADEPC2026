# EquipRent – Base de datos

Motor: **MySQL 8.0.16+** (también compatible con MariaDB 10.4+, la que trae XAMPP).

| Script | Qué hace |
|---|---|
| `01_schema.sql` | Borra y crea la base `equiprent`: 16 tablas, función de conflictos, procedimiento de disponibilidad, triggers y vistas. |
| `02_seed.sql` | Datos de prueba: 3 roles, 6 usuarios, 3 clientes, 11 equipos, 6 contratos en distintos estados. Las fechas se calculan desde el día de ejecución. |
| `03_pruebas.sql` | 8 pruebas con su resultado esperado + 10 pruebas negativas (deben fallar). |

## Cómo ejecutarlo

**Consola (XAMPP: `C:\xampp\mysql\bin`)**
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

## Columnas añadidas al diccionario de la Etapa 1

- `cliente.codigoCliente` – código único que exige RF-03 (`CLI-000001`).
- `cliente.observaciones` – pedida en E-03.
- `contrato.destino` – pedido en RF-06 y E-04.
- `fotografiaEquipo.principalDe` – columna calculada que impide dos fotos principales por equipo.

Los tipos `SERIAL` del documento (PostgreSQL) se tradujeron a `INT/SMALLINT/BIGINT AUTO_INCREMENT`.
