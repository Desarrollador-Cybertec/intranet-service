# API Insumma Intranet — Guía de integración (frontend)

Referencia de la API REST real que reemplaza los mocks del front (`src/mocks/*`). Refleja **lo implementado** en el backend Laravel, no el mock. Cuando esta doc y el mock difieran, **manda esta doc**.

- Contrato original: `.context/mocks/.context/02-contrato-api.md`
- Código fuente de verdad: `routes/api.php`, `app/Http/Controllers/Api/*`, `app/Http/Requests/*`, `app/Http/Resources/*`

---

## 1. Arranque y base URL

**Backend:**
```bash
php artisan migrate:fresh --seed      # crea esquema + datos de ejemplo (mocks)
php artisan serve --port=3000         # expone la API en http://localhost:3000
```

**Frontend** (`.env.development`):
```
VITE_USE_MOCK=false
VITE_API_BASE_URL=http://localhost:3000
```

Todas las rutas cuelgan de `/api`. Ej.: `POST http://localhost:3000/api/auth/login`.

---

## 2. Autenticación

Tokens **Bearer** (Laravel Sanctum, personal access tokens).

1. `POST /api/auth/login` (o `/register`) → devuelve `{ token, user }`.
2. Guardar `token` en `localStorage['ibg_token']`.
3. En **cada** request protegida enviar el header:
   ```
   Authorization: Bearer <token>
   Content-Type: application/json
   Accept: application/json
   ```
4. `logout` invalida el token actual en el servidor.

> Envía siempre `Accept: application/json` para que los errores lleguen como JSON (`{message}`) y no como HTML.

---

## 3. Convenciones de respuesta

| Caso | Forma |
|---|---|
| Listas | `{ "items": [ ... ] }` |
| Capacitaciones | `{ "items": [...], "progress": { total, done, percent } }` |
| Súmate participants | objeto compuesto (ver §Súmate) |
| Recurso único | el objeto directamente (sin envoltura) |
| Acción simple | `{ "success": true, "id"?: number }` o `{ "votes": number }` |
| Error | `{ "message": "texto en español" }` |

Claves siempre en **camelCase** (`roleType`, `tagBg`, `ptsEach`, `joinedAt`…). Las fechas de feed son **strings ya formateados** (`"Hace 2 horas"`, `"12 jul, 2026"`), tal como el mock.

---

## 4. Roles y permisos

Campo `user.roleType`:

| roleType | Puede |
|---|---|
| `user` | Leer todo el contenido, votar, crear posts/ideas propias, registrar sus acciones Súmate, inscribirse, editar su perfil y sus propios recursos (👤). |
| `admin` | Todo lo de `user` **+** CRUD de contenido, catálogos, moderación y gestión. |

- `user.role` (p. ej. "Colaborador") es solo **cargo de display**; la autorización usa `roleType`.
- **Ownership (👤):** un `user` solo edita/elimina sus propios recursos; un `admin` opera sobre cualquiera.

---

## 5. Códigos de error

| Código | Cuándo | Ejemplo de `message` |
|---|---|---|
| `401` | Sin token o token inválido | `No autenticado.` |
| `403` | Autenticado pero sin permiso | `No tienes permiso para realizar esta acción.` |
| `404` | Recurso inexistente | `Recurso no encontrado.` |
| `409` | Conflicto (email duplicado) | `Ya existe una cuenta con ese correo.` |
| `422` | Validación fallida | `{ "message": "...", "errors": { "campo": ["..."] } }` |

El front muestra `message` directamente al usuario.

---

## 6. CORS

`config/cors.php` permite orígenes: `http://localhost:5173`, `http://127.0.0.1:5173`, `http://localhost:3000`. Añade el dominio de producción cuando se despliegue. Auth por Bearer (no cookies) → `supports_credentials: false`.

---

## 7. Cuentas de prueba (seed)

