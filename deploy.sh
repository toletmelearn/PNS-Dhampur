#!/bin/bash

# PNS Dhampur Management System - Production Deployment Script
# This script handles the complete deployment process for production environment

set -e  # Exit on any error

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_NAME="PNS Dhampur Management System"
BACKUP_DIR="/var/backups/pns-dhampur"
LOG_FILE="/var/log/pns-dhampur-deploy.log"
MAINTENANCE_FILE="storage/framework/maintenance.php"

# Functions
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[ERROR] $1${NC}" | tee -a "$LOG_FILE"
    exit 1
}

warning() {
    echo -e "${YELLOW}[WARNING] $1${NC}" | tee -a "$LOG_FILE"
}

info() {
    echo -e "${BLUE}[INFO] $1${NC}" | tee -a "$LOG_FILE"
}

# Check if running as root
check_permissions() {
    if [[ $EUID -eq 0 ]]; then
        error "This script should not be run as root for security reasons"
    fi
}

# Pre-deployment checks
pre_deployment_checks() {
    log "Starting pre-deployment checks..."
    
    # Check if .env.production exists
    if [[ ! -f ".env.production" ]]; then
        error ".env.production file not found. Please create it before deployment."
    fi
    
    # Check PHP version
    PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
    if [[ $(echo "$PHP_VERSION < 8.1" | bc -l) -eq 1 ]]; then
        error "PHP 8.1 or higher is required. Current version: $PHP_VERSION"
    fi
    
    # Check Composer
    if ! command -v composer &> /dev/null; then
        error "Composer is not installed"
    fi
    
    # Check Node.js and npm
    if ! command -v node &> /dev/null; then
        error "Node.js is not installed"
    fi
    
    if ! command -v npm &> /dev/null; then
        error "npm is not installed"
    fi
    
    # Check required directories
    mkdir -p storage/logs
    mkdir -p storage/framework/cache
    mkdir -p storage/framework/sessions
    mkdir -p storage/framework/views
    mkdir -p bootstrap/cache
    
    log "Pre-deployment checks completed successfully"
}

# Enable maintenance mode
enable_maintenance_mode() {
    log "Enabling maintenance mode..."
    php artisan down --render="errors::503" --secret="$(grep MAINTENANCE_MODE_SECRET .env.production | cut -d '=' -f2)"
    log "Maintenance mode enabled"
}

# Disable maintenance mode
disable_maintenance_mode() {
    log "Disabling maintenance mode..."
    php artisan up
    log "Maintenance mode disabled"
}

# Create backup
create_backup() {
    log "Creating backup..."
    
    # Create backup directory
    mkdir -p "$BACKUP_DIR/$(date +%Y%m%d_%H%M%S)"
    CURRENT_BACKUP="$BACKUP_DIR/$(date +%Y%m%d_%H%M%S)"
    
    # Backup database
    if [[ -f ".env" ]]; then
        DB_NAME=$(grep DB_DATABASE .env | cut -d '=' -f2)
        DB_USER=$(grep DB_USERNAME .env | cut -d '=' -f2)
        DB_PASS=$(grep DB_PASSWORD .env | cut -d '=' -f2)
        
        mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$CURRENT_BACKUP/database.sql"
        log "Database backup created"
    fi
    
    # Backup storage directory
    if [[ -d "storage" ]]; then
        cp -r storage "$CURRENT_BACKUP/"
        log "Storage backup created"
    fi
    
    # Backup .env file
    if [[ -f ".env" ]]; then
        cp .env "$CURRENT_BACKUP/"
        log "Environment file backup created"
    fi
    
    log "Backup completed: $CURRENT_BACKUP"
}

# Update dependencies
update_dependencies() {
    log "Updating dependencies..."
    
    # Update Composer dependencies
    composer install --no-dev --optimize-autoloader --no-interaction
    
    # Update npm dependencies
    npm ci --production
    
    # Build assets
    npm run build
    
    log "Dependencies updated successfully"
}

# Configure environment
configure_environment() {
    log "Configuring production environment..."
    
    # Copy production environment file
    cp .env.production .env
    
    # Generate application key if not set
    if ! grep -q "APP_KEY=base64:" .env; then
        php artisan key:generate --force
    fi
    
    # Set proper permissions
    chmod -R 755 storage bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache
    
    log "Environment configured successfully"
}

# Run database migrations
run_migrations() {
    log "Running database migrations..."
    
    php artisan migrate --force
    
    log "Database migrations completed"
}

# Optimize application
optimize_application() {
    log "Optimizing application..."
    
    # Clear all caches
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    
    # Cache configurations
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    # Optimize autoloader
    composer dump-autoload --optimize
    
    log "Application optimization completed"
}

