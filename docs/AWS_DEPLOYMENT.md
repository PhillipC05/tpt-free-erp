# TPT Free ERP - AWS Deployment Guide

**Version:** 1.0
**Date:** September 10, 2025
**Platform:** Amazon Web Services (AWS)
**Author:** Development Team

This comprehensive guide provides step-by-step instructions for deploying TPT Free ERP on AWS using various services including EC2, RDS, Elastic Beanstalk, and ECS.

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Deployment Methods](#deployment-methods)
4. [Method 1: EC2 with Docker](#method-1-ec2-with-docker)
5. [Method 2: Elastic Beanstalk](#method-2-elastic-beanstalk)
6. [Method 3: ECS (Containerized)](#method-3-ecs-containerized)
7. [Database Configuration](#database-configuration)
8. [SSL Configuration](#ssl-configuration)
9. [Monitoring and Maintenance](#monitoring-and-maintenance)
10. [Troubleshooting](#troubleshooting)
11. [Cost Optimization](#cost-optimization)

---

## Overview

### Why AWS?

AWS provides:
- **Enterprise-grade reliability** with 99.9% uptime SLA
- **Global infrastructure** with 25+ regions worldwide
- **Comprehensive service ecosystem** for scaling and management
- **Advanced security features** and compliance certifications
- **Flexible pricing models** with reserved instances and spot pricing
- **Extensive documentation** and community support

### Deployment Options

| Method | Difficulty | Cost | Scalability | Best For |
|--------|------------|------|-------------|----------|
| EC2 + Docker | Medium | $$$ | High | Full control, custom scaling |
| Elastic Beanstalk | Low | $$$ | Medium | Quick deployment, managed scaling |
| ECS Fargate | Medium | $$$ | Very High | Container orchestration, auto-scaling |

---

## Prerequisites

### AWS Account Setup
- Active AWS account with billing enabled
- IAM user with appropriate permissions (or use root account for initial setup)
- AWS CLI installed and configured
- SSH key pair created in EC2 console

### Domain Name (Recommended)
- Domain registered with Route 53 or any provider
- DNS management access
- SSL certificate (AWS Certificate Manager will be used)

### Local Development Environment
```bash
# Required tools
aws --version       # AWS CLI
docker --version    # Docker
docker-compose --version  # Docker Compose
git --version       # Git
ssh -V             # SSH client
```

### Required AWS Permissions
```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "ec2:*",
                "rds:*",
                "elasticbeanstalk:*",
                "ecs:*",
                "iam:*",
                "cloudformation:*",
                "route53:*",
                "acm:*",
                "cloudwatch:*",
                "logs:*"
            ],
            "Resource": "*"
        }
    ]
}
```

---

## Deployment Methods

## Method 1: EC2 with Docker (Recommended for Full Control)

### Step 1: Launch EC2 Instance

1. **Navigate to EC2 Console**
   - Go to AWS Console → EC2 → Launch Instance

2. **Choose AMI**
   ```
   Amazon Linux 2 AMI (HVM), SSD Volume Type
   Or Ubuntu Server 22.04 LTS (HVM)
   ```

3. **Choose Instance Type**
   ```
   Recommended: t3.medium ($0.0416/hour)
   Minimum: t3.small ($0.0208/hour)
   For production: t3.large ($0.0832/hour)
   ```

4. **Configure Instance Details**
   - Number of instances: 1
   - Network: Create new VPC or use default
   - Auto-assign Public IP: Enable
   - IAM role: Create new role with EC2 and RDS permissions

5. **Add Storage**
   - Root volume: 20 GB (gp3)
   - Additional volume: 50 GB (gp3) for data (optional)

6. **Configure Security Group**
   ```
   SSH (22) - Your IP: 0.0.0.0/0 or your IP/32
   HTTP (80) - Anywhere: 0.0.0.0/0
   HTTPS (443) - Anywhere: 0.0.0.0/0
   PostgreSQL (5432) - Your IP or VPC CIDR
   ```

7. **Launch Instance**
   - Select existing key pair or create new
   - Launch instance

### Step 2: Initial Server Setup

```bash
# Connect to your instance
ssh -i your-key.pem ec2-user@YOUR_INSTANCE_IP

# Update system packages
sudo yum update -y  # Amazon Linux
# OR
sudo apt update && sudo apt upgrade -y  # Ubuntu

# Install Docker
sudo amazon-linux-extras install docker -y  # Amazon Linux
# OR for Ubuntu:
# sudo apt install docker.io -y

# Start and enable Docker
sudo systemctl start docker
sudo systemctl enable docker

# Add user to docker group
sudo usermod -aG docker ec2-user
# OR for Ubuntu:
# sudo usermod -aG docker ubuntu

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Install Git
sudo yum install git -y  # Amazon Linux
# OR
sudo apt install git -y  # Ubuntu
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

# Database (configure after RDS setup)
DB_CONNECTION=pgsql
DB_HOST=your-rds-endpoint.region.rds.amazonaws.com
DB_PORT=5432
DB_DATABASE=tpt_erp
DB_USERNAME=tpt_user
DB_PASSWORD=secure_password_2025

# Cache & Session (using Redis)
CACHE_DRIVER=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Redis (using ElastiCache if configured)
REDIS_HOST=your-redis-endpoint.cache.amazonaws.com
REDIS_PASSWORD=null
REDIS_PORT=6379

# AWS S3 (for file storage)
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-s3-bucket
AWS_USE_PATH_STYLE_ENDPOINT=false

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=email-smtp.us-east-1.amazonaws.com
MAIL_PORT=587
MAIL_USERNAME=your-ses-username
MAIL_PASSWORD=your-ses-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="TPT Free ERP"
```

### Step 4: Configure Docker Compose for AWS

```bash
# Create production docker-compose file
nano docker-compose.aws.yml
```

**AWS Docker Compose (docker-compose.aws.yml):**
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
      - ./nginx.aws.conf:/etc/nginx/nginx.conf:ro
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

### Step 5: AWS Nginx Configuration

```bash
# Create AWS-specific Nginx configuration
nano nginx.aws.conf
```

**AWS Nginx Configuration (nginx.aws.conf):**
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

        # SSL Configuration (using AWS Certificate Manager)
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
docker-compose -f docker-compose.aws.yml up -d

# Run database migrations
docker-compose -f docker-compose.aws.yml exec web php phinx migrate

# Seed the database (optional)
docker-compose -f docker-compose.aws.yml exec web php phinx seed:run

# Check if services are running
docker-compose -f docker-compose.aws.yml ps

# View logs
docker-compose -f docker-compose.aws.yml logs -f
```

---

## Method 2: Elastic Beanstalk (Managed)

### Step 1: Prepare Application for Elastic Beanstalk

1. **Create Elastic Beanstalk Application**
   ```bash
   # Install EB CLI
   pip install awsebcli

   # Initialize EB application
   eb init tpt-free-erp --platform "PHP 8.1 running on 64bit Amazon Linux 2" --region us-east-1
   ```

2. **Create .ebextensions Directory**
   ```bash
   mkdir .ebextensions
   ```

3. **Create EB Configuration Files**

**PHP Configuration (.ebextensions/01_php.config):**
```yaml
option_settings:
  aws:elasticbeanstalk:application:environment:
    APP_ENV: production
    APP_DEBUG: false
  aws:elasticbeanstalk:environment:proxy:staticfiles:
    /static: static
  aws:autoscaling:launchconfiguration:
    InstanceType: t3.medium
    IamInstanceProfile: aws-elasticbeanstalk-ec2-role
  aws:elasticbeanstalk:healthreporting:system:
    SystemType: enhanced
  aws:elasticbeanstalk:environment:proxy:
    ProxyServer: nginx
```

**Nginx Configuration (.ebextensions/02_nginx.config):**
```yaml
files:
  "/etc/nginx/conf.d/01_tpt.conf":
    mode: "000644"
    owner: root
    group: root
    content: |
      location / {
        try_files $uri $uri/ /index.php?$query_string;
      }

      location ~ \.php$ {
        fastcgi_pass unix:/var/run/php-fpm/www.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
      }

      location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
      }
```

**Environment Variables (.ebextensions/03_env.config):**
```yaml
option_settings:
  aws:elasticbeanstalk:application:environment:
    APP_NAME: "TPT Free ERP"
    APP_KEY: "base64:YOUR_APP_KEY_HERE"
    DB_CONNECTION: pgsql
    DB_HOST: "${RDS_HOSTNAME}"
    DB_PORT: "${RDS_PORT}"
    DB_DATABASE: "${RDS_DB_NAME}"
    DB_USERNAME: "${RDS_USERNAME}"
    DB_PASSWORD: "${RDS_PASSWORD}"
    CACHE_DRIVER: redis
    REDIS_HOST: "${REDIS_HOST}"
    REDIS_PORT: "${REDIS_PORT}"
```

### Step 2: Create Elastic Beanstalk Environment

```bash
# Create environment
eb create production --database.engine postgres --database.version 15.0 --database.size 20 --database.username tpt_user --elb-type application

# Or create without database (if using RDS separately)
eb create production --elb-type application
```

### Step 3: Configure RDS Database

1. **Create RDS Instance**
   - Go to RDS Console → Create Database
   - Engine: PostgreSQL
   - Version: 15.0
   - Instance class: db.t3.micro (free tier) or db.t3.small
   - Storage: 20 GB
   - Database name: tpt_erp
   - Master username: tpt_user

2. **Configure Security Group**
   - Allow inbound traffic from Elastic Beanstalk security group on port 5432

### Step 4: Deploy Application

```bash
# Deploy to Elastic Beanstalk
eb deploy

# Check status
eb status

# View logs
eb logs

# Open application
eb open
```

---

## Method 3: ECS (Containerized)

### Step 1: Create ECS Cluster

1. **Navigate to ECS Console**
   - Go to ECS → Create Cluster

2. **Choose Cluster Template**
   ```
   Cluster name: tpt-erp-cluster
   Infrastructure: AWS Fargate (serverless)
   ```

3. **Create Task Definition**

**Task Definition Configuration:**
```json
{
    "family": "tpt-erp-task",
    "taskRoleArn": "arn:aws:iam::ACCOUNT_ID:role/ecsTaskExecutionRole",
    "executionRoleArn": "arn:aws:iam::ACCOUNT_ID:role/ecsTaskExecutionRole",
    "networkMode": "awsvpc",
    "requiresCompatibilities": ["FARGATE"],
    "cpu": "512",
    "memory": "1024",
    "containerDefinitions": [
        {
            "name": "web",
            "image": "ACCOUNT_ID.dkr.ecr.us-east-1.amazonaws.com/tpt-erp:latest",
            "essential": true,
            "portMappings": [
                {
                    "containerPort": 80,
                    "protocol": "tcp"
                }
            ],
            "environment": [
                {"name": "APP_ENV", "value": "production"},
                {"name": "APP_KEY", "value": "base64:YOUR_APP_KEY_HERE"},
                {"name": "DB_CONNECTION", "value": "pgsql"},
                {"name": "DB_HOST", "value": "your-rds-endpoint.rds.amazonaws.com"},
                {"name": "DB_DATABASE", "value": "tpt_erp"},
                {"name": "DB_USERNAME", "value": "tpt_user"},
                {"name": "DB_PASSWORD", "value": "secure_password"}
            ],
            "logConfiguration": {
                "logDriver": "awslogs",
                "options": {
                    "awslogs-group": "/ecs/tpt-erp",
                    "awslogs-region": "us-east-1",
                    "awslogs-stream-prefix": "ecs"
                }
            }
        }
    ]
}
```

### Step 2: Create Service

1. **Create ECS Service**
   - Cluster: tpt-erp-cluster
   - Service name: tpt-erp-service
   - Task Definition: tpt-erp-task
   - Desired tasks: 1

2. **Configure Networking**
   - VPC: Your VPC
   - Subnets: Public subnets
   - Security groups: Allow HTTP/HTTPS

3. **Load Balancing**
   - Application Load Balancer
   - Target group: Create new
   - Health check path: /

### Step 3: Build and Push Docker Image

```bash
# Build Docker image
docker build -t tpt-erp .

# Tag image
docker tag tpt-erp:latest ACCOUNT_ID.dkr.ecr.us-east-1.amazonaws.com/tpt-erp:latest

# Push to ECR
aws ecr get-login-password --region us-east-1 | docker login --username AWS --password-stdin ACCOUNT_ID.dkr.ecr.us-east-1.amazonaws.com
docker push ACCOUNT_ID.dkr.ecr.us-east-1.amazonaws.com/tpt-erp:latest
```

---

## Database Configuration

### Using Amazon RDS PostgreSQL

1. **Create RDS Instance**
   ```bash
   aws rds create-db-instance \
     --db-instance-identifier tpt-erp-db \
     --db-instance-class db.t3.micro \
     --engine postgres \
     --engine-version 15.0 \
     --master-username tpt_user \
     --master-user-password secure_password_2025 \
     --allocated-storage 20 \
     --db-name tpt_erp \
     --vpc-security-group-ids sg-12345678 \
     --db-subnet-group-name your-subnet-group
   ```

2. **Configure Database Parameters**
   ```bash
   aws rds modify-db-parameter-group \
     --db-parameter-group-name your-parameter-group \
     --parameters "ParameterName=shared_preload_libraries,ParameterValue=pg_stat_statements,ApplyMethod=pending-reboot"
   ```

3. **Set Up Automated Backups**
   ```bash
   aws rds modify-db-instance \
     --db-instance-identifier tpt-erp-db \
     --backup-retention-period 7 \
     --preferred-backup-window "03:00-04:00" \
     --enable-iam-database-authentication
   ```

### Using Amazon ElastiCache (Redis)

1. **Create ElastiCache Cluster**
   ```bash
   aws elasticache create-cache-cluster \
     --cache-cluster-id tpt-erp-redis \
     --cache-node-type cache.t3.micro \
     --engine redis \
     --engine-version 7.0 \
     --num-cache-nodes 1 \
     --cache-parameter-group default.redis7 \
     --security-group-ids sg-12345678 \
     --cache-subnet-group-name your-subnet-group
   ```

---

## SSL Configuration

### Using AWS Certificate Manager (ACM)

1. **Request SSL Certificate**
   ```bash
   aws acm request-certificate \
     --domain-name your-domain.com \
     --validation-method DNS \
     --subject-alternative-names www.your-domain.com
   ```

2. **Validate Certificate**
   - Add CNAME records to Route 53
   - Wait for validation to complete

3. **Configure Load Balancer**
   ```bash
   aws elbv2 create-listener \
     --load-balancer-arn your-lb-arn \
     --protocol HTTPS \
     --port 443 \
     --certificates CertificateArn=your-cert-arn \
     --default-actions Type=forward,TargetGroupArn=your-target-group-arn
   ```

### Using CloudFront + S3 (for Static Assets)

1. **Create S3 Bucket**
   ```bash
   aws s3 mb s3://tpt-erp-assets --region us-east-1
   ```

2. **Configure CloudFront Distribution**
   ```bash
   aws cloudfront create-distribution \
     --distribution-config file://cloudfront-config.json
   ```

**CloudFront Configuration (cloudfront-config.json):**
```json
{
    "CallerReference": "tpt-erp-assets",
    "Comment": "TPT Free ERP Static Assets",
    "DefaultCacheBehavior": {
        "TargetOriginId": "tpt-erp-assets",
        "ViewerProtocolPolicy": "redirect-to-https",
        "MinTTL": 0,
        "DefaultTTL": 86400,
        "MaxTTL": 31536000
    },
    "Origins": {
        "Quantity": 1,
        "Items": [
            {
                "Id": "tpt-erp-assets",
                "DomainName": "tpt-erp-assets.s3.amazonaws.com",
                "S3OriginConfig": {
                    "OriginAccessIdentity": ""
                }
            }
        ]
    },
    "Enabled": true
}
```

---

## Monitoring and Maintenance

### AWS CloudWatch

```bash
# Create CloudWatch alarms
aws cloudwatch put-metric-alarm \
  --alarm-name "HighCPUUtilization" \
  --alarm-description "CPU utilization is high" \
  --metric-name CPUUtilization \
  --namespace AWS/EC2 \
  --statistic Average \
  --period 300 \
  --threshold 80 \
  --comparison-operator GreaterThanThreshold \
  --dimensions Name=InstanceId,Value=i-1234567890abcdef0 \
  --evaluation-periods 2 \
  --alarm-actions arn:aws:sns:us-east-1:123456789012:high-cpu-alert

# Set up log groups
aws logs create-log-group --log-group-name /ecs/tpt-erp
```

### AWS Systems Manager (SSM)

```bash
# Install SSM agent
sudo yum install -y amazon-ssm-agent
sudo systemctl enable amazon-ssm-agent
sudo systemctl start amazon-ssm-agent

# Create maintenance window
aws ssm create-maintenance-window \
  --name "tpt-erp-maintenance" \
  --schedule "cron(0 2 ? * SUN *)" \
  --duration 2 \
  --cutoff 1 \
  --allow-unassociated-targets
```

### Automated Backups

```bash
# RDS automated backup
aws rds create-db-snapshot \
  --db-instance-identifier tpt-erp-db \
  --db-snapshot-identifier tpt-erp-backup-$(date +%Y%m%d)

# S3 backup script
aws s3 sync /var/www/tpt-erp/storage s3://tpt-erp-backups/$(date +%Y%m%d)
```

---

## Troubleshooting

### Common AWS Issues

#### 1. EC2 Instance Connection Issues

```bash
# Check security group
aws ec2 describe-security-groups --group-ids sg-12345678

# Check instance status
aws ec2 describe-instances --instance-ids i-1234567890abcdef0

# Check system logs
aws ec2 get-console-output --instance-id i-1234567890abcdef0
```

#### 2. RDS Connection Issues

```bash
# Test database connectivity
psql -h your-rds-endpoint.rds.amazonaws.com -U tpt_user -d tpt_erp

# Check RDS security group
aws rds describe-db-instances --db-instance-identifier tpt-erp-db

# Check VPC configuration
aws ec2 describe-vpcs
```

#### 3. Load Balancer Issues

```bash
# Check load balancer health
aws elbv2 describe-target-health --target-group-arn your-target-group-arn

# Check load balancer logs
aws elbv2 describe-load-balancers --load-balancer-arns your-lb-arn
```

#### 4. ECS Issues

```bash
# Check service status
aws ecs describe-services --cluster tpt-erp-cluster --services tpt-erp-service

# Check task status
aws ecs list-tasks --cluster tpt-erp-cluster

# View container logs
aws logs get-log-events --log-group-name /ecs/tpt-erp --log-stream-name ecs/web/abc123
```

### Performance Issues

#### 1. High CPU Usage

```bash
# Check CloudWatch metrics
aws cloudwatch get-metric-statistics \
  --namespace AWS/EC2 \
  --metric-name CPUUtilization \
  --dimensions Name=InstanceId,Value=i-1234567890abcdef0 \
  --start-time 2025-01-01T00:00:00Z \
  --end-time 2025-01-02T00:00:00Z \
  --period 3600 \
  --statistics Average
```

#### 2. Memory Issues

```bash
# Check memory usage
free -h

# Check swap usage
swapon --show

# Add swap space if needed
sudo dd if=/dev/zero of=/swapfile bs=1M count=2048
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
```

#### 3. Database Performance

```bash
# Check RDS metrics
aws rds describe-db-instances --db-instance-identifier tpt-erp-db

# Enable enhanced monitoring
aws rds modify-db-instance \
  --db-instance-identifier tpt-erp-db \
  --monitoring-interval 60 \
  --monitoring-role-arn arn:aws:iam::ACCOUNT_ID:role/rds-monitoring-role
```

---

## Cost Optimization

### Pricing Breakdown

| Service | Configuration | Cost/Month |
|---------|---------------|------------|
| EC2 (t3.medium) | On-demand | ~$35 |
| RDS (db.t3.micro) | PostgreSQL | ~$15 |
| ElastiCache (cache.t3.micro) | Redis | ~$15 |
| Load Balancer | Application | ~$20 |
| CloudWatch | Basic monitoring | ~$5 |
| S3 | 100GB storage | ~$3 |
| **Total** | | **~$93** |

### Optimization Strategies

#### 1. Reserved Instances

```bash
# Purchase reserved instance
aws ec2 purchase-reserved-instances-offering \
  --reserved-instances-offering-id offering-id \
  --instance-count 1
```

#### 2. Auto Scaling

```bash
# Create launch template
aws ec2 create-launch-template \
  --launch-template-name tpt-erp-template \
  --launch-template-data file://launch-template.json

# Create auto scaling group
aws autoscaling create-auto-scaling-group \
  --auto-scaling-group-name tpt-erp-asg \
  --launch-template LaunchTemplateName=tpt-erp-template \
  --min-size 1 \
  --max-size 5 \
  --desired-capacity 1 \
  --availability-zones us-east-1a us-east-1b
```

#### 3. Spot Instances (for non-critical workloads)

```bash
# Request spot instance
aws ec2 request-spot-instances \
  --spot-price "0.02" \
  --instance-count 1 \
  --type "one-time" \
  --launch-specification file://spot-specification.json
```

#### 4. Storage Optimization

```bash
# Use S3 for static assets
aws s3 cp static-files/ s3://tpt-erp-assets/ --recursive

# Enable S3 storage class optimization
aws s3api put-bucket-lifecycle-configuration \
  --bucket tpt-erp-assets \
  --lifecycle-configuration file://lifecycle.json
```

---

## Security Best Practices

### IAM Configuration

```bash
# Create IAM policy for EC2
aws iam create-policy \
  --policy-name TPT-ERP-EC2-Policy \
  --policy-document file://ec2-policy.json

# Create IAM role
aws iam create-role \
  --role-name TPT-ERP-EC2-Role \
  --assume-role-policy-document file://trust-policy.json

# Attach policy to role
aws iam attach-role-policy \
  --role-name TPT-ERP-EC2-Role \
  --policy-arn arn:aws:iam::ACCOUNT_ID:policy/TPT-ERP-EC2-Policy
```

### Security Groups

```bash
# Create security group
aws ec2 create-security-group \
  --group-name tpt-erp-sg \
  --description "Security group for TPT Free ERP" \
  --vpc-id vpc-12345678

# Add inbound rules
aws ec2 authorize-security-group-ingress \
  --group-id sg-12345678 \
  --protocol tcp \
  --port 80 \
  --cidr 0.0.0.0/0

aws ec2 authorize-security-group-ingress \
  --group-id sg-12345678 \
  --protocol tcp \
  --port 443 \
  --cidr 0.0.0.0/0
```

### AWS WAF (Web Application Firewall)

```bash
# Create WAF ACL
aws wafv2 create-web-acl \
  --name tpt-erp-waf \
  --scope REGIONAL \
  --default-action Allow={} \
  --rules file://waf-rules.json \
  --visibility-config SampledRequestsEnabled=true,CloudWatchMetricsEnabled=true,MetricName=TPT-ERP-WAF
```

---

## Next Steps

1. **Domain Configuration**
   - Set up Route 53 hosted zone
   - Configure DNS records
   - Set up SSL certificates

2. **Backup Strategy**
   - Implement automated RDS backups
   - Set up cross-region replication
   - Configure S3 backup storage

3. **Monitoring Setup**
   - Configure CloudWatch dashboards
   - Set up alerts and notifications
   - Implement log aggregation

4. **Scaling Considerations**
   - Plan for traffic growth
   - Configure auto-scaling policies
   - Consider multi-region deployment

---

## Support Resources

- **AWS Documentation**: https://docs.aws.amazon.com/
- **AWS Support**: https://aws.amazon.com/support/
- **TPT Free ERP Issues**: https://github.com/PhillipC05/tpt-free-erp/issues
- **AWS Forums**: https://forums.aws.amazon.com/
- **AWS Status Page**: https://status.aws.amazon.com/

---

*Last Updated: September 10, 2025*
*Version: 1.0*
*Platform: Amazon Web Services (AWS)*
