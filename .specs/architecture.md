# Laravel Headless CMS (Strapi-Inspired Architecture)

## Role
Senior Laravel Architect & Engineer

## Goal
Build a Strapi-like dynamic content engine using native Laravel, Inertia SPA, and physical SQL tables (no EAV).

## Stack - application-info
- Laravel 11
- Vue 3
- Inertia.js
- Tailwind CSS
- Sanctum
- SQLite (dev), MySQL/Postgres (prod)

---

## Architectural Principles
- No EAV — all content uses real SQL tables.
- Schema is code-driven via services.
- Meta schema defines structure.
- Physical tables store data.
- Runtime binding enables flexibility.
- Append-only schema evolution for safety.

---

## Core Architecture

### Meta-Data Layer
System tables defining content structure:
- `content_types`
- `content_fields`

### Physical Content Layer
- One SQL table per content type.
- Tables created and modified only by `SchemaManager`.

### Dynamic Runtime Layer
- `DynamicEntity` binds to tables at runtime.
- Relationships and media resolved dynamically.

---

## Phase 0: Environment & Strictness
- `DB_CONNECTION=sqlite`
- `database/database.sqlite` must exist.
- No controllers or UI may execute raw schema changes directly.

---

## Phase 0.5: SPA Foundation (Manual Setup)

### Backend
- Install Inertia: `composer require inertiajs/inertia-laravel`.
- Generate `HandleInertiaRequests` middleware.
- **Shared Data:** Update middleware to share `auth.user` and `flash` messages globally.
- Register middleware in `bootstrap/app.php`.
- Root view: `resources/views/app.blade.php` using `@inertia`.

### Frontend
- Install Vue, Inertia, Vite Vue plugin.
- Install Tailwind CSS and PostCSS.
- Configure Vite for Vue.
- Entry file: `resources/js/app.js`.

### API
- Install Sanctum using `php artisan install:api`.

---

## Phase 1: Meta Schema

### content_types
- `id`
- `name`
- `slug` (unique)
- `description`
- `is_public` (bool)
- `has_ownership` (bool)
- `timestamps`

### content_fields
- `id`
- `content_type_id`
- `name`
- `type`
- `settings` (json)
- `timestamps`

---

## Phase 1.5: Admin Panel & Authentication

### Admin Access
- Add `is_admin` boolean to `users` table.
- Admin routes live in `routes/admin.php`.

### Admin Auth
- Custom `Admin\Auth\LoginController`.
- Inertia-based login page (`resources/js/Pages/Admin/Auth/Login.vue`).
- Unauthenticated users accessing `/admin/*` redirect to `/admin/login`.

---

## Phase 2: Schema Manager Service

### Service
`App\Services\SchemaManager`

### Responsibilities
- Validate schema input.
- Create and evolve physical tables.
- Enforce schema safety rules.

### Rules
- Always add `id`, `timestamps`, `softDeletes`.
- Add `user_id` if ownership is enabled.
- Never drop columns automatically.
- Field renames are additive with deprecation.

---

## Phase 3: Dynamic Model

### Model
`App\Models\DynamicEntity`

### Features
- `bind(string $slug)`: Sets table name dynamically.
- **Dynamic Casting:** Automatically sets `$this->casts` based on field definitions (e.g., boolean, integer, json).
- Magic `__call` resolves `belongsTo` and `media` relations.

---

## Phase 4: Catch-All Content API

### Controller
`App\Http\Controllers\Api\ContentController`

### Route
`ANY /api/content/{slug}/{id?}`

### Critical Constraint
- This route **MUST** be defined at the very end of `routes/api.php` to avoid intercepting specific routes (like `/auth`, `/media`).

### Behavior
- Auto-bind content type.
- Apply dynamic validation.
- Enforce ownership and visibility rules.

---

## Phase 5: Draft & Publish System
- Add `published_at` column to all content tables.
- Public API returns only published content.
- Admin API can view drafts.

---

## Phase 6: Relationships (BelongsTo)
- Stored in `content_fields.settings.related_content_type_id`.
- Column name format: `{relation}_id`.
- Admin UI renders dropdown selector.
- `DynamicEntity` resolves `belongsTo` relation.

---

## Phase 7: Media System

### media_files table
- `id`
- `path`
- `disk`
- `mime`
- `timestamps`

### Features
- Media field creates foreign key column.
- Upload endpoint for media (`POST /api/media/upload`).
- Preview support in Admin UI.

---

## Phase 8: API Security & Tokens
- Sanctum long-lived tokens.
- Admin UI for token management.
- Public content bypasses auth.
- Private content requires `auth:sanctum`.

---

## Phase 9: Rich Text Editor
- Tiptap with Vue 3.
- Tailwind Typography (`prose`).
- Stored as sanitized HTML.

---

## Phase 10: End-User Auth & Ownership
- Public auth endpoints: `register` and `login`.
- Auto-assign `user_id` on create if ownership enabled.
- Policies enforce update and delete permissions.

---


## Phase 10.5: Profile & Password Management

