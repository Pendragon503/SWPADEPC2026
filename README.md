# SWPADEPC 
Sistema Web para Alquiler de Equipos para Construcción 

Sistema web para la gestión de alquiler de maquinaria, herramientas y equipos de construcción. 
Permite administrar inventario, categorías, clientes, tarifas y contratos, consultar disponibilidad por fechas, realizar solicitudes de alquiler y controlar entregas, devoluciones, mantenimiento e historial de cada equipo. 

Desarrollado con HTML5, CSS3, Bootstrap, JavaScript, PHP, MySQL y arquitectura MVC.

## Para desarrolladores

Si vas a programar un módulo, empieza por la **[Guía de desarrollo](docs/GUIA_DESARROLLO.md)**: instalación en Windows (MySQL 26.7 + Apache de XAMPP), cómo trabajar con Git y Pull Requests, cómo crear un módulo copiando el de Categorías, reglas obligatorias, datos de prueba y la tabla de módulos con sus pantallas de Figma.

## Cómo probar el sistema

Con el sistema instalado (pasos en la guía) y la base cargada con `database/01_schema.sql` y
`database/02_seed.sql`, abre **http://localhost/SWPADEPC2026** e inicia sesión.

**Usuarios de prueba** (contraseña de todos: `Equip2026!`):

| Rol | Correo | Qué puede hacer |
|---|---|---|
| Administrador | `admin@equiprent.com` | Todo: equipos, categorías, alquileres, clientes, entregas y devoluciones, mantenimiento y Configuración (usuarios, reportes, equipos dados de baja) |
| Operador | `william@equiprent.com` | Alquileres, clientes, entregas y devoluciones, mantenimiento; equipos solo en consulta |
| Cliente | `contacto@constructora.com` | Catálogo, disponibilidad, "Mi solicitud", "Mis alquileres" y su perfil |

También puedes crear un cliente nuevo con **"Crear una cuenta"** en la pantalla de inicio de sesión.

**Flujo principal** (un alquiler de principio a fin):

1. **Cliente:** en *Catálogo* o *Disponibilidad* elige fechas y agrega uno o varios equipos; en
   *Mi solicitud* revisa el importe (tarifa diaria × días + depósito) y confirma. Queda **Pendiente**.
2. **Operador:** en *Alquileres → Solicitudes* la aprueba (o la rechaza con un motivo). Queda **Aprobado**.
3. **Operador:** desde el día de inicio, en *Entregas y Devoluciones* registra la entrega (un equipo o
   todos). El contrato pasa a **Activo** y los equipos a *Alquilado*.
4. **Operador:** en la pestaña *Devoluciones* registra la devolución, con daños si los hay. Sin daños el
   equipo vuelve a *Disponible*; con daños queda *En revisión*. Se muestran el atraso y la garantía a
   devolver. Con todo devuelto, el contrato queda **Finalizado**.
5. **Operador:** en *Mantenimiento* abre una orden para el equipo en revisión y la finaliza: el equipo
   vuelve a *Disponible*.
6. **Administrador:** revisa el *Dashboard* y genera los reportes en *Configuración → Ver reportes*
   (alquileres, inventario, fallas y salidas, en PDF o Excel).

Para volver a los datos de prueba iniciales, ejecuta otra vez `01_schema.sql` y `02_seed.sql`
(sección 12 de la guía); `03_pruebas.sql` comprueba las reglas de la base de datos.
