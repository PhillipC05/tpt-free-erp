# TPT Free ERP - Azure Deployment Guide

**Version:** 1.0
**Date:** September 10, 2025
**Platform:** Microsoft Azure
**Author:** Development Team

This comprehensive guide provides step-by-step instructions for deploying TPT Free ERP on Microsoft Azure using various services including Azure VMs, App Service, Container Instances, and Azure Database.

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Deployment Methods](#deployment-methods)
4. [Method 1: Azure VM with Docker](#method-1-azure-vm-with-docker)
5. [Method 2: App Service](#method-2-app-service)
6. [Method 3: Container Instances](#method-3-container-instances)
7. [Database Configuration](#database-configuration)
8. [SSL Configuration](#ssl-configuration)
9. [Monitoring and Maintenance](#monitoring-and-maintenance)
10. [Troubleshooting](#troubleshooting)
11. [Cost Optimization](#cost-optimization)

---

## Overview

### Why Azure?

Azure provides:
- **Strong Windows/.NET integration** for enterprise environments
- **Excellent hybrid cloud capabilities** with Azure Arc
- **Competitive pricing** for Windows workloads
- **Extensive compliance certifications** for regulated industries
- **Global infrastructure** with 60+ regions worldwide
- **Strong integration** with Microsoft 365 and Active Directory

### Deployment Options

| Method | Difficulty | Cost | Scalability | Best For |
|--------|------------|------|-------------|----------|
| Azure VM + Docker | Medium | $$$ | High | Full control, Windows integration |
| App Service | Low | $$$ | Medium | Quick deployment, managed scaling |
| Container Instances | Medium | $$$ | High | Containerized, serverless scaling |

---

## Prerequisites

### Azure Account Setup
- Active Azure subscription with billing enabled
- Azure CLI installed and configured
- Service principal or managed identity (recommended)

### Domain Name (Recommended)
- Domain registered with Azure DNS or any provider
- DNS management access
- SSL certificate (Azure will manage this)

### Local Development Environment
```bash
# Required tools
az --version          # Azure CLI
docker --version     # Docker
docker-compose --version  # Docker Compose
git --version        # Git
ssh -V              # SSH client
```

### Required Azure Permissions
```json
{
    "permissions": [
        {
            "actions": [
                "Microsoft.Compute/*",
                "Microsoft.Network/*",
                "Microsoft.Storage/*",
                "Microsoft.DBforPostgreSQL/*",
                "Microsoft.Web/*",
                "Microsoft.ContainerInstance/*"
            ],
            "notActions": [],
            "dataActions": [],
            "notDataActions": []
        }
    ]
}
```

---

## Deployment Methods

## Method 1: Azure VM with Docker (Recommended for Full Control)

### Step 1: Create Azure VM

1. **Navigate to Azure Portal**
   - Go to Azure Portal → Virtual Machines → Create

2. **Choose Configuration**
   ```
   Resource group: tpt-erp-rg
   VM name: tpt-erp-production
   Region: East US 2
   Image: Ubuntu Server 22.04 LTS
   Size: Standard_B2s (2 vCPUs, 4 GB RAM)
   Recommended: Standard_B4ms (4 vCPUs, 16 GB RAM)
   ```

3. **Configure Authentication**
   - Authentication type: SSH public key
   - Username: azureuser
   - SSH public key: Your public key

4. **Configure Networking**
   - Virtual network: Create new or use existing
   - Subnet: default
   - Public IP: Create new
   - NIC network security group: Advanced

5. **Configure Security**
   - Add inbound security rules:
     - SSH (22) - Your IP
     - HTTP (80) - Any
     - HTTPS (443) - Any
     - PostgreSQL (5432) - Your IP or VNet

6. **Create VM**
   - Review and create

### Step 2: Initial Server Setup

```bash
# Connect to VM
ssh azureuser@YOUR_VM_IP

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

# Add user to docker group
sudo usermod -aG docker azureuser

# Install Azure CLI (optional)
curl -sL https://aka.ms/InstallAzureCLIDeb | sudo bash

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

# Database (configure after Azure Database setup)
DB_CONNECTION=pgsql
DB_HOST=your-db-server.postgres.database.azure.com
DB_PORT=5432
DB_DATABASE=tpt_erp
DB_USERNAME=tpt_user@your-db-server
DB_PASSWORD=secure_password_2025

# Cache & Session (using Azure Cache for Redis)
CACHE_DRIVER=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Redis (using Azure Cache for Redis)
REDIS_HOST=your-redis-cache.redis.cache.windows.net
REDIS_PASSWORD=your-redis-password
REDIS_PORT=6380
REDIS_SSL=true

# Azure Storage (for file storage)
AZURE_STORAGE_ACCOUNT=yourstorageaccount
AZURE_STORAGE_KEY=your-storage-key
AZURE_STORAGE_CONTAINER=uploads

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp-mail.outlook.com
MAIL_PORT=587
MAIL_USERNAME=your-email@outlook.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@outlook.com
MAIL_FROM_NAME="TPT Free ERP"
```

### Step 4: Configure Docker Compose for Azure

```bash
# Create production docker-compose file
nano docker-compose.azure.yml
```

**Azure Docker Compose (docker-compose.azure.yml):**
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
    environment:
      - APACHE_DOCUMENT_ROOT=/var/www/html/public
      - APP_ENV=production
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
      - ./nginx.azure.conf:/etc/nginx/nginx.conf:ro
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

### Step 5: Azure Nginx Configuration

```bash
# Create Azure-specific Nginx configuration
nano nginx.azure.conf
```

**Azure Nginx Configuration (nginx.azure.conf):**
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

        # SSL Configuration (managed by Azure)
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
docker-compose -f docker-compose.azure.yml up -d

# Run database migrations
docker-compose -f docker-compose.azure.yml exec web php phinx migrate

# Seed the database (optional)
docker-compose -f docker-compose.azure.yml exec web php phinx seed:run

# Check if services are running
docker-compose -f docker-compose.azure.yml ps

# View logs
docker-compose -f docker-compose.azure.yml logs -f
```

---

## Method 2: App Service (Managed)

### Step 1: Create App Service Plan

```bash
# Create resource group
az group create --name tpt-erp-rg --location eastus2

# Create App Service plan
az appservice plan create \
  --name tpt-erp-plan \
  --resource-group tpt-erp-rg \
  --sku B2 \
  --is-linux
```

### Step 2: Create Web App

```bash
# Create web app
az webapp create \
  --name tpt-erp-app \
  --resource-group tpt-erp-rg \
  --plan tpt-erp-plan \
  --runtime "PHP|8.1" \
  --deployment-local-git
```

### Step 3: Configure Application Settings

```bash
# Configure environment variables
az webapp config appsettings set \
  --name tpt-erp-app \
  --resource-group tpt-erp-rg \
  --setting APP_ENV=production \
  --setting APP_KEY=base64:YOUR_APP_KEY_HERE \
  --setting DB_CONNECTION=pgsql \
  --setting DB_HOST=your-db-server.postgres.database.azure.com \
  --setting DB_DATABASE=tpt_erp \
  --setting DB_USERNAME=tpt_user@your-db-server \
  --setting DB_PASSWORD=secure_password_2025

# Configure PHP settings
az webapp config set \
  --name tpt-erp-app \
  --resource-group tpt-erp-rg \
  --php-version 8.1 \
  --linux-fx-version "PHP|8.1"
```

### Step 4: Deploy Application

```bash
# Get deployment URL
az webapp deployment source config-local-git \
  --name tpt-erp-app \
  --resource-group tpt-erp-rg

# Deploy via Git
git remote add azure <deployment-url>
git push azure main
```

### Step 5: Configure Custom Domain and SSL

```bash
# Add custom domain
az webapp config hostname set \
  --webapp-name tpt-erp-app \
  --resource-group tpt-erp-rg \
  --hostname your-domain.com

# Enable SSL
az webapp config ssl create \
  --name tpt-erp-app \
  --resource-group tpt-erp-rg \
  --hostname your-domain.com
```

---

## Method 3: Container Instances (Serverless)

### Step 1: Create Azure Container Registry

```bash
# Create container registry
az acr create \
  --resource-group tpt-erp-rg \
  --name tpterpacr \
  --sku Basic

# Login to registry
az acr login --name tpterpacr
```

### Step 2: Build and Push Container

```bash
# Build container
docker build -t tpt-erp .

# Tag container
docker tag tpt-erp tpterpacr.azurecr.io/tpt-erp:latest

# Push to registry
docker push tpterpacr.azurecr.io/tpt-erp:latest
```

### Step 3: Create Container Instance

```bash
# Create container instance
az container create \
  --resource-group tpt-erp-rg \
  --name tpt-erp-container \
  --image tpterpacr.azurecr.io/tpt-erp:latest \
  --cpu 1 \
  --memory 1.5 \
  --registry-login-server tpterpacr.azurecr.io \
  --registry-username $(az acr credential show --name tpterpacr --query username -o tsv) \
  --registry-password $(az acr credential show --name tpterpacr --query passwords[0].value -o tsv) \
  --environment-variables APP_ENV=production APP_KEY=base64:YOUR_APP_KEY_HERE \
  --ports 80 \
  --dns-name-label tpt-erp-app \
  --query ipAddress.fqdn
```

### Step 4: Configure Environment Variables

```bash
# Update container with database configuration
az container attach \
  --resource-group tpt-erp-rg \
  --name tpt-erp-container \
  --container-name tpt-erp-container
```

---

## Database Configuration

### Using Azure Database for PostgreSQL

1. **Create Azure Database for PostgreSQL**
   ```bash
   az postgres server create \
     --name tpt-erp-db \
     --resource-group tpt-erp-rg \
     --location eastus2 \
     --admin-user tptadmin \
     --admin-password secure_password_2025 \
     --sku-name B_Gen5_1 \
     --version 15 \
     --storage-size 5120
   ```

2. **Configure Firewall Rules**
   ```bash
   # Allow access from Azure services
   az postgres server firewall-rule create \
     --resource-group tpt-erp-rg \
     --server-name tpt-erp-db \
     --name AllowAllAzureIps \
     --start-ip-address 0.0.0.0 \
     --end-ip-address 0.0.0.0

   # Allow access from your IP
   az postgres server firewall-rule create \
     --resource-group tpt-erp-rg \
     --server-name tpt-erp-db \
     --name AllowMyIP \
     --start-ip-address YOUR_IP \
     --end-ip-address YOUR_IP
   ```

3. **Create Database and User**
   ```bash
   # Connect to database
   psql -h tpt-erp-db.postgres.database.azure.com -U tptadmin@tpt-erp-db -d postgres

   # Create database and user
   CREATE DATABASE tpt_erp;
   CREATE USER tpt_user WITH ENCRYPTED PASSWORD 'secure_password_2025';
   GRANT ALL PRIVILEGES ON DATABASE tpt_erp TO tpt_user;
   ```

4. **Configure Server Parameters**
   ```bash
   az postgres server configuration set \
     --name shared_preload_libraries \
     --resource-group tpt-erp-rg \
     --server-name tpt-erp-db \
     --value pg_stat_statements

   az postgres server configuration set \
     --name max_connections \
     --resource-group tpt-erp-rg \
     --server-name tpt-erp-db \
     --value 100
   ```

### Using Azure Cache for Redis

1. **Create Azure Cache for Redis**
   ```bash
   az redis create \
     --name tpt-erp-redis \
     --resource-group tpt-erp-rg \
     --location eastus2 \
     --sku Basic \
     --vm-size C1
   ```

2. **Get Connection Information**
   ```bash
   az redis show \
     --name tpt-erp-redis \
     --resource-group tpt-erp-rg \
     --query [hostName,sslPort,primaryKey]
   ```

3. **Configure Access Keys**
   ```bash
   # Get access keys
   az redis list-keys \
     --name tpt-erp-redis \
     --resource-group tpt-erp-rg
   ```

---

## SSL Configuration

### Using Azure App Service SSL

1. **For App Service**
   ```bash
   # SSL is automatically managed
   # Custom domain SSL certificates are provisioned automatically
   az webapp ssl list --resource-group tpt-erp-rg --name tpt-erp-app
   ```

2. **For Azure Front Door**
   ```bash
   # Create Front Door profile
   az afd profile create \
     --profile-name tpt-erp-frontdoor \
     --resource-group tpt-erp-rg \
     --sku Standard_AzureFrontDoor

   # Add custom domain with SSL
   az afd custom-domain create \
     --custom-domain-name your-domain \
     --profile-name tpt-erp-frontdoor \
     --resource-group tpt-erp-rg \
     --host-name your-domain.com
   ```

3. **For Application Gateway**
   ```bash
   # Create application gateway with WAF and SSL
   az network application-gateway create \
     --name tpt-erp-gateway \
     --resource-group tpt-erp-rg \
     --location eastus2 \
     --capacity 2 \
     --sku WAF_v2 \
     --http-settings-protocol Https \
     --cert-file certificate.pfx \
     --cert-password certificate-password
   ```

### Custom SSL Certificates

```bash
# Upload custom certificate
az webapp config ssl upload \
  --name tpt-erp-app \
  --resource-group tpt-erp-rg \
  --certificate-file certificate.pfx \
  --certificate-password password

# Bind certificate to domain
az webapp config ssl bind \
  --name tpt-erp-app \
  --resource-group tpt-erp-rg \
  --certificate-thumbprint thumbprint \
  --ssl-type SNI
```

---

## Monitoring and Maintenance

### Azure Monitor

```bash
# Create Application Insights
az monitor app-insights component create \
  --app tpt-erp-insights \
  --location eastus2 \
  --resource-group tpt-erp-rg \
  --application-type web

# Configure monitoring
az monitor diagnostic-settings create \
  --name tpt-erp-diagnostics \
  --resource /subscriptions/SUBSCRIPTION_ID/resourceGroups/tpt-erp-rg/providers/Microsoft.Web/sites/tpt-erp-app \
  --logs '[{"category": "AppServiceHTTPLogs", "enabled": true}]' \
  --metrics '[{"category": "AllMetrics", "enabled": true}]' \
  --workspace /subscriptions/SUBSCRIPTION_ID/resourceGroups/DefaultResourceGroup/providers/Microsoft.OperationalInsights/workspaces/DefaultWorkspace
```

### Azure Application Insights

```bash
# Enable Application Insights
az webapp config appsettings set \
  --name tpt-erp-app \
  --resource-group tpt-erp-rg \
  --setting APPINSIGHTS_INSTRUMENTATIONKEY=your-instrumentation-key \
  --setting APPLICATIONINSIGHTS_CONNECTION_STRING=your-connection-string
```

### Automated Backups

```bash
# Database automated backup
az postgres server update \
  --name tpt-erp-db \
  --resource-group tpt-erp-rg \
  --backup-retention-days 7

# Storage account backup
az backup protection enable-for-vm \
  --resource-group tpt-erp-rg \
  --vm tpt-erp-production \
  --policy-name DefaultPolicy
```

---

## Troubleshooting

### Common Azure Issues

#### 1. Azure Database Connection Issues

```bash
# Check database firewall rules
az postgres server firewall-rule list \
  --resource-group tpt-erp-rg \
  --server-name tpt-erp-db

# Test database connectivity
psql -h tpt-erp-db.postgres.database.azure.com -U tpt_user@tpt-erp-db -d tpt_erp

# Check database status
az postgres server show \
  --resource-group tpt-erp-rg \
  --name tpt-erp-db
```

#### 2. App Service Deployment Issues

```bash
# Check deployment logs
az webapp log download \
  --name tpt-erp-app \
  --resource-group tpt-erp-rg

# View application logs
az webapp log tail \
  --name tpt-erp-app \
  --resource-group tpt-erp-rg

# Check deployment status
az webapp deployment list-publishing-profiles \
  --name tpt-erp-app \
  --resource-group tpt-erp-rg
```

#### 3. Container Instance Issues

```bash
# Check container status
az container show \
  --resource-group tpt-erp-rg \
  --name tpt-erp-container

# View container logs
az container logs \
  --resource-group tpt-erp-rg \
  --name tpt-erp-container

# Restart container
az container restart \
  --resource-group tpt-erp-rg \
  --name tpt-erp-container
```

#### 4. VM Issues

```bash
# Check VM status
az vm show \
  --resource-group tpt-erp-rg \
  --name tpt-erp-production

# View boot diagnostics
az vm boot-diagnostics get-boot-log \
  --resource-group tpt-erp-rg \
  --name tpt-erp-production

# Check VM extensions
az vm extension list \
  --resource-group tpt-erp-rg \
  --name tpt-erp-production
```

### Performance Issues

#### 1. High CPU Usage

```bash
# Check Azure Monitor metrics
az monitor metrics list \
  --resource /subscriptions/SUBSCRIPTION_ID/resourceGroups/tpt-erp-rg/providers/Microsoft.Compute/virtualMachines/tpt-erp-production \
  --metric "Percentage CPU"

# Resize VM
az vm resize \
  --resource-group tpt-erp-rg \
  --name tpt-erp-production \
  --size Standard_B4ms
```

#### 2. Memory Issues

```bash
# Check memory usage
az monitor metrics list \
  --resource /subscriptions/SUBSCRIPTION_ID/resourceGroups/tpt-erp-rg/providers/Microsoft.Compute/virtualMachines/tpt-erp-production \
  --metric "Available Memory Bytes"

# Add memory
az vm resize \
  --resource-group tpt-erp-rg \
  --name tpt-erp-production \
  --size Standard_B4ms
```

#### 3. Database Performance

```bash
# Check database metrics
az monitor metrics list \
  --resource /subscriptions/SUBSCRIPTION_ID/resourceGroups/tpt-erp-rg/providers/Microsoft.DBforPostgreSQL/servers/tpt-erp-db \
  --metric "cpu_percent"

# Scale database
az postgres server update \
  --name tpt-erp-db \
  --resource-group tpt-erp-rg \
  --sku-name GP_Gen5_2
```

---

## Cost Optimization

### Pricing Breakdown

| Service | Configuration | Cost/Month |
|---------|---------------|------------|
| Azure VM (B2s) | 2 vCPU, 4GB RAM | $29 |
| Azure Database for PostgreSQL | Basic, 2 vCPU | $150 |
| Azure Cache for Redis | Basic C1 | $35 |
| Load Balancer | Basic | $20 |
| Azure Storage | 100GB storage | $3 |
| Azure Monitor | Basic monitoring | $6 |
| **Total** | | **$243** |

### Optimization Strategies

#### 1. Reserved Instances

```bash
# Purchase reserved instance
az vm reservation create \
  --location eastus2 \
  --vm-family Standard_B \
  --term P1Y \
  --quantity 1
```

#### 2. Azure Hybrid Benefit

```bash
# Apply hybrid benefit for Windows workloads
az vm update \
  --resource-group tpt-erp-rg \
  --name tpt-erp-production \
  --license-type Windows_Server
```

#### 3. Spot Instances

```bash
# Create spot instance
az vm create \
  --resource-group tpt-erp-rg \
  --name tpt-erp-spot \
  --image Ubuntu2204 \
  --size Standard_B2s \
  --priority Spot \
  --eviction-policy Deallocate \
  --max-price -1
```

#### 4. Auto-shutdown

```bash
# Configure auto-shutdown for development environments
az vm auto-shutdown \
  --resource-group tpt-erp-rg \
  --name tpt-erp-production \
  --time 1800
```

---

## Security Best Practices

### Azure Active Directory Integration

```bash
# Create service principal
az ad sp create-for-rbac \
  --name tpt-erp-sp \
  --role Contributor \
  --scopes /subscriptions/SUBSCRIPTION_ID

# Configure managed identity
az webapp identity assign \
  --name tpt-erp-app \
  --resource-group tpt-erp-rg
```

### Network Security

```bash
# Create network security group
az network nsg create \
  --resource-group tpt-erp-rg \
  --name tpt-erp-nsg

# Add security rules
az network nsg rule create \
  --resource-group tpt-erp-rg \
  --nsg-name tpt-erp-nsg \
  --name AllowSSH \
  --priority 100 \
  --destination-port-ranges 22 \
  --access Allow \
  --protocol Tcp

# Create virtual network
az network vnet create \
  --resource-group tpt-erp-rg \
  --name tpt-erp-vnet \
  --address-prefix 10.0.0.0/16 \
  --subnet-name default \
  --subnet-prefix 10.0.0.0/24
```

### Azure Security Center

```bash
# Enable Security Center
az security pricing create \
  --name VirtualMachines \
  --tier Standard

# Configure security policies
az security policy create \
  --name tpt-erp-security-policy \
  --resource-group tpt-erp-rg \
  --policy-file security-policy.json
```

### Azure Key Vault

```bash
# Create Key Vault
az keyvault create \
  --name tpt-erp-keyvault \
  --resource-group tpt-erp-rg \
  --location eastus2

# Store secrets
az keyvault secret set \
  --vault-name tpt-erp-keyvault \
  --name db-password \
  --value secure_password_2025

# Configure access policies
az keyvault set-policy \
  --name tpt-erp-keyvault \
  --object-id OBJECT_ID \
  --secret-permissions get list
```

---

## Next Steps

1. **Domain Configuration**
   - Set up Azure DNS
   - Configure domain mappings
   - Set up SSL certificates

2. **Backup Strategy**
   - Implement automated Azure Database backups
   - Set up cross-region replication
   - Configure Azure Storage backups

3. **Monitoring Setup**
   - Configure Azure Monitor dashboards
   - Set up alerts and notifications
   - Implement log aggregation

4. **Scaling Considerations**
   - Plan for traffic growth
   - Configure auto-scaling policies
   - Consider multi-region deployment

---

## Support Resources

- **Azure Documentation**: https://docs.microsoft.com/en-us/azure/
- **Azure Support**: https://azure.microsoft.com/en-us/support/
- **TPT Free ERP Issues**: https://github.com/PhillipC05/tpt-free-erp/issues
- **Azure Community**: https://techcommunity.microsoft.com/
- **Azure Blogs**: https://azure.microsoft.com/en-us/blog/

---

*Last Updated: September 10, 2025*
*Version: 1.0*
*Platform: Microsoft Azure*