| Email | Password | roleType |
|---|---|---|
| `demo@insumma.co` | `Insumma2026!` | `user` (Juan Díaz) |
| `admin@insumma.co` | `Admin2026#` | `admin` |

---

# Referencia por endpoint

Cada endpoint documenta:
- **Alcance** — qué hace esta ruta, en una frase.
- **Visibilidad** — quién puede llamarla y si la respuesta cambia según quién la consulta.
- **Entrada** — path params, query params y body esperados.
- **Salida** — código HTTP y forma exacta del JSON de respuesta.
- **Validaciones** / **Lógica de negocio** — solo cuando aplica.
- **Errores** — códigos posibles además de los genéricos de la §5.

---

## Auth

### POST /api/auth/login
- **Alcance:** autentica una cuenta existente y emite un token de acceso.
- **Visibilidad:** 🌐 público (no requiere token).
- **Entrada:** `{ "email": string, "password": string }`
- **Salida `200`:**
  ```json
  { "token": "1|abc...", "user": { "id": "1", "name": "Juan Díaz", "role": "Colaborador", "roleType": "user", "initials": "JD", "email": "demo@insumma.co", "area": "Comercial", "phone": "Ext. 305" } }
  ```
- **Validaciones:** `email` formato válido; `password` requerido.
- **Errores:** `401` credenciales inválidas (`Correo o contraseña incorrectos.`); `422` faltan campos.

### POST /api/auth/register
- **Alcance:** crea una cuenta nueva y autentica de inmediato (devuelve token, sin paso de confirmación).
- **Visibilidad:** 🌐 público.
- **Entrada:** `{ "name": string, "email": string, "password": string, "area": string }`
- **Validaciones:** `name` requerido; `email` formato válido; `password` mín. 6 caracteres; `area` requerido.
- **Lógica de negocio:** `roleType` se **fuerza a `"user"`** sin importar lo que envíe el cliente — no existe autoregistro como admin. `initials` se derivan automáticamente de las 2 primeras palabras del `name`. `joined_at` = fecha del día.
- **Salida `201`:** misma forma que login (`{ token, user }`).
- **Errores:** `409` email ya registrado (`Ya existe una cuenta con ese correo.`); `422` validación.

### POST /api/auth/logout
- **Alcance:** cierra la sesión actual invalidando el token Bearer usado en esta petición.
- **Visibilidad:** **user** — cualquier autenticado; solo afecta **su propio** token actual (otros tokens/dispositivos de la misma cuenta, si existieran, no se ven afectados).
- **Entrada:** sin body.
- **Salida `200`:** `{ "success": true }`.

### GET /api/auth/me
- **Alcance:** obtiene el perfil completo del usuario autenticado (para el header y "Mi Perfil").
- **Visibilidad:** 👤 estrictamente el dueño del token. **No existe** endpoint para que un admin consulte el perfil de otro usuario en esta entrega.
- **Salida `200`:** `UserProfile`:
  ```json
  { "id": "1", "name": "Juan Díaz", "role": "Colaborador", "roleType": "user", "initials": "JD", "email": "demo@insumma.co", "area": "Comercial", "phone": "Ext. 305", "color": "#2E7D32", "joinedAt": "2023-02-15", "extension": "305" }
  ```

### PATCH /api/auth/me
- **Alcance:** edita los datos editables del propio perfil.
- **Visibilidad:** 👤 propio, sin excepción — no hay endpoint de "gestionar usuarios" (editar rol/desactivar terceros) implementado todavía, aunque el contrato original lo contempla para admin.
- **Entrada (parcial, todos opcionales):** `{ "name"?: string, "area"?: string, "phone"?: string }`. **El `email` no es editable** por esta vía (se ignora si se envía).
- **Salida `200`:** `UserProfile` actualizado.

