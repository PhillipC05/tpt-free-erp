# TPT Free ERP - Digital Ocean Deployment Guide

**Version:** 1.0
**Date:** September 10, 2025
**Platform:** Digital Ocean
**Author:** Development Team

This comprehensive guide provides step-by-step instructions for deploying TPT Free ERP on Digital Ocean using various deployment methods including Droplets, App Platform, and Managed Databases.

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Deployment Methods](#deployment-methods)
4. [Method 1: Docker on Droplet](#method-1-docker-on-droplet)
5. [Method 2: Manual Setup on Droplet](#method-2-manual-setup-on-droplet)
6. [Method 3: App Platform](#method-3-app-platform)
7. [Database Configuration](#database-configuration)
8. [SSL Configuration](#ssl-configuration)
9. [Monitoring and Maintenance](#monitoring-and-maintenance)
10. [Troubleshooting](#troubleshooting)
11. [Cost Optimization](#cost-optimization)

---

## Overview

### Why Digital Ocean?

Digital Ocean provides:
- **Simple pricing** with transparent costs
- **Developer-friendly** interface and documentation
- **Global data centers** for low latency
- **Managed services** for databases and monitoring
- **One-click deployments** and marketplace apps
- **Excellent Docker support**

### Deployment Options

| Method | Difficulty | Cost | Scalability | Best For |
|--------|------------|------|-------------|----------|
| Docker on Droplet | Medium | $$ | High | Full control, custom scaling |
| Manual Setup | High | $$ | High | Learning, maximum customization |
| App Platform | Low | $$$ | Medium | Quick deployment, managed scaling |

---

## Prerequisites

### Digital Ocean Account
- Active Digital Ocean account
- Payment method configured
- SSH key added to account (recommended)

### Domain Name (Optional but Recommended)
- Domain registered with any provider
- DNS management access
- SSL certificate (Let's Encrypt will be configured)

### Local Development Environment
```bash
# Required tools
git --version        # For cloning repository
docker --version     # For container management
docker-compose --version  # For orchestration
ssh -V              # For server access
```

---

## Deployment Methods

## Method 1: Docker on Droplet (Recommended)

### Step 1: Create Digital Ocean Droplet

1. **Log in to Digital Ocean Dashboard**
   - Go to https://cloud.digitalocean.com/
   - Click "Create" → "Droplets"

2. **Choose Configuration**
   ```
   Distribution: Ubuntu 22.04 (LTS)
   Plan: Basic ($12/month - 1 GB RAM, 1 vCPU, 25 GB SSD)
   Recommended: $24/month - 2 GB RAM, 1 vCPU, 50 GB SSD
   Datacenter: Choose closest to your users
   ```

3. **Authentication**
   - Add your SSH key (highly recommended)
   - Or use password authentication (less secure)

4. **Additional Options**
   - Enable backups ($1/month)
   - Enable monitoring (free)
   - Private networking (if needed)

5. **Create Droplet**
   - Name: `tpt-erp-production`
   - Click "Create Droplet"

### Step 2: Initial Server Setup

```bash
# Connect to your droplet
ssh root@YOUR_DROPLET_IP

# Update system packages
sudo apt update && sudo apt upgrade -y

# Install Docker and Docker Compose
sudo apt install apt-transport-https ca-certificates curl gnupg lsb-release -y

# Add Docker's official GPG key
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

# Add Docker repository
echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Install Docker
sudo apt update
sudo apt install docker-ce docker-ce-cli containerd.io docker-compose-plugin -y

# Start and enable Docker
sudo systemctl start docker
sudo systemctl enable docker

# Add user to docker group (optional, for non-root usage)
sudo usermod -aG docker $USER

# Install Git
sudo apt install git -y
```

### Step 3: Deploy TPT Free ERP

```bash
# Clone the repository
git clone https://github.com/PhillipC05/tpt-free-erp.git
cd tpt-free-erp

# Create environment file
cp .env.example .env

# Edit environment configuration
nano .env
```

**Environment Configuration (.env):**
```env
# Application
APP_NAME="TPT Free ERP"
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database (we'll configure this later)
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=tpt_erp
DB_USERNAME=tpt_user
DB_PASSWORD=secure_password_2025

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Redis (optional, for better performance)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail Configuration (configure later)
MAIL_MAILER=smtp
MAIL_HOST=smtp.digitalocean.com
MAIL_PORT=587
MAIL_USERNAME=your-email@your-domain.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@your-domain.com
MAIL_FROM_NAME="TPT Free ERP"
```

### Step 4: Configure Docker Compose for Production

```bash
# Create production docker-compose file
nano docker-compose.prod.yml
```

**Production Docker Compose (docker-compose.prod.yml):**
```yaml
version: '3.8'

services:
  web:
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - .:/var/www/html
      - ./storage:/var/www/html/storage
      - ./bootstrap/cache:/var/www/html/bootstrap/cache
    depends_on:
      - db
      - redis
    environment:
      - APACHE_DOCUMENT_ROOT=/var/www/html/public
      - APP_ENV=production
    restart: unless-stopped
    networks:
      - tpt-network

  db:
    image: postgres:15
    environment:
      POSTGRES_DB: tpt_erp
      POSTGRES_USER: tpt_user
      POSTGRES_PASSWORD: secure_password_2025
    volumes:
      - postgres_data:/var/lib/postgresql/data
      - ./db/init:/docker-entrypoint-initdb.d
    ports:
      - "5432:5432"
    restart: unless-stopped
    networks:
      - tpt-network

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    restart: unless-stopped
    networks:
      - tpt-network

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf:ro
      - ./ssl:/etc/ssl/certs
      - .:/var/www/html
    depends_on:
      - web
    restart: unless-stopped
    networks:
      - tpt-network

volumes:
  postgres_data:
    driver: local

networks:
  tpt-network:
    driver: bridge
```

### Step 5: Nginx Configuration

```bash
# Create Nginx configuration
nano nginx.conf
```

**Nginx Configuration (nginx.conf):**
```nginx
events {
    worker_connections 1024;
}

http {
    include       /etc/nginx/mime.types;
    default_type  application/octet-stream;

    # Logging
    log_format main '$remote_addr - $remote_user [$time_local] "$request" '
                    '$status $body_bytes_sent "$http_referer" '
                    '"$http_user_agent" "$http_x_forwarded_for"';

    access_log /var/log/nginx/access.log main;
    error_log /var/log/nginx/error.log;

    # Performance
    sendfile        on;
    tcp_nopush      on;
    tcp_nodelay     on;
    keepalive_timeout 65;
    types_hash_max_size 2048;
    client_max_body_size 100M;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types
        text/plain
        text/css
        text/xml
        text/javascript
        application/json
        application/javascript
        application/xml+rss
        application/atom+xml
        image/svg+xml;

    # Rate limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
    limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;

    upstream tpt_backend {
        server web:80;
    }

    server {
        listen 80;
        server_name your-domain.com www.your-domain.com;
        return 301 https://$server_name$request_uri;
    }

    server {
        listen 443 ssl http2;
        server_name your-domain.com www.your-domain.com;

        # SSL Configuration
        ssl_certificate /etc/ssl/certs/fullchain.pem;
        ssl_certificate_key /etc/ssl/certs/privkey.pem;
        ssl_protocols TLSv1.2 TLSv1.3;
        ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384;
        ssl_prefer_server_ciphers off;

        # Security headers
        add_header X-Frame-Options DENY;
        add_header X-Content-Type-Options nosniff;
        add_header X-XSS-Protection "1; mode=block";
        add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
        add_header Referrer-Policy "strict-origin-when-cross-origin";

        # Root directory
        root /var/www/html/public;
        index index.php index.html;

        # Handle PHP files
        location ~ \.php$ {
            try_files $uri =404;
            fastcgi_pass tpt_backend;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include fastcgi_params;
        }

        # Handle static files
        location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
            expires 1y;
            add_header Cache-Control "public, immutable";
            try_files $uri =404;
        }

        # API rate limiting
        location /api/ {
            limit_req zone=api burst=20 nodelay;
            try_files $uri $uri/ /index.php?$query_string;
        }

        # Login rate limiting
        location /login {
            limit_req zone=login burst=5 nodelay;
            try_files $uri $uri/ /index.php?$query_string;
        }

        # Deny access to sensitive files
        location ~ /(config|db|logs|backups|\.env)/ {
            deny all;
            return 404;
        }

        # Main application
        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }
    }
}
```

### Step 6: Deploy and Start Services

```bash
# Start the application
docker-compose -f docker-compose.prod.yml up -d

# Run database migrations
docker-compose -f docker-compose.prod.yml exec web php phinx migrate

# Seed the database (optional)
docker-compose -f docker-compose.prod.yml exec web php phinx seed:run

# Check if services are running
docker-compose -f docker-compose.prod.yml ps

# View logs
docker-compose -f docker-compose.prod.yml logs -f
```

---

## Method 2: Manual Setup on Droplet

### Step 1: Server Preparation

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install software-properties-common -y
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.1 and extensions
sudo apt install php8.1 php8.1-cli php8.1-fpm php8.1-mysql php8.1-xml php8.1-curl php8.1-gd php8.1-mbstring php8.1-zip php8.1-bcmath php8.1-soap php8.1-intl -y

# Install Node.js and npm
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install nodejs -y

# Install PostgreSQL
sudo apt install postgresql postgresql-contrib -y

# Install Nginx
sudo apt install nginx -y

# Install Redis (optional)
sudo apt install redis-server -y
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

### Step 2: Database Setup

```bash
# Switch to postgres user
sudo -u postgres psql

# Create database and user
CREATE DATABASE tpt_erp;
CREATE USER tpt_user WITH ENCRYPTED PASSWORD 'secure_password_2025';
GRANT ALL PRIVILEGES ON DATABASE tpt_erp TO tpt_user;
ALTER USER tpt_user CREATEDB;
\q
```

### Step 3: Application Deployment

```bash
# Create web directory
sudo mkdir -p /var/www/tpt-erp
sudo chown -R www-data:www-data /var/www/tpt-erp
sudo chmod -R 755 /var/www/tpt-erp

# Clone repository
cd /var/www/tpt-erp
sudo -u www-data git clone https://github.com/PhillipC05/tpt-free-erp.git .

# Install PHP dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader

# Install Node.js dependencies and build assets
sudo -u www-data npm install
sudo -u www-data npm run build

# Set up environment
sudo -u www-data cp .env.example .env
sudo -u www-data nano .env  # Configure as shown earlier

# Generate application key
sudo -u www-data php artisan key:generate

# Run migrations
sudo -u www-data php phinx migrate
```

### Step 4: Nginx Configuration

```bash
# Create Nginx site configuration
sudo nano /etc/nginx/sites-available/tpt-erp
```

**Nginx Site Configuration:**
```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/tpt-erp/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options DENY;
    add_header X-Content-Type-Options nosniff;
    add_header X-XSS-Protection "1; mode=block";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Deny access to sensitive files
    location ~ /(config|db|logs|backups|\.env)/ {
        deny all;
        return 404;
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/tpt-erp /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## Method 3: App Platform (Managed)

### Step 1: Prepare Application for App Platform

1. **Create app.yaml configuration file**
```yaml
name: tpt-free-erp
services:
- name: web
  source_dir: /
  github:
    repo: PhillipC05/tpt-free-erp
    branch: main
  run_command: |
    composer install --no-dev --optimize-autoloader
    npm install && npm run build
    php phinx migrate
    php -S 0.0.0.0:8080 -t public
  instance_count: 1
  instance_size_slug: basic-xxs
  envs:
  - key: APP_ENV
    value: production
  - key: APP_KEY
    value: ${APP_KEY}
  - key: DB_CONNECTION
    value: pgsql
  - key: DB_HOST
    value: ${db.HOSTNAME}
  - key: DB_PORT
    value: ${db.PORT}
  - key: DB_DATABASE
    value: ${db.DATABASE}
  - key: DB_USERNAME
    value: ${db.USERNAME}
  - key: DB_PASSWORD
    value: ${db.PASSWORD}
  health_check:
    http_path: /health

databases:
- name: db
  engine: PG
  version: "15"
  size: basic
```

### Step 2: Deploy to App Platform

1. **Go to Digital Ocean App Platform**
   - https://cloud.digitalocean.com/apps
   - Click "Create App"

2. **Connect Repository**
   - Choose GitHub
   - Select your repository
   - Choose branch (main)

3. **Configure Resources**
   - Service: Web Service
   - Source: GitHub
   - Runtime: Docker (if using Dockerfile) or custom build

4. **Environment Variables**
   - Set all required environment variables
   - Configure database connection strings

5. **Deploy**
   - Click "Create Resources"
   - Wait for deployment to complete

---

## Database Configuration

### Using Digital Ocean Managed PostgreSQL

1. **Create Managed Database**
   - Go to Databases → Create Database Cluster
   - Choose PostgreSQL 15
   - Select plan (starting at $15/month)
   - Choose datacenter region

2. **Configure Database**
   - Create database: `tpt_erp`
   - Create user: `tpt_user`
   - Set password
   - Note connection details

3. **Update Application Configuration**
```env
DB_CONNECTION=pgsql
DB_HOST=db-postgresql-nyc1-12345-do-user-1234567-0.b.db.ondigitalocean.com
DB_PORT=25060
DB_DATABASE=tpt_erp
DB_USERNAME=tpt_user
DB_PASSWORD=your_secure_password
```

4. **SSL Configuration**
```env
DB_SSL_MODE=require
DB_SSL_CA=/path/to/ca-certificate.crt
```

---

## SSL Configuration

### Using Let's Encrypt (Free)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx -y

# Obtain SSL certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Test renewal
sudo certbot renew --dry-run

# Set up auto-renewal cron job
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

### Using Digital Ocean Load Balancer

1. **Create Load Balancer**
   - Go to Networking → Load Balancers
   - Choose datacenter region
   - Select Droplets to load balance

2. **SSL Termination**
   - Upload SSL certificate
   - Or use Let's Encrypt integration
   - Configure forwarding rules

---

## Monitoring and Maintenance

### Digital Ocean Monitoring

```bash
# Install Digital Ocean agent
curl -sSL https://repos.insights.digitalocean.com/install.sh | sudo bash

# Enable monitoring on Droplet
# Go to Droplet settings → Monitoring → Enable
```

### Application Monitoring

```bash
# Install monitoring tools
sudo apt install htop iotop nload -y

# Monitor logs
docker-compose logs -f web

# Check disk usage
df -h

# Monitor processes
htop
```

### Automated Backups

```bash
# Database backup script
nano /usr/local/bin/backup-db.sh
```

**Database Backup Script:**
```bash
#!/bin/bash
BACKUP_DIR="/var/backups/tpt-erp"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="tpt_erp"
DB_USER="tpt_user"

mkdir -p $BACKUP_DIR

# Create backup
docker-compose exec db pg_dump -U $DB_USER $DB_NAME > $BACKUP_DIR/backup_$DATE.sql

# Compress backup
gzip $BACKUP_DIR/backup_$DATE.sql

# Keep only last 7 days
find $BACKUP_DIR -name "backup_*.sql.gz" -mtime +7 -delete

echo "Backup completed: $BACKUP_DIR/backup_$DATE.sql.gz"
```

```bash
# Make executable and set up cron
sudo chmod +x /usr/local/bin/backup-db.sh
sudo crontab -e
# Add: 0 2 * * * /usr/local/bin/backup-db.sh
```

---

## Troubleshooting

### Common Issues

#### 1. Application Not Loading

```bash
# Check if services are running
docker-compose ps

# Check application logs
docker-compose logs web

# Check Nginx error logs
sudo tail -f /var/log/nginx/error.log

# Test PHP-FPM
sudo systemctl status php8.1-fpm
```

#### 2. Database Connection Issues

```bash
# Test database connection
docker-compose exec web php -r "
try {
    \$pdo = new PDO('pgsql:host=db;port=5432;dbname=tpt_erp', 'tpt_user', 'password');
    echo 'Database connection successful';
} catch (Exception \$e) {
    echo 'Database connection failed: ' . \$e->getMessage();
}
"
```

#### 3. SSL Certificate Issues

```bash
# Check certificate validity
openssl s_client -connect your-domain.com:443 -servername your-domain.com

# Renew certificate manually
sudo certbot renew

# Check Certbot logs
sudo tail -f /var/log/letsencrypt/letsencrypt.log
```

#### 4. Performance Issues

```bash
# Check resource usage
htop

# Monitor network
nload

# Check disk I/O
iotop

# PHP-FPM status
sudo systemctl status php8.1-fpm
```

### Digital Ocean Specific Issues

#### Firewall Configuration

```bash
# Check UFW status
sudo ufw status

# Allow necessary ports
sudo ufw allow 80
sudo ufw allow 443
sudo ufw allow 22

# Enable firewall
sudo ufw --force enable
```

#### Memory Issues

```bash
# Check memory usage
free -h

# Check swap
swapon --show

# Create swap file if needed
sudo fallocate -l 1G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

---

## Cost Optimization

### Pricing Breakdown

| Service | Basic Plan | Cost/Month |
|---------|------------|------------|
| Droplet (2GB RAM) | Basic | $24 |
| Managed PostgreSQL | Basic | $15 |
| Load Balancer | Basic | $12 |
| Monitoring | Free | $0 |
| Backups | Optional | +$2 |
| **Total** | | **$53+** |

### Optimization Strategies

#### 1. Reserved Instances
- Use reserved Droplets for long-term deployments
- Save up to 20% on compute costs

#### 2. Auto-scaling
- Configure monitoring alerts
- Scale up/down based on traffic
- Use Digital Ocean's auto-scaling features

#### 3. Storage Optimization
- Use object storage for file uploads
- Implement database query optimization
- Regular cleanup of old data

#### 4. Network Optimization
- Use CDN for static assets
- Implement caching strategies
- Optimize database queries

---

## Security Best Practices

### Server Security

```bash
# Disable root login
sudo sed -i 's/#PermitRootLogin yes/PermitRootLogin no/' /etc/ssh/sshd_config
sudo systemctl reload sshd

# Set up fail2ban
sudo apt install fail2ban -y
sudo systemctl enable fail2ban
sudo systemctl start fail2ban

# Configure firewall
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 80
sudo ufw allow 443
sudo ufw --force enable
```

### Application Security

```bash
# Set proper file permissions
sudo chown -R www-data:www-data /var/www/tpt-erp
sudo find /var/www/tpt-erp -type f -exec chmod 644 {} \;
sudo find /var/www/tpt-erp -type d -exec chmod 755 {} \;

# Secure sensitive files
sudo chmod 600 /var/www/tpt-erp/.env
sudo chmod 600 /etc/ssl/private/*
```

---

## Next Steps

1. **Domain Configuration**
   - Point domain to Digital Ocean nameservers
   - Configure DNS records
   - Set up SSL certificates

2. **Backup Strategy**
   - Implement automated backups
   - Test backup restoration
   - Set up off-site backup storage

3. **Monitoring Setup**
   - Configure alerts for critical metrics
   - Set up log aggregation
   - Implement performance monitoring

4. **Scaling Considerations**
   - Plan for traffic growth
   - Consider load balancing
   - Implement caching strategies

---

## Support Resources

- **Digital Ocean Documentation**: https://docs.digitalocean.com/
- **TPT Free ERP Issues**: https://github.com/PhillipC05/tpt-free-erp/issues
- **Community Forums**: https://www.digitalocean.com/community
- **Status Page**: https://status.digitalocean.com/

---

*Last Updated: September 10, 2025*
*Version: 1.0*
*Platform: Digital Ocean*
