# Baby Info

A web platform for families to manage resources related to raising a child — from feeding and sleep logs to medical history, gallery moments, and social relationships. Built as a university Web Technologies project.

---

## Demo

> 🎬 **Video demo:** _https://www.youtube.com/watch?v=X9zjE-OrN_s_

---

## Authors

## Authors

| Nume                | Contribuții |
|---------------------|-------------|
| **Romila Raluca**   | Autentificare (login/register/logout/CSRF/reset parola), profil copil + upload foto, familie + invitații, feeding/sleep/growth logs, galerie (momente + upload media), comentarii, reacții, timeline, REST API v1 Bearer token, arhitectura core (Router, AuthMiddleware, Security, SessionManager, Response) |
| **Tarpescu Sergiu** | Admin panel (backend complet + UI/frontend), pagina Medical Records (backend controller/model + frontend UI), MediaModel + integrare upload multipart, RSS Controller & Service (generare XML valid), export/import JSON și CSV (Zip archive), calcul spațiu disc (`StorageService`), relații sociale și interacțiuni (Models & Controllers), Password reset endpoint |
---

## Tech Stack

- **Backend:** PHP 8.2 (vanilla, fără framework) — arhitectură MVC cu Composer autoloader (PSR-4)
- **Database:** PostgreSQL 16 — PDO cu prepared statements exclusiv
- **Frontend:** HTML5 + CSS3 separat per pagină (`public/css/`) + JavaScript vanilla — `fetch()` pentru toate apelurile asincrone
- **Autentificare:** Sesiune PHP (frontend) + Bearer token (REST API v1)
- **Securitate:** CSRF double-submit cookie, XSS escaping, bcrypt cost=12, fișiere în afara webroot
- **Dev server:** `php -S localhost:8000 router.php`

---

## Setup

### Requirements
- PHP 8.2+ (cu extensiile `pdo_pgsql`, `mbstring`, `fileinfo`)
- PostgreSQL 16 (port 5433 în configurația implicită)
- Composer

### Installation

```bash
git clone https://github.com/tarpescu/Baby-Info-on-Web
cd Baby-Info-on-Web
composer install
cp .env.example .env
# Editează .env cu credențialele tale PostgreSQL
```

### Database

```bash
# Windows — calea completă:
"C:\Program Files\PostgreSQL\16\bin\psql.exe" -U postgres -p 5433 -c "CREATE DATABASE babyinfo;"
"C:\Program Files\PostgreSQL\16\bin\psql.exe" -U postgres -p 5433 -d babyinfo -f database/schema.sql
"C:\Program Files\PostgreSQL\16\bin\psql.exe" -U postgres -p 5433 -d babyinfo -f database/populare.sql
# Parola pentru datele de test: password
```

### Run

```bash
php -S localhost:8000 router.php
```

Aplicația e disponibilă la `http://localhost:8000`.  
Cont de test: `mirabelapopescu@gmail.com` / `test123`

---

## Project Structure

```
Baby-Info-on-Web/
├── router.php                  # Front controller — routes URLs to HTML or API
├── api/
│   ├── index.php               # API entry point
│   ├── config/
│   │   ├── constants.php       # App-wide constants
│   │   └── database.php        # DB credentials and PDO connection
│   ├── controllers/            # One class per resource
│   ├── models/                 # One class per DB entity
│   ├── services/               # CsvService, JsonService, RssService, StorageService, UploadService
│   └── core/                   # Router, Request, Response, Controller, Model, Auth, Security, Session
├── public/
│   ├── *.html                  # All frontend pages
│   └── uploads/photos/         # Uploaded child profile photos
└── database/
    ├── schema.sql              # Full PostgreSQL schema
    └── populare.sql            # Seed data
```

---

## Features

| Module | Description |
|---|---|
| Authentication | Session-based login / logout / register; family grouping with roles |
| Child profiles | Multiple children per family; name, DOB, gender, photo, blood type |
| Feeding schedule | Log breastfeeding / bottle / solids with amount and time |
| Sleep schedule | Log sleep/wake events with timestamps |
| Growth tracking | Record weight, height, head circumference over time |
| Media gallery | Upload photos linked to moments; organize by date with filters |
| Medical history | Log doctor visits, vaccinations, medications, allergies |
| Social relationships | Record cousins, classmates, friends with interaction history |
| Timeline | Chronological feed per child; filterable by type and date |
| Sharing | Mark moments as shared; RSS 2.0 feed per child |
| Admin panel | User management, ban/unban, platform statistics, storage report |
| Export / Import | JSON and CSV export; CSV import (ZIP archive) |

