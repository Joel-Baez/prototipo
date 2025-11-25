# Sistema de Vuelos y Reservas (Microservicios PHP)

Aplicación de ejemplo basada en Slim + Eloquent organizada en dos microservicios con la estructura solicitada (`app/Controllers`, `app/Models`, `app/Middleware`, `app/Config`).

## Microservicios incluidos
- **users_ms** (`backend/users_ms`): autenticación y gestión de usuarios/roles.
- **flights_ms** (`backend/flights_ms`): administración de naves y vuelos (administrador) y reservas (gestor).

> Solo debe existir la carpeta `backend/` para los microservicios. Si quedó una carpeta vieja llamada `services/` (de alguna
> iteración anterior), elimínala para evitar duplicados o rutas rotas.

El frontend es HTML/CSS/JS puro y consume directamente los endpoints REST.

## Requisitos
- PHP 8 con Composer (compatible con XAMPP).
- MySQL con la base de datos `vuelos_app` creada a partir del script proporcionado.
- Servidor web apuntando a `backend/users_ms/public` y `backend/flights_ms/public` (por ejemplo, alias virtual en XAMPP) y la carpeta `frontend` para los archivos estáticos.

## Instalación de dependencias
Sigue los pasos estándar de Slim + Composer (idénticos a la guía compartida):

1. **Entrar en cada microservicio** (`backend/users_ms` y `backend/flights_ms`).
2. **Instalar Slim y dependencias** (si no existe `vendor/`):
   ```bash
   composer require slim/slim:"4.*"
   composer require slim/psr7
   composer require illuminate/database
   composer require vlucas/phpdotenv
   composer dump-autoload
   ```
   > Ejecutar `composer install` generará el `composer.lock` y la carpeta `vendor/` automáticamente con las versiones correctas. Si ya tienes internet disponible, basta con ese comando en cada microservicio.
   > **No borres `composer.json` ni `composer.lock`**: son necesarios para que `composer install` resuelva las dependencias. El archivo `composer.lock` se recrea si falta, pero debe quedar junto al `composer.json` de cada microservicio.
3. **Copiar `.env`** desde el ejemplo y ajustar credenciales de MySQL:
   ```bash
   cp backend/users_ms/.env.example backend/users_ms/.env
   cp backend/flights_ms/.env.example backend/flights_ms/.env
   ```

## Endpoints principales
### users_ms
- `POST /login` Iniciar sesión y obtener token.
- `POST /logout` Cerrar sesión (requiere token).
- `GET /me` Perfil actual (requiere token).
- `POST /users` Crear usuario (solo administrador).
- `GET /users` Listar usuarios (solo administrador).
- `PUT /users/{id}` Actualizar datos (solo administrador).
- `PUT /users/{id}/role` Cambiar rol (solo administrador).

### flights_ms
- `GET /flights` Listar vuelos (solo administrador).
- `GET /flights/search` Búsqueda por origen/destino/fecha (solo administrador).
- `POST /flights` Crear vuelo (solo administrador).
- `PUT /flights/{id}` Actualizar vuelo (solo administrador).
- `DELETE /flights/{id}` Eliminar vuelo (solo administrador).
- `GET /naves` Listar naves (solo administrador).
- `POST /naves` Crear nave (solo administrador).
- `PUT /naves/{id}` Actualizar nave (solo administrador).
- `DELETE /naves/{id}` Eliminar nave (solo administrador).
- `GET /reservations` Listar reservas (gestor o administrador).
- `GET /reservations/user/{userId}` Reservas por usuario (gestor o administrador).
- `POST /reservations` Crear reserva (gestor o administrador).
- `DELETE /reservations/{id}` Cancelar reserva (gestor o administrador).

## Archivos .http para Visual Studio Code
Se incluyen ejemplos listos para la extensión **REST Client**:

- `user-ms.http`: login, perfil y endpoints de usuarios.
- `flights-ms.http`: vuelos, naves y reservas.

Actualiza la variable `@token` con el valor devuelto por `/login` y ajusta `@baseUrl` si cambias el host o alias.

