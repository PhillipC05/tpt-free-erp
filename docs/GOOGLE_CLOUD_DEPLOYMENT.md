# TPT Free ERP - Google Cloud Deployment Guide

**Version:** 1.0
**Date:** September 10, 2025
**Platform:** Google Cloud Platform (GCP)
**Author:** Development Team

This comprehensive guide provides step-by-step instructions for deploying TPT Free ERP on Google Cloud Platform using various services including Compute Engine, App Engine, Cloud Run, and Cloud SQL.

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Deployment Methods](#deployment-methods)
4. [Method 1: Compute Engine with Docker](#method-1-compute-engine-with-docker)
5. [Method 2: App Engine](#method-2-app-engine)
6. [Method 3: Cloud Run (Containerized)](#method-3-cloud-run-containerized)
7. [Database Configuration](#database-configuration)
8. [SSL Configuration](#ssl-configuration)
9. [Monitoring and Maintenance](#monitoring-and-maintenance)
10. [Troubleshooting](#troubleshooting)
11. [Cost Optimization](#cost-optimization)

---

## Overview

### Why Google Cloud?

Google Cloud provides:
- **Superior data analytics** with BigQuery and AI/ML capabilities
- **Competitive pricing** for compute resources
- **Strong container support** with Kubernetes and Cloud Run
- **Advanced networking** with global load balancing
- **Excellent integration** with Google Workspace
- **Committed use discounts** for cost optimization

### Deployment Options

| Method | Difficulty | Cost | Scalability | Best For |
|--------|------------|------|-------------|----------|
| Compute Engine + Docker | Medium | $$$ | High | Full control, custom scaling |
| App Engine | Low | $$$ | Medium | Quick deployment, managed scaling |
| Cloud Run | Medium | $$$ | Very High | Containerized, serverless scaling |

---

## Prerequisites

### Google Cloud Account Setup
- Active Google Cloud account with billing enabled
- Google Cloud SDK (gcloud) installed and configured
- Service account with appropriate permissions (recommended)

### Domain Name (Recommended)
- Domain registered with Google Domains or any provider
- DNS management access
- SSL certificate (managed by Google)

### Local Development Environment
```bash
# Required tools
gcloud --version     # Google Cloud SDK
docker --version     # Docker
docker-compose --version  # Docker Compose
git --version        # Git
```

### Required GCP Permissions
```yaml
roles:
  - roles/compute.admin
  - roles/storage.admin
  - roles/cloudsql.admin
  - roles/monitoring.admin
  - roles/logging.admin
  - roles/iam.serviceAccountUser
```

---

## Deployment Methods

## Method 1: Compute Engine with Docker (Recommended for Full Control)

### Step 1: Create Compute Engine Instance

1. **Navigate to Compute Engine Console**
   - Go to Google Cloud Console → Compute Engine → VM instances

2. **Choose Configuration**
   ```
   Machine type: e2-medium (2 vCPUs, 4 GB RAM)
   Recommended: n2-standard-2 (2 vCPUs, 8 GB RAM)
   Boot disk: Ubuntu 22.04 LTS, 20 GB SSD
   Region: Choose closest to your users
   ```

3. **Configure Networking**
   - Network: Default VPC or create custom
   - External IP: Create ephemeral or static IP
   - Firewall: Allow HTTP/HTTPS traffic

4. **Add Labels and Metadata**
   ```
   Labels: environment=production, application=tpt-erp
   Startup script: Install Docker and dependencies
   ```

5. **Create Instance**
   - Name: `tpt-erp-production`
   - Click "Create"

### Step 2: Initial Server Setup

```bash
# SSH into your instance
gcloud compute ssh tpt-erp-production --zone=YOUR_ZONE

# Update system packages
sudo apt update && sudo apt upgrade -y

# Install Docker
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

# Database (configure after Cloud SQL setup)
DB_CONNECTION=pgsql
DB_HOST=/cloudsql/YOUR_PROJECT_ID:YOUR_REGION:YOUR_INSTANCE_NAME
DB_PORT=5432
DB_DATABASE=tpt_erp
DB_USERNAME=tpt_user
DB_PASSWORD=secure_password_2025
DB_SOCKET=/cloudsql/YOUR_PROJECT_ID:YOUR_REGION:YOUR_INSTANCE_NAME

# Cache & Session (using Memorystore)
CACHE_DRIVER=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Redis (using Memorystore)
REDIS_HOST=10.0.0.3  # Private IP of Memorystore instance
REDIS_PASSWORD=null
REDIS_PORT=6379

# Google Cloud Storage
GOOGLE_CLOUD_PROJECT_ID=your-project-id
GOOGLE_CLOUD_STORAGE_BUCKET=your-bucket-name
GOOGLE_APPLICATION_CREDENTIALS=/path/to/service-account.json

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="TPT Free ERP"
```

### Step 4: Configure Docker Compose for GCP

```bash
# Create production docker-compose file
nano docker-compose.gcp.yml
```

**GCP Docker Compose (docker-compose.gcp.yml):**
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
      - /cloudsql:/cloudsql  # Mount Cloud SQL socket
    environment:
      - APACHE_DOCUMENT_ROOT=/var/www/html/public
      - APP_ENV=production
      - DB_SOCKET=/cloudsql/YOUR_PROJECT_ID:YOUR_REGION:YOUR_INSTANCE_NAME
    restart: unless-stopped
    networks:
      - tpt-network
    depends_on:
      - redis

  redis:
    image: redis:7-alpine
    restart: unless-stopped
    networks:
      - tpt-network

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.gcp.conf:/etc/nginx/nginx.conf:ro
      - ./ssl:/etc/ssl/certs
      - .:/var/www/html
    depends_on:
      - web
    restart: unless-stopped
    networks:
      - tpt-network

networks:
  tpt-network:
    driver: bridge
```

### Step 5: GCP Nginx Configuration

```bash
# Create GCP-specific Nginx configuration
nano nginx.gcp.conf
```

**GCP Nginx Configuration (nginx.gcp.conf):**
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

        # SSL Configuration (managed by Google)
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
docker-compose -f docker-compose.gcp.yml up -d

# Run database migrations
docker-compose -f docker-compose.gcp.yml exec web php phinx migrate

# Seed the database (optional)
docker-compose -f docker-compose.gcp.yml exec web php phinx seed:run

# Check if services are running
docker-compose -f docker-compose.gcp.yml ps

# View logs
docker-compose -f docker-compose.gcp.yml logs -f
```

---

## Method 2: App Engine (Managed)

### Step 1: Prepare Application for App Engine

1. **Create App Engine Application**
   ```bash
   # Initialize App Engine
   gcloud app create --region=us-central1

   # Set project
   gcloud config set project YOUR_PROJECT_ID
   ```

2. **Create app.yaml configuration**
   ```yaml
   runtime: php81

   env_variables:
     APP_ENV: production
     APP_KEY: "base64:YOUR_APP_KEY_HERE"
     DB_CONNECTION: pgsql
     DB_HOST: "/cloudsql/YOUR_PROJECT_ID:us-central1:YOUR_INSTANCE_NAME"
     DB_DATABASE: tpt_erp
     DB_USERNAME: tpt_user
     DB_PASSWORD: secure_password_2025
     CACHE_DRIVER: redis
     REDIS_HOST: "YOUR_MEMORYSTORE_IP"
     GOOGLE_CLOUD_PROJECT_ID: "YOUR_PROJECT_ID"

   beta_settings:
     cloud_sql_instances: "YOUR_PROJECT_ID:us-central1:YOUR_INSTANCE_NAME"

   handlers:
   - url: /(.+\.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot))$
     static_files: public/\1
     upload: public/(.+\.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot))$
     expiration: "1y"

   - url: /.*
     script: auto
     secure: always

   automatic_scaling:
     min_instances: 1
     max_instances: 10
     target_cpu_utilization: 0.7
     target_throughput_utilization: 0.7

   network:
     session_affinity: true
   ```

3. **Configure PHP Settings**
   ```yaml
   # php.ini settings
   php_ini:
     upload_max_filesize: 100M
     post_max_size: 100M
     memory_limit: 256M
     max_execution_time: 300
   ```

### Step 2: Deploy to App Engine

```bash
# Deploy application
gcloud app deploy app.yaml --version=1

# Check deployment status
gcloud app versions list

# View application logs
gcloud app logs tail -s default

# Open application
gcloud app browse
```

### Step 3: Configure Custom Domain

```bash
# Add custom domain
gcloud app domain-mappings create your-domain.com

# Verify domain ownership (add TXT record to DNS)
# Then complete SSL certificate setup
gcloud app ssl-certificates create CERT_NAME --domain=your-domain.com
```

---

## Method 3: Cloud Run (Containerized)

### Step 1: Prepare Container for Cloud Run

1. **Create Dockerfile for Cloud Run**
   ```dockerfile
   FROM php:8.1-apache

   # Install system dependencies
   RUN apt-get update && apt-get install -y \
       git \
       curl \
       libpng-dev \
       libonig-dev \
       libxml2-dev \
       libpq-dev \
       zip \
       unzip \
       nodejs \
       npm

   # Install PHP extensions
   RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

   # Install PostgreSQL extension
   RUN docker-php-ext-install pdo_pgsql pgsql

   # Get latest Composer
   COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

   # Set working directory
   WORKDIR /var/www/html

   # Copy application files
   COPY . /var/www/html

   # Install PHP dependencies
   RUN composer install --no-dev --optimize-autoloader

   # Install Node.js dependencies and build assets
   RUN npm install && npm run build

   # Set permissions
   RUN chown -R www-data:www-data /var/www/html \
       && chmod -R 755 /var/www/html/storage \
       && chmod -R 755 /var/www/html/bootstrap/cache

   # Configure Apache
   RUN a2enmod rewrite
   RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

   # Copy Apache configuration
   COPY <<EOF /etc/apache2/sites-available/000-default.conf
   <VirtualHost *:80>
       ServerAdmin webmaster@localhost
       DocumentRoot /var/www/html/public

       <Directory /var/www/html/public>
           AllowOverride All
           Require all granted
       </Directory>

       ErrorLog \${APACHE_LOG_DIR}/error.log
       CustomLog \${APACHE_LOG_DIR}/access.log combined
   </VirtualHost>
   EOF

   # Expose port
   EXPOSE 80

   # Start Apache
   CMD ["apache2-foreground"]
   ```

2. **Build and Push Container**
   ```bash
   # Build container
   docker build -t gcr.io/YOUR_PROJECT_ID/tpt-erp:latest .

   # Push to Google Container Registry
   docker push gcr.io/YOUR_PROJECT_ID/tpt-erp:latest
   ```

### Step 2: Deploy to Cloud Run

```bash
# Deploy container
gcloud run deploy tpt-erp \
  --image gcr.io/YOUR_PROJECT_ID/tpt-erp:latest \
  --platform managed \
  --region us-central1 \
  --allow-unauthenticated \
  --port 80 \
  --memory 1Gi \
  --cpu 1 \
  --max-instances 10 \
  --min-instances 1 \
  --concurrency 80 \
  --timeout 300 \
  --set-env-vars "APP_ENV=production,APP_KEY=YOUR_APP_KEY,DB_CONNECTION=pgsql,DB_HOST=/cloudsql/YOUR_PROJECT_ID:us-central1:YOUR_INSTANCE_NAME,DB_DATABASE=tpt_erp,DB_USERNAME=tpt_user,DB_PASSWORD=secure_password" \
  --set-cloudsql-instances YOUR_PROJECT_ID:us-central1:YOUR_INSTANCE_NAME \
  --service-account tpt-erp-service@YOUR_PROJECT_ID.iam.gserviceaccount.com
```

### Step 3: Configure Custom Domain

```bash
# Map custom domain
gcloud run domain-mappings create \
  --service tpt-erp \
  --domain your-domain.com \
  --region us-central1

# Verify domain and set up SSL
# SSL certificates are automatically provisioned
```

---

## Database Configuration

### Using Cloud SQL PostgreSQL

1. **Create Cloud SQL Instance**
   ```bash
   gcloud sql instances create tpt-erp-db \
     --database-version=POSTGRES_15 \
     --cpu=2 \
     --memory=4GB \
     --region=us-central1 \
     --root-password=secure_root_password \
     --backup-start-time=03:00
   ```

2. **Create Database and User**
   ```bash
   # Connect to database
   gcloud sql connect tpt-erp-db --user=postgres

   # Create database and user
   CREATE DATABASE tpt_erp;
   CREATE USER tpt_user WITH ENCRYPTED PASSWORD 'secure_password_2025';
   GRANT ALL PRIVILEGES ON DATABASE tpt_erp TO tpt_user;
   ALTER USER tpt_user CREATEDB;
   ```

3. **Configure Database Flags**
   ```bash
   gcloud sql instances patch tpt-erp-db \
     --database-flags max_connections=100,shared_preload_libraries=pg_stat_statements
   ```

4. **Set Up Automated Backups**
   ```bash
   gcloud sql instances patch tpt-erp-db \
     --backup-start-time=03:00 \
     --retained-backups-count=7 \
     --enable-bin-log
   ```

### Using Memorystore (Redis)

1. **Create Memorystore Instance**
   ```bash
   gcloud redis instances create tpt-erp-redis \
     --size=1 \
     --region=us-central1 \
     --redis-version=redis_7_0 \
     --network=default
   ```

2. **Get Instance Details**
   ```bash
   gcloud redis instances describe tpt-erp-redis --region=us-central1
   ```

3. **Configure VPC Peering**
   ```bash
   # Memorystore creates VPC peering automatically
   # Note the private IP address for application configuration
   ```

---

## SSL Configuration

### Using Google Managed SSL

1. **For App Engine**
   ```bash
   # SSL is automatically managed
   # Custom domain SSL certificates are provisioned automatically
   gcloud app ssl-certificates list
   ```

2. **For Cloud Run**
   ```bash
   # SSL is automatically managed for custom domains
   # Certificates are provisioned by Google
   gcloud run domain-mappings describe --domain=your-domain.com
   ```

3. **For Compute Engine**
   ```bash
   # Use Let's Encrypt or Google Managed SSL
   sudo certbot --nginx -d your-domain.com -d www.your-domain.com
   ```

### Custom SSL Certificates

```bash
# Upload custom certificate
gcloud compute ssl-certificates create tpt-erp-ssl \
  --certificate=certificate.pem \
  --private-key=private-key.pem \
  --description="TPT Free ERP SSL Certificate"

# Use with load balancer
gcloud compute target-https-proxies create tpt-erp-proxy \
  --ssl-certificates=tpt-erp-ssl \
  --url-map=tpt-erp-url-map
```

---

## Monitoring and Maintenance

### Google Cloud Monitoring

```bash
# Create uptime check
gcloud monitoring uptime-check-configs create tpt-erp-uptime \
  --display-name="TPT ERP Uptime Check" \
  --http-check-path="/" \
  --http-check-port=443 \
  --monitored-resource-type=uptime_url \
  --resource-labels=host=your-domain.com \
  --timeout=10s

# Create alerting policy
gcloud monitoring policies create \
  --display-name="TPT ERP High Error Rate" \
  --condition-filter="metric.type=\"logging.googleapis.com/log_entry_count\" resource.type=\"gce_instance\" metric.label.\"severity\"=\"ERROR\"" \
  --condition-threshold-value=10 \
  --condition-threshold-duration=300s \
  --notification-channels=your-notification-channel
```

### Application Monitoring

```bash
# Enable Cloud Trace
gcloud services enable cloudtrace.googleapis.com

# Enable Cloud Profiler
gcloud services enable cloudprofiler.googleapis.com

# Set up custom metrics
gcloud monitoring metrics create custom.googleapis.com/tpt_erp/response_time \
  --description="TPT ERP Response Time" \
  --display-name="Response Time" \
  --type=DOUBLE \
  --unit=s
```

### Automated Backups

```bash
# Cloud SQL automated backup
gcloud sql backups create tpt-erp-backup \
  --instance=tpt-erp-db \
  --description="Automated backup"

# Export to Cloud Storage
gcloud sql export sql tpt-erp-db \
  gs://tpt-erp-backups/$(date +%Y%m%d_%H%M%S).sql \
  --database=tpt_erp \
  --offload
```

---

## Troubleshooting

### Common GCP Issues

#### 1. Cloud SQL Connection Issues

```bash
# Check Cloud SQL instance status
gcloud sql instances describe tpt-erp-db

# Test database connectivity
gcloud sql connect tpt-erp-db --user=tpt_user

# Check VPC peering
gcloud compute networks peerings list
```

#### 2. App Engine Deployment Issues

```bash
# Check deployment status
gcloud app versions list

# View deployment logs
gcloud app logs read

# Check service account permissions
gcloud iam service-accounts get-iam-policy tpt-erp-service@YOUR_PROJECT_ID.iam.gserviceaccount.com
```

#### 3. Cloud Run Issues

```bash
# Check service status
gcloud run services describe tpt-erp

# View logs
gcloud logging read "resource.type=cloud_run_revision AND resource.labels.service_name=tpt-erp"

# Check revisions
gcloud run revisions list --service=tpt-erp
```

#### 4. Compute Engine Issues

```bash
# Check instance status
gcloud compute instances describe tpt-erp-production

# View serial console
gcloud compute instances get-serial-port-output tpt-erp-production

# Check startup script logs
gcloud logging read "resource.type=gce_instance AND resource.labels.instance_name=tpt-erp-production"
```

### Performance Issues

#### 1. High Latency

```bash
# Check global load balancer
gcloud compute url-maps describe tpt-erp-url-map

# Analyze Cloud Trace
gcloud trace traces list --filter="tpt-erp"

# Check CDN configuration
gcloud compute backend-buckets describe tpt-erp-cdn
```

#### 2. Memory Issues

```bash
# Check memory usage
gcloud monitoring query "fetch gce_instance::memory/used | filter (resource.instance_name == 'tpt-erp-production')"

# Resize instance
gcloud compute instances set-machine-type tpt-erp-production --machine-type=n2-standard-4
```

#### 3. Database Performance

```bash
# Check Cloud SQL metrics
gcloud monitoring query "fetch cloudsql_database::database/cpu/usage | filter (resource.database_id == 'tpt-erp-db')"

# Enable query insights
gcloud sql instances patch tpt-erp-db --enable-query-insights
```

---

## Cost Optimization

### Pricing Breakdown

| Service | Configuration | Cost/Month |
|---------|---------------|------------|
| Compute Engine (e2-medium) | 2 vCPU, 4GB RAM | $28 |
| Cloud SQL (PostgreSQL) | 2 vCPU, 4GB RAM | $150 |
| Memorystore (Redis) | 1GB cache | $35 |
| Load Balancer | Global HTTP(S) | $18 |
| Cloud Storage | 100GB storage | $3 |
| Cloud Monitoring | Basic monitoring | $4 |
| **Total** | | **$238** |

### Optimization Strategies

#### 1. Committed Use Discounts

```bash
# Purchase committed use contract
gcloud compute commitments create tpt-erp-commitment \
  --region=us-central1 \
  --resources=cpus=4,memory=16GB \
  --plan=12-month \
  --type=COMPUTE_OPTIMIZED
```

#### 2. Sustained Use Discounts

```bash
# Automatic discounts for sustained usage
# No configuration needed - applied automatically
# Save up to 30% for continuous usage
```

#### 3. Preemptible VMs

```bash
# Create preemptible instance
gcloud compute instances create tpt-erp-spot \
  --preemptible \
  --machine-type=e2-medium \
  --maintenance-policy=TERMINATE
```

#### 4. Storage Optimization

```bash
# Use Cloud Storage classes
gsutil lifecycle set lifecycle.json gs://tpt-erp-assets

# Lifecycle configuration (lifecycle.json)
{
  "rule": [
    {
      "action": {"type": "SetStorageClass", "storageClass": "NEARLINE"},
      "condition": {"age": 30}
    }
  ]
}
```

---

## Security Best Practices

### Identity and Access Management

```bash
# Create service account
gcloud iam service-accounts create tpt-erp-service \
  --description="TPT Free ERP Service Account" \
  --display-name="TPT ERP Service"

# Grant minimal permissions
gcloud projects add-iam-policy-binding YOUR_PROJECT_ID \
  --member="serviceAccount:tpt-erp-service@YOUR_PROJECT_ID.iam.gserviceaccount.com" \
  --role="roles/cloudsql.client"

# Use workload identity
gcloud iam service-accounts add-iam-policy-binding tpt-erp-service@YOUR_PROJECT_ID.iam.gserviceaccount.com \
  --member="serviceAccount:YOUR_PROJECT_ID.svc.id.goog[default/tpt-erp]" \
  --role="roles/iam.workloadIdentityUser"
```

### Network Security

```bash
# Create VPC network
gcloud compute networks create tpt-erp-network --subnet-mode=custom

# Create subnet
gcloud compute networks subnets create tpt-erp-subnet \
  --network=tpt-erp-network \
  --region=us-central1 \
  --range=10.0.0.0/24

# Configure firewall rules
gcloud compute firewall-rules create allow-internal \
  --network=tpt-erp-network \
  --allow=tcp,udp,icmp \
  --source-ranges=10.0.0.0/24

gcloud compute firewall-rules create allow-ssh \
  --network=tpt-erp-network \
  --allow=tcp:22 \
  --source-ranges=0.0.0.0/0 \
  --target-tags=ssh
```

### Cloud Armor (WAF)

```bash
# Create security policy
gcloud compute security-policies create tpt-erp-waf \
  --description="TPT Free ERP WAF Policy"

# Add rules
gcloud compute security-policies rules create 1000 \
  --security-policy=tpt-erp-waf \
  --expression="evaluatePreconfiguredExpr('xss-stable')" \
  --action=deny-403 \
  --description="XSS attack filtering"

# Attach to load balancer
gcloud compute backend-services update tpt-erp-backend \
  --security-policy=tpt-erp-waf
```

---

## Next Steps

1. **Domain Configuration**
   - Set up Cloud DNS
   - Configure domain mappings
   - Set up SSL certificates

2. **Backup Strategy**
   - Implement automated Cloud SQL backups
   - Set up cross-region replication
   - Configure Cloud Storage backups

3. **Monitoring Setup**
   - Configure Cloud Monitoring dashboards
   - Set up alerts and notifications
   - Implement log aggregation

4. **Scaling Considerations**
   - Plan for traffic growth
   - Configure auto-scaling policies
   - Consider multi-region deployment

---

## Support Resources

- **Google Cloud Documentation**: https://cloud.google.com/docs
- **Google Cloud Support**: https://cloud.google.com/support
- **TPT Free ERP Issues**: https://github.com/PhillipC05/tpt-free-erp/issues
- **Google Cloud Community**: https://cloud.google.com/community
- **Stack Overflow**: GCP-specific tags

---

*Last Updated: September 10, 2025*
*Version: 1.0*
*Platform: Google Cloud Platform*