**1. Controllers:**
   - **Create `Admin\ProfileController`:**
     - `update(Request $request)`:
       - Validate `name` (string), `email` (email, unique users, ignore current ID).
       - Update `auth()->user()`.
       - Return Back with flash success.
   - **Create `Admin\PasswordController`:**
     - `update(Request $request)`:
       - Validate `current_password` (current_password rule), `password` (confirmed, min:8).
       - Update `auth()->user()` with hashed password.
       - Return Back with flash success.

**2. Routes (`routes/admin.php`):**
   - `PUT /settings/profile` -> `ProfileController@update`.
   - `PUT /settings/password` -> `PasswordController@update`.

**3. Frontend (`Settings/Index.vue`):**
   - **Refactor:** Convert the existing layout into a **Tabbed Interface**.
     - **Tabs:** "API Tokens", "Profile", "Security".
   - **Tab 1 (API Tokens):** Keep existing token management here.
   - **Tab 2 (Profile):**
     - Form: Name, Email.
     - Button: "Save Profile".
     - Use `useForm` pointed to `PUT /admin/settings/profile`.
   - **Tab 3 (Security):**
     - Form: Current Password, New Password, Confirm Password.
     - Button: "Update Password".
     - Use `useForm` pointed to `PUT /admin/settings/password`.

**4. Testing (`tests/Feature/Admin/ProfileTest.php`):**
   - **Test 1:** `admin can update profile`.
     - Action: Login. PUT new name/email.
     - Assert: DB updated.
   - **Test 2:** `admin can update password`.
     - Action: Login. PUT current + new password.
     - Assert: Login with old password fails. Login with new password succeeds.
   - **Test 3:** `password update fails with wrong current password`.
     - Action: PUT wrong current password.
     - Assert: Session errors 'current_password'.


## Phase 11: Dynamic Validation Engine

### Service
`ContentValidator`

### Features
- Builds Laravel validation rules dynamically.
- Supports `required`, `unique`, `min`, `max`, and type rules.

---

## Phase 12: Filtering, Sorting & Includes
- Use `spatie/laravel-query-builder`.
- Supports filtering, sorting, and relationship includes via URL parameters.

---

## Phase 12.5: Public Auth & Content Ownership
**1. Public Auth API (`Api\AuthController`):**
   - **Controller:** Create `App\Http\Controllers\Api\AuthController`.
   - **Method `register(Request $request)`:**
     - Validate: `name`, `email` (unique), `password` (confirmed).
     - Create User (`is_admin` = false).
     - Return: `{ token: $user->createToken('default')->plainTextToken, user: ... }`.
   - **Method `login(Request $request)`:**
     - Validate: `email`, `password`.
     - Check Auth.
     - Return: `{ token: ... }`.
   - **Routes (`routes/api.php`):**
     - `POST /auth/register` -> `AuthController@register`
     - `POST /auth/login` -> `AuthController@login`

**2. Schema Ownership Logic:**
   - **Database:** Ensure `content_types` table has `has_ownership` (boolean) column (done in Phase 1).
   - **Schema Manager Update (`Services\SchemaManager`):**
     - Update `createType` and `updateType`.
     - **Logic:** If `has_ownership` is true (passed in settings):
       - Create column: `$table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');`
       - Add index.

**3. Frontend Update (`Schema/Create.vue`):**
   - Add a Checkbox: "User Owned Content" (bind to `form.has_ownership`).
   - Description: "If checked, records will be linked to the creator, and only they can edit/delete them."

**4. Controller Enforcement (`Api\ContentController`):**
   - Update `store`, `update`, `destroy`.
   - **Store:**
     - If `$type->has_ownership`:
       - Check `auth()->check()`.
       - Force `user_id` to `auth()->id()`.
   - **Update/Destroy:**
     - If `$type->has_ownership`:
       - Check `if ($item->user_id !== auth()->id() && !auth()->user()->is_admin)`.
       - If mismatch -> Abort 403 Forbidden.

**5. Testing (`tests/Feature/UserContentTest.php`):**
   - **Test 1:** `public user can register and login`.
   - **Test 2:** `user can create owned content`.
     - Setup: 'Diary' type (`has_ownership`=true).
     - Action: Login as User A. POST /api/content/diaries.
     - Assert: DB record has User A's ID.
   - **Test 3:** `user cannot edit others content`.
     - Action: User A tries to UPDATE User B's diary.
     - Assert: 403 Forbidden.


## Phase 13: Production Deployment

### CI/CD (GitHub Actions)
- **Job 1 (Test):** Run Pest.
- **Job 2 (Build):** `npm run build` (compile Vue/Tailwind).
- **Job 3 (Deploy):** Rsync build artifacts to server.

### Server Hook (`deploy.sh`)
- `php artisan down`
- `php artisan migrate --force`
- `php artisan optimize`
- `php artisan up`

---

## Non-Goals (v1)
- GraphQL
- Localization (i18n)
- Polymorphic relations
- Nested or repeatable components