# Security checks
security_checks() {
    log "Running security checks..."
    
    # Check file permissions
    find storage -type f -exec chmod 644 {} \;
    find storage -type d -exec chmod 755 {} \;
    
    # Check for sensitive files
    if [[ -f ".env.example" ]]; then
        warning ".env.example file found in production"
    fi
    
    if [[ -f "phpunit.xml" ]]; then
        warning "phpunit.xml file found in production"
    fi
    
    # Verify HTTPS configuration
    if ! grep -q "FORCE_HTTPS=true" .env; then
        warning "HTTPS is not enforced in production"
    fi
    
    # Check debug mode
    if grep -q "APP_DEBUG=true" .env; then
        error "Debug mode is enabled in production"
    fi
    
    log "Security checks completed"
}

# Start services
start_services() {
    log "Starting services..."
    
    # Start queue workers
    php artisan queue:restart
    
    # Start scheduler (if using cron)
    # Add to crontab: * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
    
    log "Services started successfully"
}

# Health check
health_check() {
    log "Running health check..."
    
    # Check if application is responding
    if command -v curl &> /dev/null; then
        APP_URL=$(grep APP_URL .env | cut -d '=' -f2)
        if curl -f -s "$APP_URL/health" > /dev/null; then
            log "Application health check passed"
        else
            warning "Application health check failed"
        fi
    fi
    
    # Check database connection
    if php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connection successful';" 2>/dev/null | grep -q "successful"; then
        log "Database connection check passed"
    else
        error "Database connection check failed"
    fi
    
    log "Health check completed"
}

# Cleanup old backups
cleanup_old_backups() {
    log "Cleaning up old backups..."
    
    # Keep only last 7 days of backups
    find "$BACKUP_DIR" -type d -mtime +7 -exec rm -rf {} \; 2>/dev/null || true
    
    log "Old backups cleaned up"
}

# Send deployment notification
send_notification() {
    log "Sending deployment notification..."
    
    # Send email notification (if configured)
    if command -v mail &> /dev/null; then
        echo "Deployment of $PROJECT_NAME completed successfully at $(date)" | mail -s "Deployment Successful" admin@pnsdhampur.com
    fi
    
    # Send Slack notification (if configured)
    SLACK_WEBHOOK=$(grep LOG_SLACK_WEBHOOK_URL .env | cut -d '=' -f2)
    if [[ -n "$SLACK_WEBHOOK" ]] && command -v curl &> /dev/null; then
        curl -X POST -H 'Content-type: application/json' \
            --data "{\"text\":\"✅ $PROJECT_NAME deployment completed successfully\"}" \
            "$SLACK_WEBHOOK"
    fi
    
    log "Deployment notification sent"
}

# Main deployment function
deploy() {
    log "Starting deployment of $PROJECT_NAME..."
    
    check_permissions
    pre_deployment_checks
    create_backup
    enable_maintenance_mode
    
    # Deployment steps
    update_dependencies
    configure_environment
    run_migrations
    optimize_application
    security_checks
    
    disable_maintenance_mode
    start_services
    health_check
    cleanup_old_backups
    send_notification
    
    log "Deployment completed successfully!"
    info "Backup location: $CURRENT_BACKUP"
    info "Log file: $LOG_FILE"
}

# Rollback function
rollback() {
    log "Starting rollback process..."
    
    enable_maintenance_mode
    
    # Find latest backup
    LATEST_BACKUP=$(ls -t "$BACKUP_DIR" | head -n1)
    if [[ -z "$LATEST_BACKUP" ]]; then
        error "No backup found for rollback"
    fi
    
    BACKUP_PATH="$BACKUP_DIR/$LATEST_BACKUP"
    
    # Restore database
    if [[ -f "$BACKUP_PATH/database.sql" ]]; then
        DB_NAME=$(grep DB_DATABASE .env | cut -d '=' -f2)
        DB_USER=$(grep DB_USERNAME .env | cut -d '=' -f2)
        DB_PASS=$(grep DB_PASSWORD .env | cut -d '=' -f2)
        
        mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$BACKUP_PATH/database.sql"
        log "Database restored from backup"
    fi
    
    # Restore storage
    if [[ -d "$BACKUP_PATH/storage" ]]; then
        rm -rf storage
        cp -r "$BACKUP_PATH/storage" .
        log "Storage restored from backup"
    fi
    
    # Restore environment
    if [[ -f "$BACKUP_PATH/.env" ]]; then
        cp "$BACKUP_PATH/.env" .
        log "Environment restored from backup"
    fi
    
    optimize_application
    disable_maintenance_mode
    
    log "Rollback completed successfully!"
}

# Script usage
usage() {
    echo "Usage: $0 {deploy|rollback|health-check}"
    echo "  deploy      - Deploy the application to production"
    echo "  rollback    - Rollback to the previous version"
    echo "  health-check - Run health checks on the application"
    exit 1
}

# Main script logic
case "${1:-}" in
    deploy)
        deploy
        ;;
    rollback)
        rollback
        ;;
    health-check)
        health_check
        ;;
    *)
        usage
        ;;
esac