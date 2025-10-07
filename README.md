# 🏫 PNS Dhampur School Management System

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-9.x-red?style=for-the-badge&logo=laravel" alt="Laravel">
  <img src="https://img.shields.io/badge/PHP-8.0+-blue?style=for-the-badge&logo=php" alt="PHP">
  <img src="https://img.shields.io/badge/Flutter-3.x-blue?style=for-the-badge&logo=flutter" alt="Flutter">
  <img src="https://img.shields.io/badge/MySQL-8.0-orange?style=for-the-badge&logo=mysql" alt="MySQL">
  <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge" alt="License">
</p>

<p align="center">
  A comprehensive, modern school management system designed specifically for PNS Dhampur School, featuring advanced automation, security, and mobile accessibility.
</p>

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Technology Stack](#technology-stack)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [API Documentation](#api-documentation)
- [Mobile Application](#mobile-application)
- [Testing](#testing)
- [Security](#security)
- [Backup & Recovery](#backup--recovery)
- [Contributing](#contributing)
- [License](#license)
- [Support](#support)

## 🎯 Overview

The PNS Dhampur School Management System is a full-featured, enterprise-grade solution designed to streamline school operations, enhance educational delivery, and provide comprehensive management tools for administrators, teachers, students, and parents.

### Key Highlights
- 🚀 **Modern Architecture**: Built with Laravel 9.x and Flutter
- 🔐 **Enterprise Security**: Multi-layer authentication and authorization
- 📱 **Mobile-First**: Native mobile app for iOS and Android
- 🤖 **AI-Powered**: Intelligent substitution system and analytics
- 📊 **Advanced Analytics**: Comprehensive reporting and insights
- 🔄 **Real-Time**: Live notifications and updates
- 🌐 **Multi-Platform**: Web, mobile, and API access

## ✨ Features

### 👥 User Management
- **Multi-Role System**: Admin, Principal, Teachers, Students, Parents
- **Advanced Authentication**: Session security, password policies
- **User Activity Tracking**: Comprehensive audit trails
- **Permission Management**: Granular access control

### 📚 Academic Management
- **Student Information System**: Complete student profiles and records
- **Class Management**: Dynamic class allocation and management
- **Subject Management**: Curriculum and syllabus tracking
- **Assignment System**: Digital assignment submission and grading
- **Exam Management**: Comprehensive examination system with security

### 👨‍🏫 Teacher Management
- **Teacher Profiles**: Complete professional information
- **Substitution System**: AI-powered automatic teacher substitution
- **Availability Tracking**: Real-time teacher availability
- **Performance Analytics**: Teaching effectiveness metrics
- **Document Management**: Digital document storage and verification

### 📊 Attendance System
- **Biometric Integration**: Fingerprint and facial recognition support
- **Real-Time Tracking**: Live attendance monitoring
- **Analytics Dashboard**: Attendance patterns and insights
- **Regularization System**: Attendance correction workflows
- **Mobile Attendance**: Mobile app-based attendance marking

### 💰 Financial Management
- **Fee Management**: Comprehensive fee collection system
- **Payment Processing**: Multiple payment gateway integration
- **Budget Management**: Department-wise budget allocation
- **Financial Reporting**: Detailed financial analytics
- **Vendor Management**: Supplier and vendor tracking

### 📱 Bell Schedule System
- **Automated Bell System**: Programmable school bell automation
- **Special Schedules**: Holiday and event-specific schedules
- **Mobile Notifications**: Real-time schedule updates
- **Audio Integration**: Automated announcement system

### 📈 Analytics & Reporting
- **Performance Dashboards**: Real-time KPI monitoring
- **Custom Reports**: Flexible report generation
- **Data Visualization**: Interactive charts and graphs
- **Export Capabilities**: PDF, Excel, CSV export options
- **Predictive Analytics**: AI-powered insights

### 🔧 System Administration
- **Backup System**: Automated backup and recovery
- **System Health Monitoring**: Performance tracking
- **Error Logging**: Comprehensive error tracking
- **Configuration Management**: System-wide settings
- **Maintenance Tools**: Database optimization and cleanup

## 🛠 Technology Stack

### Backend
- **Framework**: Laravel 9.x
- **Language**: PHP 8.0+
- **Database**: MySQL 8.0
- **Authentication**: Laravel Sanctum
- **Queue System**: Redis/Database
- **File Storage**: Local/Cloud Storage
- **PDF Generation**: DomPDF
- **Excel Processing**: Maatwebsite Excel

### Frontend
- **Web**: Blade Templates, JavaScript ES6+, CSS3
- **Mobile**: Flutter 3.x (Dart)
- **UI Framework**: Bootstrap 5, Material Design
- **Charts**: Chart.js, ApexCharts
- **Icons**: Font Awesome, Material Icons

### DevOps & Tools
- **Version Control**: Git
- **Testing**: PHPUnit, Flutter Test
- **Code Quality**: Laravel Pint, ESLint
- **Documentation**: Markdown
- **Deployment**: Apache/Nginx, Docker support

## 🚀 Installation

### Prerequisites
- PHP 8.0 or higher
- Composer
- Node.js & NPM
- MySQL 8.0
- Apache/Nginx web server
- Flutter SDK (for mobile app)

### Backend Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-repo/pns-dhampur.git
   cd pns-dhampur
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Environment configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. **Storage setup**
   ```bash
   php artisan storage:link
   ```

6. **Start the development server**
   ```bash
   php artisan serve
   ```

### Mobile App Setup

1. **Navigate to mobile app directory**
   ```bash
   cd pns_dhampur_app
   ```

2. **Install Flutter dependencies**
   ```bash
   flutter pub get
   ```

3. **Run the mobile app**
   ```bash
   flutter run
   ```

## ⚙️ Configuration

### Environment Variables
```env
APP_NAME="PNS Dhampur School Management System"
APP_ENV=production
APP_KEY=your-app-key
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pns_dhampur
DB_USERNAME=your-username
DB_PASSWORD=your-password

# Additional configurations for features
BIOMETRIC_ENABLED=true
BELL_SYSTEM_ENABLED=true
MOBILE_APP_ENABLED=true
```

### System Settings
Access the admin panel to configure:
- School information and branding
- Academic year settings
- Fee structures
- Bell schedules
- Notification preferences
- Security policies

## 📖 Usage

### Default Login Credentials

**Super Admin**
- Email: `admin@pnsdhampur.local`
- Password: `Password123`

**Test Teacher**
- Email: `test@teacher.com`
- Password: `password123`

**Test Student**
- Email: `student1@pns-dhampur.edu`
- Password: `password123`

### Quick Start Guide

1. **Admin Setup**: Configure school settings, academic year, and user roles
2. **User Management**: Create teacher and student accounts
3. **Class Setup**: Define classes, subjects, and assignments
4. **Attendance**: Configure biometric devices and attendance policies
5. **Fee Management**: Set up fee structures and payment methods
6. **Mobile App**: Deploy mobile app for teachers and students

## 🔌 API Documentation

The system provides RESTful APIs for all major functionalities:

### Authentication
```http
POST /api/login
POST /api/logout
POST /api/refresh
```

### Student Management
```http
GET /api/students
POST /api/students
PUT /api/students/{id}
DELETE /api/students/{id}
```

### Attendance
```http
GET /api/attendance
POST /api/attendance/bulk
GET /api/attendance/analytics
```

### Bell Schedule
```http
GET /api/bell-schedule
POST /api/bell-schedule/trigger
```

For complete API documentation, visit `/api/documentation` after installation.

## 📱 Mobile Application

The Flutter mobile app provides:
- **Teacher Dashboard**: Attendance marking, class management
- **Student Portal**: Assignments, schedules, notifications
- **Parent Access**: Student progress, fee payments
- **Offline Support**: Core features work without internet
- **Push Notifications**: Real-time updates and alerts

### Mobile App Features
- Cross-platform (iOS & Android)
- Biometric authentication
- Offline attendance marking
- Real-time synchronization
- Push notifications
- Dark mode support

## 🧪 Testing

### Running Tests

**Backend Tests**
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test tests/Feature/StudentTest.php

# Run with coverage
php artisan test --coverage
```

**Mobile App Tests**
```bash
# Run Flutter tests
flutter test

# Run integration tests
flutter drive --target=test_driver/app.dart
```

### Test Coverage
- Unit Tests: 95%+ coverage
- Feature Tests: 90%+ coverage
- Integration Tests: 85%+ coverage

## 🔐 Security

### Security Features
- **Multi-Factor Authentication**: SMS and email verification
- **Role-Based Access Control**: Granular permissions
- **Session Security**: Advanced session management
- **Data Encryption**: Sensitive data encryption
- **Audit Logging**: Comprehensive activity tracking
- **SQL Injection Protection**: Parameterized queries
- **XSS Protection**: Input sanitization
- **CSRF Protection**: Token-based protection

### Security Testing
Regular security audits and penetration testing ensure system integrity.

## 💾 Backup & Recovery

### Automated Backup System
- **Daily Database Backups**: 2:00 AM daily
- **Weekly File Backups**: Sundays at 3:00 AM
- **Monthly Full Exports**: 1st of each month
- **Retention Policy**: 30 days for daily, 12 months for monthly

### Manual Backup Commands
```bash
# Database backup
php artisan backup:database

# File system backup
php artisan backup:files

# Full system export
php artisan data:export --format=json --all
```

### Disaster Recovery
Comprehensive disaster recovery procedures are documented in `DISASTER_RECOVERY_PLAN.md`.

## 🤝 Contributing

We welcome contributions! Please read our contributing guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Guidelines
- Follow PSR-12 coding standards
- Write comprehensive tests
- Update documentation
- Follow semantic versioning

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

### Documentation
- **Setup Guide**: `BACKUP_SETUP_GUIDE.md`
- **Security Manual**: `manual_security_test.md`
- **API Documentation**: Available at `/api/documentation`

### Getting Help
- **Email**: support@pnsdhampur.edu
- **Documentation**: Check the `/docs` directory
- **Issues**: Create an issue on GitHub
- **Community**: Join our Discord server

### System Requirements
- **Minimum**: PHP 8.0, MySQL 5.7, 2GB RAM
- **Recommended**: PHP 8.1+, MySQL 8.0+, 4GB RAM, SSD storage

---

<p align="center">
  <strong>Built with ❤️ for PNS Dhampur School</strong><br>
  <em>Empowering Education Through Technology</em>
</p>

<p align="center">
  <a href="#top">⬆️ Back to Top</a>
</p>
