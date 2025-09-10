# TPT Free ERP - Docker Deployment Guide

**Version:** 1.0
**Date:** September 10, 2025
**Platform:** Docker
**Author:** Development Team

This comprehensive guide provides step-by-step instructions for deploying TPT Free ERP using Docker containers, covering single-container, multi-container, and orchestration scenarios.

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Quick Start](#quick-start)
4. [Single Container Deployment](#single-container-deployment)
5. [Multi-Container with Docker Compose](#multi-container-with-docker-compose)
6. [Production Docker Setup](#production-docker-setup)
7. [Docker Swarm Orchestration](#docker-swarm-orchestration)
8. [Kubernetes Deployment](#kubernetes-deployment)
9. [Monitoring Docker Containers](#monitoring-docker-containers)
10. [Troubleshooting](#troubleshooting)
11. [Best Practices](#best-practices)

---

## Overview

### Why Docker?

Docker provides:
- **Portability**: Run anywhere with consistent environments
- **Isolation**: Containerized applications with resource limits
- **Scalability**: Easy horizontal scaling with orchestration
- **Version Control**: Immutable container images
- **Rapid Deployment**: Quick setup and teardown
- **Resource Efficiency**: Lightweight compared to VMs

### Docker Architecture Options

| Method | Complexity | Scalability | Use Case |
|--------|------------|-------------|----------|
| Single Container | ⭐ | ⭐ | Development, simple apps |
| Docker Compose | ⭐⭐⭐ | ⭐⭐⭐ | Multi-service applications |
| Docker Swarm | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | Production orchestration |
| Kubernetes | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | Enterprise orchestration |

---

## Prerequisites

### System Requirements

```bash
# Minimum Requirements
OS: Linux, macOS, or Windows 10/11
RAM: 4GB minimum, 8GB recommended
CPU: 2 cores minimum, 4 cores recommended
Storage: 20GB free space
```

### Docker Installation

#### Ubuntu/Debian
```bash
# Update package index
sudo apt update

# Install required packages
sudo apt install apt-transport-https ca-certificates curl gnupg lsb-release

# Add Docker's official GPG key
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

# Add Docker repository
echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Install Docker
sudo apt update
sudo apt install docker-ce docker-ce-cli containerd.io docker-compose-plugin

# Start Docker service
sudo systemctl start docker
sudo systemctl enable docker

# Add user to docker group (optional)
sudo usermod -aG docker $USER
```

#### CentOS/RHEL
```bash
# Install Docker
sudo yum install -y yum-utils
sudo yum-config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo
sudo yum install docker-ce docker-ce-cli containerd.io docker-compose-plugin

# Start Docker service
sudo systemctl start docker
sudo systemctl enable docker
```

#### macOS (using Homebrew)
```bash
# Install Docker Desktop
brew install --cask docker

# Or install Docker CLI only
brew install docker docker-compose
```

#### Windows
```powershell
# Install Docker Desktop from official website
# Or use Chocolatey
choco install docker-desktop
```

### Docker Verification

```bash
# Check Docker version
docker --version
docker-compose --version

# Test Docker installation
docker run hello-world

# Check Docker info
docker info
```

---

## Quick Start

### One-Command Deployment

```bash
# Clone repository
git clone https://github.com/PhillipC05/tpt-free-erp.git
cd tpt-free-erp

# Start with Docker Compose
docker-compose up -d

# Access application
# Web: http://localhost
# Database: localhost:5432
```

### Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Edit configuration
nano .env
```

**Basic .env Configuration:**
```env
# Application
APP_NAME="TPT Free ERP"
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_URL=http://localhost

# Database
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=tpt_erp
DB_USERNAME=tpt_user
DB_PASSWORD=secure_password_2025

# Cache & Session
CACHE_DRIVER=file
SESSION_DRIVER=file
```

---

## Single Container Deployment

### Custom Dockerfile

```dockerfile
# Use PHP 8.1 with Apache
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
    npm \
    postgresql-client \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql pdo_mysql mbstring exif pcntl bcmath gd

# Enable Apache modules
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Install PHP dependencies
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader

# Install Node.js dependencies and build assets
RUN npm install && npm run build

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Configure Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
RUN echo '<VirtualHost *:80>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Start Apache
CMD ["apache2-foreground"]
```

### Build and Run Single Container

```bash
# Build image
docker build -t tpt-erp:latest .

# Run container
docker run -d \
  --name tpt-erp-app \
  -p 80:80 \
  -v $(pwd)/storage:/var/www/html/storage \
  -e APP_ENV=production \
  -e APP_KEY=base64:YOUR_APP_KEY_HERE \
  -e DB_CONNECTION=pgsql \
  -e DB_HOST=host.docker.internal \
  -e DB_DATABASE=tpt_erp \
  -e DB_USERNAME=tpt_user \
  -e DB_PASSWORD=secure_password_2025 \
  tpt-erp:latest

# Check logs
docker logs -f tpt-erp-app

# Access application
curl http://localhost
```

---

## Multi-Container with Docker Compose

### Complete Docker Compose Setup

```yaml
version: '3.8'

services:
  # Web Application
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
    depends_on:
      - db
      - redis
    restart: unless-stopped
    networks:
      - tpt-network

  # PostgreSQL Database
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
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U tpt_user -d tpt_erp"]
      interval: 30s
      timeout: 10s
      retries: 3

  # Redis Cache
  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    restart: unless-stopped
    networks:
      - tpt-network
    command: redis-server --appendonly yes
    volumes:
      - redis_data:/data

  # Nginx Reverse Proxy (Optional)
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

  # Database Backup Service
  backup:
    image: postgres:15
    depends_on:
      - db
    volumes:
      - ./backups:/backups
      - postgres_data:/var/lib/postgresql/data
    command: >
      bash -c "
        while true; do
          pg_dump -h db -U tpt_user tpt_erp > /backups/backup_$(date +%Y%m%d_%H%M%S).sql
          sleep 86400
        done
      "
    networks:
      - tpt-network

volumes:
  postgres_data:
    driver: local
  redis_data:
    driver: local

networks:
  tpt-network:
    driver: bridge
```

### Docker Compose Commands

```bash
# Start all services
docker-compose up -d

# View service status
docker-compose ps

# View logs
docker-compose logs -f

# View specific service logs
docker-compose logs -f web

# Stop all services
docker-compose down

# Rebuild and restart
docker-compose up -d --build

# Scale services
docker-compose up -d --scale web=3

# Execute commands in containers
docker-compose exec web php artisan migrate
docker-compose exec db psql -U tpt_user -d tpt_erp
```

---

## Production Docker Setup

### Production Dockerfile

```dockerfile
# Multi-stage build for production
FROM node:18-alpine as frontend

WORKDIR /app
COPY package*.json ./
RUN npm ci --only=production

COPY . .
RUN npm run build

# Production stage
FROM php:8.1-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    postgresql-client \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql pdo_mysql mbstring exif pcntl bcmath gd opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure PHP for production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=7963" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/opcache.ini

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html
COPY --from=frontend /app/public/build /var/www/html/public/build

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache \
    && chmod 644 /var/www/html/.env

# Configure Apache
RUN a2enmod rewrite headers ssl
RUN echo "ServerTokens Prod" >> /etc/apache2/apache2.conf \
    && echo "ServerSignature Off" >> /etc/apache2/apache2.conf

# Security headers
RUN echo '<VirtualHost *:80>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
        Header always set X-Frame-Options DENY\n\
        Header always set X-Content-Type-Options nosniff\n\
        Header always set X-XSS-Protection "1; mode=block"\n\
        Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# Use non-root user
USER www-data

# Start Apache
CMD ["apache2-foreground"]
```

### Production Docker Compose

```yaml
version: '3.8'

services:
  web:
    build:
      context: .
      dockerfile: Dockerfile.prod
    ports:
      - "80:80"
    environment:
      - APP_ENV=production
      - APP_KEY=${APP_KEY}
      - DB_CONNECTION=pgsql
      - DB_HOST=db
      - DB_DATABASE=tpt_erp
      - DB_USERNAME=tpt_user
      - DB_PASSWORD=${DB_PASSWORD}
      - REDIS_HOST=redis
    depends_on:
      db:
        condition: service_healthy
      redis:
        condition: service_started
    restart: unless-stopped
    networks:
      - tpt-network
    deploy:
      resources:
        limits:
          cpus: '1.0'
          memory: 1G
        reservations:
          cpus: '0.5'
          memory: 512M

  db:
    image: postgres:15
    environment:
      POSTGRES_DB: tpt_erp
      POSTGRES_USER: tpt_user
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    volumes:
      - postgres_data:/var/lib/postgresql/data
    restart: unless-stopped
    networks:
      - tpt-network
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U tpt_user -d tpt_erp"]
      interval: 30s
      timeout: 10s
      retries: 3
    deploy:
      resources:
        limits:
          cpus: '1.0'
          memory: 2G

  redis:
    image: redis:7-alpine
    restart: unless-stopped
    networks:
      - tpt-network
    command: redis-server --appendonly yes --maxmemory 256mb --maxmemory-policy allkeys-lru
    deploy:
      resources:
        limits:
          cpus: '0.5'
          memory: 512M

volumes:
  postgres_data:
    driver: local

networks:
  tpt-network:
    driver: bridge
```

---

## Docker Swarm Orchestration

### Initialize Docker Swarm

```bash
# Initialize swarm on manager node
docker swarm init

# Get join token for worker nodes
docker swarm join-token worker

# Join worker nodes to swarm
docker swarm join --token <token> <manager-ip>:2377
```

### Docker Swarm Stack

```yaml
version: '3.8'

services:
  web:
    image: tpt-erp:latest
    ports:
      - "80:80"
      - "443:443"
    environment:
      - APP_ENV=production
      - DB_CONNECTION=pgsql
      - DB_HOST=db
    networks:
      - tpt-network
    deploy:
      mode: replicated
      replicas: 3
      restart_policy:
        condition: on-failure
      resources:
        limits:
          cpus: '1.0'
          memory: 1G
      labels:
        - "traefik.enable=true"
        - "traefik.http.routers.web.rule=Host(`tpt-erp.com`)"
        - "traefik.http.routers.web.entrypoints=websecure"
        - "traefik.http.routers.web.tls.certresolver=letsencrypt"
      placement:
        constraints:
          - node.role != manager

  db:
    image: postgres:15
    environment:
      POSTGRES_DB: tpt_erp
      POSTGRES_USER: tpt_user
      POSTGRES_PASSWORD: secure_password_2025
    volumes:
      - postgres_data:/var/lib/postgresql/data
    networks:
      - tpt-network
    deploy:
      mode: replicated
      replicas: 1
      placement:
        constraints:
          - node.role == manager

  redis:
    image: redis:7-alpine
    networks:
      - tpt-network
    deploy:
      mode: replicated
      replicas: 1

  traefik:
    image: traefik:v2.5
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock
      - ./traefik.yml:/etc/traefik/traefik.yml
    networks:
      - tpt-network
    deploy:
      mode: replicated
      replicas: 1
      placement:
        constraints:
          - node.role == manager

volumes:
  postgres_data:
    driver: local

networks:
  tpt-network:
    driver: overlay
```

### Deploy to Swarm

```bash
# Deploy stack
docker stack deploy -c docker-compose.swarm.yml tpt-erp

# List services
docker stack services tpt-erp

# Scale services
docker service scale tpt-erp_web=5

# View logs
docker service logs tpt-erp_web

# Update services
docker service update --image tpt-erp:v2.0 tpt-erp_web
```

---

## Kubernetes Deployment

### Kubernetes Manifests

```yaml
# Namespace
apiVersion: v1
kind: Namespace
metadata:
  name: tpt-erp

---
# ConfigMap for application configuration
apiVersion: v1
kind: ConfigMap
metadata:
  name: tpt-erp-config
  namespace: tpt-erp
data:
  APP_ENV: "production"
  APP_DEBUG: "false"
  DB_CONNECTION: "pgsql"

---
# Secret for sensitive data
apiVersion: v1
kind: Secret
metadata:
  name: tpt-erp-secret
  namespace: tpt-erp
type: Opaque
data:
  app-key: <base64-encoded-key>
  db-password: <base64-encoded-password>

---
# PostgreSQL Deployment
apiVersion: apps/v1
kind: Deployment
metadata:
  name: postgres
  namespace: tpt-erp
spec:
  replicas: 1
  selector:
    matchLabels:
      app: postgres
  template:
    metadata:
      labels:
        app: postgres
    spec:
      containers:
      - name: postgres
        image: postgres:15
        env:
        - name: POSTGRES_DB
          value: "tpt_erp"
        - name: POSTGRES_USER
          value: "tpt_user"
        - name: POSTGRES_PASSWORD
          valueFrom:
            secretKeyRef:
              name: tpt-erp-secret
              key: db-password
        ports:
        - containerPort: 5432
        volumeMounts:
        - name: postgres-storage
          mountPath: /var/lib/postgresql/data
      volumes:
      - name: postgres-storage
        persistentVolumeClaim:
          claimName: postgres-pvc

---
# PostgreSQL Service
apiVersion: v1
kind: Service
metadata:
  name: postgres
  namespace: tpt-erp
spec:
  selector:
    app: postgres
  ports:
  - port: 5432
    targetPort: 5432

---
# TPT Free ERP Deployment
apiVersion: apps/v1
kind: Deployment
metadata:
  name: tpt-erp
  namespace: tpt-erp
spec:
  replicas: 3
  selector:
    matchLabels:
      app: tpt-erp
  template:
    metadata:
      labels:
        app: tpt-erp
    spec:
      containers:
      - name: tpt-erp
        image: tpt-erp:latest
        ports:
        - containerPort: 80
        env:
        - name: APP_KEY
          valueFrom:
            secretKeyRef:
              name: tpt-erp-secret
              key: app-key
        - name: DB_HOST
          value: "postgres"
        - name: DB_PASSWORD
          valueFrom:
            secretKeyRef:
              name: tpt-erp-secret
              key: db-password
        envFrom:
        - configMapRef:
            name: tpt-erp-config
        resources:
          requests:
            memory: "512Mi"
            cpu: "500m"
          limits:
            memory: "1Gi"
            cpu: "1000m"
        livenessProbe:
          httpGet:
            path: /health
            port: 80
          initialDelaySeconds: 30
          periodSeconds: 10
        readinessProbe:
          httpGet:
            path: /health
            port: 80
          initialDelaySeconds: 5
          periodSeconds: 5

---
# TPT Free ERP Service
apiVersion: v1
kind: Service
metadata:
  name: tpt-erp
  namespace: tpt-erp
spec:
  selector:
    app: tpt-erp
  ports:
  - port: 80
    targetPort: 80
  type: LoadBalancer

---
# Ingress for external access
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: tpt-erp-ingress
  namespace: tpt-erp
  annotations:
    nginx.ingress.kubernetes.io/rewrite-target: /
    cert-manager.io/cluster-issuer: "letsencrypt-prod"
spec:
  ingressClassName: nginx
  tls:
  - hosts:
    - tpt-erp.com
    secretName: tpt-erp-tls
  rules:
  - host: tpt-erp.com
    http:
      paths:
      - path: /
        pathType: Prefix
        backend:
          service:
            name: tpt-erp
            port:
              number: 80

---
# Persistent Volume Claim
apiVersion: v1
kind: PersistentVolumeClaim
metadata:
  name: postgres-pvc
  namespace: tpt-erp
spec:
  accessModes:
    - ReadWriteOnce
  resources:
    requests:
      storage: 50Gi
```

### Deploy to Kubernetes

```bash
# Create namespace
kubectl create namespace tpt-erp

# Apply manifests
kubectl apply -f k8s/

# Check deployment status
kubectl get pods -n tpt-erp
kubectl get services -n tpt-erp

# View logs
kubectl logs -f deployment/tpt-erp -n tpt-erp

# Scale deployment
kubectl scale deployment tpt-erp --replicas=5 -n tpt-erp

# Update deployment
kubectl set image deployment/tpt-erp tpt-erp=tpt-erp:v2.0 -n tpt-erp
```

---

## Monitoring Docker Containers

### Docker Monitoring Tools

```bash
# Docker stats
docker stats

# Container resource usage
docker stats --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}"

# System monitoring
docker system df
docker system info
```

### Prometheus + Grafana Setup

```yaml
version: '3.8'

services:
  prometheus:
    image: prom/prometheus
    ports:
      - "9090:9090"
    volumes:
      - ./prometheus.yml:/etc/prometheus/prometheus.yml
      - prometheus_data:/prometheus
    networks:
      - monitoring

  grafana:
    image: grafana/grafana
    ports:
      - "3000:3000"
    environment:
      - GF_SECURITY_ADMIN_PASSWORD=admin
    volumes:
      - grafana_data:/var/lib/grafana
    networks:
      - monitoring

  node-exporter:
    image: prom/node-exporter
    ports:
      - "9100:9100"
    networks:
      - monitoring

volumes:
  prometheus_data:
  grafana_data:

networks:
  monitoring:
    driver: bridge
```

### cAdvisor for Container Metrics

```bash
# Run cAdvisor
docker run \
  --volume=/:/rootfs:ro \
  --volume=/var/run:/var/run:ro \
  --volume=/sys:/sys:ro \
  --volume=/var/lib/docker/:/var/lib/docker:ro \
  --volume=/dev/disk/:/dev/disk:ro \
  --publish=8080:8080 \
  --detach=true \
  --name=cadvisor \
  google/cadvisor:latest
```

---

## Troubleshooting

### Common Docker Issues

#### 1. Container Won't Start

```bash
# Check container logs
docker logs <container-name>

# Check container status
docker ps -a

# Inspect container
docker inspect <container-name>

# Check resource limits
docker stats <container-name>
```

#### 2. Port Conflicts

```bash
# Check port usage
docker ps --format "table {{.Names}}\t{{.Ports}}"
netstat -tulpn | grep :80

# Stop conflicting containers
docker stop <container-name>
docker rm <container-name>
```

#### 3. Permission Issues

```bash
# Fix file permissions
docker exec -it <container-name> chown -R www-data:www-data /var/www/html

# Check volume mounts
docker inspect <container-name> | grep -A 10 Mounts
```

#### 4. Database Connection Issues

```bash
# Test database connection from container
docker exec -it <web-container> php artisan tinker
DB::connection()->getPdo();

# Check network connectivity
docker exec -it <web-container> ping db

# Check environment variables
docker exec -it <web-container> env | grep DB_
```

### Docker Compose Issues

#### 1. Service Dependencies

```bash
# Check service health
docker-compose ps

# Restart specific service
docker-compose restart web

# Rebuild and restart
docker-compose up -d --build web
```

#### 2. Volume Issues

```bash
# Check volume mounts
docker volume ls
docker volume inspect <volume-name>

# Clean up volumes
docker-compose down -v
docker volume prune
```

### Performance Issues

#### 1. High Memory Usage

```bash
# Check container memory
docker stats --format "table {{.Container}}\t{{.MemUsage}}\t{{.MemPerc}}"

# Limit container memory
docker update --memory=1g --memory-swap=2g <container-name>
```

#### 2. High CPU Usage

```bash
# Check container CPU
docker stats --format "table {{.Container}}\t{{.CPUPerc}}"

# Limit container CPU
docker update --cpus=1.0 <container-name>
```

#### 3. Slow Application

```bash
# Check PHP-FPM status
docker exec -it <web-container> php-fpm -t

# Enable OPcache
docker exec -it <web-container> php -r "var_dump(opcache_get_status());"

# Check database performance
docker exec -it <db-container> psql -U tpt_user -d tpt_erp -c "SELECT * FROM pg_stat_activity;"
```

---

## Best Practices

### Security Best Practices

```dockerfile
# Use non-root user
RUN useradd -r -s /bin/false appuser
USER appuser

# Minimize attack surface
RUN apt-get remove --purge -y curl wget && \
    apt-get autoremove -y && \
    apt-get clean

# Use specific versions
FROM php:8.1.15-apache

# Scan for vulnerabilities
RUN apt-get update && apt-get install -y \
    clamav \
    && freshclam \
    && clamscan --version
```

### Performance Optimization

```dockerfile
# Multi-stage builds
FROM node:18-alpine as builder
# Build frontend
FROM php:8.1-apache
# Copy built assets

# Use .dockerignore
node_modules
.git
.env
*.log

# Optimize layers
RUN apt-get update && apt-get install -y \
    package1 \
    package2 \
    && rm -rf /var/lib/apt/lists/*
```

### Docker Compose Best Practices

```yaml
# Use version 3.8+
version: '3.8'

# Define networks explicitly
networks:
  app-network:
    driver: bridge

# Use health checks
services:
  web:
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost"]
      interval: 30s
      timeout: 10s
      retries: 3

# Use secrets for sensitive data
secrets:
  db_password:
    file: ./secrets/db_password.txt

# Define resource limits
deploy:
  resources:
    limits:
      cpus: '1.0'
      memory: 1G
```

### Production Considerations

```bash
# Use Docker secrets
echo "my-secret-password" | docker secret create db_password -

# Enable Docker logging
docker run --log-driver json-file --log-opt max-size=10m --log-opt max-file=3

# Use Docker health checks
docker run --health-cmd="curl -f http://localhost/health" --health-interval=30s

# Implement graceful shutdown
STOPSIGNAL SIGTERM
CMD ["apache2", "-DFOREGROUND"]
```

### Backup and Recovery

```bash
# Database backup
docker exec <db-container> pg_dump -U tpt_user tpt_erp > backup.sql

# Volume backup
docker run --rm -v tpt_erp_postgres_data:/data -v $(pwd):/backup alpine tar czf /backup/volume-backup.tar.gz -C /data .

# Container backup
docker commit <container-name> tpt-erp-backup:$(date +%Y%m%d)
docker save tpt-erp-backup:$(date +%Y%m%d) > tpt-erp-backup.tar
```

### Monitoring and Alerting

```bash
# Set up log rotation
docker run --log-opt max-size=50m --log-opt max-file=5

# Use Docker events
docker events --filter 'event=start'

# Monitor with Prometheus
docker run -d \
  -p 9090:9090 \
  -v /path/to/prometheus.yml:/etc/prometheus/prometheus.yml \
  prom/prometheus
```

---

## Support Resources

- **Docker Documentation**: https://docs.docker.com/
- **Docker Compose**: https://docs.docker.com/compose/
- **Docker Best Practices**: https://docs.docker.com/develop/dev-best-practices/
- **TPT Free ERP Issues**: https://github.com/PhillipC05/tpt-free-erp/issues

---

*Last Updated: September 10, 2025*
*Version: 1.0*
*Platform: Docker*
