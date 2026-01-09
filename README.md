# Laravel Headless CMS

A powerful, dynamic, and lightweight Headless CMS built with **Laravel 12**, **Inertia.js v2**, and **Vue 3**.

This project provides a robust backend for managing dynamic content schemas and a modern, reactive admin panel for content creation. It exposes a JSON API for consuming content in your frontend applications.

## 🚀 Features

-   **Dynamic Schema Management**: Create and manage custom content types (schemas) directly from the admin panel.
-   **Content Management**: Full CRUD capabilities for any defined schema.
-   **Headless API**: flexible REST API for fetching and managing content from external applications.
-   **Modern Admin Panel**: Built with **Inertia.js 2**, **Vue 3**, and **Tailwind CSS 4** for a smooth, SPA-like experience.
-   **Rich Text Editor**: Integrated **Tiptap** editor for rich content creation.
-   **Authentication**: Secure authentication using **Laravel Sanctum**.
-   **Type Safety**: TypeScript support for frontend components.

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
    git clone https://github.com/yourusername/laravel-headless-cms.git
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

6.  **Run Development Server**
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

### Admin Panel
Access the admin panel at `http://localhost:8000/login`.
-   **Dashboard**: Overview of system stats and content.
-   **Schemas**: Define new content types (e.g., "Articles", "Products").
-   **Content**: Create entries for your defined schemas.
-   **Settings**: Manage API tokens and user profile.

### API Endpoints

The CMS exposes the following API endpoints (prefixed with `/api`):

#### Public
-   `POST /api/auth/login`: Login to get an API token.
-   `POST /api/auth/register`: Register a new user.
-   `GET /api/content/{slug}`: List content entries for a schema type.
-   `GET /api/content/{slug}/{id}`: Get a specific content entry.

#### Protected (Requires Bearer Token)
-   `POST /api/content/{slug}`: Create new content.
-   `PUT /api/content/{slug}/{id}`: Update content.
-   `DELETE /api/content/{slug}/{id}`: Delete content.

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