### POST /api/auth/forgot-password
- **Alcance:** dispara el flujo de recuperación de contraseña.
- **Visibilidad:** 🌐 público.
- **Entrada:** `{ "email": string }`.
- **Salida `200`:** `{ "success": true }` **siempre**, exista o no la cuenta (evita que un atacante enumere correos válidos).
- **Nota de implementación:** el envío real del correo aún no está conectado; el endpoint solo valida el formato y responde éxito.

---

## Entérate — Noticias y Comunicados

### GET /api/news
- **Alcance:** feed de noticias corporativas para el tab "Noticias".
- **Visibilidad:** **user** — todos los autenticados ven exactamente el mismo listado (sin filtrar por área, rol ni antigüedad).
- **Salida `200`:** `{ "items": Article[] }` (solo `type:"noticias"`):
  ```json
  { "id": 1, "type": "noticias", "tag": "Corporativo", "tagBg": "#E8F5E9", "tagColor": "#2E7D32",
    "title": "...", "excerpt": "...", "date": "Hace 2 horas", "author": "Recursos Humanos",
    "imgs": ["bien1","bien2","bien3"], "body": "<p>HTML…</p>" }
  ```

### GET /api/news/{id}
- **Alcance:** detalle de una noticia puntual (para el modal de artículo).
- **Visibilidad:** **user**, igual para todos.
- **Salida `200`:** `Article` completo (mismo shape que arriba, incluye `body` en HTML).
- **Errores:** `404` si el id no existe o no corresponde a `type:"noticias"`.

### GET /api/comunicados
- **Alcance:** feed de comunicados oficiales para el tab "Comunicados".
- **Visibilidad:** **user**, mismo listado para todos.
- **Salida `200`:** `{ "items": Article[] }` (solo `type:"comunicados"`), mismo shape de Article.

### GET /api/reconocimientos
- **Alcance:** feed de reconocimientos a colaboradores/equipos.
- **Visibilidad:** **user**, mismo listado para todos.
- **Salida `200`:** `{ "items": Article[] }` (solo `type:"reconocimientos"`), mismo shape de Article.

### GET /api/events
- **Alcance:** feed de eventos corporativos (para el grid de Eventos, no el calendario).
- **Visibilidad:** **user**, mismo listado para todos.
- **Salida `200`:** `{ "items": Article[] }` (solo `type:"eventos"`), mismo shape de Article.

### POST /api/news · PUT /api/news/{id} · DELETE /api/news/{id}
*(mismo patrón exacto para `/api/comunicados`, `/api/reconocimientos` y `/api/events` — solo cambia el `type` implícito en la ruta)*
- **Alcance:** crear, editar o eliminar contenido editorial.
- **Visibilidad:** **admin** exclusivamente para escribir. Una vez creado/editado, el contenido es visible para **todos** los `user` en el GET correspondiente de inmediato (sin revisión ni estado "borrador").
- **Entrada (POST, todos requeridos salvo `imgs`):** `{ "type", "tag", "tagBg", "tagColor", "title", "excerpt", "date", "author", "imgs"?: string[], "body" }`. En **PUT** todos los campos son opcionales (solo se actualiza lo enviado).
- **Validaciones:** `type ∈ {noticias, comunicados, reconocimientos, eventos}`; resto strings requeridos en POST; `imgs` array de strings si se envía.
- **Salida:** `201` con el `Article` creado (POST) · `200` con el `Article` actualizado (PUT) · `200` `{ "success": true }` (DELETE).
- **Errores:** `403` si el usuario no es admin.

---

## Eventos — Inscripción

### POST /api/events/{id}/inscripcion
- **Alcance:** registra la intención de asistencia del usuario autenticado a un evento.
- **Visibilidad:** **user**, acción sobre uno mismo (implícita por el token, no se envía `userId`).
- **Entrada:** sin body.
- **Salida `200`:** `{ "success": true }`.
- **Errores:** `404` si el `id` no corresponde a un `Article` de `type:"eventos"`.
- **⚠️ Limitación actual:** la inscripción **no se persiste** todavía (no hay tabla de inscritos ni endpoint para que el usuario vea "mis eventos" o el admin liste asistentes). El endpoint solo confirma la acción; tratarlo como *fire-and-forget* hasta que se documente lo contrario.

