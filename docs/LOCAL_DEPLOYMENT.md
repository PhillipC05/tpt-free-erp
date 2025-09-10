# TPT Free ERP - Local Deployment Guide

**Version:** 1.0
**Date:** September 10, 2025
**Platform:** Local Development Environment
**Author:** Development Team

This comprehensive guide provides step-by-step instructions for deploying TPT Free ERP on local development environments including manual setup, XAMPP/WAMP/MAMP, and custom server configurations.

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Deployment Methods](#deployment-methods)
4. [Method 1: XAMPP (Windows/Linux/macOS)](#method-1-xampp-windowslinuxmacos)
5. [Method 2: WAMP (Windows)](#method-2-wamp-windows)
6. [Method 3: MAMP (macOS)](#method-3-mamp-macos)
7. [Method 4: Manual PHP/MySQL Setup](#method-4-manual-phpmysql-setup)
8. [Method 5: Laravel Valet (macOS)](#method-5-laravel-valet-macos)
9. [Database Configuration](#database-configuration)
10. [Web Server Configuration](#web-server-configuration)
11. [Development Tools Setup](#development-tools-setup)
12. [Troubleshooting](#troubleshooting)
13. [Best Practices](#best-practices)

---

## Overview

### Why Local Deployment?

Local deployment provides:
- **Development flexibility** with full control over environment
- **No cloud costs** for development and testing
- **Fast iteration** with instant code changes
- **Offline development** without internet dependency
- **Custom configurations** for specific development needs
- **Integration with local tools** and workflows

### Local Environment Options

| Method | Platforms | Complexity | Best For |
|--------|-----------|------------|----------|
| XAMPP | Windows/Linux/macOS | ⭐ | Beginners, quick setup |
| WAMP | Windows | ⭐ | Windows developers |
| MAMP | macOS | ⭐ | macOS developers |
| Manual Setup | All | ⭐⭐⭐ | Advanced users, custom configs |
| Laravel Valet | macOS | ⭐⭐ | PHP developers |

---

## Prerequisites

### System Requirements

```bash
# Minimum Requirements
OS: Windows 10/11, macOS 10.15+, Ubuntu 18.04+
RAM: 4GB minimum, 8GB recommended
Storage: 10GB free space
```

### Required Software

#### For All Methods
- **Web Browser**: Chrome, Firefox, Safari, or Edge
- **Text Editor/IDE**: VS Code, PHPStorm, Sublime Text
- **Git**: For cloning repository

#### Method-Specific Requirements
- **XAMPP**: None (comes bundled)
- **WAMP**: Windows only
- **MAMP**: macOS only
- **Manual**: PHP 8.1+, MySQL/PostgreSQL, Apache/Nginx
- **Laravel Valet**: macOS, Homebrew

---

## Deployment Methods

## Method 1: XAMPP (Windows/Linux/macOS)

### Step 1: Download and Install XAMPP

1. **Download XAMPP**
   - Go to https://www.apachefriends.org/
   - Download version with PHP 8.1+
   - Choose appropriate version for your OS

2. **Install XAMPP**
   ```bash
   # Windows: Run installer as administrator
   # Linux/macOS: Extract to /opt/lampp or ~/xampp
   ```

3. **Start XAMPP Control Panel**
   ```bash
   # Windows: Run xampp-control.exe
   # Linux/macOS: Run xampp start/stop commands
   ```

### Step 2: Configure XAMPP

1. **Start Services**
   - Apache (web server)
   - MySQL (database)
   - FileZilla (optional, for FTP)

2. **Security Configuration**
   - Open XAMPP Control Panel
   - Click "Config" → "Apache (httpd.conf)"
   - Add security configurations

### Step 3: Deploy TPT Free ERP

```bash
# Navigate to web root
# Windows: C:\xampp\htdocs\
# Linux: /opt/lampp/htdocs/
# macOS: /Applications/XAMPP/htdocs/

cd /path/to/htdocs

# Clone repository
git clone https://github.com/PhillipC05/tpt-free-erp.git tpt-erp

# Navigate to project
cd tpt-erp

# Copy environment file
cp .env.example .env
```

### Step 4: Database Setup

1. **Access phpMyAdmin**
   - Open browser: http://localhost/phpmyadmin
   - Username: root
   - Password: (leave blank for default)

2. **Create Database**
   ```sql
   CREATE DATABASE tpt_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'tpt_user'@'localhost' IDENTIFIED BY 'secure_password_2025';
   GRANT ALL PRIVILEGES ON tpt_erp.* TO 'tpt_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

### Step 5: Configure Application

**Edit .env file:**
```env
# Application
APP_NAME="TPT Free ERP"
APP_ENV=local
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=true
APP_URL=http://localhost/tpt-erp

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tpt_erp
DB_USERNAME=tpt_user
DB_PASSWORD=secure_password_2025

# Cache & Session
CACHE_DRIVER=file
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Mail Configuration (optional for local)
MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@localhost
MAIL_FROM_NAME="TPT Free ERP"
```

### Step 6: Install Dependencies and Setup

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies (if using frontend assets)
npm install
npm run build

# Generate application key
php artisan key:generate

# Run database migrations
php phinx migrate

# Seed database (optional)
php phinx seed:run
```

### Step 7: Access Application

- **URL**: http://localhost/tpt-erp
- **Admin Panel**: http://localhost/tpt-erp/admin
- **API**: http://localhost/tpt-erp/api

---

## Method 2: WAMP (Windows)

### Step 1: Download and Install WAMP

1. **Download WAMP**
   - Go to http://www.wampserver.com/
   - Download latest version (64-bit recommended)
   - Ensure PHP 8.1+ compatibility

2. **Install WAMP**
   ```bash
   # Run installer as administrator
   # Install in default location: C:\wamp64\
   ```

3. **Start WAMP**
   - Run wampmanager.exe
   - Click "Start All Services"
   - Wait for services to turn green

### Step 2: Configure PHP

1. **Check PHP Version**
   - Left-click WAMP icon → PHP → Version → 8.1.x

2. **Configure PHP Extensions**
   - Left-click WAMP icon → PHP → PHP extensions
   - Enable: pdo_mysql, mysqli, mbstring, curl, gd, intl, openssl

### Step 3: Deploy Application

```bash
# Navigate to web root
cd C:\wamp64\www\

# Clone repository
git clone https://github.com/PhillipC05/tpt-free-erp.git tpt-erp

# Navigate to project
cd tpt-erp

# Copy environment file
copy .env.example .env
```

### Step 4: Database Setup

1. **Access phpMyAdmin**
   - Left-click WAMP icon → phpMyAdmin
   - Or open: http://localhost/phpmyadmin

2. **Create Database**
   ```sql
   CREATE DATABASE tpt_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'tpt_user'@'localhost' IDENTIFIED BY 'secure_password_2025';
   GRANT ALL PRIVILEGES ON tpt_erp.* TO 'tpt_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

### Step 5: Configure and Install

```bash
# Install dependencies
composer install

# Configure .env file (same as XAMPP method)

# Generate key
php artisan key:generate

# Run migrations
php phinx migrate
```

### Step 6: Virtual Host Setup (Optional)

1. **Edit httpd-vhosts.conf**
   ```
   C:\wamp64\bin\apache\apache2.4.51\conf\extra\httpd-vhosts.conf
   ```

2. **Add Virtual Host**
   ```apache
   <VirtualHost *:80>
       ServerName tpt-erp.local
       DocumentRoot "C:/wamp64/www/tpt-erp/public"
       <Directory "C:/wamp64/www/tpt-erp/public">
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

3. **Edit hosts file**
   ```
   C:\Windows\System32\drivers\etc\hosts
   ```
   Add: `127.0.0.1 tpt-erp.local`

---

## Method 3: MAMP (macOS)

### Step 1: Download and Install MAMP

1. **Download MAMP**
   - Go to https://www.mamp.info/
   - Download MAMP (not MAMP PRO)
   - Install with default settings

2. **Start MAMP**
   - Open MAMP application
   - Click "Start Servers"
   - Open "WebStart" page

### Step 2: Configure PHP and MySQL

1. **Set PHP Version**
   - MAMP → Preferences → PHP
   - Select PHP 8.1 or higher

2. **Configure Ports**
   - MAMP → Preferences → Ports
   - Apache: 8888, MySQL: 8889 (or use defaults)

### Step 3: Deploy Application

```bash
# Navigate to web root
cd /Applications/MAMP/htdocs/

# Clone repository
git clone https://github.com/PhillipC05/tpt-free-erp.git tpt-erp

# Navigate to project
cd tpt-erp

# Copy environment file
cp .env.example .env
```

### Step 4: Database Setup

1. **Access phpMyAdmin**
   - Open MAMP WebStart page
   - Click "phpMyAdmin" or go to: http://localhost:8888/phpMyAdmin

2. **Create Database**
   ```sql
   CREATE DATABASE tpt_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'tpt_user'@'localhost' IDENTIFIED BY 'secure_password_2025';
   GRANT ALL PRIVILEGES ON tpt_erp.* TO 'tpt_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

### Step 5: Configure Application

**Edit .env file:**
```env
# Application
APP_NAME="TPT Free ERP"
APP_ENV=local
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=true
APP_URL=http://localhost:8888/tpt-erp

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=8889
DB_DATABASE=tpt_erp
DB_USERNAME=tpt_user
DB_PASSWORD=secure_password_2025
```

### Step 6: Install and Setup

```bash
# Install dependencies
composer install

# Generate key
php artisan key:generate

# Run migrations
php phinx migrate
```

---

## Method 4: Manual PHP/MySQL Setup

### Step 1: Install PHP

#### Ubuntu/Debian
```bash
# Add PHP repository
sudo apt update
sudo apt install software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt update

# Install PHP 8.1
sudo apt install php8.1 php8.1-cli php8.1-fpm php8.1-mysql php8.1-xml php8.1-curl php8.1-gd php8.1-mbstring php8.1-zip php8.1-bcmath php8.1-soap php8.1-intl

# Set default PHP version
sudo update-alternatives --set php /usr/bin/php8.1
```

#### CentOS/RHEL
```bash
# Install PHP 8.1
sudo yum install php php-cli php-fpm php-mysql php-xml php-curl php-gd php-mbstring php-zip php-bcmath php-soap php-intl
```

#### macOS (using Homebrew)
```bash
# Install PHP
brew install php@8.1

# Add to PATH
echo 'export PATH="/usr/local/opt/php@8.1/bin:$PATH"' >> ~/.zshrc
echo 'export PATH="/usr/local/opt/php@8.1/sbin:$PATH"' >> ~/.zshrc
```

#### Windows
```powershell
# Download from https://windows.php.net/
# Extract to C:\php\
# Add to PATH environment variable
```

### Step 2: Install MySQL/PostgreSQL

#### MySQL (Ubuntu)
```bash
# Install MySQL
sudo apt install mysql-server

# Secure installation
sudo mysql_secure_installation

# Start MySQL
sudo systemctl start mysql
sudo systemctl enable mysql
```

#### PostgreSQL (Ubuntu)
```bash
# Install PostgreSQL
sudo apt install postgresql postgresql-contrib

# Start PostgreSQL
sudo systemctl start postgresql
sudo systemctl enable postgresql

# Create user
sudo -u postgres createuser --interactive --pwprompt tpt_user
sudo -u postgres createdb -O tpt_user tpt_erp
```

### Step 3: Install Web Server

#### Apache (Ubuntu)
```bash
# Install Apache
sudo apt install apache2

# Enable modules
sudo a2enmod rewrite
sudo a2enmod ssl

# Start Apache
sudo systemctl start apache2
sudo systemctl enable apache2
```

#### Nginx (Ubuntu)
```bash
# Install Nginx
sudo apt install nginx

# Start Nginx
sudo systemctl start nginx
sudo systemctl enable nginx
```

### Step 4: Configure Virtual Host

#### Apache Configuration
```bash
# Create virtual host
sudo nano /etc/apache2/sites-available/tpt-erp.conf
```

**Apache Virtual Host:**
```apache
<VirtualHost *:80>
    ServerName tpt-erp.local
    DocumentRoot /var/www/tpt-erp/public

    <Directory /var/www/tpt-erp/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/tpt-erp_error.log
    CustomLog ${APACHE_LOG_DIR}/tpt-erp_access.log combined
</VirtualHost>
```

```bash
# Enable site
sudo a2ensite tpt-erp.conf
sudo systemctl reload apache2
```

#### Nginx Configuration
```bash
# Create site configuration
sudo nano /etc/nginx/sites-available/tpt-erp
```

**Nginx Configuration:**
```nginx
server {
    listen 80;
    server_name tpt-erp.local;
    root /var/www/tpt-erp/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/tpt-erp /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Step 5: Deploy Application

```bash
# Create web directory
sudo mkdir -p /var/www/tpt-erp
sudo chown -R $USER:www-data /var/www/tpt-erp
sudo chmod -R 755 /var/www/tpt-erp

# Clone repository
cd /var/www/tpt-erp
git clone https://github.com/PhillipC05/tpt-free-erp.git .

# Install dependencies
composer install

# Configure environment
cp .env.example .env
nano .env

# Generate key
php artisan key:generate

# Run migrations
php phinx migrate
```

---

## Method 5: Laravel Valet (macOS)

### Step 1: Install Laravel Valet

```bash
# Install Homebrew (if not installed)
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# Install PHP
brew install php@8.1

# Install Composer
brew install composer

# Install Valet
composer global require laravel/valet

# Install Valet
valet install

# Start Valet
valet start
```

### Step 2: Configure Valet

```bash
# Park directory
cd /path/to/projects
valet park

# Create database
mysql -u root -p
CREATE DATABASE tpt_erp;
CREATE USER 'tpt_user'@'localhost' IDENTIFIED BY 'secure_password_2025';
GRANT ALL PRIVILEGES ON tpt_erp.* TO 'tpt_user'@'localhost';
FLUSH PRIVILEGES;
```

### Step 3: Deploy Application

```bash
# Clone repository
git clone https://github.com/PhillipC05/tpt-free-erp.git tpt-erp
cd tpt-erp

# Install dependencies
composer install
npm install
npm run build

# Configure environment
cp .env.example .env
nano .env

# Generate key
php artisan key:generate

# Run migrations
php phinx migrate

# Link with Valet
valet link tpt-erp
```

### Step 4: Access Application

- **URL**: http://tpt-erp.test
- **Secure**: valet secure tpt-erp (https://tpt-erp.test)

---

## Database Configuration

### MySQL Configuration

1. **Create Database**
   ```sql
   CREATE DATABASE tpt_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'tpt_user'@'localhost' IDENTIFIED BY 'secure_password_2025';
   GRANT ALL PRIVILEGES ON tpt_erp.* TO 'tpt_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

2. **Optimize MySQL**
   ```ini
   # my.cnf or my.ini
   [mysqld]
   innodb_buffer_pool_size = 256M
   innodb_log_file_size = 64M
   max_connections = 100
   query_cache_size = 64M
   ```

### PostgreSQL Configuration

1. **Create Database**
   ```sql
   CREATE DATABASE tpt_erp;
   CREATE USER tpt_user WITH ENCRYPTED PASSWORD 'secure_password_2025';
   GRANT ALL PRIVILEGES ON DATABASE tpt_erp TO tpt_user;
   ```

2. **Optimize PostgreSQL**
   ```ini
   # postgresql.conf
   shared_buffers = 256MB
   effective_cache_size = 1GB
   work_mem = 4MB
   maintenance_work_mem = 64MB
   max_connections = 100
   ```

---

## Web Server Configuration

### Apache Optimization

```apache
# httpd.conf optimizations
<IfModule mpm_prefork_module>
    StartServers 5
    MinSpareServers 5
    MaxSpareServers 10
    MaxRequestWorkers 150
    MaxConnectionsPerChild 1000
</IfModule>

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>
```

### Nginx Optimization

```nginx
# nginx.conf optimizations
worker_processes auto;
worker_connections 1024;

# Gzip compression
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;

# Cache static files
location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}

# PHP upstream
upstream php_backend {
    server unix:/var/run/php/php8.1-fpm.sock;
}
```

### PHP Optimization

```ini
# php.ini optimizations
memory_limit = 256M
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300

# OPcache
opcache.enable = 1
opcache.memory_consumption = 256
opcache.max_accelerated_files = 7963
opcache.revalidate_freq = 0

# Realpath cache
realpath_cache_size = 4096k
realpath_cache_ttl = 600
```

---

## Development Tools Setup

### IDE Configuration

#### Visual Studio Code
```json
// .vscode/settings.json
{
    "php.validate.executablePath": "/usr/bin/php8.1",
    "php.debug.executablePath": "/usr/bin/php8.1",
    "files.associations": {
        "*.blade.php": "blade"
    },
    "emmet.includeLanguages": {
        "blade": "html"
    }
}
```

#### PHPStorm
- Configure PHP Interpreter: /usr/bin/php8.1
- Configure Database connection
- Enable Blade plugin
- Configure Xdebug

### Debugging Setup

#### Xdebug Configuration
```ini
# php.ini or xdebug.ini
[xdebug]
zend_extension=xdebug.so
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
xdebug.idekey=VSCODE
```

#### Browser DevTools
- Chrome DevTools for frontend debugging
- Network tab for API calls
- Console for JavaScript errors
- Application tab for local storage

### Version Control

```bash
# Git configuration
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"

# Create .gitignore
node_modules/
vendor/
.env
storage/logs/*
bootstrap/cache/*
```

---

## Troubleshooting

### Common Local Deployment Issues

#### 1. Permission Issues

```bash
# Fix storage permissions
sudo chown -R $USER:www-data storage/
sudo chown -R $USER:www-data bootstrap/cache/
sudo chmod -R 775 storage/
sudo chmod -R 775 bootstrap/cache/
```

#### 2. Database Connection Issues

```bash
# Test database connection
php artisan tinker
DB::connection()->getPdo();

# Check database credentials
php artisan config:cache
php artisan cache:clear
```

#### 3. Web Server Issues

**Apache:**
```bash
# Check Apache status
sudo systemctl status apache2

# Check Apache configuration
sudo apache2ctl configtest

# View error logs
sudo tail -f /var/log/apache2/error.log
```

**Nginx:**
```bash
# Check Nginx status
sudo systemctl status nginx

# Test configuration
sudo nginx -t

# View error logs
sudo tail -f /var/log/nginx/error.log
```

#### 4. PHP Issues

```bash
# Check PHP version
php --version

# Check PHP modules
php -m

# Check PHP configuration
php -i

# Test PHP-FPM
sudo systemctl status php8.1-fpm
```

#### 5. Composer Issues

```bash
# Clear Composer cache
composer clear-cache

# Update Composer
composer self-update

# Check Composer dependencies
composer show
```

#### 6. Node.js Issues

```bash
# Check Node.js version
node --version
npm --version

# Clear npm cache
npm cache clean --force

# Reinstall dependencies
rm -rf node_modules package-lock.json
npm install
```

### XAMPP/WAMP/MAMP Specific Issues

#### XAMPP Issues
```bash
# Check XAMPP services
sudo /opt/lampp/lampp status

# Restart XAMPP
sudo /opt/lampp/lampp restart

# Check XAMPP logs
tail -f /opt/lampp/logs/error_log
```

#### WAMP Issues
```bash
# Check WAMP services
# Left-click WAMP icon → Apache → Service → Start/Stop

# Check PHP error logs
# C:\wamp64\logs\php_error.log

# Check Apache error logs
# C:\wamp64\logs\apache_error.log
```

#### MAMP Issues
```bash
# Check MAMP logs
# /Applications/MAMP/logs/

# Restart MAMP services
# MAMP → Stop Servers → Start Servers

# Check PHP error logs
# /Applications/MAMP/logs/php_error.log
```

### Performance Issues

#### 1. Slow Page Loads

```bash
# Enable OPcache
php -r "var_dump(opcache_get_status());"

# Check database queries
php artisan tinker
DB::enableQueryLog();
# Make some requests
dd(DB::getQueryLog());
```

#### 2. Memory Issues

```bash
# Check memory usage
php -r "echo 'Memory: ' . memory_get_peak_usage(true) / 1024 / 1024 . ' MB' . PHP_EOL;"

# Increase PHP memory limit
php.ini: memory_limit = 512M
```

#### 3. Database Performance

```sql
# Check slow queries
SHOW PROCESSLIST;
SHOW ENGINE INNODB STATUS;

# Analyze table
ANALYZE TABLE your_table;

# Check indexes
SHOW INDEX FROM your_table;
```

---

## Best Practices

### Development Environment

```bash
# Use different environments
# .env.local for local development
# .env.staging for staging
# .env.production for production

# Environment-specific configurations
APP_ENV=local
APP_DEBUG=true
DB_DATABASE=tpt_erp_local
```

### Security Best Practices

```bash
# Never commit sensitive data
# .env files should be in .gitignore

# Use strong passwords
# Regularly update dependencies
composer update
npm audit fix

# Enable HTTPS in development
# Use mkcert for local SSL certificates
```

### Backup and Recovery

```bash
# Database backup script
mysqldump -u tpt_user -p tpt_erp > backup_$(date +%Y%m%d_%H%M%S).sql

# File backup
tar -czf backup_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/tpt-erp

# Automated backups with cron
crontab -e
# Add: 0 2 * * * /path/to/backup-script.sh
```

### Monitoring and Logging

```bash
# Enable Laravel logging
# config/logging.php
'channels' => [
    'single' => [
        'driver' => 'single',
        'path' => storage_path('logs/laravel.log'),
        'level' => 'debug',
    ],
],

# Monitor logs
tail -f storage/logs/laravel.log

# Use Laravel Telescope for debugging
composer require laravel/telescope
php artisan telescope:install
php artisan migrate
```

### Code Quality

```bash
# Run PHPStan for static analysis
./vendor/bin/phpstan analyse

# Run PHPCS for code style
./vendor/bin/phpcs --standard=phpcs.xml

# Run tests
./vendor/bin/phpunit

# Format code
./vendor/bin/php-cs-fixer fix
```

---

## Support Resources

- **XAMPP Documentation**: https://www.apachefriends.org/docs/
- **WAMP Documentation**: http://www.wampserver.com/documentation/
- **MAMP Documentation**: https://documentation.mamp.info/
- **Laravel Valet**: https://laravel.com/docs/valet
- **PHP Documentation**: https://www.php.net/docs.php
- **MySQL Documentation**: https://dev.mysql.com/doc/
- **PostgreSQL Documentation**: https://www.postgresql.org/docs/

---

*Last Updated: September 10, 2025*
*Version: 1.0*
*Platform: Local Development Environment*
