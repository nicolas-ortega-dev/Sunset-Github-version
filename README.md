# Sunset — Social Book & Reading Platform

Full-stack social networking web application for readers, built from scratch using a custom **PHP MVC architecture**, **MySQL**, and the **Open Library API**.

### Overview

Sunset combines a relational social network with a book discovery catalog. Users can create profiles, follow other readers, publish posts and book reviews, participate in real-time discussion forums, and manage personal reading lists.

### Key Features

- **Custom PHP MVC Architecture:** Modular codebase separated into `Controllers`, `Models`, `Services`, `Core`, `Helpers`, and `Views`.
- **Security & Validation:** Session-based authentication, hashed passwords, CSRF token protection (`Helpers/Csrf.php`), strict server-side validation (`Helpers/Validator.php`), and `.htaccess` rules protecting internal directories.
- **Social & Community:** User profiles, follow/unfollow system, activity feed, image uploads (`Services/ImageUploader.php`), comments, likes, notifications, and live-chat forums.
- **Open Library Integration & Caching:** Real-time book search and metadata fetching (`Services/OpenLibraryService.php`) with local database caching (`Models/SearchCache.php`) and external store search links.

### Tech Stack

- **Backend:** PHP 8+ (Custom MVC, PDO)
- **Database:** MySQL (Relational schema + SQL migrations)
- **Frontend:** HTML5, Custom CSS, Vanilla JavaScript
- **External API:** Open Library REST API

### Project Structure

```text
sunset/
├── config/          # Application and database configuration
├── database/        # Relational schema and SQL migrations
├── public/          # Public entry point (index.php), CSS, JS, and uploads
├── scripts/         # CLI seeders for catalog and demo data
├── src/
│   ├── Controllers/ # Request handling and routing logic
│   ├── Core/        # Database connection, Config, and View renderer
│   ├── Helpers/     # Auth, CSRF, Flash messages, and Validators
│   ├── Models/      # Database queries and domain entities
│   └── Services/    # Open Library API, image uploads, and catalog logic
└── views/           # Server-rendered templates and UI partials
```

### Local Setup

1. Clone the repository into your local web server root (e.g., `htdocs` or `www`):
   ```bash
   git clone https://github.com
   ```
2. Create a MySQL database named `sunset` and import `database/schema.sql` followed by `database/migration_001_catalogo.sql`.
3. Open `config/config.php` and verify your local MySQL credentials (`localhost`, `root`, empty password by default for XAMPP).
4. _(Optional)_ Populate the catalog and demo users from your terminal:
   ```bash
   php scripts/seed_catalog.php
   php scripts/seed_demo.php
   ```
5. Point your web server or virtual host to the `public/` directory and open `http://localhost/sunset/public` in your browser.
