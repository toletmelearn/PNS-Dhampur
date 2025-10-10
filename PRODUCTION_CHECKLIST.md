# Production Deployment Checklist - PNS Dhampur

## Pre-Deployment Checklist ✅

### 1. Environment Setup
- [ ] Production server configured (Ubuntu 20.04+ or CentOS 8+)
- [ ] PHP 8.0+ installed with required extensions
- [ ] MySQL 8.0+ or MariaDB 10.4+ configured
- [ ] Nginx/Apache web server configured
- [ ] SSL certificate installed and configured
- [ ] Firewall configured (ports 80, 443, 22)

### 2. Application Configuration
- [ ] `.env` file configured for production
- [ ] Database credentials updated
- [ ] Mail server configuration
- [ ] Cache driver configured (Redis recommended)
- [ ] Queue driver configured
- [ ] Session driver configured
- [ ] File storage configured

### 3. Security Configuration
- [ ] APP_DEBUG=false in production
- [ ] Strong APP_KEY generated
- [ ] Database passwords are strong
- [ ] API rate limiting configured
- [ ] CORS settings configured
- [ ] Security headers configured

### 4. Performance Optimization
- [ ] Composer autoloader optimized
- [ ] Configuration cached
- [ ] Routes cached
- [ ] Views cached
- [ ] OPcache enabled
- [ ] Database indexes optimized

## Deployment Steps

### Step 1: Server Preparation
```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y nginx mysql-server php8.0-fpm php8.0-mysql php8.0-xml php8.0-mbstring php8.0-curl php8.0-zip php8.0-gd php8.0-bcmath

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Step 2: Application Deployment
```bash
# Clone repository
git clone https://github.com/your-repo/PNS-Dhampur.git /var/www/pns-dhampur
cd /var/www/pns-dhampur

# Install dependencies
composer install --optimize-autoloader --no-dev

# Set permissions
sudo chown -R www-data:www-data /var/www/pns-dhampur
sudo chmod -R 755 /var/www/pns-dhampur
sudo chmod -R 775 /var/www/pns-dhampur/storage
sudo chmod -R 775 /var/www/pns-dhampur/bootstrap/cache
```

### Step 3: Environment Configuration
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database
php artisan migrate --force

# Seed initial data
php artisan db:seed --class=ProductionSeeder

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 4: Web Server Configuration
```nginx
# Nginx configuration for PNS Dhampur
server {
    listen 80;
    listen [::]:80;
    server_name pnsdhampur.edu.in www.pnsdhampur.edu.in;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name pnsdhampur.edu.in www.pnsdhampur.edu.in;

    root /var/www/pns-dhampur/public;
    index index.php index.html index.htm;

    # SSL Configuration
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Step 5: Database Setup
```sql
-- Create production database
CREATE DATABASE pns_dhampur_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create database user
CREATE USER 'pns_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON pns_dhampur_prod.* TO 'pns_user'@'localhost';
FLUSH PRIVILEGES;
```

### Step 6: Monitoring Setup
```bash
# Setup log rotation
sudo nano /etc/logrotate.d/pns-dhampur

# Setup cron jobs
sudo crontab -e
# Add the following lines:
# * * * * * cd /var/www/pns-dhampur && php artisan schedule:run >> /dev/null 2>&1
# 0 2 * * * cd /var/www/pns-dhampur && php artisan backup:create --type=full
# 0 */6 * * * cd /var/www/pns-dhampur && php artisan monitoring:check
```

## Post-Deployment Verification

### 1. Application Health Checks
- [ ] Website loads correctly (https://pnsdhampur.edu.in)
- [ ] Login functionality works
- [ ] Database connections are working
- [ ] File uploads are working
- [ ] Email notifications are working
- [ ] API endpoints are responding

### 2. Performance Checks
- [ ] Page load times < 3 seconds
- [ ] Database queries optimized
- [ ] Cache is working properly
- [ ] Static assets are served efficiently

### 3. Security Checks
- [ ] SSL certificate is valid
- [ ] Security headers are present
- [ ] No sensitive information exposed
- [ ] Rate limiting is working
- [ ] Authentication is secure

### 4. Backup Verification
- [ ] Automated backups are running
- [ ] Backup files are being created
- [ ] Restore process tested
- [ ] Backup retention policy working

### 5. Monitoring Setup
- [ ] System monitoring is active
- [ ] Error logging is working
- [ ] Performance metrics are collected
- [ ] Alerts are configured

## Rollback Plan

### If Issues Occur:
1. **Database Issues**: Restore from latest backup
2. **Application Issues**: Revert to previous version
3. **Server Issues**: Switch to backup server
4. **DNS Issues**: Update DNS records

### Emergency Contacts:
- System Administrator: [Contact Info]
- Database Administrator: [Contact Info]
- Network Administrator: [Contact Info]
- Project Manager: [Contact Info]

## Production Environment Variables

```env
APP_NAME="PNS Dhampur"
APP_ENV=production
APP_KEY=base64:generated_key_here
APP_DEBUG=false
APP_URL=https://pnsdhampur.edu.in

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pns_dhampur_prod
DB_USERNAME=pns_user
DB_PASSWORD=strong_password_here

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=noreply@pnsdhampur.edu.in
MAIL_PASSWORD=app_password_here
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@pnsdhampur.edu.in
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

## Maintenance Schedule

### Daily Tasks:
- Monitor system health
- Check error logs
- Verify backup completion

### Weekly Tasks:
- Review performance metrics
- Update security patches
- Clean up old log files

### Monthly Tasks:
- Full system backup verification
- Security audit
- Performance optimization review

---

**Deployment Date**: ___________
**Deployed By**: ___________
**Verified By**: ___________
**Production URL**: https://pnsdhampur.edu.in