---

## Directorio

### GET /api/directory
- **Alcance:** búsqueda/listado de colaboradores para la sección Directorio.
- **Visibilidad:** **user**, mismo resultado para todos dado el mismo query.
- **Entrada (query, ambos opcionales):** `search` (coincide contra nombre, rol o área), `area` (filtro exacto). Ej.: `/api/directory?search=laura&area=Bioseguridad`.
- **Salida `200`:** `{ "items": DirectoryPerson[] }`:
  ```json
  { "id": 1, "name": "Laura Peña", "role": "Coordinadora HSEQ", "area": "Bioseguridad",
    "image": "/assets/img/directorio/laura-pena.jpg", "initials": "LP", "color": "#2E7D32",
    "email": "laura.pena@insumma.co", "phone": "Ext. 201" }
  ```
  `image` puede ser `null` (mostrar avatar de iniciales/color en ese caso).

### POST /api/directory · PUT /api/directory/{id} · DELETE /api/directory/{id}
- **Alcance:** administrar las fichas del directorio.
- **Visibilidad:** **admin** exclusivamente; el resultado se refleja de inmediato en el GET público.
- **Entrada:** `{ "name", "role", "area", "image"?, "initials", "color", "email", "phone" }` (opcionales en PUT).
- **Salida:** `201` ficha creada · `200` ficha actualizada · `200` `{ "success": true }` al borrar.
- **Errores:** `403` si no es admin.

---

## Foro

### GET /api/forum/posts
- **Alcance:** listado completo de publicaciones del foro.
- **Visibilidad:** **user**, todos ven todas las publicaciones de todos los autores (no hay posts privados ni moderación oculta en el listado).
- **Salida `200`:** `{ "items": ForumPost[] }`:
  ```json
  { "id": 1, "title": "...", "body": "...", "tag": "Bioseguridad",
    "tags": ["Bioseguridad","Protocolos","🔥 Popular"], "votes": 24,
    "author": "Laura Peña", "date": "Hace 3 horas", "replies": 8 }
  ```

### POST /api/forum/posts
- **Alcance:** crea una publicación nueva.
- **Visibilidad:** **user**; queda visible para todos de inmediato, atribuida al autor real (no hay opción de post anónimo en el foro, a diferencia de Ideas).
- **Entrada:** `{ "title": string, "body": string, "tags"?: string[], "tag"?: string }`.
- **Lógica de negocio:** `author`/`author_id` se asignan del token, no del body. `votes`/`replies` inician en 0. `date` = `"Ahora mismo"`. Si no envías `tag`, se toma `tags[0]`.
- **Salida `201`:** `ForumPost` creado.

### POST /api/forum/posts/{id}/vote
- **Alcance:** emite o cambia el voto del usuario sobre una publicación.
- **Visibilidad:** **user**, un voto por usuario por post (no acumulable).
- **Entrada:** `{ "direction": "up" | "down" }`.
- **Lógica de negocio (autoritativa, no confía en el cliente):** votar la **misma** dirección otra vez es **idempotente** (no suma de nuevo); **cambiar** de dirección ajusta el total en **±2** respecto al voto anterior del mismo usuario.
- **Salida `200`:** `{ "votes": 25 }` — total real recalculado en servidor.
- **Uso recomendado en front:** update optimista sobre el contador local y reconciliar/revertir con el `votes` devuelto si la llamada falla.
- **Validaciones:** `direction ∈ {up, down}` → `422` si es otro valor.

