# MisterPlan — Gestión de reservas

Aplicación de reservas de alojamiento desarrollada con PHP nativo,
MySQL, Twig y JavaScript, sin framework.

## Requisitos

Para ejecutar la aplicación en local se necesita:

- Docker
- Docker Compose
- Git

No es necesario instalar PHP, Composer ni MySQL en el equipo,
ya que se ejecutan dentro de los contenedores Docker.

## Instalación local
1. Clonar el repositorio.

```
git clone https://github.com/jmontero-27/misterplan.git
```

```
cd misterplan
```

2. Copiar .env.example a .env.

```
cp .env.example .env
```

3. Configurar las variables de conexión.
4. Arrancar los contenedores.

```
docker compose up -d
```

5. Instalar las dependencias con Composer.

```
docker compose exec app composer install
```

6. Abrir http://localhost:8080.

## Rutas

### Vistas web

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/` | Listado de reservas, filtros y formulario de creación |
| GET | `/reservas/{id}` | Detalle de una reserva con su historial |

### API

Las respuestas de la API se devuelven en formato JSON.

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/api/health` | Comprueba que la aplicación responde |
| GET | `/api/reservations` | Lista reservas y permite combinar filtros |
| GET | `/api/reservations/{id}` | Obtiene una reserva y su historial |
| POST | `/api/reservations` | Crea una reserva en estado pendiente |
| PATCH | `/api/reservations/{id}/confirm` | Confirma una reserva pendiente |
| PATCH | `/api/reservations/{id}/cancel` | Cancela una reserva pendiente o confirmada |

### Filtros del listado

Se pueden combinar mediante parámetros de consulta:

| Parámetro | Descripción |
|-----------|-------------|
| `estado` | `pendiente`, `confirmada` o `cancelada` |
| `fecha_desde` | Fecha de entrada mínima, incluida (`YYYY-MM-DD`) |
| `fecha_hasta` | Fecha de entrada máxima, incluida (`YYYY-MM-DD`) |
| `huesped` | Búsqueda parcial por nombre del huésped |

Ejemplo:

```
GET /api/reservations?estado=pendiente&fecha_desde=2026-11-01&fecha_hasta=2026-11-30&huesped=Juan
```

### Crear una reserva

Enviar a `POST /api/reservations` con la cabecera
`Content-Type: application/json` y este cuerpo:

```json
{
  "nombre_huesped": "Ana García",
  "email_huesped": "ana@example.com",
  "nombre_alojamiento": "Hotel Costa Azul",
  "fecha_entrada": "2026-11-10",
  "fecha_salida": "2026-11-15",
  "importe": "450.00",
  "notas": "Llegada por la tarde"
}
```

Todos los campos son obligatorios excepto `notas`.
El importe se envía como cadena decimal o entero.
El estado inicial se asigna automáticamente a `pendiente`.

Las peticiones de confirmación y cancelación no necesitan cuerpo.

### Códigos de respuesta

| Código | Significado |
|--------|-------------|
| 200 | Consulta o cambio de estado realizado correctamente |
| 201 | Reserva creada correctamente |
| 400 | Datos, filtros o identificador inválidos |
| 404 | Reserva o ruta inexistente |
| 405 | Método HTTP no permitido |
| 409 | Transición de estado no permitida |
| 500 | Error interno del servidor |

## Organización y decisiones técnicas

La aplicación se ha desarrollado con PHP nativo, sin utilizar frameworks, para trabajar directamente con los fundamentos de PHP, HTTP, SQL y la separación de responsabilidades.

La organización del código distingue varias capas:

- **Controladores:** reciben las peticiones HTTP, coordinan los casos de uso y generan las respuestas.
- **Servicios:** implementan las reglas de negocio y gestionan las operaciones sobre las reservas.
- **Repositorios:** encapsulan las consultas SQL y el acceso a MySQL mediante PDO.
- **Validadores:** comprueban los datos recibidos antes de ejecutar las operaciones.
- **Vistas Twig:** generan el HTML de las páginas.
- **JavaScript:** consume la API para realizar operaciones sin recargar la página.

Se utiliza MySQL como base de datos relacional para almacenar las reservas y su historial, mantener la integridad referencial y gestionar operaciones mediante transacciones.

### Estados de las reservas

Las reservas pueden encontrarse en tres estados: `pendiente`, `confirmada` y `cancelada`.

Las transiciones permitidas son:

- `pendiente` → `confirmada`
- `pendiente` → `cancelada`
- `confirmada` → `cancelada`

Una reserva cancelada no puede cambiar nuevamente de estado.

### Historial y consistencia

La creación de una reserva y los cambios de estado generan registros en la tabla `reserva_evento`.

Ambas operaciones se realizan dentro de una misma transacción para garantizar que los datos de la reserva y su historial permanezcan sincronizados.

Durante los cambios de estado se utiliza `SELECT ... FOR UPDATE` para bloquear la fila correspondiente y evitar modificaciones concurrentes incompatibles.

### Criterio de idiomas

