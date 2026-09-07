# Plataforma de Gobierno de Datos con Inteligencia Artificial

Proyecto universitario de una plataforma web para catalogar, medir la calidad y aplicar politicas de gobierno sobre datos organizacionales, con soporte de Inteligencia Artificial (clasificacion automatica, deteccion de anomalias y sugerencias).

## Tecnologias

- PHP 8.x (puro, sin frameworks) + PDO
- MySQL 8 + consultas preparadas
- HTML5, CSS3 y JavaScript (vanilla)
- API REST con respuestas JSON
- API de OpenAI para funciones de IA
- Git/GitHub y Postman para pruebas

## Estructura

```
gobierno-datos-ia/
├── api/            Endpoints REST (JSON)
├── config/         Conexion PDO y configuracion general
├── includes/       Logica reutilizable (auth, funciones, servicios IA)
├── views/          Interfaces HTML
├── assets/         CSS, JavaScript, imagenes
├── sql/            Scripts de base de datos
├── .env            Variables de entorno (NO se sube a GitHub)
└── index.php       Punto de entrada
```

## Instalacion

1. Clona o descarga el proyecto en tu servidor local (XAMPP/WAMP: carpeta `htdocs`/`www`).
2. En MySQL, ejecuta en orden:
   - `sql/database.sql` (crea la base de datos y las tablas)
   - `sql/seed.sql` (datos de prueba)
3. Copia `.env.example` como `.env` y ajusta los datos de tu MySQL.
4. Abre `http://localhost/gobierno-datos-ia`.

## Usuarios de prueba

| Rol      | Email              | Contrasena  |
|----------|--------------------|-------------|
| admin    | admin@example.com  | Admin123!   |
| steward  | steward@example.com| Steward123! |
| owner    | owner@example.com  | Owner123!   |
| analyst  | analyst@example.com| Analyst123! |

## Modulos

- Dashboard con metricas y alertas
- Catalogo de datos (datasets)
- Monitoreo de calidad de datos
- Politicas de gobierno de datos
- Modulo de Inteligencia Artificial
- Gestion de usuarios y roles
- Reportes y bitacora de actividad

## Seguridad

- Contrasenas con `password_hash()` (bcrypt)
- Consultas preparadas con PDO (anti SQL Injection)
- Salida escapada con `htmlspecialchars()` (anti XSS)
- Validacion de entradas y control de roles por sesion
- Claves de API solo en el servidor (`.env`)