### PUT /api/forum/posts/{id} · DELETE /api/forum/posts/{id}
- **Alcance:** editar o eliminar una publicación existente.
- **Visibilidad:** 👤 **solo el autor original o un admin** (`ForumPostPolicy`). Otro `user` no puede tocar el post aunque esté autenticado.
- **Entrada (PUT, opcionales):** `{ "title"?, "body"?, "tags"?, "tag"? }`.
- **Salida:** `200` post actualizado (PUT) · `200` `{ "success": true }` (DELETE).
- **Errores:** `403` si no eres el autor ni admin.

---

## Buzón de Ideas

### GET /api/ideas
- **Alcance:** listado de ideas enviadas por colaboradores.
- **Visibilidad — varía según el rol de quien consulta:**
  - **user:** ve todas las ideas, pero si `anonymous:true` el campo `author` llega como `"Anónimo"` — no hay forma de deducir al autor real desde esta respuesta.
  - **admin:** además de `author` enmascarado, recibe `realAuthor` (nombre real), `authorId` y `status` (para auditoría/moderación) — incluso en ideas anónimas.
- **Salida `200`:** `{ "items": Idea[] }`:
  ```json
  { "id": 1, "category": "Bienestar laboral", "title": "...", "description": "...",
    "anonymous": true, "votes": 12, "date": "Hace 3 días", "author": "Anónimo" }
  ```

### POST /api/ideas
- **Alcance:** envía una idea nueva, con opción de anonimato.
- **Visibilidad:** **user**; queda visible para todos de inmediato (no hay cola de aprobación previa a mostrarse).
- **Entrada:** `{ "category": string, "title": string, "description": string, "anonymous"?: boolean }`.
- **Validaciones:** `title` 5–100 caracteres; `description` 20–500 caracteres; `category` requerido (réplica de `ideaSchema` del front).
- **Lógica de negocio:** el `author_id` real del usuario autenticado **siempre se persiste**, incluso si `anonymous:true` — el anonimato es solo de cara a la respuesta pública (ver visibilidad de GET arriba). `votes` inicia en 0.
- **Salida `201`:** `{ "success": true, "id": 3 }`.
- **Errores:** `422` si `title`/`description` no cumplen longitud.

### POST /api/ideas/{id}/vote
- **Alcance:** vota a favor de una idea.
- **Visibilidad:** **user**, un voto por usuario por idea (idempotente: repetir la llamada no vuelve a sumar).
- **Entrada:** sin body.
- **Salida `200`:** `{ "votes": 13 }`.

### PATCH /api/ideas/{id} · DELETE /api/ideas/{id}
- **Alcance:** moderación — cambiar el estado de una idea o eliminarla.
- **Visibilidad:** **admin** exclusivamente.
- **Entrada (PATCH):** `{ "status"?: string }`.
- **Salida:** `200` idea actualizada (PATCH, con el shape "admin" que incluye `realAuthor`) · `200` `{ "success": true }` (DELETE).
- **Errores:** `403` si no es admin.

---

## Súmate (gamificación)

