# Sistema de Vuelos y Reservas (Microservicios PHP)

Aplicación de ejemplo basada en Slim + Eloquent organizada en dos microservicios con la estructura solicitada (`app/Controllers`, `app/Models`, `app/Middleware`, `app/Config`):

- **users_ms**: autenticación y gestión de usuarios/roles.
- **flights_ms**: administración de naves y vuelos (administrador) y reservas (gestor).

El frontend es HTML/CSS/JS puro y consume directamente los endpoints REST.

## Requisitos
- PHP 8 con Composer (compatible con XAMPP).
- MySQL con la base de datos `vuelos_app` creada a partir del script proporcionado.
- Servidor web apuntando a `services/users_ms/public` y `services/flights_ms/public` (por ejemplo, alias virtual en XAMPP) y la carpeta `frontend` para los archivos estáticos.

## Instalación de dependencias
Ejecuta Composer en cada microservicio:

```bash
cd services/users_ms && composer install
cd ../flights_ms && composer install
```

Copia el archivo `.env.example` de cada servicio a `.env` y ajusta las credenciales:

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
