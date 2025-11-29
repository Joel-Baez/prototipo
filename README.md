# Sistema de Vuelos y Reservas (Microservicios PHP)

Proyecto con dos microservicios en `backend/users_ms` (usuarios/autenticación) y `backend/flights_ms` (vuelos, naves y reservas) más un frontend HTML/CSS/JS en `frontend`.

- Los `composer.json` ya están en `backend/users_ms` y `backend/flights_ms`; ejecuta los comandos de Composer para descargar las dependencias.
- Las conexiones a MySQL se configuran directamente en `app/Config/database.php` (puedes ajustar host/usuario/clave allí o mediante variables de entorno estándar: `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).

## Requisitos
- PHP 8 con extensiones mysqli/pdo_mysql habilitadas (XAMPP funciona).
- MySQL con la base `vuelos_app` creada usando el SQL del enunciado.
- Composer instalado para descargar Slim 4, PSR-7 y Eloquent (se instalan por microservicio).

## Instalación con Composer (por microservicio)
Dentro de `backend/users_ms` y `backend/flights_ms` ejecuta exactamente esta secuencia (igual a la guía que enviaste):

```bash
composer require slim/slim:"4.*"
composer require slim/psr7
composer require illuminate/database
```

Después agrega el autoload PSR-4 (Composer lo generará si aún no existe):
```json
"autoload": {
  "psr-4": {
    "App\\": "app/"
  }
}
```

Y reconstruye el autoloader:
```bash
composer dump-autoload
```

> Repite el proceso en **cada** microservicio (`backend/users_ms` y `backend/flights_ms`).

## Arranque rápido con PHP embebido
1. Sitúate en `backend/users_ms` y levanta el servicio:
   ```bash
   php -S 127.0.0.1:8001 -t public
   ```
2. En otra terminal, en `backend/flights_ms`:
   ```bash
   php -S 127.0.0.1:8002 -t public
   ```
3. Sirve el frontend:
   ```bash
   php -S 127.0.0.1:8000 -t frontend
   ```
4. Abre `http://127.0.0.1:8000` en el navegador. El frontend detecta automáticamente los microservicios en `8001` y `8002` o, si lo usas desde Apache/XAMPP en `htdocs/prototipo`, llamará a `backend/users_ms/public` y `backend/flights_ms/public` sin mostrar las rutas en pantalla.

## Endpoints principales
### users_ms
- `POST /login` (email+password) genera un token único y lo almacena.
- `POST /logout` elimina el token guardado.
- `GET /me` devuelve el usuario logueado.
- `POST /users` crea usuarios (solo administrador).
- `GET /users` lista usuarios (solo administrador).
- `PUT /users/{id}` actualiza datos (solo administrador).
- `PUT /users/{id}/role` cambia rol (solo administrador).

### flights_ms
- `GET /flights` lista/busca vuelos (`origin`, `destination`, `date`).
- `POST /flights`, `PUT /flights/{id}`, `DELETE /flights/{id}` (administrador).
- `GET /naves`, `POST /naves`, `PUT /naves/{id}`, `DELETE /naves/{id}` (administrador).
- `GET /reservations`, `POST /reservations`, `PUT /reservations/{id}/cancel` (solo gestor).

Todas las respuestas son JSON. Las rutas protegidas requieren `Authorization: Bearer <token>`.

## Visual Studio Code – REST Client
- `user-ms.http` y `flights-ms.http` apuntan a `http://127.0.0.1:8001` y `http://127.0.0.1:8002` por defecto.
- Inicia sesión con `admin@system.com/admin123` o `gestor@system.com/gestor123`, copia el `token` y asígnalo a `@token` en los archivos `.http`.

## Notas sobre credenciales y seguridad
- Cada login genera un token único guardado en BD; `/logout` lo elimina.
- El frontend guarda el token en `sessionStorage` durante la sesión y lo limpia al cerrar o al recibir 401.

## ¿Cómo ajustar la base de datos?
Edita `backend/users_ms/app/Config/database.php` y `backend/flights_ms/app/Config/database.php` con tus credenciales MySQL (o exporta variables de entorno). Ejemplo incluido con host `127.0.0.1`, base `vuelos_app`, usuario `root`, contraseña vacía.

## ¿Y si uso Apache/XAMPP?
- Copia la carpeta del proyecto en `htdocs/prototipo`.
- Activa Apache y MySQL.
- Instala dependencias en cada microservicio con los comandos Composer anteriores.
- Accede a:
  - `http://localhost/prototipo/backend/users_ms/public/`
  - `http://localhost/prototipo/backend/flights_ms/public/`
  - `http://localhost/prototipo/frontend/`
- El frontend no muestra las rutas internas del backend; solo verás el estado de sesión y los paneles según el rol.
