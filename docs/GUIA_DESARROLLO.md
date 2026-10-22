# Guía de desarrollo – EquipRent

Guía para los integrantes del Grupo de Proyecto 3 que van a programar un módulo del sistema.
No da nada por sabido: si es tu primera vez con el proyecto, síguela en orden y copia los
comandos tal como están.

> Si algo de esta guía no funciona en tu computadora, avísale al grupo antes de "arreglarlo" por tu cuenta.

## Contenido

1. [Qué vas a instalar](#1-qué-vas-a-instalar)
2. [Instalación en Windows, paso a paso](#2-instalación-en-windows-paso-a-paso)
3. [Errores comunes y su solución](#3-errores-comunes-y-su-solución)
4. [Git paso a paso](#4-git-paso-a-paso)
5. [Estructura del proyecto](#5-estructura-del-proyecto)
6. [Cómo funciona una página por dentro](#6-cómo-funciona-una-página-por-dentro)
7. [Receta: crear un módulo nuevo copiando Categorías (ejemplo: Usuarios y roles)](#7-receta-crear-un-módulo-nuevo-copiando-categorías-ejemplo-usuarios-y-roles)
8. [Reglas obligatorias](#8-reglas-obligatorias)
9. [Librerías, estilos y componentes](#9-librerías-estilos-y-componentes)
10. [Reglas entre módulos](#10-reglas-entre-módulos)
11. [Datos de prueba](#11-datos-de-prueba)
12. [Restaurar la base de prueba](#12-restaurar-la-base-de-prueba)
13. [Lista de "terminado" antes del Pull Request](#13-lista-de-terminado-antes-del-pull-request)
14. [Módulos, responsables y pantallas de Figma](#14-módulos-responsables-y-pantallas-de-figma)

---

## 1. Qué vas a instalar

| Programa | Para qué | Versión |
|---|---|---|
| **MySQL Server** | Base de datos | **26.7** (la misma de todo el grupo) |
| **XAMPP** | Solo **Apache** (servidor web) y **PHP** | PHP 8.0 o superior (el grupo usa 8.0.30) |
| **Git para Windows** | Descargar el proyecto y subir tus cambios | La más reciente |
| **Visual Studio Code** (recomendado) | Editar el código | La más reciente |
| Google Chrome y Microsoft Edge | Probar (el sistema debe funcionar en ambos, RNF-07) | Las más recientes |

> **Importante:** XAMPP trae su propia base de datos (MariaDB, el botón "MySQL" del panel de XAMPP).
> **Nunca la enciendas.** El proyecto usa MySQL 26.7 instalado aparte, y los dos no pueden usar el
> puerto 3306 al mismo tiempo.

---

## 2. Instalación en Windows, paso a paso

### 2.1 MySQL 26.7

1. Descarga el instalador de MySQL Server 26.7 para Windows desde la página oficial de MySQL.
2. Ejecútalo y usa estas opciones (los nombres de las pantallas pueden variar un poco):

   | Pantalla | Qué elegir |
   |---|---|
   | Tipo de instalación | Solo **MySQL Server** (no hace falta Workbench ni los ejemplos) |
   | Si detecta otro servidor MySQL o MariaDB | Instalar **lado a lado** (*side-by-side*), sin tocar el otro |
   | Tipo de configuración | *Development Computer* |
   | Conectividad | **TCP/IP, puerto 3306**, y marcar "abrir el puerto en el firewall" |
   | Cuenta root | Escribe una **contraseña para root** y **anótala**: la vas a usar en los pasos 2.5 y 2.6 |
   | Servicio de Windows | **Sí, como servicio de Windows**, con el nombre **`MySQL267`** y que **inicie con Windows** |
   | Opciones avanzadas → *Table Names Case* | **Lower Case** (`lower_case_table_names = 1`) |
   | Bases de datos de ejemplo (*sakila*, *world*) | **No instalar** |

   > ⚠️ La opción **Table Names Case = Lower Case** solo se puede elegir al instalar. Si te equivocas,
   > hay que desinstalar MySQL y borrar su carpeta de datos para volver a instalar.

3. Comprueba que quedó bien. Abre **Símbolo del sistema** (tecla Windows, escribe `cmd`, Enter) y copia:

   ```bat
   "C:\Program Files\MySQL\MySQL Server 26.7\bin\mysql.exe" -u root -p -e "SELECT VERSION(), @@lower_case_table_names, @@port;"
   ```

   Te pedirá la contraseña de root. Debe mostrar `26.7.x`, `1` y `3306`.

> En esta guía todos los comandos son para **Símbolo del sistema (cmd)**, no para PowerShell.
> PowerShell no entiende el `<` que usamos para ejecutar los scripts SQL.

### 2.2 XAMPP (solo Apache y PHP)

1. Descarga XAMPP para Windows (con PHP 8.0 o superior) desde apachefriends.org e instálalo en `C:\xampp`.
   En los componentes basta con **Apache** y **PHP**; si marcas MySQL, no lo uses.
2. Abre el **XAMPP Control Panel** y presiona **Start** solo en **Apache**.
3. **No presiones Start en MySQL.** Si alguna vez quedó configurado para arrancar solo, desmárcalo en
   *Config → Autostart of modules*.
4. Prueba en el navegador: `http://localhost` debe mostrar la página de bienvenida de XAMPP.

### 2.3 Git

1. Instala **Git para Windows** (git-scm.com) con las opciones por defecto.
2. Configura tu nombre y tu correo, **los mismos de tu cuenta de GitHub** (se hace una sola vez):

   ```bat
   git config --global user.name "Tu Nombre Apellido"
   git config --global user.email "tu-correo@ejemplo.com"
   ```

3. Pídele a William (dueño del repositorio) que te agregue como colaborador en GitHub
   (*Settings → Collaborators*). Acepta la invitación que te llega por correo.

### 2.4 Descargar (clonar) el proyecto

El proyecto debe quedar dentro de `C:\xampp\htdocs`, porque esa es la carpeta que sirve Apache:

```bat
cd /d C:\xampp\htdocs
git clone https://github.com/Pendragon503/SWPADEPC2026.git
cd SWPADEPC2026
```

La primera vez Git puede abrir una ventana para iniciar sesión en GitHub: inicia sesión con tu cuenta.

### 2.5 Crear la base de datos

Ejecuta los tres scripts **en este orden**. Cada comando pide la contraseña de root:

```bat
cd /d C:\xampp\htdocs\SWPADEPC2026\database
"C:\Program Files\MySQL\MySQL Server 26.7\bin\mysql.exe" -u root -p < 01_schema.sql
"C:\Program Files\MySQL\MySQL Server 26.7\bin\mysql.exe" -u root -p < 02_seed.sql
"C:\Program Files\MySQL\MySQL Server 26.7\bin\mysql.exe" -u root -p --table < 03_pruebas.sql
```

- `01_schema.sql` **borra y vuelve a crear** la base `equiprent` (tablas, triggers, vistas).
- `02_seed.sql` carga los datos de prueba (sección 11).
- `03_pruebas.sql` muestra 8 resultados; compáralos con el texto `ESPERADO` que está en ese mismo
  archivo. Si todos coinciden, la base está bien.

Si un comando no muestra nada, salió bien. Si muestra `ERROR`, revisa la sección 3.

### 2.6 Configurar tu conexión

1. Copia la plantilla de configuración:

   ```bat
   cd /d C:\xampp\htdocs\SWPADEPC2026
   copy config\config.example.php config\config.local.php
   ```

2. Abre `config\config.local.php` en VS Code y escribe tu contraseña de root en `'clave'`:

   ```php
   'clave'   => 'tu-contraseña-de-root',
   ```

   Deja `'debug' => true` mientras programas: si algo falla, verás el detalle del error.

> `config.local.php` **nunca se sube a GitHub** (está en `.gitignore`). Cada quien tiene el suyo con su
> propia contraseña. No lo borres del `.gitignore` ni lo agregues a un commit.

### 2.7 Abrir el sistema

1. Con Apache encendido, abre **`http://localhost/SWPADEPC2026/public`**.
2. Entra con un usuario de prueba; la contraseña de todos es **`Equip2026!`**:
   - Administrador: `admin@equiprent.com`
   - Operador: `william@equiprent.com`
   - Cliente: `contacto@constructora.com`
3. Cada rol entra a su propio panel. Si ves el panel, la instalación está completa. 🎉

---

## 3. Errores comunes y su solución

### MySQL no arranca o el puerto 3306 está ocupado

Casi siempre es la MariaDB de XAMPP encendida.

1. En el XAMPP Control Panel, presiona **Stop** en MySQL y desmarca su *Autostart*.
2. Para ver qué programa usa el puerto 3306:

   ```bat
   netstat -ano | findstr :3306
   ```

   El último número de cada línea es el PID. Búscalo con:

   ```bat
   tasklist /FI "PID eq 1234"
   ```

   (cambia `1234` por tu número). Si dice `mysqld.exe` de `C:\xampp`, es la de XAMPP: apágala.
3. Enciende el servicio de MySQL 26.7: tecla Windows → **Servicios** → `MySQL267` → **Iniciar**.

### Error 1045 "Access denied for user 'root'"

La contraseña no coincide.

- En la consola: escribe bien la contraseña de root cuando la pida.
- En el sistema (aparece como "código 1045: acceso denegado"): revisa `'clave'` en `config\config.local.php`.

### Apache no arranca (puerto 80 ocupado)

Otro programa usa el puerto 80 (Skype, IIS o el servicio "World Wide Web Publishing").

```bat
netstat -ano | findstr :80
```

- Cierra ese programa, o detén el servicio "Servicio de publicación World Wide Web" en **Servicios**.
- Si no puedes, cambia el puerto de Apache: XAMPP → Apache → *Config* → `httpd.conf`, cambia
  `Listen 80` por `Listen 8080`, y en tu `config.local.php` usa
  `'urlBase' => 'http://localhost:8080/SWPADEPC2026/public'`. Abre el sistema con `:8080`.

### Las tildes o la ñ se ven mal (`Ã¡`, `â”œÃ­`) o se guardan mal

- Los scripts SQL ya incluyen `SET NAMES utf8mb4;`. Si ejecutas SQL a mano en la consola, escribe
  primero `SET NAMES utf8mb4;`.
- Guarda tus archivos `.php` en **UTF-8** (VS Code lo hace por defecto; abajo a la derecha dice `UTF-8`).
- La conexión PDO ya usa `charset=utf8mb4`; no la cambies.

### Página en blanco, 404 en todas las páginas o "Not Found" de Apache

1. La dirección debe incluir **`/public`**: `http://localhost/SWPADEPC2026/public`.
2. `urlBase` en `config.local.php` debe ser exactamente esa dirección.
3. **mod_rewrite** debe estar activo: abre `C:\xampp\apache\conf\httpd.conf` y verifica que la línea
   `LoadModule rewrite_module modules/mod_rewrite.so` **no** empiece con `#`, y que en el bloque
   `<Directory "C:/xampp/htdocs">` diga `AllowOverride All`. Reinicia Apache después de cambiarlo.
4. Con `'debug' => true` los errores de PHP se muestran en pantalla. Si aun así está en blanco,
   revisa el final de `C:\xampp\apache\logs\error.log`.

### "Falta config/config.local.php"

No hiciste el paso 2.6. Copia `config.example.php` como `config.local.php`.

### "El formulario expiró. Inténtalo de nuevo."

El formulario se envió sin el token CSRF o con uno viejo (por ejemplo, la sesión venció). Recarga la
página y vuelve a enviarlo. Si te pasa siempre en tu módulo, te falta `<?= csrfCampo() ?>` dentro del `<form>`.

### Me da 403 al abrir una página

- Es normal si entras al panel de otro rol (por ejemplo, el Operador abriendo `/admin`).
- Es normal en `http://localhost/SWPADEPC2026/config/...` o `/database/...`: esas carpetas están
  bloqueadas a propósito. Solo `public/` es visible desde el navegador.

---

## 4. Git paso a paso

### La regla de oro

**Nadie sube directo a `main`.** Cada quien trabaja en **su rama** y pide que se una a `main` con un
**Pull Request** en GitHub, que otra persona revisa antes de aceptarlo.

> Para William: en GitHub, *Settings → Branches → Add branch protection rule*, rama `main`, marca
> *Require a pull request before merging*. Así GitHub no deja subir directo a `main` por error.

### Cada día, antes de empezar

Trae lo último que subieron los demás:

```bat
cd /d C:\xampp\htdocs\SWPADEPC2026
git checkout main
git pull origin main
```

Luego vuelve a tu rama y trae esos cambios a ella (ver "Actualizar tu rama con main" más abajo).

### Crear tu rama (una sola vez por módulo)

```bat
git checkout main
git pull origin main
git checkout -b feature/inventario
```

Usa el nombre de la tabla de la sección 14 (`feature/inventario`, `feature/clientes`, …).
Para volver a tu rama otro día: `git checkout feature/inventario`.

### Guardar tu trabajo (commit)

1. Revisa qué cambiaste:

   ```bat
   git status
   ```

2. Agrega los archivos que son de tu módulo (con su ruta; **no** uses `git add .` sin revisar antes):

   ```bat
   git add app/controllers/UsuarioAdminController.php app/models/Usuario.php app/views/usuarios/index.php app/rutas.php
   ```

3. Haz el commit siguiendo la **convención del grupo**:

   ```bat
   git commit -m "Add: listado de usuarios con búsqueda y paginación"
   ```

**Convención de mensajes:** `Prefijo: descripción en español`, en minúscula después de los dos
puntos y sin punto final.

| Prefijo | Cuándo |
|---|---|
| `Add:` | algo nuevo (`Add: formulario de registro de clientes`) |
| `Fix:` | corregir un error (`Fix: el total no sumaba el depósito`) |
| `Update:` | cambiar algo que ya existía (`Update: se agregó el filtro por estado`) |
| `Style:` | solo apariencia (`Style: tabla de entregas en celular`) |
| `Refactor:` | reorganizar sin cambiar lo que hace |
| `Docs:` | documentación |

Haz **commits pequeños**: uno por cada cosa que funcione (listar, crear, editar…), no uno gigante al final.

### Subir tu rama a GitHub

La primera vez:

```bat
git push -u origin feature/inventario
```

Las siguientes veces, desde tu rama, basta con:

```bat
git push
```

### Crear el Pull Request (desde la página de GitHub)

1. Entra a `https://github.com/Pendragon503/SWPADEPC2026`.
2. Aparece un aviso amarillo con tu rama y el botón **Compare & pull request**. Presiónalo.
   (Si no aparece: pestaña **Pull requests → New pull request**, *base:* `main`, *compare:* tu rama.)
3. Título con la convención, por ejemplo `Add: módulo de usuarios y roles`.
4. En la descripción: qué hace, cómo probarlo (usuario y pasos) y la lista de la sección 13 marcada.
5. En **Reviewers**, elige a un compañero. Presiona **Create pull request**.
6. Si el revisor pide cambios, hazlos en tu misma rama, `commit` y `push`: el Pull Request se actualiza solo.
7. Cuando esté aprobado, se une con **Merge pull request** y se borra la rama (**Delete branch**).

### Actualizar tu rama con main

Hazlo cada día y siempre antes de crear el Pull Request:

```bat
git checkout main
git pull origin main
git checkout feature/inventario
git merge main
```

Si no hay conflictos, Git une todo solo (puede abrir un editor para el mensaje del merge: guarda y
cierra). Después: `git push`.

### Resolver un conflicto

Un conflicto pasa cuando tú y otra persona cambiaron las mismas líneas (muy común en `app/rutas.php`
o en el menú). Git te avisa con `CONFLICT (content): Merge conflict in app/rutas.php`.

1. Abre el archivo en VS Code. Verás algo así:

   ```text
   <<<<<<< HEAD
   $router->get('/admin/usuarios', 'UsuarioAdminController@index');
   =======
   $router->get('/admin/clientes', 'ClienteAdminController@index');
   >>>>>>> main
   ```

   - Entre `<<<<<<< HEAD` y `=======` está **tu** versión.
   - Entre `=======` y `>>>>>>> main` está la versión que viene de **main**.
2. Deja el archivo como debe quedar. En las rutas casi siempre son **las dos líneas**. VS Code tiene
   botones arriba del conflicto: *Accept Both Changes* (aceptar ambos) suele ser lo correcto aquí.
3. Borra las líneas `<<<<<<<`, `=======` y `>>>>>>>` si quedaron.
4. Guarda, prueba que el sistema siga funcionando, y termina el merge:

   ```bat
   git add app/rutas.php
   git commit -m "Fix: conflicto de rutas al unir main"
   git push
   ```

Si te sientes perdido en medio de un merge, `git merge --abort` deja todo como estaba antes.

### Comandos de rescate

| Situación | Comando |
|---|---|
| ¿En qué rama estoy y qué cambié? | `git status` |
| Ver los últimos commits | `git log --oneline -10` |
| Deshacer los cambios de un archivo (sin commit) | `git restore ruta/del/archivo.php` |
| Cambié de opinión con un `git add` | `git restore --staged ruta/del/archivo.php` |
| Hice cambios en `main` por error (sin commit) | `git checkout -b feature/mi-modulo` (los cambios se van contigo a la rama nueva) |

---

## 5. Estructura del proyecto

```
SWPADEPC2026/
├── .htaccess                 ← bloquea todo lo que no sea public/ y redirige la raíz a public/
├── app/
│   ├── bootstrap.php         ← arranque: configuración, autocarga, errores, sesión
│   ├── rutas.php             ← TABLA DE RUTAS: aquí agregas las de tu módulo
│   ├── core/                 ← núcleo (Router, Controller, Database, Auth, helpers)  ⚠️
│   ├── controllers/          ← un controlador por módulo (CategoriaController.php…)
│   ├── models/               ← un modelo por tabla o tema (Categoria.php…)
│   └── views/
│       ├── layouts/          ← plantillas: principal (Admin/Operador), cliente, simple, menú  ⚠️
│       ├── componentes/      ← piezas reutilizables (paginacion.php, estadoEquipos.php)
│       ├── categorias/       ← vistas de cada módulo, una carpeta por módulo
│       └── …
├── config/
│   ├── config.example.php    ← plantilla (se sube)
│   └── config.local.php      ← TU configuración con tu contraseña (NO se sube)
├── database/
│   ├── 01_schema.sql         ← estructura de la base  ⚠️
│   ├── 02_seed.sql           ← datos de prueba
│   ├── 03_pruebas.sql        ← pruebas de la base
│   └── README.md
├── docs/                     ← documento de la Etapa 1 y esta guía
└── public/                   ← LO ÚNICO visible desde el navegador
    ├── index.php             ← punto de entrada: toda página pasa por aquí
    ├── .htaccess
    ├── assets/
    │   ├── css/app.css       ← TODOS los estilos propios  ⚠️
    │   ├── js/app.js         ← JavaScript propio
    │   ├── img/
    │   └── vendor/           ← Bootstrap, Bootstrap Icons, jQuery, Inter (no se tocan)
    └── uploads/              ← archivos que suben los usuarios (fotos de equipos)
```

**No se tocan sin avisar al grupo** (marcados con ⚠️): `app/core/`, `database/01_schema.sql`,
`app/views/layouts/` y `public/assets/css/app.css`, además de `public/assets/vendor/` y los
`.htaccess`. Un cambio ahí afecta a todos los módulos. Si necesitas algo nuevo en ellos (una función
de ayuda, un componente de estilo, una columna), propónlo en el grupo y que lo haga una sola persona.

Sí puedes (y debes) agregar tus líneas en `app/rutas.php` y tu enlace en el menú
(`layouts/menu.php` o `layouts/cliente.php`): son cambios pequeños y esperados.

---

## 6. Cómo funciona una página por dentro

Ejemplo: el administrador abre `http://localhost/SWPADEPC2026/public/admin/categorias?buscar=excav`.

1. **`public/index.php`** recibe la petición y carga `app/bootstrap.php`.
2. **El Router** busca en `app/rutas.php` la ruta `GET /admin/categorias` y encuentra
   `CategoriaController@index`.
3. **El controlador** (`CategoriaController`) revisa el rol con `requireRole('Administrador')`, lee
   `$_GET['buscar']` y le pide los datos al modelo.
4. **El modelo** (`Categoria`) hace la consulta SQL con PDO y devuelve un arreglo.
5. **La vista** (`app/views/categorias/index.php`) dibuja el HTML con esos datos, dentro del
   **layout** (`layouts/principal.php`), que pone el menú y la barra superior.

**Quién hace qué (regla del grupo):**

| Capa | Hace | No hace |
|---|---|---|
| **Controlador** | Lee `$_GET`/`$_POST`, llama al modelo y decide qué mostrar: vista, `flash()`, `redirect()` | No valida datos ni aplica reglas de negocio |
| **Modelo** | **Valida** (`validar($datos, $id)` devuelve `['campo' => 'mensaje']`), aplica las **reglas de negocio** (`validarCambioEstado(...)` devuelve el mensaje de bloqueo o `null`) y hace las consultas | No lee `$_POST` ni imprime HTML |
| **Vista** | Dibuja el HTML con `e()` | No consulta la base |

Ejemplo en `CategoriaController::guardar()`:

```php
$datos = $this->leerFormulario();          // controlador: leer
$errores = Categoria::validar($datos, 0);  // modelo: validar
if ($errores) {
    $this->volverConErrores('crear', 0, $datos, $errores);  // controlador: decidir
}
Categoria::crear($datos);                  // modelo: guardar
```

**Rutas sin parámetros en la URL.** El Router compara la ruta completa, así que direcciones como
`/admin/categorias/5` **no existen**. En su lugar:

- Los formularios **POST** llevan el id en un **campo oculto**: `<input type="hidden" name="idCategoria" value="5">`.
- Las páginas **GET de detalle** usan **query string**: `/admin/equipos/detalle?id=5`, y el
  controlador lo lee con `(int) ($_GET['id'] ?? 0)`.

**Nunca uses un enlace GET para cambiar datos** (crear, editar, desactivar, aprobar…): siempre un
formulario POST con `csrfCampo()`.

---

## 7. Receta: crear un módulo nuevo copiando Categorías (ejemplo: Usuarios y roles)

El módulo de **Categorías** es el ejemplo oficial: listado + modal, con validaciones en el modelo.
Está comentado línea por línea; léelo completo antes de empezar:

- `app/controllers/CategoriaController.php`
- `app/models/Categoria.php`
- `app/views/categorias/index.php`

Para algo más grande (filtros, página de detalle, fotos, transacciones, bitácora), mira
**Inventario**: `EquipoController`, `Equipo`, `FotografiaEquipo`, `Tarifa` y `app/views/equipos/`.

Vamos a crear **Usuarios y roles** (Administrador, RF-02): listar los usuarios internos
(Administrador y Operador), crear, editar y activar/desactivar. Tabla `usuario` en
`database/01_schema.sql`: `idUsuario, nombres, apellidos, correo (único), claveHash, telefono,
documentoIdentidad (único), direccion, activo, ultimoAcceso, idRol`.

### Paso 0. Rama

```bat
git checkout main
git pull origin main
git checkout -b feature/usuarios-clientes
```

### Paso 1. Rutas (`app/rutas.php`)

Agrega tu bloque **debajo** de los que ya existen:

```php
// Administrador: usuarios y roles (RF-02)
$router->get('/admin/usuarios', 'UsuarioAdminController@index');
$router->post('/admin/usuarios/guardar', 'UsuarioAdminController@guardar');
$router->post('/admin/usuarios/actualizar', 'UsuarioAdminController@actualizar');
$router->post('/admin/usuarios/cambiar-estado', 'UsuarioAdminController@cambiarEstado');
```

(El modelo `Usuario` ya existe y lo usa el login: agrégale métodos, no lo reemplaces.
El controlador se llama `UsuarioAdminController` para no confundirlo con `AuthController`.)

### Paso 2. Modelo (`app/models/Usuario.php`)

Copia de `Categoria.php` los métodos que necesites y adáptalos a la tabla `usuario`. El listado
trae también el nombre del rol, y **solo los usuarios internos**:

```php
public static function listarInternos(string $buscar, int $limite, int $desplazamiento): array
{
    $sentencia = Database::conexion()->prepare(
        "SELECT u.idUsuario, u.nombres, u.apellidos, u.correo, u.telefono, u.activo,
                u.ultimoAcceso, u.idRol, r.nombre AS rol
           FROM usuario u
           JOIN rol r ON r.idRol = u.idRol
          WHERE r.nombre IN ('Administrador', 'Operador')
            AND (u.nombres LIKE ? OR u.apellidos LIKE ? OR u.correo LIKE ?)
          ORDER BY u.apellidos, u.nombres
          LIMIT ? OFFSET ?"
    );
    $patron = '%' . $buscar . '%';
    $sentencia->bindValue(1, $patron);
    $sentencia->bindValue(2, $patron);
    $sentencia->bindValue(3, $patron);
    $sentencia->bindValue(4, $limite, PDO::PARAM_INT);
    $sentencia->bindValue(5, $desplazamiento, PDO::PARAM_INT);
    $sentencia->execute();
    return $sentencia->fetchAll();
}
```

Las **validaciones van en el modelo** (`Usuario::validar(array $datos, int $idUsuario): array`),
nunca en el controlador. Ejemplos:

```php
if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
    $errores['correo'] = 'Escribe un correo válido.';
} elseif (self::existeCorreo($datos['correo'], $idUsuario)) {
    $errores['correo'] = 'Ya hay un usuario con ese correo.';
}
// Al crear, la contraseña es obligatoria; al editar, vacía = no cambiarla.
if ($idUsuario === 0 && mb_strlen($datos['clave']) < 8) {
    $errores['clave'] = 'La contraseña debe tener al menos 8 caracteres.';
}
```

Y la contraseña **nunca** se guarda tal cual: `password_hash($datos['clave'], PASSWORD_DEFAULT)`.

Reglas de negocio, también en el modelo (`validarCambioEstado()`, como en Categorías):
- un administrador no puede desactivarse a sí mismo (`Auth::id()`);
- no se puede desactivar al último administrador activo.

### Paso 3. Controlador (`app/controllers/UsuarioAdminController.php`)

Copia `CategoriaController.php` y:

1. Cambia el nombre de la clase, `SESION_FORMULARIO` (`'formularioUsuario'`) y la ruta de `urlListado()`
   (`'/admin/usuarios'`).
2. Deja `requireRole('Administrador')` en el constructor.
3. En `leerFormulario()`, lee los campos (el controlador **solo lee**):

   ```php
   return [
       'nombres'   => trim((string) ($_POST['nombres'] ?? '')),
       'apellidos' => trim((string) ($_POST['apellidos'] ?? '')),
       'correo'    => mb_strtolower(trim((string) ($_POST['correo'] ?? ''))),
       'telefono'  => trim((string) ($_POST['telefono'] ?? '')),
       'idRol'     => (int) ($_POST['idRol'] ?? 0),
       'clave'     => (string) ($_POST['clave'] ?? ''),
   ];
   ```

4. Llama al modelo para validar y guardar, igual que en Categorías.
5. **Nunca** devuelvas la contraseña a la vista: en `volverConErrores()`, quítala de `$datos`
   antes de guardarlos en la sesión (`unset($datos['clave'])`).
6. Si la base responde con un duplicado, usa el helper `esValorDuplicado($e, 'uq_usuario_correo')`.

### Paso 4. Vista (`app/views/usuarios/index.php`)

Crea la carpeta `app/views/usuarios/` y copia `categorias/index.php`. Cambia:

- Los textos ("Usuarios y roles", "Nuevo usuario"), las URLs (`/admin/usuarios/...`) y el id del modal (`modalUsuario`).
- Las columnas: Nombre, Correo, Rol, Último acceso (`fechaCorta($u['ultimoAcceso'], true)`), Estado
  (`estadoBadge($u['activo'] ? 'Activo' : 'Inactivo')`) y Acciones.
- Los campos del modal y su `data-campos` (todos los `name` del formulario). Si el formulario
  tiene muchos campos, usa `modal-lg` y dos columnas, como `equipos/_formulario.php`. Ejemplo de `<select>`:

  ```php
  <div>
      <label for="idRol" class="form-label"><i class="bi bi-person-badge" aria-hidden="true"></i> Rol</label>
      <select id="idRol" name="idRol" class="form-select<?= isset($errores['idRol']) ? ' is-invalid' : '' ?>">
          <option value="">Elige un rol</option>
          <?php foreach ($roles as $rol): ?>
              <option value="<?= e($rol['idRol']) ?>"<?= (string) $valores['idRol'] === (string) $rol['idRol'] ? ' selected' : '' ?>>
                  <?= e($rol['nombre']) ?>
              </option>
          <?php endforeach; ?>
      </select>
      <?php if (isset($errores['idRol'])): ?>
          <div class="invalid-feedback"><?= e($errores['idRol']) ?></div>
      <?php endif; ?>
  </div>
  ```

- La contraseña: `<input type="password" name="clave" autocomplete="new-password">`, **sin** `value`
  (nunca se vuelve a mostrar). Al editar, un texto de ayuda: "Déjala vacía para no cambiarla".
- La paginación: `$rutaPaginacion = '/admin/usuarios';`.

### Paso 5. Enlace en el menú

En `app/views/layouts/menu.php`, cambia el `'#'` de la fila de Usuarios por la ruta:

```php
['bi-people-fill', 'Usuarios y roles', '/admin/usuarios'],
```

Y en tu controlador pasa `'menuActivo' => 'Usuarios y roles'` a la vista (debe ser igual al texto
del menú), para que el enlace se marque en naranja. Para el **Cliente** el menú está en
`layouts/cliente.php` (arreglo `$menuCliente`) y `menuActivo` usa la clave (`'catalogo'`, `'alquileres'`…).

### Paso 6. Roles

- Admin: `requireRole('Administrador')`. Operador: `requireRole('Operador')`.
- Si el requerimiento dice que lo usan los dos (por ejemplo, Clientes en RF-03):
  `requireRole('Administrador', 'Operador')`.
- Cliente: `requireRole('Cliente')`, y **siempre** filtra sus datos con `Auth::idCliente()`
  (`WHERE c.idCliente = ?`), para que un cliente nunca vea los alquileres de otro.

### Paso 7. Probar y subir

Prueba con la lista de la sección 13, haz tus commits y crea el Pull Request (sección 4).

### ¿Modal o página aparte?

- **Formularios cortos** (pocos campos, como Categorías o Tarifas): en un **modal `.modal-er`**, igual que Categorías.
- **Formularios largos** (registrar un equipo con fotos, la solicitud de alquiler, registrar una
  devolución con varios equipos): en **una página aparte** con su propia ruta GET
  (por ejemplo `/admin/equipos/nuevo` y `/admin/equipos/editar?id=5`), con el formulario dentro de un `.panel`.
- Si el frame de Figma de tu pantalla dice otra cosa, sigue a Figma.

---

## 8. Reglas obligatorias

1. **Siempre sentencias preparadas.** Nunca pegues un valor dentro del SQL:

   ```php
   // MAL: inyección SQL
   $db->query("SELECT * FROM cliente WHERE codigoCliente = '$codigo'");
   // BIEN
   $sentencia = Database::conexion()->prepare('SELECT * FROM cliente WHERE codigoCliente = ?');
   $sentencia->execute([$codigo]);
   ```

2. **Siempre `e()` al imprimir datos** en una vista: `<?= e($cliente['razonSocial']) ?>`.
   Las únicas excepciones son las funciones que ya devuelven HTML seguro: `estadoBadge()` y `csrfCampo()`.
3. **CSRF en todo formulario POST:** `<?= csrfCampo() ?>` dentro de cada `<form method="post">`.
   El Router rechaza los POST sin token.
4. **Nombres de tablas y columnas exactos** como en `database/01_schema.sql`: `detalleContrato`,
   no `detallecontrato`; `idCliente`, no `idcliente`. En Windows un error de mayúsculas no se nota,
   pero en un servidor Linux la consulta falla.
5. **Antes de cambiar el estado de un equipo**, escribe el motivo para la bitácora (ver sección 10):
   `SET @app_motivo = ?`.
6. **Mensajes en español**, claros, que digan qué pasó y qué hacer (RF-15, RNF-04):
   "No se puede desactivar: la categoría tiene 2 equipos activos…", no "Error 23000".
7. **No se borran registros: se desactivan** (`activo = FALSE` / `activa = FALSE`). Nada de `DELETE`
   en el sistema.
8. **Validación en el servidor**, siempre, aunque el navegador también valide (`required`, `maxlength`).
   **Las validaciones y las reglas de negocio van en el modelo** (`validar()`, `validarCambioEstado()`…),
   que devuelve los errores en un arreglo o el mensaje de bloqueo; el controlador solo recibe los
   datos, llama al modelo y decide qué mostrar (sección 6).
9. **POST → redirect → GET:** después de guardar, `flash(...)` y `$this->redirect(...)`. Nunca muestres
   una vista directamente después de un POST.
10. **Roles:** todo controlador llama a `requireRole(...)` en su constructor.
11. **Usa los estilos existentes** (sección 9). No pongas `<style>` ni `style="..."` en las vistas.
12. **Operaciones de varias tablas en una transacción** (RNF-03): registrar una solicitud, una entrega o una devolución.

    ```php
    $db = Database::conexion();
    $db->beginTransaction();
    try {
        // varios INSERT / UPDATE
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
    ```

---

## 9. Librerías, estilos y componentes

Todas las librerías están **dentro del proyecto**, en `public/assets/vendor/`, sin CDN (funcionan sin
internet). Los layouts ya las cargan; no agregues otras sin avisar.

| Librería | Versión | Carpeta |
|---|---|---|
| Bootstrap (CSS y JS) | 5.3.3 | `public/assets/vendor/bootstrap-5.3.3/` |
| Bootstrap Icons | 1.11.3 | `public/assets/vendor/bootstrap-icons-1.11.3/` |
| jQuery | 3.7.1 | `public/assets/vendor/jquery-3.7.1/` |
| Inter (fuente) | 5.2.8 | `public/assets/vendor/inter-5.2.8/` |

**Identidad del sistema:** la marca es **EQUIPRENT**, el naranja principal es **`#F9A825`** (texto
naranja `#E89400`) y la fuente es **Inter**. Algunos frames de Figma usan otros nombres
("ALQUI EQUIPOS", "Panel Control") u otros naranjas (`#F2A516`, `#FFAA00`, `#F5B000`): **manda
siempre la identidad del sistema**, no la del frame.

**Iconos:** solo Bootstrap Icons (`<i class="bi bi-truck"></i>`). Buscador: https://icons.getbootstrap.com

### Componentes base (ya existen en `app.css`)

| Necesito… | Usa | Ejemplo en |
|---|---|---|
| Título de página con botón | `.encabezado-pagina` | `categorias/index.php` |
| Tarjeta blanca con título | `.panel`, `.panel-cabecera`, `.panel-titulo`, `.panel-cuerpo` | `categorias/index.php` |
| Tabla | `<div class="table-responsive"><table class="table tabla-er">` | `categorias/index.php` |
| Columna de botones en la tabla | `<td class="col-acciones">` | `categorias/index.php` |
| Código (EQ-1001, ALQ-2026-00001) | `<span class="codigo">` | `admin/dashboard.php` |
| Estado en píldora de color | `<?= estadoBadge($fila['estado']) ?>` | `categorias/index.php` |
| Botón principal (naranja) | `btn btn-primary` | |
| Botón secundario (borde gris) | `btn btn-secondary` | |
| Botón de peligro (rojo) | `btn btn-danger` | |
| Botón gris claro (Cancelar, Ver detalle) | `btn btn-claro` | |
| Botón pequeño | agrega `btn-sm` | |
| Buscador sobre una tabla | `.buscador-tabla` | `categorias/index.php` |
| Paginación | `require RAIZ . '/app/views/componentes/paginacion.php';` | `categorias/index.php` |
| Pie de tabla ("Mostrando 1–10 de 23") | `.pie-tabla` | `categorias/index.php` |
| Formulario en modal | `.modal fade modal-er` | `categorias/index.php` |
| Campo con error | `form-control is-invalid` + `<div class="invalid-feedback">` | `categorias/index.php` |
| Confirmar antes de enviar | `<form ... data-confirmar="¿Seguro…?">` | `categorias/index.php` |
| Panel sin datos | `.estado-vacio` | `admin/dashboard.php` |
| Tarjeta con número (indicador) | `.indicador` | `admin/dashboard.php` |
| Mensaje después de guardar | `flash('success', '...')` (tipos: success, danger, warning, info) | `CategoriaController.php` |

`estadoBadge()` conoce todos los estados de la base: equipo (`Disponible`, `Alquilado`, `Revision`,
`Mantenimiento`, `FueraServicio`), contrato (`Pendiente`, `Aprobado`, `Rechazado`, `Activo`,
`Finalizado`, `Cancelado`), daño, mantenimiento y `Activa`/`Inactiva`/`Inactivo`.

Agregados con Inventario (ejemplos en `app/views/equipos/`):

| Necesito… | Usa | Ejemplo en |
|---|---|---|
| Varios filtros sobre una tabla (texto + listas) | `<form method="get" class="filtros-tabla">` | `equipos/index.php` |
| Enlace "Volver" en una página de detalle | `<a class="enlace-volver">` | `equipos/detalle.php` |
| Botones a la derecha del título | `<div class="acciones-pagina">` | `equipos/detalle.php` |
| Datos de una ficha (etiqueta y valor) | `<dl class="ficha-datos">` / `<dl class="ficha-textos">` | `equipos/detalle.php` |
| Modal ancho en dos columnas | `modal-dialog modal-lg` + `<div class="row g-3">` | `equipos/_formulario.php` |
| Modal de una acción delicada (cabecera con icono rojo) | `modal-er modal-er-peligro` | `equipos/detalle.php` |
| Foto de equipo o marcador gris | `<?= imagenEquipo($ruta, $texto) ?>` | `equipos/detalle.php`, `cliente/dashboard.php` |
| Galería con acciones | `.galeria-fotos` / `.foto-galeria` | `equipos/detalle.php` |
| Campo que se escribe en mayúsculas | `class="form-control campo-mayusculas"` | `equipos/_formulario.php` |
| Fecha o dinero para mostrar | `fechaCorta($fecha, $conHora)`, `dinero($monto)` | `equipos/index.php` |

Atributos que entiende `app.js` (sin escribir JavaScript):

- `data-modo="crear"` / `"editar"` en el botón que abre un modal: al editar, los campos con
  `data-bloquear-al-editar` quedan en solo lectura y se muestran los bloques `data-solo-al-editar`.
- `data-mostrar-si="motivo=Otro"` en un bloque: se ve solo si el campo `name="motivo"` vale "Otro".
- `data-confirmar="¿Seguro…?"` en un `<form>`: pide confirmación antes de enviarlo.

Subir archivos: el formulario lleva `enctype="multipart/form-data"`, el controlador convierte
`$_FILES` en una lista (`FotografiaEquipoController::leerArchivos()`) y el modelo valida el **tipo real**
con `finfo` y `getimagesize` (no la extensión). Los archivos van a `public/uploads/`, que tiene un
`.htaccess` que impide ejecutar scripts.

Si necesitas un componente que no existe, **propónlo al grupo** y se agrega en `app.css`
(sección 5 del archivo). Los estilos propios van **solo** en `app.css`.

### Modales: cómo se llenan y se reabren (sin escribir JavaScript)

`app.js` ya hace todo; solo pones atributos:

- El botón que abre el modal lleva `data-bs-toggle="modal"`, `data-bs-target="#tuModal"`,
  `data-accion` (URL del POST), `data-titulo` y `data-campos` (JSON con el valor de cada `name`).
- Si la validación falla, el controlador guarda lo escrito y los errores en la sesión, y la vista
  pone `data-abrir-al-cargar="1"` en el modal: se abre solo con los errores.

---

## 10. Reglas entre módulos

Estas reglas cruzan módulos: si tu módulo toca estos datos, respétalas aunque "no sean tuyas".

- **No asignar equipos a categorías inactivas (Inventario).** `Equipo::validar()` rechaza una
  categoría inactiva (al editar solo se acepta si es la que el equipo ya tenía), y
  `Equipo::validarReactivacion()` no reactiva un equipo cuya categoría esté inactiva. Categorías, a su
  vez, no deja desactivar una categoría con equipos activos.
- **Estados de un equipo (Inventario → Operación y Mantenimiento).** El administrador solo elige a mano
  Disponible, En revisión, Mantenimiento o Fuera de servicio (`Equipo::ESTADOS_MANUALES`).
  **"Alquilado" lo pone Operación** al registrar la entrega, y lo quita al registrar la devolución;
  mientras está Alquilado, Inventario no deja cambiarle el estado. Para cambiar un estado desde
  cualquier módulo usa `Equipo::cambiarEstado($idEquipo, $estado, $motivo)`: ya pone `@app_motivo`.
- **Baja de equipos.** Un equipo no se borra: `Equipo::desactivar()` lo deja Fuera de servicio y
  `activo = FALSE` en una transacción, con el motivo en la bitácora. No se puede dar de baja si está en
  un contrato Pendiente, Aprobado o Activo sin devolver (`Equipo::contratoAbierto()`).
- **Fotos de equipos.** Muéstralas siempre con `imagenEquipo($ruta, $texto)`: si el archivo no existe
  (las fotos del seed), dibuja el marcador gris. La foto del catálogo es `v_catalogoEquipo.fotoPrincipal`.
  Una foto sí se borra (registro y archivo): es la única excepción a "no borrar registros".
- **Bitácora de equipos.** La tabla `equipo` tiene un trigger que registra cada cambio de estado en
  `bitacoraEstadoEquipo`, y exige saber quién lo hizo:
  - `@app_idUsuario`: **lo pone el sistema solo** al conectar (`Database`), con el usuario en sesión.
  - `@app_motivo`: **lo pones tú**, justo antes del `UPDATE`:

    ```php
    $db = Database::conexion();
    $db->prepare('SET @app_motivo = ?')->execute(['Entrega del contrato ALQ-2026-00005']);
    $db->prepare('UPDATE equipo SET estado = ? WHERE idEquipo = ?')->execute(['Alquilado', $idEquipo]);
    $db->exec('SET @app_motivo = NULL');
    ```

    Sin usuario en sesión, el trigger rechaza el cambio con "Falta @app_idUsuario…".
- **Disponibilidad:** usa `CALL sp_equiposDisponibles(inicio, fin, idCategoria o NULL)`; no escribas
  tu propia consulta. Un equipo no puede estar en dos contratos Pendiente/Aprobado/Activo con fechas
  cruzadas (lo impide un trigger), y un contrato Activo vencido sin devolución bloquea el equipo.
- **Tarifas:** el catálogo usa la vista `v_tarifaDiariaVigente` (la tarifa diaria activa y vigente).
- **Números automáticos:** `numeroContrato` (`ALQ-AAAA-00001`) y `codigoCliente` (`CLI-000001`) los
  asigna la aplicación después del `INSERT`, dentro de la misma transacción.
- **`database/01_schema.sql` no se modifica sin avisar al grupo.** Si tu módulo necesita una columna
  o un cambio, propónlo; se hace una sola vez y todos vuelven a ejecutar los scripts (sección 12).

---

## 11. Datos de prueba

`02_seed.sql` calcula las fechas desde **el día en que lo ejecutas**, así que los escenarios siempre
tienen sentido ("hoy+5" = dentro de 5 días).

### Usuarios (contraseña de todos: `Equip2026!`)

| Rol | Nombre | Correo | Notas |
|---|---|---|---|
| Administrador | Administrador General | `admin@equiprent.com` | |
| Operador | William Martínez | `william@equiprent.com` | |
| Operador | Ana Gómez | `ana@equiprent.com` | |
| Cliente | Juan Pérez | `contacto@constructora.com` | CLI-000001, Constructora SA de CV (jurídica) |
| Cliente | Carlos Mendoza | `carlos.mendoza@correo.com` | CLI-000002 (persona natural) |
| Cliente | María López | `compras@delvalle.com` | CLI-000003, Constructora del Valle (jurídica) |

### Categorías

Minicargadores, Excavadoras, Martillos Hidráulicos, Compactadores, Generadores Diésel y
Retroexcavadoras (todas activas).

### Equipos (11 unidades)

| Código | Equipo | Categoría | Estado | Tarifa diaria |
|---|---|---|---|---|
| EQ-1001 | Minicargador Bobcat S70 | Minicargadores | Disponible | $120 (la de 2025, $100, ya venció) |
| EQ-1002 | Minicargador Bobcat S70 | Minicargadores | **Alquilado** (contrato 2) | $120 |
| EQ-1003 | Minicargador Bobcat S18 | Minicargadores | Disponible | $110 |
| EQ-2001 | Excavadora Caterpillar 320D | Excavadoras | Disponible | $450 |
| EQ-2002 | Excavadora Caterpillar 320D | Excavadoras | **Mantenimiento** | $450 |
| EQ-3001 | Martillo Hidráulico HB10G | Martillos Hidráulicos | Disponible (reservado en contrato 5) | $80 |
| EQ-3002 | Martillo Hidráulico HB10G | Martillos Hidráulicos | Disponible | $80 |
| EQ-4001 | Compactadora Dynapac CA250 | Compactadores | **Fuera de servicio** | $150 |
| EQ-5001 | Generador Diésel CAT 50kVA | Generadores Diésel | **Alquilado y atrasado** (contrato 3) | $90 |
| EQ-6001 | Retroexcavadora CAT 416F | Retroexcavadoras | Disponible (solicitado en contrato 4) | $280 |
| EQ-6002 | Retroexcavadora JCB 3CX | Retroexcavadoras | Disponible (reservado en contrato 5) | $260 |

Fotos: hay registros en `fotografiaEquipo` para EQ-1001, 1002, 1003, 2001 y 6001, pero los archivos
todavía no existen en `public/uploads/` (el módulo de Inventario los subirá).

### Contratos

| # | Número | Estado | Cliente | Equipos | Fechas | Sirve para probar |
|---|---|---|---|---|---|---|
| 1 | ALQ-2026-00001 | Finalizado | Juan Pérez | EQ-2001 | hace 30 a 25 días | Historial, reportes; entregado, devuelto, daño leve resuelto |
| 2 | ALQ-2026-00002 | Activo | Carlos Mendoza | EQ-1002 | hace 2 días a hoy+3 | **Devoluciones** (entregado, sin devolver) |
| 3 | ALQ-2026-00003 | Activo **atrasado** | María López | EQ-5001 | hace 10 a hace 3 días | Alertas de atraso, devolución con penalización |
| 4 | ALQ-2026-00004 | Pendiente | Juan Pérez | EQ-6001 | hoy+5 a hoy+9 | **Aprobar / rechazar** solicitudes |
| 5 | ALQ-2026-00005 | Aprobado | Carlos Mendoza | EQ-6002 + EQ-3001 | hoy+1 a hoy+4 | **Entregas** (aprobado, sin entregar; dos equipos) |
| 6 | ALQ-2026-00006 | Rechazado | María López | EQ-2001 | hoy+5 a hoy+7 | Motivo de rechazo |

### Mantenimiento, entregas y devoluciones

- Mantenimiento **abierto**: EQ-2002, correctivo "Falla en bomba de inyección", estado *EsperandoRepuesto*.
- Mantenimiento **cerrado**: EQ-1001, preventivo de 500 h, hace 40 días.
- Entregas registradas: contratos 1, 2 y 3. Devolución registrada: contrato 1 (con un daño leve).

### ¿Con qué pruebo mi módulo?

| Módulo | Usuario | Datos que ya existen |
|---|---|---|
| Inventario | admin | 11 equipos en todos los estados; EQ-1001 con tarifa vencida y vigente |
| Usuarios y clientes | admin / un cliente | 6 usuarios, 3 clientes (jurídicos y natural) |
| Catálogo y solicitud | contacto@constructora.com | Disponibilidad hoy+2 a hoy+6: EQ-1001, 1003, 2001, 3002 |
| Operación | william | Pendiente (4), Aprobado sin entregar (5), Activo sin devolver (2), atrasado (3) |
| Mantenimiento y reportes | william / admin | Mantenimiento abierto en EQ-2002; contratos en todos los estados |

---

## 12. Restaurar la base de prueba

Cuando tus pruebas dejen los datos desordenados (contratos a medias, equipos en estados raros), vuelve
al estado inicial. **Esto borra todo lo que hayas creado en tu base local** (no afecta a nadie más):

```bat
cd /d C:\xampp\htdocs\SWPADEPC2026\database
"C:\Program Files\MySQL\MySQL Server 26.7\bin\mysql.exe" -u root -p < 01_schema.sql
"C:\Program Files\MySQL\MySQL Server 26.7\bin\mysql.exe" -u root -p < 02_seed.sql
```

Hazlo también cada vez que alguien avise en el grupo que cambió `01_schema.sql` o `02_seed.sql`
(después de `git pull origin main`).

---

## 13. Lista de "terminado" antes del Pull Request

Copia esta lista en la descripción de tu Pull Request y márcala (`[x]`):

```markdown
- [ ] Funciona con los usuarios de prueba (anoté cuáles en la descripción)
- [ ] Valida los datos en el servidor (campos obligatorios, formatos, duplicados)
- [ ] Muestra mensajes claros de éxito y de error, en español
- [ ] Respeta los roles: otro rol recibe 403 y sin sesión redirige al login
- [ ] Todo formulario POST tiene csrfCampo(); toda consulta usa sentencias preparadas; todo dato se imprime con e()
- [ ] No borra registros (desactiva) y respeta las reglas entre módulos (sección 10)
- [ ] Sigue el diseño: componentes de app.css, Bootstrap Icons, marca EQUIPRENT y #F9A825
- [ ] Se ve bien en celular (DevTools con F12 → icono de celular, ancho 390 px)
- [ ] Probado en Chrome y en Edge
- [ ] Los commits siguen la convención (Add:, Fix:, Update:…) y no incluyen config.local.php
- [ ] Actualicé mi rama con main y sigue funcionando
```

---

## 14. Módulos, responsables y pantallas de Figma

### Tabla de módulos

| Módulo | Rol | Requerimientos (Etapa 1) | Rama sugerida | Responsable |
|---|---|---|---|---|
| Inventario: equipos, fotografías y tarifas | Administrador | RF-04, RF-15 | `feature/inventario` | |
| Usuarios y clientes: usuarios y roles, clientes, registro de clientes y perfil | Administrador / Cliente (RF-03 también Operador) | RF-02, RF-03, RF-15 | `feature/usuarios-clientes` | |
| Catálogo y solicitud: catálogo, disponibilidad por fechas, solicitud de alquiler y "Mis alquileres" | Cliente | RF-04, RF-05, RF-06, RF-07, RF-08, RF-15 | `feature/catalogo-solicitud` | |
| Operación: aprobar/rechazar solicitudes, contratos, entregas, devoluciones y daños | Operador / Administrador | RF-06, RF-08, RF-09, RF-10, RF-11, RF-15 | `feature/operacion` | |
| Mantenimiento y reportes: mantenimiento, bitácora, dashboards con indicadores, reportes y configuración | Operador / Administrador | RF-12, RF-13, RF-14, RF-15 | `feature/mantenimiento-reportes` | |

Ya hechos y en `main`: login y control de acceso por rol (RF-01), layout y paneles de los tres roles,
el módulo de ejemplo **Categorías** e **Inventario** (equipos, fotos, tarifas, estados y bitácora; RF-04).
Los RNF (seguridad, usabilidad, accesibilidad, compatibilidad…) aplican a todos los módulos.

Resumen de los RF (texto completo en el documento de la Etapa 1, en `docs/`):
RF-01 autenticación · RF-02 usuarios y perfiles · RF-03 clientes · RF-04 catálogo de equipos ·
RF-05 disponibilidad · RF-06 registro de alquileres · RF-07 cálculo del importe · RF-08 consulta y
seguimiento de alquileres · RF-09 entregas · RF-10 devoluciones · RF-11 estados tras la devolución ·
RF-12 mantenimiento · RF-13 panel de información · RF-14 reportes e historial · RF-15 mensajes y validaciones.

### Pantallas de Figma

Archivo **"Sistema Web"**, página **Page 1**:
https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=0-1

Inventario de los frames por módulo (solo como referencia; abre el enlace para verlos):

**Ya implementados**

| Frame | Enlace |
|---|---|
| Login - Remberto | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=1-2 |
| Dashboard - Remberto (Admin) | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=52-627 |
| dashboard-operador | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=44-4 |
| Inicio - Daniel (Cliente) | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=176-483 |
| Formulario Crear Categoria Carlos | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=53-1130 |

**Inventario: equipos, fotografías y tarifas**

| Frame | Enlace |
|---|---|
| Equipos Carlos | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=38-2 |
| Formulario Registrar Equipo Carlos | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=40-339 |
| Formulario Editar Equipo Carlos | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=40-551 |
| Formulario Desactivar Equpo Carlos | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=53-1052 |
| Equipos Emerson (vista del Operador) | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=74-1130 |

Ya implementado (en `main`): listado, detalle (`?id=`), fotos, tarifas (modal en el detalle) y bitácora.

**Usuarios y clientes**

| Frame | Enlace |
|---|---|
| Registro Carlos | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=25-2 |
| clientes-main — Oswaldo | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=98-92 |
| clientes-registrar — Oswaldo | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=98-270 |
| clientes-detalle — Oswaldo | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=98-448 |
| clientes-editar — Oswaldo | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=98-626 |
| clientes-guardado — Oswaldo | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=105-3 |
| clientes-nuevo-detalle — Oswaldo | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=105-197 |
| Perfil - Daniel (Cliente) | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=176-1030 |

No hay frame de "Usuarios y roles": usar el patrón de Categorías.

**Catálogo y solicitud (Cliente)**

| Frame | Enlace |
|---|---|
| Catalogo - Daniel | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=176-689 |
| Detalle - Daniel (detalle de equipo) | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=176-805 |
| Disponibilidad - Daniel | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=176-849 |
| Solicitar - Daniel | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=176-1072 |
| Mis-alquileres - Daniel | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=176-968 |
| Detalle - alquiler - Daniel | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=176-909 |

**Operación: solicitudes, contratos, entregas, devoluciones y daños**

| Frame | Enlace |
|---|---|
| Alquiler - Remberto (Admin) | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=60-709 |
| alquileres-main | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=45-5 |
| alquileres-modal | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=45-261 |
| alquileres-detalle — Oswaldo | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=98-537 |
| alquileres-nuevo-detalle — Oswaldo | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=105-664 |
| alquileres-registrado — Oswaldo | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=105-800 |
| alquileres-finalizado — Oswaldo | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=105-344 |
| alquileres-con-devolucion — Oswaldo | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=105-1239 |
| Entregas Carlos (Admin) | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=51-249 |
| Formulario Entrega Carlos (Admin) | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=51-482 |
| Entregas (Operador) | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=90-2163 |
| Formulario Entrega (Operador) | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=90-2092 |
| devoluciones-main — Oswaldo | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=98-3 |
| devoluciones-registrar — Oswaldo | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=98-181 |
| devoluciones-detalle — Oswaldo | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=98-359 |
| devoluciones-historial — Oswaldo | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=105-480 |
| Devoluciones - Remberto (Admin) | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=66-2219 |

No hay frame específico de Daños: se registran dentro de la devolución.

**Mantenimiento y reportes**

| Frame | Enlace |
|---|---|
| Mantenimiento Carlos (Admin) | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=51-554 |
| Mantenimiento (Operador) | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=90-2398 |
| Formulario Mantenimiento | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=90-2662 |
| Configuracion Carlos | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=53-870 |
| Ver Reportes Carlos | https://www.figma.com/design/uGjJN3mgJTYV8bdHy05NVx/Sistema-Web?node-id=53-1184 |

Notas sobre Figma:

- Hay pantallas repetidas para Admin y Operador (Equipos, Entregas, Mantenimiento) hechas por
  distintas personas: el que tome el módulo elige una y la usa para los dos roles, cambiando solo lo
  que el rol puede hacer.
- **La marca y los colores oficiales son los del sistema (EQUIPRENT, `#F9A825`, Inter)**, aunque algunos
  frames usen otros nombres o naranjas. El menú de cada rol es el de los requerimientos, no el del frame.
- Los menús, barras superiores y pies ya están hechos en los layouts: de cada frame solo se implementa
  el **contenido** de la página.
