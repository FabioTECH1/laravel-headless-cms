# Headless CMS API Documentation

This API allows you to manage content, authentication, and retrieve dynamic content types from your headless CMS.

## Base URL

All API requests should be prefixed with `/api`.
Example: `https://your-cms-url.com/api`

## Authentication

The API uses **Laravel Sanctum** for authentication. You obtain a token by logging in and must provide it in the Authorization header for protected endpoints.

**Header Format:**
```
Authorization: Bearer <your-token>
```

### 1. Register
Create a new user account. Warning: Registration is disabled if no admin user exists.

- **Endpoint:** `POST /auth/register`
- **Body:**
  ```json
  {
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password",
    "password_confirmation": "password"
  }
  ```
- **Response (201 Created):**
  ```json
  {
    "token": "1|laravel_sanctum_token...",
    "user": { "id": 1, "name": "John Doe", "email": "john@example.com" }
  }
  ```

### 2. Login
Authenticate and receive an access token.

- **Endpoint:** `POST /auth/login`
- **Body:**
  ```json
  {
    "email": "john@example.com",
    "password": "password"
  }
  ```
- **Response (200 OK):**
  ```json
  {
    "token": "2|laravel_sanctum_token...",
    "user": { ... }
  }
  ```

### 3. Logout
Invalidate the current token.

- **Endpoint:** `POST /auth/logout`
- **Headers:** `Authorization: Bearer <token>`
- **Response (200 OK):** `{"message": "Logged out successfully."}`

---

## Content API

The content API is dynamic. You interact with content types by their **slug** (e.g., `blog-posts`, `products`, `categories`).

### 1. List Content
Retrieve a paginated list of content items.

- **Endpoint:** `GET /content/{slug}`
- **Parameters:**
  - `page` (optional): Page number for pagination.
  - `per_page` (optional): Items per page (default: 10, max: 100).
  - `status` (optional): Set to `draft` to view drafts (requires authentication + permissions). Public endpoint defaults to published only.
- **Response (200 OK):**
  ```json
  {
    "data": [
      {
        "id": "01kem...",
        "title": "My Blog Post",
        "status": "published",
        "published_at": "2026-01-10T...",
        ... // dynamic fields
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 5,
      "total_items": 50,
      "per_page": 10
    }
  }
  ```


### 1.1 Advanced Querying

The API supports powerful querying capabilities compatible with Strapi syntax.

#### Filtering
Filter by fields using operators: `$eq`, `$ne`, `$lt`, `$lte`, `$gt`, `$gte`, `$contains`, `$notContains`, `$in`, `$notIn`, `$null`, `$notNull`.

- Syntax: `filters[field][operator]=value`
- Example: `GET /api/content/posts?filters[category][$eq]=tech`
- Example (Complex): `GET /api/content/posts?filters[views][$gt]=100&filters[title][$contains]=Vue`

#### Sorting
Sort results by one or multiple fields.

- Syntax: `sort=field:direction` or `sort[0]=field:desc`
- Example: `GET /api/content/posts?sort=published_at:desc`
- Example (Multiple): `GET /api/content/posts?sort[0]=views:desc&sort[1]=title:asc`

#### Field Selection (Sparse Fieldsets)
Retrieve only specific fields to optimize payload size.

- Syntax: `fields[0]=field_name`
- Example: `GET /api/content/posts?fields[0]=title&fields[1]=slug`

#### Population
Eager load relationships.

- Syntax: `populate[0]=relation_name`
- Example: `GET /api/content/posts?populate[0]=author`


### 2. Get Single Content
Retrieve a specific content item by its ID.

- **Endpoint:** `GET /content/{slug}/{id}`
- **Response (200 OK):**
  ```json
  {
    "data": {
      "id": "01kem...",
      "title": "My Blog Post",
      ...
    }
  }
  ```

### 3. Create Content
Create a new content item. Requires authentication.

- **Endpoint:** `POST /content/{slug}`
- **Headers:** `Authorization: Bearer <token>`
- **Body:**
  - Dynamic fields defined in your Content Type schema.
  - `status` (optional): `"published"` to publish immediately, or `"draft"` (default) to save as draft.
  - `published_at` (optional): Manually set a publish date.
- **Example Body:**
  ```json
  {
    "title": "Start of a Journey",
    "excerpt": "A short summary.",
    "content": "<p>HTML content...</p>",
    "category_id": "01kem...",
    "status": "published"
  }
  ```
- **Response (201 Created):** Returns the created item.

### 4. Update Content
Update an existing content item. Requires authentication. Ownership or Admin policies may apply.

- **Endpoint:** `PUT /content/{slug}/{id}`
- **Headers:** `Authorization: Bearer <token>`
- **Body:** Fields to update.
- **Response (200 OK):** Returns the updated item.

### 5. Delete Content
Delete a content item. Requires authentication.

- **Endpoint:** `DELETE /content/{slug}/{id}`
- **Headers:** `Authorization: Bearer <token>`
- **Response (204 No Content):** Empty body.

---

## Dynamic Schema

When interacting with endpoints, the `{slug}` corresponds to the `slug` of your **Content Type**.
Example:
- Content Type: "Blog Post" -> Slug: `blog-post` (or plural `blog-posts` depending on your naming convention, usually singular slug in CMS definition, plural used optionally or singular). *Note: System usually expects the slug defined in ContentType.*

Common Fields:
- `id`: ULID (Unique Identifier)
- `created_at`: Date
- `updated_at`: Date
- `published_at`: Date (Nullable)
- `user_id`: ULID (Owner, if ownership enabled)
