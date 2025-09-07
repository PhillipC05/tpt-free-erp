#!/bin/bash

# TPT Open ERP Database Backup Script
# This script creates automated backups of the PostgreSQL database

BACKUP_DIR="/var/backups/tpt-erp"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="tpt_open_erp"
DB_USER="postgres"
DB_HOST="localhost"
DB_PORT="5432"

# Load environment variables if .env file exists
if [ -f "../.env" ]; then
    export $(grep -v '^#' ../.env | xargs)
    DB_HOST=${DB_HOST:-localhost}
    DB_PORT=${DB_PORT:-5432}
    DB_NAME=${DB_DATABASE:-tpt_open_erp}
    DB_USER=${DB_USERNAME:-postgres}
fi

# Create backup directory if it doesn't exist
mkdir -p $BACKUP_DIR

# Create backup with custom format (compressed)
pg_dump -h $DB_HOST -p $DB_PORT -U $DB_USER -d $DB_NAME -F c -f $BACKUP_DIR/${DB_NAME}_$DATE.backup

# Also create SQL dump for reference
pg_dump -h $DB_HOST -p $DB_PORT -U $DB_USER -d $DB_NAME -f $BACKUP_DIR/${DB_NAME}_$DATE.sql

# Compress the SQL dump
gzip $BACKUP_DIR/${DB_NAME}_$DATE.sql

# Verify backup integrity
if [ $? -eq 0 ]; then
    echo "$(date): Backup completed successfully - $BACKUP_DIR/${DB_NAME}_$DATE.backup" >> $BACKUP_DIR/backup.log
    echo "Backup completed: $BACKUP_DIR/${DB_NAME}_$DATE.backup"

    # Calculate backup size
    BACKUP_SIZE=$(du -h $BACKUP_DIR/${DB_NAME}_$DATE.backup | cut -f1)
    echo "Backup size: $BACKUP_SIZE" >> $BACKUP_DIR/backup.log
else
    echo "$(date): Backup failed!" >> $BACKUP_DIR/backup.log
    echo "Backup failed!"
    exit 1
fi

# Remove backups older than 30 days
find $BACKUP_DIR -name "*.backup" -mtime +30 -delete
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete

# Optional: Send notification (uncomment and configure)
# curl -X POST -H 'Content-type: application/json' --data '{"text":"Database backup completed"}' YOUR_SLACK_WEBHOOK_URL

# Optional: Upload to cloud storage (uncomment and configure)
# aws s3 cp $BACKUP_DIR/${DB_NAME}_$DATE.backup s3://your-bucket/backups/