### GET /api/sumate/participants
- **Alcance:** trae el estado completo del programa: catálogos (precondiciones/acciones/niveles), todos los participantes con su detalle, y el leaderboard.
- **Visibilidad:** **user** — ⚠️ **cualquier colaborador autenticado ve el detalle completo de *todos* los participantes**, incluyendo sus precondiciones individuales (`pre`: antigüedad, puntualidad, asistencia, disciplinarios, capacitaciones) y conteo de acciones (`acc`) — no solo su propio estado ni solo el leaderboard resumido. No hay ocultamiento de datos entre compañeros en este endpoint.
- **Salida `200`** (objeto compuesto):
  ```json
  {
    "trimestre": "Q3 2026",
    "periodoLabel": "Julio – Septiembre 2026",
    "cierreLabel": "30 sep 2026",
    "precondiciones": [ { "id": "antiguedad", "label": "Antigüedad", "req": "> 3 meses", "desc": "..." }, ... ],
    "acciones": [ { "id": "yoAporto", "label": "Yo Aporto", "icon": "💡", "desc": "...", "ptsEach": 10, "max": 3, "maxPts": 30, "color": "#2E7D32", "bg": "#E8F5E9", "rango": "1 a 3 reportes" }, ... ],
    "niveles": [ { "nivel": 1, "emoji": "🏆", "label": "Nivel 1", "min": 100, "max": 100, "color": "#2E7D32", "bg": "#E8F5E9", "beneficio": "...", "condicion": "..." }, ... ],
    "participantes": [ { "id": 10, "name": "Juan Díaz", "initials": "JD", "color": "#388E3C", "area": "Comercial",
                        "pre": { "antiguedad": true, "puntualidad": true, "asistencia": true, "disciplinarios": true, "capacitaciones": false },
                        "acc": { "yoAporto": 2, "mejora": 1, "infraestructura": 0, "inseguras": 1, "redes": 1 },
                        "pts": 50, "eligible": false }, ... ],
    "myParticipantId": 10,
    "leaderboard": { "players": [ { "id": 1, "name": "Laura Peña", "initials": "LP", "color": "#2E7D32", "area": "Bioseguridad", "pts": 100 }, ... ], "myPoints": 50, "myRank": 9 }
  }
  ```
- **Lógica de negocio (recalculada en el servidor, no manipulable por el cliente):**
  - **Puntos por acción:** `min(count, max) * ptsEach`, con tope `maxPts`. **Total = suma de las 5 acciones, cap 100.**
  - **Elegibilidad:** `eligible = true` **solo si las 5 precondiciones son `true`**. Ejemplos del seed:
    - *Felipe Castro*: 100 pts pero `puntualidad:false` → `eligible:false`.
    - *Juan Díaz*: `capacitaciones:false` → `eligible:false` (aunque tenga puntos).
  - **Nivel:** se asigna por rango de puntos **solo si es elegible**.

### POST /api/sumate/acciones
- **Alcance:** registra o retira una unidad de una acción para el participante del usuario autenticado.
- **Visibilidad:** 👤 solo el participante vinculado al usuario autenticado (via `sumate_participants.user_id`) — no se puede registrar acciones a nombre de otro.
- **Entrada:** `{ "accionId": "yoAporto", "delta": 1 }` (`delta ∈ {-1, 1}`; `accionId` debe existir en el catálogo).
- **Lógica de negocio:** el conteo nunca baja de 0 ni supera el `max` de la acción; se recalcula `pts`/`eligible` tras cada cambio.
- **Salida `200`:** estado del participante:
  ```json
  { "id": 10, "name": "Juan Díaz", "initials": "JD", "color": "#388E3C", "area": "Comercial",
    "pre": {...}, "acc": { "yoAporto": 3, ... }, "pts": 60, "eligible": false, "nivel": null }
  ```
- **Errores:** `403` si el usuario autenticado no tiene participante Súmate asociado; `422` si `accionId`/`delta` son inválidos.

---

## Capacitaciones

### GET /api/capacitaciones
- **Alcance:** catálogo de cursos con el progreso de finalización.
- **Visibilidad:** **user** — el catálogo (`items`) es el mismo para todos, pero el campo `completed` de cada curso y el objeto `progress` son **relativos al usuario autenticado** (no se ve el progreso de otros colaboradores).
- **Salida `200`:** `{ "items": Course[], "progress": { total, done, percent } }`:
  ```json
  { "items": [ { "id": 1, "label": "Inducción corporativa", "icon": "🏢", "tag": "Obligatorio",
                 "tagColor": "#C62828", "tagBg": "#FFEBEE", "desc": "...", "duration": "4 horas",
                 "modality": "Virtual", "completed": true } ],
    "progress": { "total": 6, "done": 1, "percent": 17 } }
  ```

### POST /api/capacitaciones/{id}/inscripcion
- **Alcance:** inscribe al usuario autenticado en un curso (no lo marca como completado).
- **Visibilidad:** **user**, acción sobre uno mismo.
- **Entrada:** sin body.
- **Salida `200`:** `{ "success": true, "id": 2 }`.

