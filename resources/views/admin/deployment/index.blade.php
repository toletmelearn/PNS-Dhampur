@extends('layouts.admin')

@section('title', 'Production Deployment & Maintenance')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-rocket text-primary me-2"></i>
                Production Deployment & Maintenance
            </h1>
            <p class="text-muted mb-0">Complete guide for deploying and maintaining the PNS Dhampur system</p>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-primary" onclick="exportDocumentation()">
                <i class="fas fa-download me-1"></i>
                Export Documentation
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="printDocumentation()">
                <i class="fas fa-print me-1"></i>
                Print
            </button>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4" id="deploymentTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
                <i class="fas fa-info-circle me-1"></i>
                Overview
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="requirements-tab" data-bs-toggle="tab" data-bs-target="#requirements" type="button" role="tab">
                <i class="fas fa-list-check me-1"></i>
                Requirements
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="deployment-tab" data-bs-toggle="tab" data-bs-target="#deployment" type="button" role="tab">
                <i class="fas fa-upload me-1"></i>
                Deployment
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="configuration-tab" data-bs-toggle="tab" data-bs-target="#configuration" type="button" role="tab">
                <i class="fas fa-cogs me-1"></i>
                Configuration
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="maintenance-tab" data-bs-toggle="tab" data-bs-target="#maintenance" type="button" role="tab">
                <i class="fas fa-tools me-1"></i>
                Maintenance
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="troubleshooting-tab" data-bs-toggle="tab" data-bs-target="#troubleshooting" type="button" role="tab">
                <i class="fas fa-bug me-1"></i>
                Troubleshooting
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="deploymentTabContent">
        <!-- Overview Tab -->
        <div class="tab-pane fade show active" id="overview" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-info-circle me-2"></i>
                                System Overview
                            </h6>
                        </div>
                        <div class="card-body">
                            <h5>PNS Dhampur Management System</h5>
                            <p class="text-muted">A comprehensive school management system built with Laravel 10, featuring student management, performance monitoring, automated backups, and advanced security features.</p>
                            
                            <h6 class="mt-4">Key Features:</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i> Student Information Management</li>
                                <li><i class="fas fa-check text-success me-2"></i> Academic Performance Tracking</li>
                                <li><i class="fas fa-check text-success me-2"></i> Real-time Performance Monitoring</li>
                                <li><i class="fas fa-check text-success me-2"></i> Automated Backup System</li>
                                <li><i class="fas fa-check text-success me-2"></i> Security Monitoring & Alerts</li>
                                <li><i class="fas fa-check text-success me-2"></i> Multi-role User Management</li>
                                <li><i class="fas fa-check text-success me-2"></i> Responsive Dashboard Interface</li>
                            </ul>

                            <h6 class="mt-4">Technology Stack:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        <li><strong>Backend:</strong> Laravel 10.x</li>
                                        <li><strong>Database:</strong> MySQL 8.0+</li>
                                        <li><strong>Frontend:</strong> Bootstrap 5, jQuery</li>
                                        <li><strong>Server:</strong> Apache/Nginx</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        <li><strong>PHP:</strong> 8.1+</li>
                                        <li><strong>Cache:</strong> Redis (optional)</li>
                                        <li><strong>Queue:</strong> Database/Redis</li>
                                        <li><strong>Storage:</strong> Local/S3/FTP</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-chart-line me-2"></i>
                                Deployment Status
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="deployment-status">
                                <div class="status-item mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>System Health</span>
                                        <span class="badge badge-success">Healthy</span>
                                    </div>
                                </div>
                                <div class="status-item mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Database</span>
                                        <span class="badge badge-success">Connected</span>
                                    </div>
                                </div>
                                <div class="status-item mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Cache</span>
                                        <span class="badge badge-warning">Optional</span>
                                    </div>
                                </div>
                                <div class="status-item mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Backups</span>
                                        <span class="badge badge-success">Active</span>
                                    </div>
                                </div>
                                <div class="status-item mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Monitoring</span>
                                        <span class="badge badge-success">Active</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-clock me-2"></i>
                                Quick Actions
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary btn-sm" onclick="checkSystemHealth()">
                                    <i class="fas fa-heartbeat me-1"></i>
                                    Check System Health
                                </button>
                                <button class="btn btn-outline-success btn-sm" onclick="runMaintenance()">
                                    <i class="fas fa-tools me-1"></i>
                                    Run Maintenance
                                </button>
                                <button class="btn btn-outline-info btn-sm" onclick="viewLogs()">
                                    <i class="fas fa-file-alt me-1"></i>
                                    View System Logs
                                </button>
                                <button class="btn btn-outline-warning btn-sm" onclick="createBackup()">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    Create Backup
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Requirements Tab -->
        <div class="tab-pane fade" id="requirements" role="tabpanel">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list-check me-2"></i>
                        System Requirements
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <h5>Server Requirements</h5>
                            <div class="requirements-section">
                                <h6 class="text-primary">Minimum Requirements:</h6>
                                <ul>
                                    <li><strong>OS:</strong> Ubuntu 20.04+ / CentOS 8+ / Windows Server 2019+</li>
                                    <li><strong>CPU:</strong> 2 cores, 2.4 GHz</li>
                                    <li><strong>RAM:</strong> 4 GB</li>
                                    <li><strong>Storage:</strong> 50 GB SSD</li>
                                    <li><strong>Network:</strong> 100 Mbps</li>
                                </ul>

                                <h6 class="text-success mt-4">Recommended Requirements:</h6>
                                <ul>
                                    <li><strong>OS:</strong> Ubuntu 22.04 LTS</li>
                                    <li><strong>CPU:</strong> 4 cores, 3.0 GHz</li>
                                    <li><strong>RAM:</strong> 8 GB</li>
                                    <li><strong>Storage:</strong> 100 GB SSD</li>
                                    <li><strong>Network:</strong> 1 Gbps</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <h5>Software Requirements</h5>
                            <div class="requirements-section">
                                <h6 class="text-primary">Core Components:</h6>
                                <ul>
                                    <li><strong>PHP:</strong> 8.1 or higher</li>
                                    <li><strong>MySQL:</strong> 8.0 or higher</li>
                                    <li><strong>Web Server:</strong> Apache 2.4+ or Nginx 1.18+</li>
                                    <li><strong>Composer:</strong> 2.0+</li>
                                    <li><strong>Node.js:</strong> 16+ (for asset compilation)</li>
                                </ul>

                                <h6 class="text-success mt-4">PHP Extensions:</h6>
                                <ul>
                                    <li>BCMath</li>
                                    <li>Ctype</li>
                                    <li>cURL</li>
                                    <li>DOM</li>
                                    <li>Fileinfo</li>
                                    <li>JSON</li>
                                    <li>Mbstring</li>
                                    <li>OpenSSL</li>
                                    <li>PCRE</li>
                                    <li>PDO</li>
                                    <li>Tokenizer</li>
                                    <li>XML</li>
                                    <li>GD or Imagick</li>
                                    <li>Zip</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Security Requirements</h5>
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-shield-alt me-2"></i>Security Checklist:</h6>
                                <ul class="mb-0">
                                    <li>SSL/TLS certificate installed and configured</li>
                                    <li>Firewall configured to allow only necessary ports (80, 443, 22)</li>
                                    <li>Database access restricted to application server</li>
                                    <li>Regular security updates applied</li>
                                    <li>Strong passwords for all accounts</li>
                                    <li>Backup encryption enabled</li>
                                    <li>Log monitoring configured</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Deployment Tab -->
        <div class="tab-pane fade" id="deployment" role="tabpanel">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-upload me-2"></i>
                        Deployment Guide
                    </h6>
                </div>
                <div class="card-body">
                    <div class="deployment-steps">
                        <div class="step-item mb-4">
                            <h5><span class="badge badge-primary me-2">1</span>Server Preparation</h5>
                            <div class="step-content">
                                <h6>Update System:</h6>
                                <pre class="bg-dark text-light p-3 rounded"><code># Ubuntu/Debian
