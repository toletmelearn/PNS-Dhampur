# PNS-Dhampur Administrator Guide

## Table of Contents

1. [Introduction](#introduction)
2. [System Architecture](#system-architecture)
3. [Initial Setup and Configuration](#initial-setup-and-configuration)
4. [User Management](#user-management)
5. [System Configuration](#system-configuration)
6. [Biometric System Management](#biometric-system-management)
7. [Bell Schedule Administration](#bell-schedule-administration)
8. [Database Management](#database-management)
9. [Backup and Recovery](#backup-and-recovery)
10. [Security Management](#security-management)
11. [Performance Monitoring](#performance-monitoring)
12. [Troubleshooting](#troubleshooting)
13. [Maintenance Procedures](#maintenance-procedures)
14. [API Management](#api-management)
15. [System Updates](#system-updates)

## Introduction

This administrator guide provides comprehensive instructions for managing the PNS-Dhampur School Management System. It covers system configuration, user management, security, maintenance, and troubleshooting procedures.

### Administrator Responsibilities

- **System Configuration**: Configure all system settings and parameters
- **User Management**: Create, manage, and maintain user accounts
- **Security Management**: Implement and maintain security policies
- **Data Management**: Ensure data integrity and backup procedures
- **Performance Monitoring**: Monitor system performance and optimization
- **Support Coordination**: Provide technical support to users
- **System Maintenance**: Regular maintenance and updates

### Prerequisites

- **Technical Knowledge**: Basic understanding of web applications and databases
- **Network Knowledge**: Understanding of network configuration and troubleshooting
- **Security Awareness**: Knowledge of security best practices
- **Database Skills**: Basic SQL and database management skills

## System Architecture

### Technology Stack

#### Backend Components
- **Framework**: Laravel 10.x (PHP 8.1+)
- **Database**: MySQL 8.0+
- **Web Server**: Apache/Nginx
- **Cache**: Redis (optional)
- **Queue System**: Laravel Queue with Redis/Database driver

#### Frontend Components
- **UI Framework**: Bootstrap 5.x
- **JavaScript**: Vanilla JS with modern ES6+ features
- **CSS**: SCSS with custom styling
- **Icons**: Font Awesome and custom SVG icons

#### Integration Components
- **Biometric SDK**: Device-specific SDKs for fingerprint scanners
- **SMS Gateway**: Multiple provider support (Twilio, TextLocal, etc.)
- **Email Service**: SMTP with multiple provider support
- **Bell System**: Network-based bell controllers

### System Requirements

#### Server Requirements
- **Operating System**: Linux (Ubuntu 20.04+ recommended) or Windows Server
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: Version 8.1 or higher
- **Database**: MySQL 8.0+ or MariaDB 10.6+
- **Memory**: Minimum 4GB RAM (8GB+ recommended)
- **Storage**: Minimum 50GB SSD (100GB+ recommended)
- **Network**: Stable internet connection with adequate bandwidth

#### Client Requirements
- **Browsers**: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- **JavaScript**: Must be enabled
- **Cookies**: Must be enabled for session management
- **Screen Resolution**: Minimum 1024x768

## Initial Setup and Configuration

### Environment Setup

#### 1. Environment Configuration
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database settings
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pns_dhampur
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

#### 2. Database Setup
```bash
# Run database migrations
php artisan migrate

# Seed initial data
php artisan db:seed

# Create admin user
php artisan make:admin
```

#### 3. Storage Configuration
```bash
# Create storage link
php artisan storage:link

# Set proper permissions
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

### Application Configuration

#### 1. Basic Settings
Navigate to **Administration → System Settings**

- **School Information**
  - School Name: Official institution name
  - Address: Complete school address
  - Contact Information: Phone, email, website
  - Logo: Upload school logo (recommended: 200x200px PNG)

- **Academic Configuration**
  - Academic Year: Set current academic session
  - Terms/Semesters: Configure marking periods
  - Class Structure: Set up grades and sections
  - Subjects: Add available subjects

#### 2. Time and Localization
- **Time Zone**: Set local time zone
- **Date Format**: Choose preferred date format
- **Language**: Set default system language
- **Currency**: Set local currency for fee management

#### 3. Communication Settings
- **Email Configuration**
  ```
  MAIL_MAILER=smtp
  MAIL_HOST=your-smtp-server
  MAIL_PORT=587
  MAIL_USERNAME=your-email
  MAIL_PASSWORD=your-password
  MAIL_ENCRYPTION=tls
  ```

- **SMS Configuration**
  ```
  SMS_PROVIDER=twilio
  SMS_API_KEY=your-api-key
  SMS_API_SECRET=your-api-secret
  SMS_FROM_NUMBER=your-phone-number
  ```

## User Management

### User Account Administration

#### Creating User Accounts

1. **Navigate to User Management**
   - Go to **Administration → Users**
   - Click **"Add New User"**

2. **User Information Form**
   ```
   Username: unique_username
   Full Name: User's complete name
   Email: user@example.com
   Phone: +91-XXXXXXXXXX
   Role: Select appropriate role
   Status: Active/Inactive
   ```

3. **Password Management**
   - **Auto-generate**: System creates secure password
   - **Manual**: Set custom password
   - **Force Change**: Require password change on first login
   - **Expiration**: Set password expiration period

#### Bulk User Import

1. **Prepare Import File**
   - Download Excel template from system
   - Fill in user information following format
   - Validate data before import

2. **Import Process**
   ```bash
   # Via web interface
   Administration → Users → Import Users → Upload File
   
   # Via command line
   php artisan users:import /path/to/users.xlsx
   ```

3. **Post-Import Tasks**
   - Review import results
   - Send welcome emails to new users
   - Distribute login credentials securely
   - Schedule user training sessions

### Role and Permission Management

#### Predefined Roles

1. **Super Administrator**
   - Complete system access
   - User management capabilities
   - System configuration rights
   - Backup and recovery access

2. **School Administrator**
   - School-wide management
   - User management (limited)
   - Report generation
   - Configuration access (limited)

3. **Principal**
   - Academic oversight
   - Staff management
   - Student information access
   - Report generation

4. **Teacher**
   - Class management
   - Student information (assigned classes)
   - Attendance marking
   - Grade entry

5. **Office Staff**
   - Student registration
   - Attendance management
   - Communication functions
   - Basic reporting

#### Custom Role Creation

1. **Role Definition**
   ```php
   // Navigate to Administration → Roles → Create Role
   Role Name: Custom Role Name
   Description: Role description
   Permissions: Select specific permissions
   ```

2. **Permission Categories**
   - **User Management**: Create, edit, delete users
   - **Student Management**: Student CRUD operations
   - **Teacher Management**: Staff CRUD operations
   - **Attendance**: Mark, edit, view attendance
   - **Reports**: Generate, view, export reports
   - **System Settings**: Configure system parameters
   - **Biometric**: Manage biometric devices and data

### User Account Maintenance

#### Account Status Management
- **Active**: Normal account status
- **Inactive**: Temporarily disabled
- **Suspended**: Disciplinary suspension
- **Locked**: Security lockout
- **Expired**: Account past expiration date

#### Password Policies
```php
// Configure in config/auth.php
'password_policy' => [
    'min_length' => 8,
    'require_uppercase' => true,
    'require_lowercase' => true,
    'require_numbers' => true,
    'require_symbols' => true,
    'expiry_days' => 90,
    'history_count' => 5,
]
```

#### Session Management
- **Session Timeout**: Configure idle timeout
- **Concurrent Sessions**: Limit simultaneous logins
- **IP Restrictions**: Restrict access by IP address
- **Device Tracking**: Monitor login devices

## System Configuration

### General System Settings

#### Application Settings
```php
// config/app.php
'name' => 'PNS-Dhampur School Management System',
'env' => 'production', // production, staging, development
'debug' => false, // Never true in production
'url' => 'https://your-domain.com',
'timezone' => 'Asia/Kolkata',
'locale' => 'en',
```

#### Database Configuration
```php
// config/database.php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'pns_dhampur'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options' => [
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
    ],
]
```

### Academic Configuration

#### Class and Section Setup
1. **Navigate to Academic Settings**
   - Go to **Administration → Academic → Classes**
   - Add classes (e.g., Class 1, Class 2, etc.)
   - Add sections for each class (A, B, C, etc.)

2. **Subject Configuration**
   - Go to **Administration → Academic → Subjects**
   - Add subjects with codes and descriptions
   - Assign subjects to appropriate classes
   - Set subject teachers

#### Marking Periods
```php
// Configure academic terms
'academic_terms' => [
    'term1' => [
        'name' => 'First Term',
        'start_date' => '2024-04-01',
        'end_date' => '2024-09-30',
    ],
    'term2' => [
        'name' => 'Second Term',
        'start_date' => '2024-10-01',
        'end_date' => '2024-03-31',
    ],
]
```

### Notification Configuration

#### Email Settings
1. **SMTP Configuration**
   ```
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=your-email@gmail.com
   MAIL_PASSWORD=your-app-password
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=noreply@pns-dhampur.edu.in
   MAIL_FROM_NAME="PNS-Dhampur School"
   ```

2. **Email Templates**
   - Navigate to **Administration → Communications → Email Templates**
   - Customize templates for different notification types
   - Use variables for dynamic content
   - Test templates before deployment

#### SMS Configuration
1. **Provider Setup**
   ```
   SMS_PROVIDER=textlocal
   SMS_API_KEY=your-api-key
   SMS_SENDER_ID=PNSDHA
   SMS_ROUTE=4
   ```

2. **Message Templates**
   - Create templates for common notifications
   - Include school branding
   - Keep messages concise and clear
   - Test delivery before mass sending

## Biometric System Management

### Device Configuration

#### Supported Devices
- **Fingerprint Scanners**: ZKTeco, eSSL, Realtime
- **Face Recognition**: Advanced biometric systems
- **Card Readers**: RFID and proximity cards
- **Multi-modal**: Combined biometric systems

#### Device Setup Process

1. **Physical Installation**
   ```bash
   # Network configuration
   Device IP: 192.168.1.100
   Subnet Mask: 255.255.255.0
   Gateway: 192.168.1.1
   DNS: 8.8.8.8
   ```

2. **System Registration**
   - Navigate to **Biometric → Devices → Add Device**
   - Enter device details:
     ```
     Device Name: Main Entrance Scanner
     IP Address: 192.168.1.100
     Port: 4370
     Device Type: Fingerprint Scanner
     Location: Main Entrance
     Status: Active
     ```

3. **Communication Test**
   ```bash
   # Test device connectivity
   php artisan biometric:test-device --device-id=1
   
   # Sync device time
   php artisan biometric:sync-time --device-id=1
   ```

### User Enrollment Management

#### Bulk Enrollment Process
1. **Prepare User List**
   - Export users from system
   - Verify user information
   - Schedule enrollment sessions

2. **Enrollment Procedure**
   ```bash
   # Start enrollment session
   php artisan biometric:start-enrollment
   
   # Monitor enrollment progress
   php artisan biometric:enrollment-status
   ```

3. **Quality Control**
   - Set minimum template quality
   - Require multiple finger enrollment
   - Verify enrollment success
   - Handle enrollment failures

#### Template Management
```bash
# Backup biometric templates
php artisan biometric:backup-templates

# Restore templates
php artisan biometric:restore-templates --file=backup.dat

# Clean invalid templates
php artisan biometric:clean-templates
```

### Data Synchronization

#### Automatic Sync Configuration
```php
// config/biometric.php
'sync_settings' => [
    'interval' => 300, // 5 minutes
    'batch_size' => 100,
    'retry_attempts' => 3,
    'timeout' => 30,
]
```

#### Manual Sync Operations
```bash
# Sync all devices
php artisan biometric:sync-all

# Sync specific device
php artisan biometric:sync --device-id=1

# Force sync (ignore last sync time)
php artisan biometric:sync --force
```

## Bell Schedule Administration

### Schedule Configuration

#### Basic Schedule Setup
1. **Navigate to Bell Management**
   - Go to **Administration → Bell Schedule**
   - Click **"Create New Schedule"**

2. **Schedule Parameters**
   ```
   Schedule Name: Winter Schedule 2024
   Effective From: 2024-11-01
   Effective To: 2024-02-28
   Days: Monday to Saturday
   Status: Active
   ```

3. **Period Configuration**
   ```
   Period 1: 08:00 - 08:40 (Assembly)
   Period 2: 08:40 - 09:20 (First Class)
   Break: 09:20 - 09:35 (Short Break)
   Period 3: 09:35 - 10:15 (Second Class)
   ...and so on
   ```

#### Advanced Schedule Features

1. **Seasonal Schedules**
   ```php
   // Automatic schedule switching
   'seasonal_schedules' => [
       'summer' => [
           'start_date' => '03-01',
           'end_date' => '06-30',
           'schedule_id' => 1,
       ],
       'monsoon' => [
           'start_date' => '07-01',
           'end_date' => '10-31',
           'schedule_id' => 2,
       ],
       'winter' => [
           'start_date' => '11-01',
           'end_date' => '02-28',
           'schedule_id' => 3,
       ],
   ]
   ```

2. **Special Event Schedules**
   - Exam schedules with extended periods
   - Half-day schedules for events
   - Assembly schedules for special occasions
   - Holiday schedules

### Bell System Integration

#### Hardware Configuration
1. **Bell Controller Setup**
   ```
   Controller IP: 192.168.1.200
   Port: 8080
   Protocol: HTTP/TCP
   Zones: 4 (Main Building, Annexe, Playground, Office)
   ```

2. **Audio Configuration**
   ```php
   'bell_sounds' => [
       'class_start' => 'sounds/class_bell.mp3',
       'class_end' => 'sounds/end_bell.mp3',
       'break_start' => 'sounds/break_bell.mp3',
       'assembly' => 'sounds/assembly_bell.mp3',
       'emergency' => 'sounds/emergency_alert.mp3',
   ]
   ```

#### Automated Bell Operations
```bash
# Start bell scheduler
php artisan bell:start-scheduler

# Test bell system
php artisan bell:test --zone=all

# Manual bell trigger
php artisan bell:ring --sound=class_start --zone=1

# Emergency bell
php artisan bell:emergency --message="Emergency evacuation"
```

## Database Management

### Database Maintenance

#### Regular Maintenance Tasks
```bash
# Optimize database tables
php artisan db:optimize

# Clean up old logs
php artisan logs:cleanup --days=30

# Update statistics
php artisan db:update-stats

# Rebuild indexes
php artisan db:rebuild-indexes
```

#### Data Cleanup Procedures
```bash
# Clean old attendance records (older than 2 years)
php artisan cleanup:attendance --older-than=2years

# Remove deleted user data
php artisan cleanup:users --soft-deleted

# Clean temporary files
php artisan cleanup:temp-files

# Optimize storage
php artisan storage:optimize
```

### Database Backup

#### Automated Backup Configuration
```php
// config/backup.php
'backup' => [
    'name' => 'pns-dhampur-backup',
    'source' => [
        'files' => [
            'include' => [
                base_path(),
            ],
            'exclude' => [
                base_path('vendor'),
                base_path('node_modules'),
            ],
        ],
        'databases' => ['mysql'],
    ],
    'destination' => [
        'filename_prefix' => 'backup-',
        'disks' => ['local', 's3'],
    ],
]
```

#### Manual Backup Operations
```bash
# Create full backup
php artisan backup:run

# Database only backup
php artisan backup:run --only-db

# Files only backup
php artisan backup:run --only-files

# List available backups
php artisan backup:list

# Clean old backups
php artisan backup:clean
```

### Database Monitoring

#### Performance Monitoring
```bash
# Monitor slow queries
php artisan db:monitor-slow-queries

# Check database connections
php artisan db:check-connections

# Analyze table sizes
php artisan db:analyze-tables

# Monitor replication (if applicable)
php artisan db:check-replication
```

#### Query Optimization
```sql
-- Identify slow queries
SELECT * FROM mysql.slow_log 
WHERE start_time > DATE_SUB(NOW(), INTERVAL 1 DAY)
ORDER BY query_time DESC;

-- Check index usage
SHOW INDEX FROM students;
SHOW INDEX FROM attendance;

-- Analyze table performance
ANALYZE TABLE students, teachers, attendance;
```

## Backup and Recovery

### Backup Strategy

#### Backup Types
1. **Full System Backup**
   - Complete application and database backup
   - Includes all files, configurations, and data
   - Recommended frequency: Weekly

2. **Incremental Backup**
   - Only changed files since last backup
   - Faster backup process
   - Recommended frequency: Daily

3. **Database Backup**
   - Database content only
   - Quick backup and restore
   - Recommended frequency: Every 6 hours

#### Backup Schedule Configuration
```bash
# Configure cron jobs for automated backups
# Edit crontab: crontab -e

# Full backup every Sunday at 2 AM
0 2 * * 0 cd /path/to/app && php artisan backup:run --only-files

# Database backup every 6 hours
0 */6 * * * cd /path/to/app && php artisan backup:run --only-db

# Incremental backup daily at 1 AM
0 1 * * * cd /path/to/app && php artisan backup:incremental
```

### Recovery Procedures

#### Database Recovery
```bash
# List available backups
php artisan backup:list

# Restore from specific backup
php artisan backup:restore --backup=backup-2024-01-15-02-00-00.zip

# Database only restore
php artisan backup:restore --backup=db-backup-2024-01-15.sql --only-db

# Verify restore integrity
php artisan backup:verify-restore
```

#### File System Recovery
```bash
# Restore application files
php artisan backup:restore-files --backup=files-backup-2024-01-15.zip

# Restore specific directories
php artisan backup:restore-files --directories=storage,public

# Set proper permissions after restore
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
chown -R www-data:www-data storage/
```

#### Disaster Recovery Plan

1. **Immediate Response**
   - Assess the extent of data loss
   - Identify the most recent clean backup
   - Notify stakeholders about the incident
   - Begin recovery procedures

2. **Recovery Steps**
   ```bash
   # Step 1: Prepare clean environment
   # Step 2: Restore database
   php artisan backup:restore --backup=latest --only-db
   
   # Step 3: Restore application files
   php artisan backup:restore --backup=latest --only-files
   
   # Step 4: Verify system integrity
   php artisan system:verify
   
   # Step 5: Test critical functions
   php artisan test:critical-functions
   ```

3. **Post-Recovery Tasks**
   - Verify data integrity
   - Test all system functions
   - Update users about system status
   - Document the incident and recovery process
   - Review and improve backup procedures

## Security Management

### Security Configuration

#### Authentication Security
```php
// config/auth.php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'api' => [
        'driver' => 'sanctum',
        'provider' => 'users',
    ],
],

'password_timeout' => 10800, // 3 hours
```

#### Session Security
```php
// config/session.php
'lifetime' => 120, // 2 hours
'expire_on_close' => true,
'encrypt' => true,
'http_only' => true,
'same_site' => 'strict',
'secure' => true, // HTTPS only
```

#### CSRF Protection
```php
// Ensure CSRF protection is enabled
'csrf' => [
    'enabled' => true,
    'token_lifetime' => 3600, // 1 hour
    'regenerate_on_login' => true,
]
```

### Access Control

#### IP-based Restrictions
```php
// config/security.php
'ip_restrictions' => [
    'admin_panel' => [
        'allowed_ips' => [
            '192.168.1.0/24', // Local network
            '10.0.0.0/8',     // Private network
        ],
        'blocked_ips' => [
            // Specific IPs to block
        ],
    ],
]
```

#### Rate Limiting
```php
// config/rate-limiting.php
'rate_limits' => [
    'login' => '5,1', // 5 attempts per minute
    'api' => '60,1',  // 60 requests per minute
    'password_reset' => '3,60', // 3 attempts per hour
]
```

### Security Monitoring

#### Audit Logging
```bash
# Enable audit logging
php artisan audit:enable

# View audit logs
php artisan audit:view --user-id=1 --date=2024-01-15

# Export audit logs
php artisan audit:export --format=csv --date-range=2024-01-01,2024-01-31
```

#### Security Scanning
```bash
# Run security scan
php artisan security:scan

# Check for vulnerabilities
php artisan security:check-vulnerabilities

# Validate file permissions
php artisan security:check-permissions

# Monitor failed login attempts
php artisan security:monitor-logins
```

### SSL/TLS Configuration

#### Certificate Installation
```bash
# Install SSL certificate
sudo certbot --apache -d your-domain.com

# Auto-renewal setup
sudo crontab -e
0 12 * * * /usr/bin/certbot renew --quiet
```

#### HTTPS Enforcement
```php
// Force HTTPS in production
if (app()->environment('production')) {
    URL::forceScheme('https');
}

// HSTS header
'hsts' => [
    'max_age' => 31536000, // 1 year
    'include_subdomains' => true,
    'preload' => true,
]
```

## Performance Monitoring

### System Performance

#### Performance Metrics
```bash
# Monitor system performance
php artisan monitor:performance

# Check memory usage
php artisan monitor:memory

# Monitor database performance
php artisan monitor:database

# Check queue performance
php artisan monitor:queues
```

#### Performance Optimization
```bash
# Optimize application
php artisan optimize

# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

### Database Performance

#### Query Optimization
```bash
# Enable query logging
php artisan db:enable-query-log

# Analyze slow queries
php artisan db:analyze-slow-queries

# Optimize database tables
php artisan db:optimize-tables

# Update table statistics
php artisan db:update-statistics
```

#### Index Management
```sql
-- Check missing indexes
SELECT * FROM sys.schema_unused_indexes;

-- Add recommended indexes
CREATE INDEX idx_attendance_date ON attendance(attendance_date);
CREATE INDEX idx_student_class ON students(class_id);
CREATE INDEX idx_teacher_subject ON teacher_subjects(teacher_id, subject_id);
```

### Application Monitoring

#### Error Monitoring
```bash
# Monitor application errors
php artisan monitor:errors

# Check error logs
php artisan logs:show --level=error --lines=100

# Clear error logs
php artisan logs:clear --older-than=30days
```

#### Health Checks
```bash
# Run health checks
php artisan health:check

# Check database connectivity
php artisan health:database

# Check external services
php artisan health:external-services

# Generate health report
php artisan health:report --format=json
```

## Troubleshooting

### Common Issues

#### Login Problems
1. **Users Cannot Login**
   ```bash
   # Check user account status
   php artisan user:check --email=user@example.com
   
   # Reset user password
   php artisan user:reset-password --email=user@example.com
   
   # Clear sessions
   php artisan session:clear
   ```

2. **Session Issues**
   ```bash
   # Clear application cache
   php artisan cache:clear
   
   # Clear configuration cache
   php artisan config:clear
   
   # Restart session driver
   php artisan session:restart
   ```

#### Database Issues
1. **Connection Problems**
   ```bash
   # Test database connection
   php artisan db:test-connection
   
   # Check database configuration
   php artisan config:show database
   
   # Verify database credentials
   mysql -u username -p -h hostname database_name
   ```

2. **Performance Issues**
   ```bash
   # Check slow queries
   php artisan db:slow-queries --limit=10
   
   # Optimize tables
   php artisan db:optimize
   
   # Update statistics
   php artisan db:analyze
   ```

#### Biometric System Issues
1. **Device Connectivity**
   ```bash
   # Test device connection
   php artisan biometric:test --device-id=1
   
   # Ping device
   ping 192.168.1.100
   
   # Check device status
   php artisan biometric:status --device-id=1
   ```

2. **Sync Problems**
   ```bash
   # Force sync
   php artisan biometric:sync --force --device-id=1
   
   # Clear sync cache
   php artisan biometric:clear-cache
   
   # Reset device connection
   php artisan biometric:reset-connection --device-id=1
   ```

### Diagnostic Tools

#### System Diagnostics
```bash
# Run comprehensive system check
php artisan system:diagnose

# Check file permissions
php artisan system:check-permissions

# Verify configuration
php artisan system:verify-config

# Test external connections
php artisan system:test-connections
```

#### Log Analysis
```bash
# View recent logs
php artisan logs:tail

# Search logs
php artisan logs:search --term="error" --lines=50

# Analyze log patterns
php artisan logs:analyze --date=2024-01-15

# Export logs
php artisan logs:export --format=csv --date-range=2024-01-01,2024-01-31
```

## Maintenance Procedures

### Regular Maintenance

#### Daily Tasks
```bash
# Check system health
php artisan health:check

# Monitor error logs
php artisan logs:check --level=error

# Verify backup completion
php artisan backup:verify

# Check disk space
df -h
```

#### Weekly Tasks
```bash
# Optimize database
php artisan db:optimize

# Clean temporary files
php artisan cleanup:temp

# Update system statistics
php artisan stats:update

# Review security logs
php artisan security:review-logs
```

#### Monthly Tasks
```bash
# Full system backup verification
php artisan backup:verify --full

# Performance analysis
php artisan performance:analyze --month

# Security audit
php artisan security:audit

# User account review
php artisan users:review-inactive
```

### Preventive Maintenance

#### Database Maintenance
```bash
# Rebuild indexes
php artisan db:rebuild-indexes

# Update table statistics
php artisan db:update-stats

# Check table integrity
php artisan db:check-integrity

# Optimize storage
php artisan db:optimize-storage
```

#### File System Maintenance
```bash
# Clean old logs
find /path/to/logs -name "*.log" -mtime +30 -delete

# Clean temporary files
php artisan cleanup:temp-files

# Optimize storage
php artisan storage:optimize

# Check file permissions
php artisan system:fix-permissions
```

## API Management

### API Configuration

#### API Security
```php
// config/sanctum.php
'stateful' => [
    'localhost',
    'localhost:3000',
    '127.0.0.1',
    '127.0.0.1:8000',
    '::1',
    'your-domain.com',
],

'expiration' => 525600, // 1 year in minutes
'token_prefix' => 'pns_',
```

#### Rate Limiting
```php
// API rate limits
'api' => [
    'throttle:api',
    'auth:sanctum',
],

'rate_limits' => [
    'api' => '60,1', // 60 requests per minute
    'auth' => '5,1', // 5 auth attempts per minute
]
```

### API Monitoring

#### Usage Analytics
```bash
# Monitor API usage
php artisan api:usage-stats

# Check rate limit violations
php artisan api:rate-limit-violations

# Analyze API performance
php artisan api:performance-report

# Export API logs
php artisan api:export-logs --date=2024-01-15
```

#### API Health Checks
```bash
# Test API endpoints
php artisan api:test-endpoints

# Validate API responses
php artisan api:validate-responses

# Check API authentication
php artisan api:test-auth

# Monitor API uptime
php artisan api:uptime-check
```

## System Updates

### Update Procedures

#### Application Updates
```bash
# Backup before update
php artisan backup:run

# Put application in maintenance mode
php artisan down

# Update application code
git pull origin main

# Update dependencies
composer install --no-dev --optimize-autoloader

# Run database migrations
php artisan migrate --force

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Bring application back online
php artisan up
```

#### Security Updates
```bash
# Check for security updates
composer audit

# Update security patches
composer update --with-dependencies

# Run security scan
php artisan security:scan

# Update system packages
sudo apt update && sudo apt upgrade
```

### Version Management

#### Release Management
```bash
# Tag new release
git tag -a v1.2.0 -m "Release version 1.2.0"

# Create release notes
php artisan release:notes --version=1.2.0

# Deploy to staging
php artisan deploy:staging

# Deploy to production
php artisan deploy:production
```

#### Rollback Procedures
```bash
# Rollback to previous version
php artisan rollback:previous

# Rollback to specific version
php artisan rollback:version --version=1.1.0

# Rollback database migrations
php artisan migrate:rollback --step=1

# Restore from backup if needed
php artisan backup:restore --backup=pre-update-backup.zip
```

---

## Support and Resources

### Technical Support
- **Email**: admin-support@pns-dhampur.edu.in
- **Phone**: +91-XXXX-XXXXXX (24/7 for critical issues)
- **Documentation**: https://docs.pns-dhampur.edu.in
- **Issue Tracker**: https://github.com/pns-dhampur/issues

### Training Resources
- **Administrator Training**: Monthly training sessions
- **Video Tutorials**: Available in documentation portal
- **Best Practices Guide**: System optimization guidelines
- **Community Forum**: https://community.pns-dhampur.edu.in

### Emergency Contacts
- **System Administrator**: [Name] - +91-XXXX-XXXXXX
- **Database Administrator**: [Name] - +91-XXXX-XXXXXX
- **Network Administrator**: [Name] - +91-XXXX-XXXXXX
- **Security Officer**: [Name] - +91-XXXX-XXXXXX

---

*This administrator guide is regularly updated with new features and procedures. Please ensure you have the latest version and subscribe to update notifications.*