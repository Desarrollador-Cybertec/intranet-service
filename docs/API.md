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

Leyenda de permiso: 🌐 público · **user** autenticado · **admin** · 👤 solo recurso propio (o admin).

---

## Auth

### 🌐 POST /api/auth/login
- **Entrada:** `{ "email": string, "password": string }`
- **Salida `200`:**
  ```json
  { "token": "1|abc...", "user": { "id": "1", "name": "Juan Díaz", "role": "Colaborador", "roleType": "user", "initials": "JD", "email": "demo@insumma.co", "area": "Comercial", "phone": "Ext. 305" } }
  ```
- **Errores:** `401` credenciales inválidas (`Correo o contraseña incorrectos.`); `422` faltan campos.

### 🌐 POST /api/auth/register
- **Entrada:** `{ "name": string, "email": string, "password": string, "area": string }`
- **Validaciones:** `name` req; `email` email; `password` min 6; `area` req.
- **Lógica:** `roleType` se **fuerza a `user`**; `initials` se derivan del nombre (2 iniciales); `joined_at` = hoy.
- **Salida `201`:** igual forma que login (`{ token, user }`).
- **Errores:** `409` email ya existe (`Ya existe una cuenta con ese correo.`); `422` validación.

### POST /api/auth/logout · user
- **Entrada:** sin body. **Salida `200`:** `{ "success": true }`. Invalida el token actual.

### GET /api/auth/me · user
- **Salida `200`:** `UserProfile` (User + `color`, `joinedAt` `YYYY-MM-DD`, `extension`):
  ```json
  { "id": "1", "name": "Juan Díaz", "role": "Colaborador", "roleType": "user", "initials": "JD", "email": "demo@insumma.co", "area": "Comercial", "phone": "Ext. 305", "color": "#2E7D32", "joinedAt": "2023-02-15", "extension": "305" }
  ```

### PATCH /api/auth/me · 👤 propio
- **Entrada (parcial):** `{ "name"?, "area"?, "phone"? }`. **El email NO es editable.**
- **Salida `200`:** `UserProfile` actualizado.

### 🌐 POST /api/auth/forgot-password
- **Entrada:** `{ "email": string }`. **Salida `200`** siempre (`{ "success": true }`), no revela si la cuenta existe.

---

## Entérate — Noticias y Comunicados

### GET /api/news · user
### GET /api/comunicados · user
### GET /api/reconocimientos · user
### GET /api/events · user
- **Salida `200`:** `{ "items": Article[] }`. Cada uno filtra por su `type` (`noticias` / `comunicados` / `reconocimientos` / `eventos`).
- **Article:**
  ```json
  { "id": 1, "type": "noticias", "tag": "Corporativo", "tagBg": "#E8F5E9", "tagColor": "#2E7D32",
    "title": "...", "excerpt": "...", "date": "Hace 2 horas", "author": "Recursos Humanos",
    "imgs": ["bien1","bien2","bien3"], "body": "<p>HTML…</p>" }
  ```

### GET /api/news/{id} · user
- **Salida `200`:** `Article`. `404` si no existe o no es de tipo `noticias`.

### POST /api/news · admin  ·  PUT /api/news/{id} · admin  ·  DELETE /api/news/{id} · admin
(mismo patrón para `comunicados`, `reconocimientos`, `events`)
- **Entrada (POST):** todos los campos de Article salvo `id` — `type, tag, tagBg, tagColor, title, excerpt, date, author, imgs?, body`.
- **Validaciones:** `type ∈ {noticias,comunicados,reconocimientos,eventos}`; strings requeridos; `imgs` array de strings. En PUT todos opcionales.
- **Salida:** `201` con el `Article` creado / `200` actualizado / `{ "success": true }` al borrar.
- **Errores:** `403` si no es admin.

---

## Eventos (inscripción)

### POST /api/events/{id}/inscripcion · user
- **Entrada:** sin body. **Salida `200`:** `{ "success": true }`. `404` si el id no es un evento.

---

## Directorio

### GET /api/directory · user
- **Query (opcionales):** `search` (busca en nombre/rol/área), `area` (filtro exacto). Ej.: `/api/directory?search=laura&area=Bioseguridad`.
- **Salida `200`:** `{ "items": DirectoryPerson[] }`:
  ```json
  { "id": 1, "name": "Laura Peña", "role": "Coordinadora HSEQ", "area": "Bioseguridad",
    "image": "/assets/img/directorio/laura-pena.jpg", "initials": "LP", "color": "#2E7D32",
    "email": "laura.pena@insumma.co", "phone": "Ext. 201" }
  ```
  `image` puede ser `null`.

### POST /api/directory · admin · PUT /api/directory/{id} · admin · DELETE /api/directory/{id} · admin
- **Entrada:** `name, role, area, image?, initials, color, email, phone`.

---

## Foro

### GET /api/forum/posts · user
- **Salida `200`:** `{ "items": ForumPost[] }`:
  ```json
  { "id": 1, "title": "...", "body": "...", "tag": "Bioseguridad",
    "tags": ["Bioseguridad","Protocolos","🔥 Popular"], "votes": 24,
    "author": "Laura Peña", "date": "Hace 3 horas", "replies": 8 }
  ```

### POST /api/forum/posts · user
- **Entrada:** `{ "title": string, "body": string, "tags"?: string[], "tag"?: string }`.
- **Lógica:** `author`/`author_id` = usuario actual; `votes`/`replies` = 0; `date` = "Ahora mismo". Si no envías `tag`, se toma `tags[0]`.
- **Salida `201`:** `ForumPost`.