### Roles & Permissions

| Role | Dashboard | Gallery | Can add/edit | Can invite |
|---|---|---|---|---|
| `owner` | ✅ | All moments | ✅ | ✅ |
| `coparent` | ✅ | All moments | ✅ | ❌ |
| `caregiver` | Redirected | Shared only | ❌ | ❌ |
| `viewer` | Redirected | Shared only | ❌ | ❌ |

---

## Security

- Passwords hashed with `password_hash(PASSWORD_BCRYPT, cost=12)`
- Session ID regenerated on every login (`session_regenerate_id`)
- All SQL via PDO prepared statements — no string interpolation
- All output escaped with `htmlspecialchars()` (ENT_QUOTES)
- CSRF tokens on all state-changing requests (stored in `$_SESSION['csrf_token']`)
- File uploads validated by MIME type (`finfo_file()`) and extension whitelist

---

## API Reference

All endpoints are under `/api/`. Authentication is session-based — the session cookie is set on login and must be included in all subsequent requests (`credentials: 'include'` in fetch).

Responses are JSON. Errors follow the format:
```json
{ "error": "Message describing the problem" }
```

### HTTP Status Codes

| Code | Meaning |
|---|---|
| 200 | OK |
| 201 | Created |
| 204 | No content |
| 400 | Bad request (missing/invalid field) |
| 401 | Unauthenticated |
| 403 | Forbidden (insufficient role) |
| 404 | Not found |
| 409 | Conflict (e.g. email already registered) |
| 410 | Gone (e.g. expired invite) |
| 422 | Unprocessable (validation failed) |
| 500 | Server error |

---

### Auth

#### `POST /api/auth/register`
Register a new user. If `invite_token` is provided, the user is automatically added to the corresponding child's family.

**Body:**
```json
{
  "first_name": "Ana",
  "last_name": "Pop",
  "email": "ana@example.com",
  "password": "secret123",
  "invite_token": "optional"
}
```

**Response `201`:**
```json
{ "id": 1, "first_name": "Ana", "email": "ana@example.com" }
```

---

#### `POST /api/auth/login`
**Body:** `{ "email": "...", "password": "..." }`

**Response `200`:**
```json
{
  "id": 1,
  "first_name": "Ana",
  "last_name": "Pop",
  "email": "ana@example.com",
  "role": "viewer",
  "is_superadmin": false,
  "theme": "girl",
  "avatar_color": "c1"
}
```

---

#### `POST /api/auth/logout`
Destroys the session. No body required.

---

#### `GET /api/auth/me`
Returns the currently authenticated user (same shape as login response). Returns `401` if not logged in.

---

#### `POST /api/auth/reset`
Trigger a password reset code for an email address.

**Body:** `{ "email": "ana@example.com" }`

---

### Children

#### `GET /api/children`
Returns all children the authenticated user has access to, including their `permission` field.

#### `POST /api/children`
Create a new child. The creator becomes `owner`.

**Body:**
```json
{
  "first_name": "Maria",
  "last_name": "Pop",
  "date_of_birth": "2023-05-10",
  "gender": "F",
  "blood_type": "A+"
}
```

#### `GET /api/children/{id}`
Get a single child by ID.

#### `PUT /api/children/{id}`
Update child fields. Requires write permission.

#### `DELETE /api/children/{id}`
Delete a child and all related data. Requires `owner` role.

#### `POST /api/children/{id}/photo`
Upload a profile photo. `Content-Type: multipart/form-data`, field name `photo`.
Allowed: JPEG, PNG, WebP. Max 10 MB.

---

### Family

#### `GET /api/children/{id}/family`
List all family members with their permissions.

#### `PUT /api/children/{id}/family/permission`
Update a member's permission. Requires write access.

**Body:** `{ "user_id": 5, "permission": "caregiver" }`

#### `DELETE /api/children/{id}/family/member`
Remove a member from the family. Cannot remove yourself.

**Body:** `{ "user_id": 5 }`

---

### Invitations

#### `POST /api/children/{id}/invites`
Generate an invite link. Requires write permission.

**Body:**
```json
{ "email": "optional@example.com", "permission": "viewer" }
```