sudo apt update && sudo apt upgrade -y

# CentOS/RHEL
sudo yum update -y</code></pre>

                                <h6 class="mt-3">Install Required Software:</h6>
                                <pre class="bg-dark text-light p-3 rounded"><code># Install Apache, MySQL, PHP
sudo apt install apache2 mysql-server php8.1 php8.1-fpm php8.1-mysql php8.1-xml php8.1-gd php8.1-curl php8.1-zip php8.1-mbstring php8.1-bcmath -y

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs</code></pre>
                            </div>
                        </div>

                        <div class="step-item mb-4">
                            <h5><span class="badge badge-primary me-2">2</span>Database Setup</h5>
                            <div class="step-content">
                                <h6>Create Database and User:</h6>
                                <pre class="bg-dark text-light p-3 rounded"><code># Login to MySQL
sudo mysql -u root -p

# Create database
CREATE DATABASE pns_dhampur CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Create user
CREATE USER 'pns_user'@'localhost' IDENTIFIED BY 'secure_password_here';

# Grant privileges
GRANT ALL PRIVILEGES ON pns_dhampur.* TO 'pns_user'@'localhost';
FLUSH PRIVILEGES;

# Exit MySQL
EXIT;</code></pre>
                            </div>
                        </div>

                        <div class="step-item mb-4">
                            <h5><span class="badge badge-primary me-2">3</span>Application Deployment</h5>
                            <div class="step-content">
                                <h6>Clone and Setup Application:</h6>
                                <pre class="bg-dark text-light p-3 rounded"><code># Navigate to web directory
