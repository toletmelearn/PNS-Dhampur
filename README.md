# 🏫 PNS Dhampur School Management System

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-10.x-red?style=for-the-badge&logo=laravel" alt="Laravel">
  <img src="https://img.shields.io/badge/PHP-8.1+-blue?style=for-the-badge&logo=php" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-8.0-orange?style=for-the-badge&logo=mysql" alt="MySQL">
  <img src="https://img.shields.io/badge/Bootstrap-5.x-purple?style=for-the-badge&logo=bootstrap" alt="Bootstrap">
  <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge" alt="License">
</p>

<p align="center">
  A comprehensive, modern school management system designed specifically for PNS Dhampur School, featuring advanced automation, security, and comprehensive academic management.
</p>

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Technology Stack](#technology-stack)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Testing](#testing)
- [Security](#security)
- [Deployment](#deployment)
- [Contributing](#contributing)
- [License](#license)
- [Support](#support)

## 🎯 Overview

The PNS Dhampur School Management System is a full-featured, enterprise-grade solution designed to streamline school operations, enhance educational delivery, and provide comprehensive management tools for administrators, teachers, students, and parents.

### Key Highlights
- 🚀 **Modern Architecture**: Built with Laravel 10.x
- 🔐 **Enterprise Security**: Multi-layer authentication and authorization
- 📊 **Advanced Analytics**: Comprehensive reporting and insights
- 🔄 **Real-Time**: Live notifications and updates
- 🌐 **Web-Based**: Responsive design for all devices
- ✅ **Production Ready**: Fully tested and verified system

## ✨ Features

### 👥 User Management
- **Multi-Role Authentication**: Admin, Teacher, Student, Parent access levels
- **Secure Login System**: Role-based dashboard redirection
- **User Profile Management**: Complete profile management for all user types
- **Password Security**: Advanced password policies and security

### 📚 Academic Management
- **Class & Section Management**: Organize students by classes and sections
- **Subject Management**: Comprehensive subject allocation and tracking
- **Student Enrollment**: Complete student registration and management
- **Teacher Assignment**: Teacher-subject-class allocation system
- **Academic Year Management**: Multi-year academic planning

### 📊 Attendance System
- **Daily Attendance**: Mark and track student attendance
- **Attendance Reports**: Generate detailed attendance analytics
- **Biometric Integration**: Support for biometric attendance devices
- **Attendance Alerts**: Automated notifications for low attendance

### 📝 Examination System
- **Exam Management**: Create and manage various types of examinations
- **Result Processing**: Automated result calculation and grade assignment
- **Report Cards**: Generate comprehensive student report cards
- **Performance Analytics**: Track student academic performance
- **Admit Card Generation**: Automated admit card creation

### 💰 Fee Management
- **Fee Structure**: Define and manage fee structures by class
- **Payment Tracking**: Track fee payments and outstanding amounts
- **Receipt Generation**: Automated fee receipt generation
- **Financial Reports**: Comprehensive financial reporting
- **Payment Reminders**: Automated fee reminder system

### 📢 Communication & Notifications
- **Notification System**: Send notifications to students, teachers, and parents
- **Announcements**: School-wide announcement management
- **Email Integration**: Automated email notifications
- **SMS Integration**: SMS notification support

### ⏰ Schedule Management
- **Bell Timing**: Configure school bell schedules
- **Timetable Management**: Create and manage class timetables
- **Holiday Calendar**: Manage academic calendar and holidays
- **Event Management**: School event planning and tracking

### 📋 Administrative Features
- **Data Audit**: Track all system changes and modifications
- **Document Management**: Teacher document and experience tracking
- **System Settings**: Comprehensive system configuration
- **Backup Management**: Automated data backup systems
- **User Activity Logs**: Complete audit trail

## 🛠️ Technology Stack

- **Backend**: Laravel 10.x (PHP 8.1+)
- **Frontend**: Blade Templates with Bootstrap 5
- **Database**: MySQL 8.0+
- **Authentication**: Laravel Sanctum
- **File Storage**: Laravel Storage
- **Task Scheduling**: Laravel Scheduler
- **Testing**: PHPUnit
- **Build Tools**: Vite
- **Version Control**: Git

## 📋 Requirements

### System Requirements
- **PHP**: 8.1 or higher
- **Composer**: Latest version
- **Node.js**: 16.x or higher
- **NPM**: 8.x or higher
- **MySQL**: 8.0 or higher
- **Apache/Nginx**: Web server
- **Git**: Version control

### PHP Extensions
- OpenSSL PHP Extension
- PDO PHP Extension
- Mbstring PHP Extension
- Tokenizer PHP Extension
- XML PHP Extension
- Ctype PHP Extension
- JSON PHP Extension
- BCMath PHP Extension

## 🚀 Installation

### 1. Clone the Repository
```bash
git clone https://github.com/yourusername/pns-dhampur-school-management.git
cd pns-dhampur-school-management
```

### 2. Install PHP Dependencies
```bash
composer install
```

### 3. Install Node.js Dependencies
```bash
npm install
```

### 4. Environment Configuration
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 5. Database Setup
```bash
# Configure your database in .env file
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pns_dhampur
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Run migrations
php artisan migrate

# Seed the database (optional)
php artisan db:seed
```

### 6. Storage Setup
```bash
# Create storage link
php artisan storage:link

# Set proper permissions (Linux/Mac)
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### 7. Build Frontend Assets
```bash
npm run build
```

### 8. Start the Application
```bash
# Development server
php artisan serve

# Or configure your web server to point to the public directory
```

## ⚙️ Configuration

### Environment Variables
Key environment variables to configure:

```env
APP_NAME="PNS Dhampur School Management"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pns_dhampur
DB_USERNAME=your_username
DB_PASSWORD=your_password

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls

SESSION_DRIVER=database
CACHE_DRIVER=file
QUEUE_CONNECTION=database
```

### Default Admin Credentials
After running migrations and seeders:
- **Email**: admin@pnsdhampur.edu.in
- **Password**: admin123

**⚠️ Important**: Change the default admin password immediately after first login.

## 🧪 Testing

### Run All Tests
```bash
php artisan test
```

### Run Specific Test Suites
```bash
# Unit tests
php artisan test --testsuite=Unit

# Feature tests
php artisan test --testsuite=Feature
```

### Code Coverage
```bash
php artisan test --coverage
```

### System Health Check
```bash
# Verify routes
php artisan route:list

# Check migration status
php artisan migrate:status

# Run tests
php artisan test

# Build frontend assets
npm run build
```

## 🔒 Security Features

- **CSRF Protection**: All forms protected against CSRF attacks
- **SQL Injection Prevention**: Eloquent ORM with prepared statements
- **XSS Protection**: Input sanitization and output escaping
- **Authentication**: Secure user authentication with password hashing
- **Authorization**: Role-based access control (RBAC)
- **Data Validation**: Comprehensive input validation
- **Security Headers**: Proper security headers configuration
- **Session Security**: Secure session management
- **Password Policies**: Enforced password complexity
- **Audit Logging**: Complete activity tracking

## 📁 Project Structure

```
├── app/
│   ├── Http/Controllers/     # Application controllers
│   ├── Models/              # Eloquent models
│   ├── Services/            # Business logic services
│   ├── Middleware/          # Custom middleware
│   └── Providers/           # Service providers
├── database/
│   ├── migrations/          # Database migrations
│   ├── seeders/            # Database seeders
│   └── factories/          # Model factories
├── resources/
│   ├── views/              # Blade templates
│   ├── js/                 # JavaScript files
│   └── css/                # CSS files
├── routes/
│   ├── web.php             # Web routes
│   └── api.php             # API routes
├── storage/
│   ├── app/                # Application files
│   ├── framework/          # Framework files
│   └── logs/               # Application logs
├── tests/
│   ├── Feature/            # Feature tests
│   └── Unit/               # Unit tests
└── public/
    ├── css/                # Compiled CSS
    ├── js/                 # Compiled JavaScript
    └── images/             # Static images
```

## 🚀 Deployment

### Production Deployment Steps

1. **Server Setup**
   ```bash
   # Update server
   sudo apt update && sudo apt upgrade -y
   
   # Install required packages
   sudo apt install php8.1 php8.1-fpm nginx mysql-server composer nodejs npm
   ```

2. **Application Deployment**
   ```bash
   # Clone repository
   git clone https://github.com/yourusername/pns-dhampur-school-management.git
   cd pns-dhampur-school-management
   
   # Install dependencies
   composer install --optimize-autoloader --no-dev
   npm install && npm run build
   
   # Set permissions
   sudo chown -R www-data:www-data storage bootstrap/cache
   sudo chmod -R 775 storage bootstrap/cache
   ```

3. **Environment Configuration**
   ```bash
   # Configure production environment
   cp .env.example .env
   php artisan key:generate
   
   # Run migrations
   php artisan migrate --force
   
   # Cache configuration
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

### Performance Optimization
```bash
# Optimize autoloader
composer install --optimize-autoloader --no-dev

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize images and assets
npm run build
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Guidelines
- Follow PSR-12 coding standards
- Write comprehensive tests
- Update documentation
- Follow semantic versioning

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

For support and questions:
- **Email**: support@pnsdhampur.edu.in
- **Phone**: +91-XXXXXXXXXX
- **Address**: PNS Dhampur, Uttar Pradesh, India

### Documentation
- [Installation Guide](docs/installation.md)
- [User Manual](docs/user-manual.md)
- [API Documentation](docs/api.md)
- [Troubleshooting](docs/troubleshooting.md)

## 🙏 Acknowledgments

- Laravel Framework Team
- Bootstrap Team
- All contributors and testers
- PNS Dhampur Administration

## 📊 System Status

✅ **Routes**: Verified and configured  
✅ **Database**: Migrations applied successfully  
✅ **Tests**: Core functionality verified  
✅ **Frontend**: Assets compiled and optimized  
✅ **Security**: Security measures implemented  
✅ **Production**: Ready for deployment  

---

**Made with ❤️ for PNS Dhampur School**

*Last Updated: January 2025*