**Response `201`:**
```json
{
  "token": "abc123...",
  "link": "/invite?token=abc123...",
  "expires_at": "2026-06-11T12:00:00"
}
```

Invite links expire after **72 hours**.

#### `GET /api/invite?token=abc123...`
Validate an invite token. Returns child name and assigned permission.

---

### Timeline & Moments

#### `GET /api/children/{id}/timeline`
Chronological list of moments. Query params: `?type=photo|video|audio|note`, `?limit=50`, `?offset=0`.

Each moment includes `comments` count and `reactions` array.

#### `GET /api/children/{id}/feed`
Same as timeline but grouped by month: `{ "June 2026": [...] }`.

#### `POST /api/children/{id}/moments`
Create a new moment. Supports `multipart/form-data` for file attachment (field `photo`).

**Body:**
```json
{
  "type": "photo",
  "title": "First steps",
  "body": "She walked today!",
  "happened_at": "2026-06-08T10:00:00",
  "is_pinned": false,
  "is_shared": true
}
```

`is_shared: true` makes the moment visible to `caregiver` and `viewer` roles.

#### `DELETE /api/moments/{id}`
Delete a moment. Requires write permission on the child.

---

### Comments & Reactions

#### `GET /api/moments/{id}/comments`
List all comments on a moment.

#### `POST /api/moments/{id}/comments`
**Body:** `{ "body": "So cute!" }`

#### `POST /api/moments/{id}/reactions`
Toggle a reaction (adds if not present, removes if already reacted with the same emoji).

**Body:** `{ "emoji_type": "heart" }` — allowed values: `heart`, `star`, `laugh`

#### `DELETE /api/moments/{id}/reactions`
Remove a reaction. **Body:** `{ "emoji_type": "heart" }`

---

### Feeding

#### `GET /api/children/{id}/feedings?limit=20`
#### `POST /api/children/{id}/feedings`

**Body:**
```json
{
  "type": "breast",
  "amount_ml": null,
  "duration_min": 15,
  "food_desc": null,
  "fed_at": "2026-06-08T08:30:00",
  "notes": "optional"
}
```
`type` values: `breast`, `bottle`, `solid`.

---

### Sleep

#### `GET /api/children/{id}/sleep?limit=20`
#### `POST /api/children/{id}/sleep`

**Body:**
```json
{
  "type": "night",
  "started_at": "2026-06-07T22:00:00",
  "ended_at": "2026-06-08T06:00:00",
  "notes": "optional"
}
```
`type` values: `nap`, `night`.

---

### Growth

#### `GET /api/children/{id}/growth`
#### `POST /api/children/{id}/growth`

**Body:**
```json
{
  "weight_kg": 8.5,
  "height_cm": 72.0,
  "head_cm": 44.5,
  "measured_at": "2026-06-08",
  "notes": "optional"
}
```

---

### Medical

#### `GET /api/children/{id}/medical?limit=50`
#### `POST /api/children/{id}/medical`

**Body:**
```json
{
  "type": "vaccination",
  "title": "MMR vaccine",
  "description": "optional",
  "date_at": "2026-06-01",
  "doctor": "Dr. Ionescu",
  "location": "Policlinica"
}
```
`type` values: `visit`, `vaccination`, `medication`, `allergy`, `other`.

---

### Relationships & Interactions

#### `GET /api/children/{id}/relationships`
#### `POST /api/children/{id}/relationships`

**Body:**
```json
{
  "name": "Andrei",
  "relationship": "cousin",
  "group_type": "family",
  "age_years": 3,
  "notes": "optional"
}
```
`group_type` values: `family`, `daycare`, `friends`, `other`.

#### `PUT /api/relationships/{id}`
Update a relationship.

#### `DELETE /api/relationships/{id}`

#### `GET /api/children/{id}/interactions`
All interactions for a child.

#### `GET /api/relationships/{id}/interactions`
Interactions for a specific relationship.

#### `POST /api/relationships/{id}/interactions`
**Body:** `{ "description": "Played in the park", "happened_at": "2026-06-08" }`

#### `DELETE /api/interactions/{id}`

---

### Export & Import

#### `GET /api/children/{id}/export/json`
Download a full child profile backup as `.json`.

#### `GET /api/children/{id}/export/csv`
Download a `.zip` archive containing one `.csv` file per data type (feedings, sleep, growth, medical, moments).