## Frontend
Abrir `frontend/index.html` desde el servidor web. El token se almacena en `localStorage` y se envía en el header `Authorization: Bearer <token>`.

- Usa las credenciales de prueba `admin@system.com / admin123` y `gestor@system.com / gestor123` (según el SQL suministrado).
- Las secciones de administración y gestor se muestran según el rol devuelto por `/login`.

## Puesta en marcha en XAMPP (Apache + MySQL)
1. **Ubica el proyecto en `htdocs`** (por ejemplo `C:\xampp\htdocs\prototipo`).
2. **Instala dependencias por microservicio** desde la consola de XAMPP/PowerShell:
   ```bash
   cd C:\xampp\htdocs\prototipo\backend\users_ms
   composer install

   cd ..\flights_ms
   composer install
   ```
   > Esto descarga la carpeta `vendor` que faltaba para ejecutar Slim y Eloquent.
3. **Copia los entornos** y coloca tus credenciales de MySQL:
   ```bash
   copy C:\xampp\htdocs\prototipo\backend\users_ms\.env.example C:\xampp\htdocs\prototipo\backend\users_ms\.env
   copy C:\xampp\htdocs\prototipo\backend\flights_ms\.env.example C:\xampp\htdocs\prototipo\backend\flights_ms\.env
   ```
   Ajusta `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` a tu instancia local (la base `vuelos_app`).
4. **Activa Apache y MySQL** desde el panel de control de XAMPP.
5. **Rutas de prueba** (sin necesidad de alias extra) usando el DocumentRoot por defecto:
   - Users: `http://localhost/prototipo/backend/users_ms/public/index.php`
   - Flights: `http://localhost/prototipo/backend/flights_ms/public/index.php`
   - Frontend: `http://localhost/prototipo/frontend/`
   Las reglas `.htaccess` ya están incluidas en cada carpeta `public` para que Apache reescriba al `index.php` de Slim.
6. (Opcional) Si prefieres URLs cortas, crea dos alias o vhosts que apunten a cada carpeta `public`.

Cada microservicio responde en JSON y valida el token en las rutas protegidas.

## Paso a paso para probar los microservicios (local/VS Code REST Client)
1. **Crear la base de datos** en MySQL con el SQL entregado en el enunciado (asegúrate de crear la base `vuelos_app`).
2. **Instalar dependencias** (una vez por microservicio):
   ```bash
   cd backend/users_ms && composer install
   cd ../flights_ms && composer install
   ```
3. **Configurar entorno** copiando los `.env.example` y ajustando usuario/clave de MySQL:
   ```bash
   cp backend/users_ms/.env.example backend/users_ms/.env
   cp backend/flights_ms/.env.example backend/flights_ms/.env
   ```
4. **Levantar los servicios** (puedes usar PHP embebido para pruebas rápidas):
   - Terminal 1:
     ```bash
     cd backend/users_ms
     php -S 127.0.0.1:8001 -t public
     ```
   - Terminal 2:
     ```bash
     cd backend/flights_ms
     php -S 127.0.0.1:8002 -t public
     ```
   Ajusta `@baseUrl` en los `.http` según los puertos elegidos:
   - `user-ms.http`: `http://127.0.0.1:8001`
   - `flights-ms.http`: `http://127.0.0.1:8002`
5. **Obtener token** con la extensión REST Client de VS Code:
   - Abre `user-ms.http` y ejecuta la petición **Login** con las credenciales de prueba (`admin@system.com` o `gestor@system.com`).
   - Copia el valor `token` de la respuesta y pégalo en la variable `@token` de ambos archivos `.http`.
6. **Probar endpoints protegidos**:
   - Con token de **administrador** prueba usuarios, vuelos y naves.
   - Con token de **gestor** prueba creación/listado/cancelación de reservas.
7. **Frontend**: si prefieres interfaz visual, sirve `frontend/` (por ejemplo `php -S 127.0.0.1:8000 -t frontend`) y usa las mismas credenciales; el token se guarda en `localStorage` y se envía en cada llamada.

Si recibes 401 en rutas protegidas, revisa que el header `Authorization: Bearer <token>` se envía y que el token existe en la tabla `users`.
