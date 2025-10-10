# Production Deployment Guide

## Overview

This guide provides comprehensive instructions for deploying the PNS-Dhampur application to a production environment. The application includes advanced features such as biometric integration, automated backup systems, comprehensive monitoring, and security measures.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Server Requirements](#server-requirements)
3. [Environment Setup](#environment-setup)
4. [Database Configuration](#database-configuration)
5. [Application Deployment](#application-deployment)
6. [Security Configuration](#security-configuration)
7. [Backup System Setup](#backup-system-setup)
8. [Monitoring and Alerting](#monitoring-and-alerting)
9. [Performance Optimization](#performance-optimization)
10. [Maintenance and Updates](#maintenance-and-updates)
11. [Troubleshooting](#troubleshooting)

## Prerequisites

### System Requirements

- **Operating System**: Ubuntu 20.04 LTS or CentOS 8+ (recommended)
- **Web Server**: Nginx 1.18+ or Apache 2.4+
- **PHP**: 8.1 or higher with required extensions
- **Database**: MySQL 8.0+ or MariaDB 10.5+
- **Memory**: Minimum 4GB RAM (8GB+ recommended)
- **Storage**: Minimum 50GB SSD (100GB+ recommended)
- **Network**: Stable internet connection with SSL certificate

### Required PHP Extensions

```bash
# Install required PHP extensions
sudo apt-get install php8.1-cli php8.1-fpm php8.1-mysql php8.1-xml php8.1-curl \
    php8.1-gd php8.1-mbstring php8.1-zip php8.1-bcmath php8.1-intl \
    php8.1-redis php8.1-imagick php8.1-soap
```

### Additional Software

- **Composer**: Latest version for dependency management
- **Node.js**: 16+ for asset compilation
- **Redis**: For caching and session storage
- **Supervisor**: For queue management
- **Certbot**: For SSL certificate management

## Server Requirements

### Hardware Specifications

#### Minimum Requirements
- **CPU**: 2 cores
- **RAM**: 4GB
- **Storage**: 50GB SSD
- **Network**: 100Mbps

#### Recommended Requirements
- **CPU**: 4+ cores
- **RAM**: 8GB+
- **Storage**: 100GB+ SSD with RAID 1
- **Network**: 1Gbps
- **Backup Storage**: Additional 500GB for backups

### Network Configuration

```bash
# Configure firewall
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw enable

# Configure fail2ban for SSH protection
sudo apt-get install fail2ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

## Environment Setup

### 1. Create Application User

```bash
# Create dedicated user for the application
sudo adduser pns-app
sudo usermod -aG www-data pns-app
sudo mkdir -p /var/www/pns-dhampur
sudo chown pns-app:www-data /var/www/pns-dhampur
```

### 2. Clone Repository

```bash
# Switch to application user
sudo su - pns-app
cd /var/www/pns-dhampur

# Clone the repository
git clone https://github.com/your-org/pns-dhampur.git .
```

### 3. Install Dependencies

```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node.js dependencies
npm ci --production

# Build assets
npm run production
```

### 4. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Set proper permissions
chmod 644 .env
chown pns-app:www-data .env
```

### 5. Configure Environment Variables

Edit `.env` file with production settings:

```env
# Application
APP_NAME="PNS Dhampur"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pns_dhampur_prod
DB_USERNAME=pns_user
DB_PASSWORD=secure_password_here

# Cache & Sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=redis_password_here
REDIS_PORT=6379

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"

# Backup Configuration
BACKUP_ENCRYPTION_KEY=your-32-character-encryption-key
BACKUP_RETENTION_DAYS=30

# Monitoring
MONITORING_ENABLED=true
MONITORING_EMAIL=admin@your-domain.com
MONITORING_SLACK_WEBHOOK=https://hooks.slack.com/your-webhook

# Security
SECURITY_SCAN_ENABLED=true
RATE_LIMIT_ENABLED=true
```

## Database Configuration

### 1. Create Database and User

```sql
-- Connect to MySQL as root
mysql -u root -p

-- Create database
CREATE DATABASE pns_dhampur_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user
CREATE USER 'pns_user'@'localhost' IDENTIFIED BY 'secure_password_here';

-- Grant privileges
GRANT ALL PRIVILEGES ON pns_dhampur_prod.* TO 'pns_user'@'localhost';
FLUSH PRIVILEGES;

-- Exit MySQL
EXIT;
```

### 2. Run Migrations

```bash
# Run database migrations
php artisan migrate --force

# Seed initial data (if needed)
php artisan db:seed --class=ProductionSeeder
```

### 3. Database Optimization

```sql
-- Optimize MySQL configuration in /etc/mysql/mysql.conf.d/mysqld.cnf
[mysqld]
innodb_buffer_pool_size = 2G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
query_cache_type = 1
query_cache_size = 256M
max_connections = 200
```

## Application Deployment

### 1. Set File Permissions

```bash
# Set ownership
sudo chown -R pns-app:www-data /var/www/pns-dhampur

# Set directory permissions
sudo find /var/www/pns-dhampur -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/pns-dhampur -type f -exec chmod 644 {} \;

# Set executable permissions
sudo chmod +x /var/www/pns-dhampur/artisan

# Set writable directories
sudo chmod -R 775 /var/www/pns-dhampur/storage
sudo chmod -R 775 /var/www/pns-dhampur/bootstrap/cache
```

### 2. Configure Web Server

#### Nginx Configuration

Create `/etc/nginx/sites-available/pns-dhampur`:

```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com www.your-domain.com;
    root /var/www/pns-dhampur/public;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss;

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Rate limiting
    limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;
    location /login {
        limit_req zone=login burst=5 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/pns-dhampur /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 3. SSL Certificate Setup

```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Set up auto-renewal
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

### 4. PHP-FPM Configuration

Edit `/etc/php/8.1/fpm/pool.d/www.conf`:

```ini
[www]
user = www-data
group = www-data
listen = /var/run/php/php8.1-fpm.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.process_idle_timeout = 10s
pm.max_requests = 500
```

## Security Configuration

### 1. Application Security

```bash
# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set up security scanning
php artisan security:scan --schedule
```

### 2. Server Security

```bash
# Disable root login
sudo sed -i 's/PermitRootLogin yes/PermitRootLogin no/' /etc/ssh/sshd_config

# Change SSH port (optional)
sudo sed -i 's/#Port 22/Port 2222/' /etc/ssh/sshd_config

# Restart SSH
sudo systemctl restart ssh

# Install and configure fail2ban
sudo apt-get install fail2ban
sudo cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local

# Configure fail2ban for nginx
cat > /etc/fail2ban/jail.local << EOF
[nginx-http-auth]
enabled = true
port = http,https
logpath = /var/log/nginx/error.log

[nginx-limit-req]
enabled = true
port = http,https
logpath = /var/log/nginx/error.log
maxretry = 10
EOF

sudo systemctl restart fail2ban
```

## Backup System Setup

### 1. Configure Backup Storage

```bash
# Create backup directories
sudo mkdir -p /var/backups/pns-dhampur
sudo chown pns-app:www-data /var/backups/pns-dhampur
sudo chmod 750 /var/backups/pns-dhampur

# Create backup script
sudo ln -s /var/www/pns-dhampur/storage/backups /var/backups/pns-dhampur/app-backups
```

### 2. Test Backup System

```bash
# Test database backup
php artisan backup:create database

# Test full backup
php artisan backup:create full

# Test backup restoration
php artisan backup:restore --list
```

### 3. Schedule Automated Backups

```bash
# Add to crontab for pns-app user
sudo crontab -u pns-app -e

# Add these lines:
# Daily full backup at 2 AM
0 2 * * * cd /var/www/pns-dhampur && php artisan backup:create full >> /var/log/backup.log 2>&1

# Hourly database backup during business hours
0 8-18 * * 1-5 cd /var/www/pns-dhampur && php artisan backup:create database >> /var/log/backup.log 2>&1

# Weekly cleanup at 3 AM on Sundays
0 3 * * 0 cd /var/www/pns-dhampur && php artisan backup:cleanup >> /var/log/backup.log 2>&1
```

## Monitoring and Alerting

### 1. System Monitoring Setup

```bash
# Test monitoring system
php artisan monitoring:check

# Set up monitoring schedule
php artisan schedule:run
```

### 2. Log Management

```bash
# Create log rotation configuration
sudo cat > /etc/logrotate.d/pns-dhampur << EOF
/var/www/pns-dhampur/storage/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 pns-app www-data
    postrotate
        /bin/systemctl reload php8.1-fpm > /dev/null 2>&1 || true
    endscript
}
EOF
```

### 3. Performance Monitoring

```bash
# Install system monitoring tools
sudo apt-get install htop iotop nethogs

# Set up performance monitoring
php artisan performance:monitor --schedule
```

## Performance Optimization

### 1. PHP Optimization

Edit `/etc/php/8.1/fpm/php.ini`:

```ini
; Memory and execution
memory_limit = 512M
max_execution_time = 300
max_input_time = 300

; File uploads
upload_max_filesize = 100M
post_max_size = 100M

; OPcache
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

### 2. Database Optimization

```bash
# Run database optimization
php artisan db:optimize

# Set up query optimization
php artisan optimize
```

### 3. Caching Configuration

```bash
# Set up Redis for caching
sudo systemctl enable redis-server
sudo systemctl start redis-server

# Configure application caching
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Queue Management

### 1. Supervisor Configuration

Create `/etc/supervisor/conf.d/pns-dhampur-worker.conf`:

```ini
[program:pns-dhampur-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/pns-dhampur/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=pns-app
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/pns-dhampur/storage/logs/worker.log
stopwaitsecs=3600
```

Start supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start pns-dhampur-worker:*
```

## Maintenance and Updates

### 1. Update Process

```bash
# Create update script
cat > /var/www/pns-dhampur/deploy.sh << 'EOF'
#!/bin/bash
set -e

echo "Starting deployment..."

# Put application in maintenance mode
php artisan down

# Pull latest changes
git pull origin main

# Install/update dependencies
composer install --no-dev --optimize-autoloader

# Run database migrations
php artisan migrate --force

# Clear and cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
sudo supervisorctl restart pns-dhampur-worker:*
sudo systemctl reload php8.1-fpm

# Bring application back online
php artisan up

echo "Deployment completed successfully!"
EOF

chmod +x /var/www/pns-dhampur/deploy.sh
```

### 2. Health Checks

```bash
# Set up health check endpoint monitoring
curl -f https://your-domain.com/health || echo "Health check failed"

# Monitor application logs
tail -f /var/www/pns-dhampur/storage/logs/laravel.log
```

## Troubleshooting

### Common Issues

#### 1. Permission Issues
```bash
# Fix file permissions
sudo chown -R pns-app:www-data /var/www/pns-dhampur
sudo chmod -R 755 /var/www/pns-dhampur
sudo chmod -R 775 /var/www/pns-dhampur/storage
sudo chmod -R 775 /var/www/pns-dhampur/bootstrap/cache
```

#### 2. Database Connection Issues
```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

#### 3. Cache Issues
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

#### 4. Queue Issues
```bash
# Restart queue workers
sudo supervisorctl restart pns-dhampur-worker:*

# Check failed jobs
php artisan queue:failed
```

### Log Locations

- **Application Logs**: `/var/www/pns-dhampur/storage/logs/`
- **Nginx Logs**: `/var/log/nginx/`
- **PHP-FPM Logs**: `/var/log/php8.1-fpm.log`
- **MySQL Logs**: `/var/log/mysql/`
- **System Logs**: `/var/log/syslog`

### Monitoring Commands

```bash
# Check application status
php artisan system:health-check

# Monitor system resources
htop
iotop
df -h

# Check service status
sudo systemctl status nginx
sudo systemctl status php8.1-fpm
sudo systemctl status mysql
sudo systemctl status redis-server
```

## Security Checklist

- [ ] SSL certificate installed and configured
- [ ] Firewall configured with minimal open ports
- [ ] SSH hardened (key-based auth, non-standard port)
- [ ] Fail2ban configured for brute force protection
- [ ] Regular security updates scheduled
- [ ] Database user has minimal required privileges
- [ ] Application debug mode disabled
- [ ] Sensitive files protected (.env, etc.)
- [ ] Security headers configured in web server
- [ ] Rate limiting enabled for sensitive endpoints
- [ ] Backup encryption enabled
- [ ] Log monitoring and alerting configured

## Performance Checklist

- [ ] OPcache enabled and configured
- [ ] Database queries optimized
- [ ] Caching layers implemented (Redis)
- [ ] Static assets compressed and cached
- [ ] CDN configured (if applicable)
- [ ] Database indexes optimized
- [ ] Queue workers running
- [ ] Log rotation configured
- [ ] Monitoring and alerting active

## Backup Checklist

- [ ] Automated daily backups configured
- [ ] Backup encryption enabled
- [ ] Backup verification working
- [ ] Restore process tested
- [ ] Off-site backup storage configured
- [ ] Backup retention policy implemented
- [ ] Backup monitoring and alerting active
- [ ] Documentation for restore procedures

This deployment guide ensures a secure, performant, and maintainable production environment for the PNS-Dhampur application.