#### `POST /api/import/csv`
Import data from a `.zip` archive (mirror of the CSV export). Field name: `file`.

Runs in a single transaction — all or nothing.

---

### RSS Feed

#### `GET /api/rss/{child_id}`
RSS 2.0 feed of shared moments (`is_shared = true`) for a child.

Returns `Content-Type: application/rss+xml`.

---

### Admin *(super-admin only)*

#### `GET /api/admin/stats`
```json
{
  "users": { "total": 42, "banned": 2, "admins": 1 },
  "families": 15,
  "children": 20,
  "moments": 340,
  "media": 180,
  "comments": 95
}
```

#### `GET /api/admin/users`
List all users with ban status.

#### `POST /api/admin/users/{id}/ban`
**Body:** `{ "reason": "Violation of terms" }`

#### `POST /api/admin/users/{id}/unban`

#### `GET /api/admin/storage`
Disk usage breakdown by media type, plus detection of orphan files.

---

---

## REST API v1 — Bearer Token Authentication

All endpoints above also exist under `/api/v1/` and accept **Bearer token** authentication instead of session cookies. This is the standard integration path for external clients, mobile apps, or scripts.

### How it works

1. **Get a token** — exchange credentials for a Bearer token:

```http
POST /api/v1/auth/token
Content-Type: application/json

{
  "email": "ana@example.com",
  "password": "secret123",
  "name": "My script",
  "expires_days": 30
}
```

Response `201`:
```json
{
  "token": "a3f2c8...64hexchars",
  "token_type": "Bearer",
  "expires_in": 2592000,
  "user_id": 1,
  "name": "My script"
}
```

2. **Use the token** — include it in every subsequent request:

```http
GET /api/v1/children
Authorization: Bearer a3f2c8...64hexchars
```

3. **Revoke** — invalidate all tokens for the current user:

```http
DELETE /api/v1/auth/token
Authorization: Bearer a3f2c8...64hexchars
```

### Token security

- The raw token is returned only once at creation; only its `SHA-256` hash is stored in the database.
- Tokens have a configurable expiry (`expires_days`; `0` = no expiry).
- Banned accounts cannot use their tokens even if unexpired.

### Available API v1 endpoints

All endpoints from the session-based `/api/` exist at `/api/v1/` with identical request/response formats.

| Prefix | Auth method |
|--------|-------------|
| `/api/` | Session cookie (`credentials: 'include'` in fetch) |
| `/api/v1/` | `Authorization: Bearer <token>` header |

Quick reference for v1:

```
POST   /api/v1/auth/token                        — issue token
DELETE /api/v1/auth/token                        — revoke all tokens
GET    /api/v1/auth/tokens                       — list active tokens

GET    /api/v1/children                          — list children
POST   /api/v1/children                          — add child
GET    /api/v1/children/{id}                     — get child
PUT    /api/v1/children/{id}                     — update child
DELETE /api/v1/children/{id}                     — delete child

GET    /api/v1/children/{id}/feedings            — feeding log
POST   /api/v1/children/{id}/feedings
GET    /api/v1/children/{id}/sleep               — sleep log
POST   /api/v1/children/{id}/sleep
GET    /api/v1/children/{id}/growth              — growth measurements
POST   /api/v1/children/{id}/growth
GET    /api/v1/children/{id}/medical             — medical records
POST   /api/v1/children/{id}/medical

GET    /api/v1/children/{id}/timeline            — chronological feed
POST   /api/v1/children/{id}/moments             — add moment
DELETE /api/v1/moments/{id}
GET    /api/v1/moments/{id}/comments
POST   /api/v1/moments/{id}/comments
POST   /api/v1/moments/{id}/reactions
DELETE /api/v1/moments/{id}/reactions

GET    /api/v1/children/{id}/relationships
POST   /api/v1/children/{id}/relationships
PUT    /api/v1/relationships/{id}
DELETE /api/v1/relationships/{id}
GET    /api/v1/children/{id}/interactions
POST   /api/v1/relationships/{id}/interactions

GET    /api/v1/children/{id}/export/json
GET    /api/v1/children/{id}/export/csv
POST   /api/v1/import/json
POST   /api/v1/import/csv

GET    /api/v1/admin/stats                       — super-admin only
GET    /api/v1/admin/users
POST   /api/v1/admin/users/{id}/ban
POST   /api/v1/admin/users/{id}/unban
```

---