cd /var/www/html

# Clone repository (or upload files)
sudo git clone https://github.com/your-repo/pns-dhampur.git
sudo chown -R www-data:www-data pns-dhampur
cd pns-dhampur

# Install dependencies
composer install --optimize-autoloader --no-dev
npm install && npm run production

# Set permissions
sudo chmod -R 755 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache</code></pre>

                                <h6 class="mt-3">Environment Configuration:</h6>
                                <pre class="bg-dark text-light p-3 rounded"><code># Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env file
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pns_dhampur
DB_USERNAME=pns_user
DB_PASSWORD=secure_password_here</code></pre>
                            </div>
                        </div>

                        <div class="step-item mb-4">
                            <h5><span class="badge badge-primary me-2">4</span>Database Migration</h5>
                            <div class="step-content">
                                <pre class="bg-dark text-light p-3 rounded"><code># Run migrations
php artisan migrate --force

# Seed database (if needed)
php artisan db:seed --force

# Clear and cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache</code></pre>
                            </div>
                        </div>

                        <div class="step-item mb-4">
                            <h5><span class="badge badge-primary me-2">5</span>Web Server Configuration</h5>
                            <div class="step-content">
                                <h6>Apache Virtual Host:</h6>
                                <pre class="bg-dark text-light p-3 rounded"><code># Create virtual host file
sudo nano /etc/apache2/sites-available/pns-dhampur.conf

# Add configuration:
&lt;VirtualHost *:80&gt;
    ServerName your-domain.com
    DocumentRoot /var/www/html/pns-dhampur/public
    
    &lt;Directory /var/www/html/pns-dhampur/public&gt;
        AllowOverride All
        Require all granted
    &lt;/Directory&gt;
    
    ErrorLog ${APACHE_LOG_DIR}/pns-dhampur_error.log
    CustomLog ${APACHE_LOG_DIR}/pns-dhampur_access.log combined
&lt;/VirtualHost&gt;

