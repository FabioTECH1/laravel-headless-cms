# Laravel Headless CMS

A powerful, dynamic, and lightweight Headless CMS built with **Laravel 12**, **Inertia.js v2**, and **Vue 3**.

This project provides a robust backend for managing dynamic content schemas and a modern, reactive admin panel for content creation. It exposes a JSON API for consuming content in your frontend applications.

## 🚀 Features

-   **Dynamic Content Modeling:** Create Custom Types and reusable **Components** (Dynamic Zones).
-   **Content Management:** Full CRUD capabilities with flexible filtering, sorting, and pagination.
-   **Headless API:** Robust REST API for consuming content from any frontend.
-   **Media Library:** integrated asset management with support for drag-and-drop uploads.
-   **Webhooks System:** Event-driven architecture with retries, logging, and HMAC security.
-   **Role-Based Access Control (RBAC):** Granular permissions for Admins, Editors, and API Users.
-   **Modern Admin Panel:** Built with **Inertia.js 2**, **Vue 3**, and **Tailwind CSS 4** (Dark Mode support included).
-   **Rich Text Editor:** Integrated **Tiptap** editor for rich storytelling.
-   **Authentication:** Secure authentication using **Laravel Sanctum**.

## 🛠 Tech Stack

### Backend
-   **Framework**: [Laravel 12](https://laravel.com)
-   **Database**: SQLite (default), extensible to MySQL/PostgreSQL
-   **Authentication**: Laravel Sanctum
-   **Testing**: Pest v4

### Frontend
-   **Framework**: [Vue 3](https://vuejs.org/)
-   **Router/Glue**: [Inertia.js v2](https://inertiajs.com)
-   **Styling**: [Tailwind CSS v4](https://tailwindcss.com)
-   **Bundler**: Vite
-   **Editor**: Tiptap

## 🏁 Getting Started

### Prerequisites
-   PHP 8.2+
-   Node.js & NPM
-   Composer

### Installation

1.  **Clone the repository**
    ```bash
    git clone https://github.com/fabiotech1/laravel-headless-cms.git
    cd laravel-headless-cms
    ```

2.  **Install PHP dependencies**
    ```bash
    composer install
    ```

3.  **Install Node.js dependencies**
    ```bash
    npm install
    ```

4.  **Environment Setup**
    Copy the example environment file and configure it:
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

5.  **Database Setup**
    Running migrations will set up the core tables (users, personal_access_tokens, etc.) and any existing core schemas.
    ```bash
    touch database/database.sqlite
    php artisan migrate
    ```

6.  **Create Admin Account**
    Visit `http://localhost:8000/admin/register` to create your first admin account.
    *Note: This route is only available if no users exist in the system.*

7.  **Seed Database (Essential)**
    Run the essential seeders (Roles & Permissions only) to get the system ready:
    ```bash
    php artisan db:seed
    ```

8.  **Seed Demo Content (Optional)**
    Populate the database with a full demo structure (Categories, Authors, Blog Posts, Media) to verify functionality:
    ```bash
    php artisan db:seed --class=DemoSeeder
    ```

9.  **Run Development Server**
    Start the Laravel development server and Vite build process:
    ```bash
    composer run dev
    ```
    Or manually:
    ```bash
    php artisan serve
    npm run dev
    ```

## 📖 Usage

## Admin Panel

Access the admin panel at: `http://localhost:8000/admin/login`

**Initial Setup:**
On your first visit, go to `/admin/register` to create your administrator account. Subsequent registrations are disabled.

### Features:

- **Dashboard**: Overview of system statistics and recent content.
- **Schema Builder**: Define Content Types (e.g., "Articles") and reusable **Components** (e.g., "SEO Metadata", "CTA Block").
- **Content Manager**: Manage entries with support for Dynamic Zones (flexible page building).
- **Media Library**: Manage images and files.
- **Webhooks**: Configure event triggers (e.g., `content.created`) to notify external services.
- **Roles & Permissions**: Manage user access and security.
- **Settings**: System configuration.

### API Endpoints

The CMS exposes the following API endpoints (prefixed with `/api`):

#### Public
-   `POST /api/auth/login`: Login to get an API token.
-   `GET /api/content/{slug}`: List content entries.
-   `GET /api/content/{slug}/{id}`: Get specific content entry.

#### Protected (Requires Bearer Token)
-   `POST /api/content/{slug}`: Create content.
-   `PUT /api/content/{slug}/{id}`: Update content.
-   `DELETE /api/content/{slug}/{id}`: Delete content.
-   `POST /api/media/upload`: Upload files.
-   `GET /api/webhooks`: Manage webhooks.

## 🧪 Testing

Run the test suite using **Pest**:

```bash
php artisan test
```

## 🎨 Code Style

This project uses **Pint** for PHP formatting and **Prettier** for frontend code.

```bash
# Fix PHP code style
./vendor/bin/pint

# Fix Frontend code style
npm run format
```

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