### POST /api/forum/posts/{id}/vote · user
- **Entrada:** `{ "direction": "up" | "down" }`.
- **Lógica (autoritativa):** cada usuario tiene **un** voto por post. Votar la misma dirección es **idempotente**; cambiar de dirección ajusta el total en **±2**. El backend devuelve el total real (no confía en el conteo del cliente).
- **Salida `200`:** `{ "votes": 25 }`.
- **Uso en front:** update optimista y reconciliar con el `votes` devuelto (revertir si falla).

### PUT /api/forum/posts/{id} · 👤 autor o admin · DELETE /api/forum/posts/{id} · 👤 autor o admin
- **Entrada (PUT):** `title?, body?, tags?, tag?`.
- **Errores:** `403` si no eres el autor ni admin (`ForumPostPolicy`).

---

## Buzón de Ideas

### GET /api/ideas · user
- **Salida `200`:** `{ "items": Idea[] }`:
  ```json
  { "id": 1, "category": "Bienestar laboral", "title": "...", "description": "...",
    "anonymous": true, "votes": 12, "date": "Hace 3 días", "author": "Anónimo" }
  ```
- **Anonimato:** si `anonymous:true`, `author` se expone como `"Anónimo"`. **Solo admin** recibe además `realAuthor`, `authorId` y `status` (auditoría/moderación).

### POST /api/ideas · user
- **Entrada:** `{ "category": string, "title": string, "description": string, "anonymous"?: boolean }`.
- **Validaciones:** `title` 5–100, `description` 20–500, `category` req. (réplica de `ideaSchema`).
- **Lógica:** el `author_id` real **siempre** se persiste (aunque sea anónima). `votes` = 0.
- **Salida `201`:** `{ "success": true, "id": 3 }`. `422` validación.

### POST /api/ideas/{id}/vote · user
- **Entrada:** sin body. **Lógica:** un voto por usuario (idempotente). **Salida `200`:** `{ "votes": 13 }`.

### PATCH /api/ideas/{id} · admin · DELETE /api/ideas/{id} · admin
- **PATCH entrada:** `{ "status"?: string }` (moderación / cambio de estado). **DELETE:** `{ "success": true }`.

---

## Súmate (gamificación)

### GET /api/sumate/participants · user
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

**Lógica de negocio (recalculada en el servidor, no manipulable por el cliente):**
- **Puntos por acción:** `min(count, max) * ptsEach`, con tope `maxPts`. **Total = suma de las 5 acciones, cap 100.**
- **Elegibilidad:** `eligible = true` **solo si las 5 precondiciones son `true`**. Ejemplos del seed:
  - *Felipe Castro*: 100 pts pero `puntualidad:false` → `eligible:false`.
  - *Juan Díaz*: `capacitaciones:false` → `eligible:false` (aunque tenga puntos).
- **Nivel:** se asigna por rango de puntos **solo si es elegible**.

### POST /api/sumate/acciones · 👤 (participante actual)
- **Entrada:** `{ "accionId": "yoAporto", "delta": 1 }` (`delta ∈ {-1, 1}`; `accionId` debe existir).
- **Lógica:** respeta el `max` de la acción (no sube por encima ni baja de 0) y recalcula.
- **Salida `200`:** estado del participante:
  ```json
  { "id": 10, "name": "Juan Díaz", "initials": "JD", "color": "#388E3C", "area": "Comercial",
    "pre": {...}, "acc": { "yoAporto": 3, ... }, "pts": 60, "eligible": false, "nivel": null }
  ```
- **Errores:** `403` si el usuario no es participante; `422` `accionId`/`delta` inválidos.

---

## Capacitaciones

### GET /api/capacitaciones · user
- **Salida `200`:** `{ "items": Course[], "progress": { total, done, percent } }`. `completed` y `progress` son **relativos al usuario autenticado**.
  ```json
  { "items": [ { "id": 1, "label": "Inducción corporativa", "icon": "🏢", "tag": "Obligatorio",
                 "tagColor": "#C62828", "tagBg": "#FFEBEE", "desc": "...", "duration": "4 horas",
                 "modality": "Virtual", "completed": true } ],
    "progress": { "total": 6, "done": 1, "percent": 17 } }
  ```

### POST /api/capacitaciones/{id}/inscripcion · user
- **Entrada:** sin body. **Salida `200`:** `{ "success": true, "id": 2 }`.

### POST /api/capacitaciones · admin · PUT /api/capacitaciones/{id} · admin · DELETE /api/capacitaciones/{id} · admin
- **Entrada:** `label, icon, tag (Obligatorio|Desarrollo|Técnico), tagColor, tagBg, desc, duration, modality (Virtual|Presencial|Mixto)`.

---

## Gestión: RH · SST · SIG

### GET /api/rh/modules · user
### GET /api/sst/modules · user
### GET /api/sig/modules · user
- **Salida `200`:** `{ "items": Module[] }` — el `id` es el **slug** estable:
  ```json
  { "id": "nomina", "label": "Nómina y liquidaciones", "icon": "💰", "color": "#F57C00", "bg": "#FFF3E0", "desc": "..." }
  ```

### POST /api/{rh|sst|sig}/modules · admin · PUT .../{id} · admin · DELETE .../{id} · admin
- **Entrada:** `slug, label, icon, color, bg, desc`.

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