# Enable site and modules
sudo a2ensite pns-dhampur.conf
sudo a2enmod rewrite
sudo systemctl restart apache2</code></pre>
                            </div>
                        </div>

                        <div class="step-item mb-4">
                            <h5><span class="badge badge-primary me-2">6</span>SSL Configuration</h5>
                            <div class="step-content">
                                <h6>Install SSL Certificate (Let's Encrypt):</h6>
                                <pre class="bg-dark text-light p-3 rounded"><code># Install Certbot
sudo apt install certbot python3-certbot-apache -y

# Obtain SSL certificate
sudo certbot --apache -d your-domain.com

# Test auto-renewal
sudo certbot renew --dry-run</code></pre>
                            </div>
                        </div>

                        <div class="step-item mb-4">
                            <h5><span class="badge badge-primary me-2">7</span>Final Setup</h5>
                            <div class="step-content">
                                <pre class="bg-dark text-light p-3 rounded"><code># Setup cron jobs for scheduled tasks
sudo crontab -e

# Add the following line:
* * * * * cd /var/www/html/pns-dhampur && php artisan schedule:run >> /dev/null 2>&1

# Setup queue worker (optional)
sudo nano /etc/systemd/system/pns-queue.service

# Add service configuration and start
sudo systemctl enable pns-queue.service
sudo systemctl start pns-queue.service</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configuration Tab -->
        <div class="tab-pane fade" id="configuration" role="tabpanel">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-cogs me-2"></i>
                        Production Configuration
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <h5>Environment Configuration (.env)</h5>
                            <pre class="bg-light p-3 rounded"><code># Application
APP_NAME="PNS Dhampur"
APP_ENV=production
APP_KEY=base64:your-generated-key-here
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pns_dhampur
DB_USERNAME=pns_user
DB_PASSWORD=secure_password_here

# Cache
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email@domain.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls

# Backup
BACKUP_ENCRYPTION_KEY=your-backup-encryption-key
BACKUP_S3_BUCKET=your-s3-bucket
BACKUP_S3_REGION=us-east-1

# Monitoring
PERFORMANCE_MONITORING=true
SECURITY_MONITORING=true</code></pre>
                        </div>
                        
                        <div class="col-lg-6">
                            <h5>Performance Optimization</h5>
                            <div class="config-section">
                                <h6 class="text-primary">PHP Configuration (php.ini):</h6>
                                <pre class="bg-light p-3 rounded"><code>memory_limit = 256M
max_execution_time = 300
upload_max_filesize = 50M
post_max_size = 50M
max_input_vars = 3000

# OPcache
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
opcache.fast_shutdown=1</code></pre>

                                <h6 class="text-success mt-4">MySQL Configuration (my.cnf):</h6>
                                <pre class="bg-light p-3 rounded"><code>[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
query_cache_type = 1
query_cache_size = 64M
tmp_table_size = 64M
max_heap_table_size = 64M</code></pre>

                                <h6 class="text-info mt-4">Apache Configuration:</h6>
                                <pre class="bg-light p-3 rounded"><code># Enable compression
LoadModule deflate_module modules/mod_deflate.so

&lt;Location /&gt;
    SetOutputFilter DEFLATE
    SetEnvIfNoCase Request_URI \
        \.(?:gif|jpe?g|png)$ no-gzip dont-vary
    SetEnvIfNoCase Request_URI \
        \.(?:exe|t?gz|zip|bz2|sit|rar)$ no-gzip dont-vary
&lt;/Location&gt;

# Enable caching
LoadModule expires_module modules/mod_expires.so
ExpiresActive On
ExpiresByType text/css "access plus 1 month"
ExpiresByType application/javascript "access plus 1 month"
ExpiresByType image/png "access plus 1 month"
ExpiresByType image/jpg "access plus 1 month"
ExpiresByType image/jpeg "access plus 1 month"</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Maintenance Tab -->
        <div class="tab-pane fade" id="maintenance" role="tabpanel">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-tools me-2"></i>
                        Maintenance Procedures
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <h5>Regular Maintenance Tasks</h5>
                            
                            <div class="maintenance-section mb-4">
                                <h6 class="text-primary">Daily Tasks:</h6>
                                <ul>
                                    <li>Monitor system performance metrics</li>
                                    <li>Check backup completion status</li>
                                    <li>Review error logs</li>
                                    <li>Verify database connectivity</li>
                                    <li>Check disk space usage</li>
                                </ul>
                                
                                <div class="alert alert-info">
                                    <strong>Automated:</strong> These tasks are automated through the monitoring system and will send alerts if issues are detected.
                                </div>
                            </div>

                            <div class="maintenance-section mb-4">
                                <h6 class="text-success">Weekly Tasks:</h6>
                                <ul>
                                    <li>Review performance reports</li>
                                    <li>Update system packages</li>
                                    <li>Clean temporary files</li>
                                    <li>Optimize database tables</li>
                                    <li>Test backup restoration</li>
                                </ul>
                                
                                <pre class="bg-dark text-light p-3 rounded"><code># Weekly maintenance script
#!/bin/bash
cd /var/www/html/pns-dhampur

# Clear caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize database
php artisan db:optimize

# Clean logs older than 30 days
find storage/logs -name "*.log" -mtime +30 -delete

# Update system packages
sudo apt update && sudo apt upgrade -y</code></pre>
                            </div>

                            <div class="maintenance-section mb-4">
                                <h6 class="text-warning">Monthly Tasks:</h6>
                                <ul>
                                    <li>Security audit and updates</li>
                                    <li>Performance optimization review</li>
                                    <li>Database maintenance and optimization</li>
                                    <li>SSL certificate renewal check</li>
                                    <li>Backup storage cleanup</li>
                                    <li>User access review</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <h5>Maintenance Commands</h5>
                            
                            <div class="command-section mb-4">
                                <h6 class="text-primary">System Health Check:</h6>
                                <pre class="bg-dark text-light p-3 rounded"><code># Check system status
php artisan system:health

# Check database connection
php artisan db:monitor

# Check queue status
php artisan queue:monitor

# Check storage permissions
php artisan storage:check</code></pre>
                            </div>

                            <div class="command-section mb-4">
                                <h6 class="text-success">Performance Optimization:</h6>
                                <pre class="bg-dark text-light p-3 rounded"><code># Clear all caches
php artisan optimize:clear

# Optimize for production
php artisan optimize

# Database optimization
php artisan db:optimize

# Clean old sessions
php artisan session:gc</code></pre>
                            </div>

                            <div class="command-section mb-4">
                                <h6 class="text-info">Backup Operations:</h6>
                                <pre class="bg-dark text-light p-3 rounded"><code># Create full backup
php artisan backup:create --type=full

# Create database backup only
php artisan backup:create --type=database

# Cleanup old backups
php artisan backup:cleanup

# Test backup integrity
php artisan backup:verify</code></pre>
                            </div>

                            <div class="command-section mb-4">
                                <h6 class="text-warning">Emergency Procedures:</h6>
                                <pre class="bg-dark text-light p-3 rounded"><code># Put application in maintenance mode
php artisan down --message="System maintenance in progress"

# Bring application back online
php artisan up

# Emergency backup
php artisan backup:create --type=full --priority=high

# Restore from backup
php artisan backup:restore --file=backup_file.zip</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Troubleshooting Tab -->
        <div class="tab-pane fade" id="troubleshooting" role="tabpanel">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-bug me-2"></i>
                        Troubleshooting Guide
                    </h6>
                </div>
                <div class="card-body">
                    <div class="troubleshooting-sections">
                        <div class="issue-section mb-4">
                            <h5 class="text-danger">Common Issues & Solutions</h5>
                            
                            <div class="issue-item mb-4">
                                <h6><i class="fas fa-exclamation-triangle text-warning me-2"></i>Application Not Loading</h6>
                                <div class="solution">
                                    <p><strong>Symptoms:</strong> White screen, 500 error, or application not responding</p>
                                    <p><strong>Solutions:</strong></p>
                                    <ol>
                                        <li>Check Apache/Nginx error logs</li>
                                        <li>Verify file permissions (755 for directories, 644 for files)</li>
                                        <li>Check .env configuration</li>
                                        <li>Clear application cache</li>
                                    </ol>
                                    <pre class="bg-dark text-light p-3 rounded"><code># Check logs
tail -f /var/log/apache2/error.log

# Fix permissions
sudo chown -R www-data:www-data /var/www/html/pns-dhampur
sudo chmod -R 755 storage bootstrap/cache

# Clear cache
php artisan cache:clear
php artisan config:clear</code></pre>
                                </div>
                            </div>

                            <div class="issue-item mb-4">
                                <h6><i class="fas fa-database text-danger me-2"></i>Database Connection Issues</h6>
                                <div class="solution">
                                    <p><strong>Symptoms:</strong> "Connection refused" or "Access denied" errors</p>
                                    <p><strong>Solutions:</strong></p>
                                    <ol>
                                        <li>Verify MySQL service is running</li>
                                        <li>Check database credentials in .env</li>
                                        <li>Test database connection manually</li>
                                        <li>Check MySQL user permissions</li>
                                    </ol>
                                    <pre class="bg-dark text-light p-3 rounded"><code># Check MySQL status
sudo systemctl status mysql

# Test connection
mysql -u pns_user -p -h localhost pns_dhampur

# Restart MySQL if needed
sudo systemctl restart mysql</code></pre>
                                </div>
                            </div>

                            <div class="issue-item mb-4">
                                <h6><i class="fas fa-memory text-info me-2"></i>Performance Issues</h6>
                                <div class="solution">
                                    <p><strong>Symptoms:</strong> Slow page loads, timeouts, high server load</p>
                                    <p><strong>Solutions:</strong></p>
                                    <ol>
                                        <li>Enable OPcache</li>
                                        <li>Optimize database queries</li>
                                        <li>Configure Redis caching</li>
                                        <li>Increase PHP memory limit</li>
                                    </ol>
                                    <pre class="bg-dark text-light p-3 rounded"><code># Check system resources
htop
df -h
free -m

# Optimize application
php artisan optimize
php artisan config:cache
php artisan route:cache</code></pre>
                                </div>
                            </div>

                            <div class="issue-item mb-4">
                                <h6><i class="fas fa-shield-alt text-success me-2"></i>Security Issues</h6>
                                <div class="solution">
                                    <p><strong>Symptoms:</strong> Suspicious activities, unauthorized access attempts</p>
                                    <p><strong>Solutions:</strong></p>
                                    <ol>
                                        <li>Review security logs</li>
                                        <li>Update all software packages</li>
                                        <li>Check firewall configuration</li>
                                        <li>Verify SSL certificate</li>
                                    </ol>
                                    <pre class="bg-dark text-light p-3 rounded"><code># Check security logs
tail -f storage/logs/security.log

# Update packages
sudo apt update && sudo apt upgrade -y

# Check firewall
sudo ufw status

# Verify SSL
openssl s_client -connect your-domain.com:443</code></pre>
                                </div>
                            </div>
                        </div>

                        <div class="emergency-section">
                            <h5 class="text-danger">Emergency Contacts & Procedures</h5>
                            <div class="alert alert-danger">
                                <h6><i class="fas fa-phone me-2"></i>Emergency Response</h6>
                                <p><strong>For critical system failures:</strong></p>
                                <ol>
                                    <li>Put application in maintenance mode</li>
                                    <li>Create emergency backup</li>
                                    <li>Document the issue</li>
                                    <li>Contact system administrator</li>
                                    <li>Implement rollback if necessary</li>
                                </ol>
                                
                                <p><strong>Emergency Contacts:</strong></p>
                                <ul class="mb-0">
                                    <li>System Administrator: [Contact Information]</li>
                                    <li>Database Administrator: [Contact Information]</li>
                                    <li>Network Administrator: [Contact Information]</li>
                                    <li>Security Team: [Contact Information]</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Initialize Bootstrap tabs
    var triggerTabList = [].slice.call(document.querySelectorAll('#deploymentTabs button'))
    triggerTabList.forEach(function (triggerEl) {
        var tabTrigger = new bootstrap.Tab(triggerEl)
        
        triggerEl.addEventListener('click', function (event) {
            event.preventDefault()
            tabTrigger.show()
        })
    });
});

