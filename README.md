# Sistema de Vuelos y Reservas (Microservicios PHP)

Aplicación de ejemplo basada en Slim + Eloquent organizada en dos microservicios con la estructura solicitada (`app/Controllers`, `app/Models`, `app/Middleware`, `app/Config`).

## Microservicios incluidos
- **users_ms** (`services/users_ms`): autenticación y gestión de usuarios/roles.
- **flights_ms** (`services/flights_ms`): administración de naves y vuelos (administrador) y reservas (gestor).

El frontend es HTML/CSS/JS puro y consume directamente los endpoints REST.

## Requisitos
- PHP 8 con Composer (compatible con XAMPP).
- MySQL con la base de datos `vuelos_app` creada a partir del script proporcionado.
- Servidor web apuntando a `services/users_ms/public` y `services/flights_ms/public` (por ejemplo, alias virtual en XAMPP) y la carpeta `frontend` para los archivos estáticos.

## Instalación de dependencias
Sigue los pasos estándar de Slim + Composer (idénticos a la guía compartida):

1. **Entrar en cada microservicio** (`services/users_ms` y `services/flights_ms`).
2. **Instalar Slim y dependencias** (si no existe `vendor/`):
   ```bash
   composer require slim/slim:"4.*"
   composer require slim/psr7
   composer require illuminate/database
   composer require vlucas/phpdotenv
   composer dump-autoload
   ```
   > Si ya tienes `composer.json`, también puedes ejecutar `composer install` directamente.
3. **Copiar `.env`** desde el ejemplo y ajustar credenciales de MySQL:
   ```bash
   cp services/users_ms/.env.example services/users_ms/.env
   cp services/flights_ms/.env.example services/flights_ms/.env
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

## Puesta en marcha en XAMPP
1. Ubica el proyecto dentro de la carpeta pública de XAMPP.
2. Configura dos alias virtuales o rutas:
   - `http://localhost/users_ms/public/index.php`
   - `http://localhost/flights_ms/public/index.php`
3. Sirve la carpeta `frontend` como sitio estático para consumir los endpoints.

Cada microservicio responde en JSON y valida el token en las rutas protegidas.

## Paso a paso para probar los microservicios (local/VS Code REST Client)
1. **Crear la base de datos** en MySQL con el SQL entregado en el enunciado (asegúrate de crear la base `vuelos_app`).
2. **Instalar dependencias** (una vez por microservicio):
   ```bash
   cd services/users_ms && composer install
   cd ../flights_ms && composer install
   ```
3. **Configurar entorno** copiando los `.env.example` y ajustando usuario/clave de MySQL:
   ```bash
   cp services/users_ms/.env.example services/users_ms/.env
   cp services/flights_ms/.env.example services/flights_ms/.env
   ```
4. **Levantar los servicios** (puedes usar PHP embebido para pruebas rápidas):
   - Terminal 1:
     ```bash
     cd services/users_ms
     php -S 127.0.0.1:8001 -t public
     ```
   - Terminal 2:
     ```bash
     cd services/flights_ms
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