### POST /api/capacitaciones · PUT /api/capacitaciones/{id} · DELETE /api/capacitaciones/{id}
- **Alcance:** administrar el catálogo de cursos.
- **Visibilidad:** **admin** exclusivamente.
- **Entrada:** `{ "label", "icon", "tag": "Obligatorio"|"Desarrollo"|"Técnico", "tagColor", "tagBg", "desc", "duration", "modality": "Virtual"|"Presencial"|"Mixto" }` (opcionales en PUT).
- **Errores:** `403` si no es admin.

---

## Gestión: RH · SST · SIG

### GET /api/rh/modules
### GET /api/sst/modules
### GET /api/sig/modules
- **Alcance:** catálogo de accesos/tarjetas de cada módulo de gestión (nómina, permisos, EPP, calidad ISO, etc.).
- **Visibilidad:** **user**, mismo catálogo para todos dentro de cada sección.
- **Salida `200`:** `{ "items": Module[] }` — el `id` es el **slug** estable (p. ej. `"nomina"`, `"epp"`, `"calidad"`), no un correlativo numérico:
  ```json
  { "id": "nomina", "label": "Nómina y liquidaciones", "icon": "💰", "color": "#F57C00", "bg": "#FFF3E0", "desc": "..." }
  ```
- **Nota:** hoy cada tarjeta solo dispara un toast en el front; no hay flujo propio detrás de cada `id` todavía.

### POST /api/{rh|sst|sig}/modules · PUT .../{id} · DELETE .../{id}
- **Alcance:** administrar el catálogo de módulos de la sección correspondiente.
- **Visibilidad:** **admin** exclusivamente.
- **Entrada:** `{ "slug", "label", "icon", "color", "bg", "desc" }` (opcionales en PUT).
- **Errores:** `403` si no es admin.

---

## Fuera de alcance de esta API

| Función | Fuente | En esta API |
|---|---|---|
| Calendario corporativo | Nextcloud ICS | No (`/api/calendar/*` no implementado) |
| Reservas de salas | Nextcloud CalDAV | No (`/api/rooms/*` no implementado) |
| SICREO 2.0 | Enlaces externos | No |
| S!NTyC | App externa | No |

---

# Apéndice · Shapes de entidades (para tipar el front)

Claves exactas que devuelve cada recurso. Todas en camelCase.

| Entidad | Campos |
|---|---|
| **User** | `id`(string), `name`, `role`, `roleType`('admin'\|'user'), `initials`, `email`, `area`, `phone` |
| **UserProfile** | User + `color`, `joinedAt`('YYYY-MM-DD'), `extension` |
| **Article** | `id`(number), `type`, `tag`, `tagBg`, `tagColor`, `title`, `excerpt`, `date`(string), `author`, `imgs`(string[]), `body`(HTML) |
| **DirectoryPerson** | `id`, `name`, `role`, `area`, `image`(string\|null), `initials`, `color`, `email`, `phone` |
| **ForumPost** | `id`, `title`, `body`, `tag`, `tags`(string[]), `votes`(number), `author`, `date`(string), `replies`(number) |
| **Idea** | `id`, `category`, `title`, `description`, `anonymous`(bool), `votes`, `date`, `author` — (admin: `realAuthor`, `authorId`, `status`) |
| **Course** | `id`, `label`, `icon`, `tag`('Obligatorio'\|'Desarrollo'\|'Técnico'), `tagColor`, `tagBg`, `desc`, `duration`, `modality`('Virtual'\|'Presencial'\|'Mixto'), `completed`(bool) |
| **Module** | `id`(slug), `label`, `icon`, `color`, `bg`, `desc` |
| **SumateParticipant** | `id`, `name`, `initials`, `color`, `area`, `pre`(Record<slug,bool>), `acc`(Record<slug,number>), `pts`, `eligible` |