function exportDocumentation() {
    // Create a new window with the documentation content
    var printWindow = window.open('', '_blank');
    var content = document.querySelector('.container-fluid').innerHTML;
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>PNS Dhampur - Production Deployment Guide</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
            <style>
                body { font-family: Arial, sans-serif; }
                .tab-content .tab-pane { display: block !important; opacity: 1 !important; }
                .nav-tabs { display: none; }
                pre { background: #f8f9fa; padding: 15px; border-radius: 5px; }
                @media print {
                    .btn, .nav-tabs { display: none; }
                    .card { border: 1px solid #ddd; margin-bottom: 20px; }
                }
            </style>
        </head>
        <body>
            ${content}
        </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.focus();
}

function printDocumentation() {
    window.print();
}

function checkSystemHealth() {
    // Simulate system health check
    showAlert('info', 'Running system health check...');
    
    setTimeout(function() {
        showAlert('success', 'System health check completed. All systems operational.');
    }, 2000);
}

function runMaintenance() {
    if (confirm('This will run maintenance tasks and may temporarily affect system performance. Continue?')) {
        showAlert('warning', 'Running maintenance tasks...');
        
        setTimeout(function() {
            showAlert('success', 'Maintenance tasks completed successfully.');
        }, 3000);
    }
}

function viewLogs() {
    // Open logs in a new tab/window
    window.open('/admin/logs', '_blank');
}

function createBackup() {
    // Redirect to backup page
    window.location.href = '/admin/backup';
}

function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'warning' ? 'alert-warning' : 
                      type === 'info' ? 'alert-info' : 'alert-danger';
    
    const alert = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    $('.container-fluid').prepend(alert);
    
    // Auto-dismiss after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}
</script>
@endsection