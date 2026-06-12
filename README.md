# OdinBO - MVC PHP 8.3

Aplicacion web en PHP 8.3 con arquitectura MVC pura (sin frameworks), con login, dashboard y gestion de usuarios consumiendo API externa.

## Requisitos

- Apache 2.4+
- PHP 8.3+
- Extension cURL habilitada

## Estructura

- app/controllers
- app/models
- app/services
- app/middleware
- app/views/layouts
- app/views/components
- app/views/users
- config
- public/assets/css
- public/assets/js
- public/assets/img
- routes
- storage/logs
- storage/sessions

## Configuracion

Editar `config/constants.php`:

- `APP_URL`: URL publica del proyecto (ejemplo: `http://localhost/OdinBO/public`)
- `API_BASE_URL`: URL base de la API
- `TOKEN_REFRESH_MINUTES`: minutos previos para refrescar token
- `SESSION_TIMEOUT`: timeout de sesion en minutos

## Ejecucion local

1. Apuntar Apache al proyecto (o usar el root del repo y permitir .htaccess).
2. Verificar permisos de escritura en:
   - `storage/logs`
   - `storage/sessions`
3. Abrir en navegador:
   - `http://localhost/OdinBO/public/login`

## Flujo funcional

- Login: `POST /api/User/Login`
- Refresh transparente token: `GET /api/User/RefreshToken`
- Listado de usuarios: `GET /api/User/GetAllUsers`
- Crear usuario: `POST /api/User/CreateUser`
- Actualizar usuario: `PUT /api/User/UpdateUser`

## Seguridad implementada

- SessionManager para token, usuario y expiracion.
- AuthMiddleware para proteger rutas.
- TokenManager para refresco automatico.
- CSRF token en formularios y endpoints sensibles.
- Sanitizacion y validaciones de entrada.
- Manejo global de excepciones.
- Logs de errores en `storage/logs/app.log`.

## Frontend

- Bootstrap 5
- Sidebar responsive + navbar superior
- Tabla de usuarios con:
  - busqueda instantanea
  - ordenamiento por columnas
  - paginacion cliente
- Modales para crear/editar
- Toasts para mensajes
- Loader global para llamadas HTTP
