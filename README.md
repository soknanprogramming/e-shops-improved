# Khmer24 PHP — C2C E-Commerce Marketplace

> A modern Consumer-to-Consumer e-commerce platform inspired by Khmer24, Cambodia's popular online marketplace.

**Live Demo:** [https://soknan-shop.free.nf/](https://soknan-shop.free.nf/)

![Platform Preview](markdown/image1.png)

## 🌟 Features

### For Users
- **Browse Products** — View listings with category filtering, search, and pagination
- **Post Products** — Upload products with multiple images (up to 6 per product)
- **Social Interactions** — Like products and leave comments
- **User Profiles** — Manage personal information and contact details
- **Authentication** — Secure login and registration system

### For Admins
- **Product Management** — Approve, edit, or hide product listings
- **Category Management** — Full CRUD operations for product categories
- **User Management** — Approve posting permissions and manage user accounts
- **Dashboard** — Centralized admin panel for platform oversight

## 🛠️ Tech Stack

| Layer       | Technology                          |
|-------------|-------------------------------------|
| Backend     | PHP 8.2 (Apache)                    |
| Database    | MySQL 8.0                           |
| Frontend    | Vanilla HTML/CSS/JS                 |
| Styling     | CSS Variables, Design System        |
| Container   | Docker & Docker Compose             |
| DB Admin    | phpMyAdmin                          |

## 📦 Installation & Setup

### Prerequisites

- [Docker](https://www.docker.com/products/docker-desktop/) installed
- [Docker Compose](https://docs.docker.com/compose/install/) installed

### Quick Start

```bash
# Clone the repository
git clone <repository-url>
cd code

# Start all services
docker-compose up -d

# View logs (optional)
docker-compose logs -f
```

### Access Points

| Service       | URL                           | Credentials         |
|---------------|-------------------------------|---------------------|
| Live Demo     | https://soknan-shop.free.nf/  | —                   |
| Web App       | http://localhost              | —                   |
| phpMyAdmin    | http://localhost:8080         | root / root         |

### Database Configuration

| Parameter    | Value        |
|--------------|--------------|
| Host         | `mysql`      |
| Database     | `khmer245_db` |
| User         | `app_user`   |
| Password     | `secret`     |
| Port (host)  | `3307`       |

## 🏗️ Project Architecture

```
khmer24-php/
├── index.php                 # Entry point (redirects to views)
├── docker-compose.yml        # Service orchestration
├── Dockerfile                # PHP/Apache configuration
│
├── configs/                  # Database connection (PDO)
├── controllers/              # Request handlers & business logic
├── repos/                    # Data access layer (Repository pattern)
├── views/                    # Frontend pages & UI components
│
├── uploads/                  # User-uploaded media
│   ├── products/             # Product images
│   ├── categories/           # Category images
│   └── profiles/             # Profile pictures
│
├── sql/                      # SQL schemas & migrations
├── prompts/                  # Design system documentation
└── markdown/                 # Documentation assets
```

## 📖 Development Guide

### Repository Pattern

All database operations use repository classes for clean separation of concerns:

```php
require_once '../repos/ProductRepository.php';
$productRepo = new ProductRepository($conn);
$products = $productRepo->getAll();
```

### Available Repositories

| Repository             | Key Methods                                      |
|------------------------|--------------------------------------------------|
| `ProductRepository`    | `getAll`, `search`, `getById`, `create`, `update`, `delete`, `toggleVisibility` |
| `CategoryRepository`   | `getAll`, `getById`, `create`, `update`, `delete` |
| `UserRepository`       | `getById`, `getByEmail`, `create`, `update`, `authenticate` |
| `ProfileRepository`    | `getByUserId`, `update`, `create`                |
| `CommentRepository`    | `getByProductId`, `create`, `delete`             |
| `LikeRepository`       | `countByProductId`, `toggleLike`, `isLikedByUser` |

### Controller Structure

Controllers handle POST requests, file uploads, and session validation:

```php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
```

### Available Controllers

| Controller    | Responsibility                              |
|---------------|---------------------------------------------|
| `auth.php`    | Login, register, session management         |
| `product.php` | Create, update, delete, toggle visibility   |
| `category.php`| Admin CRUD for categories                   |
| `profile.php` | User profile management                     |
| `comment.php` | Add/delete product comments                 |
| `like.php`    | Toggle product likes                        |
| `user.php`    | Admin user management (approve posting)     |

### View Pages

| View                    | Purpose                                      |
|-------------------------|----------------------------------------------|
| `home.php`              | Main product listing with filters & pagination |
| `product_detail.php`    | Single product view with comments            |
| `product_create.php`    | Form to post a new product                   |
| `product_edit.php`      | Form to edit an existing product             |
| `login.php` / `register.php` | Authentication pages                  |
| `user_dashboard.php`    | User's own products & profile link           |
| `user_profile.php`      | User profile editing                         |
| `admin.php`             | Admin dashboard                              |
| `admin_product.php`     | Admin product management                     |
| `admin_category.php`    | Admin category management                    |
| `admin_user.php`        | Admin user management                        |

## 🎨 Design System

The platform follows **"The Scholarly Forest Editorial"** theme (see `prompts/DESIGN.md`).

### Color Palette

| Token       | Color              | Usage                          |
|-------------|--------------------|--------------------------------|
| Primary     | `#1a3325` 🟢       | Navigation, CTAs              |
| Secondary   | `#9d7c39` 🟡       | Highlights, micro-moments     |
| Tertiary    | `#7e000a` 🔴       | Alerts, sale highlights       |
| Background  | `#fff9ee` 🟠       | Global canvas                 |
| Text        | `#201b09` ⚫       | Body text (never pure black)  |

### Typography

- **Headlines:** [Manrope](https://fonts.google.com/specimen/Manrope) (Google Fonts)
- **Body Text:** [Public Sans](https://fonts.google.com/specimen/Public+Sans) (Google Fonts)

### Design Rules

✅ Use background color shifts for boundaries (no 1px borders)  
✅ Use `rounded-full` or `border-radius` (no sharp corners)  
✅ Use `#201b09` for text (never pure black `#000000`)  
❌ No `<hr>` divider lines  
❌ No 1px solid black borders  

## 🗄️ Database Schema

### Core Tables

| Table              | Description                              |
|--------------------|------------------------------------------|
| `User`             | User accounts with `can_post` flag       |
| `Product`          | Product listings with `showed` visibility|
| `category`         | Product categories                       |
| `product_image`    | Image references (main + 5 additional)   |
| `user_profile`     | User profiles with phone numbers         |
| `product_likes`    | Product likes tracking                   |
| `comment`          | Product comments                         |

### Schema Management

- Base schema: `backups/table/default-table.sql`
- Migrations: `backups/sql_update.sql`

**Adding schema changes:**
```sql
-- Add new column to Product table (2024-01-01)
ALTER TABLE Product ADD COLUMN price DECIMAL(10,2) DEFAULT 0;
```

## 🐳 Docker Commands

```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# Rebuild containers (after code changes)
docker-compose up -d --build

# View logs
docker-compose logs -f

# View specific service logs
docker-compose logs -f php

# Execute commands inside container
docker exec -it php_app bash

# Database backup
docker exec mysql_db mysqldump -u app_user -psecret app_db > backup.sql

# Database restore
docker exec -i mysql_db mysql -u app_user -psecret app_db < backup.sql
```

## 🔧 Common Tasks

### Adding a New Feature

1. Create controller in `controllers/`
2. Create repository in `repos/` (if database operations needed)
3. Create view in `views/`
4. Update navigation in shared components
5. Test with `docker-compose up -d --build`

### Database Migration

1. Add ALTER statements to `backups/sql_update.sql`
2. Run migration via phpMyAdmin or MySQL CLI
3. Update repository methods if needed

### File Upload Paths

| Type         | Path                        |
|--------------|-----------------------------|
| Products     | `/uploads/products/`        |
| Categories   | `/uploads/categories/`      |
| Profiles     | `/uploads/profiles/`        |

## 📝 Notes

- `index.php` redirects to `/views/index.php`
- Docker volumes mount the entire project for live development
- The `uploads/` directory is git-ignored (see `.dockerignore`)
- Session management uses PHP `$_SESSION` for authentication
- Admin users have `$_SESSION['is_admin'] == 1`

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is open-source and available under the MIT License.

## 📞 Support

For questions or issues, please open an issue in the repository or contact the development team.

---

Built with ❤️ for the Cambodian marketplace community
