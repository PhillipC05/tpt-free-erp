# TPT Free ERP — Local Deployment Guide

**Version:** 2.0  
**Updated:** 2026-05-31  
**Stack:** Laravel 13.8 · PHP 8.3+ · SQLite/MySQL/PostgreSQL · Vue 3 · Vite

---

## Quick Start (any platform)

```bash
git clone https://github.com/PhillipC05/tpt-free-erp.git
cd tpt-free-erp
composer run setup    # installs everything and migrates
composer run dev      # starts the dev server
```

Open **http://localhost:8000** — API docs at **http://localhost:8000/api/documentation**.

---

## Prerequisites

| Tool | Min version | Notes |
|------|-------------|-------|
| PHP | 8.3 | Needs `pdo_sqlite`, `mbstring`, `openssl`, `tokenizer` |
| Composer | 2.x | [getcomposer.org](https://getcomposer.org/) |
| Node.js | 18 | [nodejs.org](https://nodejs.org/) |
| Git | any | |

---

## Windows

### Option A — Laravel Herd (recommended, one-click)

[Laravel Herd](https://herd.laravel.com/) is the official tool from the Laravel team. The free tier gives you PHP 8.3, a zero-config PHP server, and automatic virtual hosts.

1. Download and install **Laravel Herd** from [herd.laravel.com](https://herd.laravel.com/)
2. Install **Node.js** from [nodejs.org](https://nodejs.org/) (LTS)
3. Open PowerShell and run:

```powershell
git clone https://github.com/PhillipC05/tpt-free-erp.git
cd tpt-free-erp
composer run setup
composer run dev
```

### Option B — PowerShell install script

The repo includes an interactive installer that checks prerequisites for you:

```powershell
git clone https://github.com/PhillipC05/tpt-free-erp.git
cd tpt-free-erp
.\install.ps1
```

The script verifies PHP 8.3+, Composer, and Node.js 18+, then runs the full setup and prints the URL on completion.

### Option C — Manual (WAMP / XAMPP)

If you already have WAMP or XAMPP running PHP 8.3+:

```powershell
# From your htdocs (e.g. C:\wamp64\www\)
git clone https://github.com/PhillipC05/tpt-free-erp.git tpt-erp
cd tpt-erp
copy .env.example .env
# Create SQLite file (or configure MySQL — see below)
New-Item -ItemType File database\database.sqlite
composer install
php artisan key:generate
php artisan migrate
npm install && npm run build
php artisan serve   # or use the WAMP virtual host
```

---

## macOS / Linux

### macOS — Laravel Herd or Valet

```bash
# Laravel Herd (GUI app, easiest)
# Download from https://herd.laravel.com/

# Or Valet (CLI)
brew install php@8.3 composer
composer global require laravel/valet
valet install

git clone https://github.com/PhillipC05/tpt-free-erp.git
cd tpt-free-erp
composer run setup
```

### Ubuntu / Debian

```bash
sudo apt update
sudo apt install php8.3 php8.3-cli php8.3-sqlite3 php8.3-mbstring php8.3-xml \
                 php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl unzip curl

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js 20 LTS
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install nodejs

git clone https://github.com/PhillipC05/tpt-free-erp.git
cd tpt-free-erp
composer run setup
composer run dev
```

---

## Database Options

### SQLite (default — no setup needed)

The default `.env` uses SQLite. Setup creates `database/database.sqlite` automatically. No server needed.

```env
DB_CONNECTION=sqlite
# DB_DATABASE is auto-detected as database/database.sqlite
```

### MySQL / MariaDB

```sql
-- Create the database first
CREATE DATABASE tpt_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'tpt_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON tpt_erp.* TO 'tpt_user'@'localhost';
FLUSH PRIVILEGES;
```

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tpt_erp
DB_USERNAME=tpt_user
DB_PASSWORD=your_password
```

Then run:

```bash
php artisan migrate
```

### PostgreSQL

```sql
CREATE DATABASE tpt_erp;
CREATE USER tpt_user WITH ENCRYPTED PASSWORD 'your_password';
GRANT ALL PRIVILEGES ON DATABASE tpt_erp TO tpt_user;
```

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=tpt_erp
DB_USERNAME=tpt_user
DB_PASSWORD=your_password
```

---

## Redis Caching (optional)

The API includes tag-based cache invalidation. With the default `database` cache driver everything works, but Redis gives better performance for production.

```env
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

Windows: install Redis via [Memurai](https://www.memurai.com/) (free Redis-compatible Windows port) or WSL.

---

## Dev Server Details

`composer run dev` starts four processes concurrently:

| Name | Command | Purpose |
|------|---------|---------|
| `server` | `php artisan serve` | Laravel HTTP on :8000 |
| `queue` | `php artisan queue:listen` | Background jobs |
| `logs` | `php artisan pail` | Tail Laravel logs |
| `vite` | `npm run dev` | HMR for Vue/CSS assets |

---

## Nginx / Apache (production-style)

### Nginx

```nginx
server {
    listen 80;
    server_name tpt-erp.local;
    root /path/to/tpt-free-erp/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }
}
```

### Apache

```apache
<VirtualHost *:80>
    ServerName tpt-erp.local
    DocumentRoot /path/to/tpt-free-erp/public
    <Directory /path/to/tpt-free-erp/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Enable `mod_rewrite`:
```bash
sudo a2enmod rewrite && sudo systemctl reload apache2
```

---

## Useful Commands

```bash
# Reset database (drops all tables, re-migrates)
php artisan migrate:fresh --seed

# Run tests (191 passing, uses in-memory SQLite)
composer run test

# Regenerate OpenAPI docs
php artisan l5-swagger:generate

# Format PHP code
./vendor/bin/pint

# Clear all caches
php artisan cache:clear && php artisan config:clear && php artisan route:clear

# Check registered API routes
php artisan route:list --path=api
```

---

## Troubleshooting

**`Class "PDO" not found` / SQLite errors**
```bash
# Ubuntu/Debian
sudo apt install php8.3-sqlite3

# Windows (Herd auto-enables this; for manual PHP, uncomment in php.ini)
# extension=pdo_sqlite
```

**`npm run build` fails**
```bash
node --version   # must be 18+
rm -rf node_modules package-lock.json
npm install && npm run build
```

**Port 8000 already in use**
```bash
php artisan serve --port=8001
```

**Permissions error on storage/**
```bash
chmod -R 775 storage/ bootstrap/cache/
chown -R $USER:www-data storage/ bootstrap/cache/
```

**Key not set error**
```bash
php artisan key:generate
```