El código interno utiliza nombres en inglés para clases, métodos y variables. Se mantienen en español los nombres del esquema de base de datos, las claves del contrato de la API y los valores de los estados, mientras que los mensajes de la interfaz están dirigidos al usuario en español.

## Tests
La aplicación dispone de pruebas automatizadas con PHPUnit que comprueban el comportamiento de los componentes y las operaciones sobre las reservas.

Para ejecutarlas:
```
docker compose exec app vendor/bin/phpunit
```

Las pruebas de integración utilizan una base de datos independiente de la principal.

Durante la inicialización de MySQL se crea una base de pruebas cuyo nombre se obtiene añadiendo el sufijo _test al nombre de la base de datos principal.

Por ejemplo:

Base principal: misterplan

Base de pruebas: misterplan_test

Esta separación permite ejecutar pruebas que modifican registros sin afectar a las reservas de la aplicación.

Importante: las pruebas de integración eliminan los datos de prueba durante su preparación y limpieza. No deben ejecutarse contra una base de datos con información real.

## Base de datos

La aplicación utiliza MySQL 8.4 y la codificación `utf8mb4` para almacenar correctamente caracteres especiales.

En una instalación nueva, el contenedor MySQL ejecuta automáticamente los archivos de inicialización:

1. `01-schema.sql`: crea las tablas y sus restricciones.
2. `02-seed.sql`: inserta reservas de ejemplo y su historial.
3. `03-test-database.sh`: crea y prepara la base de datos de pruebas.

Estos scripts se ejecutan únicamente durante la inicialización de un directorio de datos MySQL vacío.

Si ya existe un volumen con datos, reiniciar los contenedores no vuelve a ejecutar los scripts ni sobrescribe los registros existentes.

## Seguridad

- Las consultas utilizan parámetros preparados con PDO y la emulación
  de consultas preparadas está desactivada.
- La validación del servidor comprueba campos obligatorios, tipos,
  longitudes, correo electrónico, fechas e importe. También rechaza
  campos y filtros no permitidos.
- Twig tiene activado el escape automático de HTML. En JavaScript,
  los datos de las reservas se insertan mediante `textContent`.
- Los errores se gestionan de forma centralizada. Las excepciones
  inesperadas se registran en el servidor y la API devuelve un mensaje
  genérico, sin exponer consultas SQL ni detalles internos.
- La creación y los cambios de estado se ejecutan dentro de transacciones,
  de modo que la reserva y su historial se guardan conjuntamente.
- Los cambios de estado utilizan un bloqueo de fila (`SELECT ... FOR UPDATE`)
  para evitar modificaciones concurrentes sobre la misma reserva.
- Las credenciales se configuran mediante variables de entorno y el
  archivo `.env` está excluido del repositorio.

La aplicación no incluye autenticación ni autorización. Antes de utilizarla
con datos reales, sería necesario restringir el acceso a las reservas
y a las operaciones de creación y cambio de estado.

## Mejoras con más tiempo

- Renderizar con Twig las reservas iniciales del listado y mantener
  JavaScript para actualizar los resultados al aplicar filtros.
- Añadir autenticación y autorización para proteger los datos de huéspedes.
- Añadir paginación al listado y a la API mediante parámetros `limit`
  y `offset`, validando sus valores y estableciendo un límite máximo
  de resultados por petición.
- Ampliar los tests con casos límite de entrada y pruebas de los flujos web.
- Evaluar una caché con Redis para consultas frecuentes si las mediciones
  muestran que reduciría los tiempos de respuesta y la carga sobre MySQL.
- Configurar el entorno de producción, desactivando el modo de depuración
  y activando la caché de plantillas de Twig.

## Escalabilidad

1000 reservas por hora equivalen a unas 17 por minuto de media.
Antes de introducir cambios de infraestructura, mediría los tiempos
de respuesta, las consultas y los picos de peticiones.

Las primeras mejoras serían:

- Paginar el listado mediante `limit` y `offset` y seleccionar únicamente
  los campos necesarios, evitando recuperar todas las reservas en cada
  consulta. Para desplazamientos muy grandes, valorar paginación por cursor.
- Si las consultas repetidas se convierten en un cuello de botella,
  incorporar Redis con un tiempo de expiración e invalidación de la caché
  al crear, confirmar o cancelar reservas. Las claves incluirían los filtros
  y la paginación para distinguir los resultados.
- Revisar los planes de ejecución y ajustar los índices a los filtros
  y al orden del listado. Actualmente existen índices por estado
  y fecha de entrada.
- Activar la caché de plantillas de Twig y comprobar la configuración
  de OPcache en producción.
- Mantener las transacciones breves y los bloqueos por reserva para
  preservar la consistencia sin bloquear operaciones independientes.
- Registrar tiempos de respuesta, errores y consultas lentas para
  identificar los cuellos de botella.
- Si se incorporan correos de confirmación u otras tareas externas,
  ejecutarlas en segundo plano para que no retrasen la respuesta de la API.

Si las mediciones justificaran ampliar la capacidad, se podría aumentar
el número de instancias de la aplicación detrás de un balanceador,
manteniendo una base de datos compartida.