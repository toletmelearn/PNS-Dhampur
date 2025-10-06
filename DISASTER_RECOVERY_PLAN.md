# Disaster Recovery Plan - PNS Dhampur School Management System

## Overview
This document outlines the comprehensive disaster recovery procedures for the PNS Dhampur School Management System to ensure business continuity and data protection in case of system failures, data corruption, or other disasters.

## 1. Backup Strategy

### 1.1 Automated Database Backups
- **Frequency**: Daily at 2:00 AM
- **Retention**: 30 days for daily backups, 12 months for monthly backups
- **Command**: `php artisan backup:database`
- **Storage**: Local storage + Cloud backup (if configured)

### 1.2 File Storage Backups
- **Frequency**: Weekly on Sundays at 3:00 AM
- **Retention**: 4 weeks for weekly backups, 6 months for monthly backups
- **Command**: `php artisan backup:files`
- **Includes**: Student documents, teacher files, system uploads

### 1.3 Full System Export
- **Frequency**: Monthly on the 1st at 1:00 AM
- **Command**: `php artisan data:export --format=json --all`
- **Purpose**: Complete system migration capability

## 2. Recovery Procedures

### 2.1 Database Recovery

#### Minor Data Loss (Recent Changes)
1. **Identify the Issue**
   ```bash
   # Check backup logs
   php artisan backup:logs --type=database --recent
   ```

2. **Restore from Latest Backup**
   ```bash
   # Import latest database backup
   php artisan data:import /path/to/backup.sql --format=sql
   ```

#### Major Database Corruption
1. **Stop the Application**
   ```bash
   # Stop web server
   php artisan down --message="System maintenance in progress"
   ```

2. **Create Current State Backup** (if possible)
   ```bash
   php artisan backup:database --emergency
   ```

3. **Restore from Known Good Backup**
   ```bash
   # List available backups
   php artisan backup:list --type=database
   
   # Restore specific backup
   php artisan data:import /backups/database_2025_10_05_020000.sql --format=sql --truncate
   ```

4. **Verify Data Integrity**
   ```bash
   # Run system checks
   php artisan backup:verify
   ```

5. **Bring System Online**
   ```bash
   php artisan up
   ```

### 2.2 File System Recovery

#### Lost Student/Teacher Documents
1. **Identify Missing Files**
   ```bash
   # Check file backup logs
   php artisan backup:logs --type=files --recent
   ```

2. **Restore from File Backup**
   ```bash
   # Extract specific directories from backup
   php artisan backup:restore-files /backups/files_2025_10_05.zip --directory=storage/app/students
   ```

#### Complete File System Loss
1. **Restore Full File Backup**
   ```bash
   # Restore complete file system
   php artisan backup:restore-files /backups/files_2025_10_05.zip --full
   ```

2. **Set Proper Permissions**
   ```bash
   # Fix file permissions
   chmod -R 755 storage/
   chmod -R 644 storage/app/
   ```

### 2.3 Complete System Recovery

#### Total System Failure
1. **Prepare Clean Environment**
   - Install fresh Laravel application
   - Configure database connection
   - Set up web server

2. **Restore Database**
   ```bash
   php artisan migrate:fresh
   php artisan data:import /backups/complete_export_2025_10_01.json --format=json
   ```

3. **Restore Files**
   ```bash
   php artisan backup:restore-files /backups/files_2025_10_01.zip --full
   ```

4. **Verify System Integrity**
   ```bash
   php artisan backup:verify --full
   ```

## 3. Emergency Contacts

### Technical Team
- **System Administrator**: [Contact Information]
- **Database Administrator**: [Contact Information]
- **Lead Developer**: [Contact Information]

### Management
- **IT Manager**: [Contact Information]
- **Principal**: [Contact Information]
- **Administrative Officer**: [Contact Information]

## 4. Recovery Time Objectives (RTO)

| Disaster Type | Target Recovery Time | Maximum Acceptable Downtime |
|---------------|---------------------|----------------------------|
| Database Corruption | 2 hours | 4 hours |
| File System Loss | 4 hours | 8 hours |
| Complete System Failure | 8 hours | 24 hours |
| Hardware Failure | 6 hours | 12 hours |

## 5. Recovery Point Objectives (RPO)

| Data Type | Maximum Data Loss | Backup Frequency |
|-----------|------------------|------------------|
| Student Records | 24 hours | Daily |
| Financial Data | 24 hours | Daily |
| Attendance Data | 24 hours | Daily |
| Documents/Files | 7 days | Weekly |

## 6. Testing and Validation

### 6.1 Monthly Recovery Tests
- Test database restoration on staging environment
- Verify backup integrity
- Document any issues or improvements needed

### 6.2 Quarterly Full Recovery Tests
- Complete system recovery simulation
- Performance testing after recovery
- Update procedures based on findings

### 6.3 Annual Disaster Recovery Drill
- Full-scale disaster simulation
- All team members participate
- Complete documentation review and update

## 7. Backup Monitoring and Alerts

### 7.1 Automated Monitoring
```bash
# Check backup status daily
php artisan backup:monitor

# Send alerts for failed backups
php artisan backup:alert --email=admin@pnsdhampur.edu
```

### 7.2 Manual Verification
- Weekly backup integrity checks
- Monthly restore testing
- Quarterly storage space monitoring

## 8. Security Considerations

### 8.1 Backup Encryption
- All backups are compressed and can be encrypted
- Use strong passwords for backup files
- Secure transmission of backup files

### 8.2 Access Control
- Limit backup access to authorized personnel only
- Maintain audit logs of backup operations
- Regular review of access permissions

## 9. Documentation Updates

### 9.1 Maintenance Schedule
- Review procedures quarterly
- Update contact information as needed
- Incorporate lessons learned from incidents

### 9.2 Version Control
- Keep previous versions of this document
- Track all changes with dates and reasons
- Distribute updates to all stakeholders

## 10. Backup Commands Reference

### Database Operations
```bash
# Create database backup
php artisan backup:database [--incremental] [--compress]

# List database backups
php artisan backup:list --type=database

# Import database backup
php artisan data:import {file} --format=sql [--truncate] [--ignore-errors]
```

### File Operations
```bash
# Create file backup
php artisan backup:files [--directories=storage,public] [--exclude=logs,cache]

# Restore file backup
php artisan backup:restore-files {backup-file} [--directory=specific-dir] [--full]
```

### Export/Import Operations
```bash
# Export all data
php artisan data:export --format=json --all

# Export specific tables
php artisan data:export --format=csv --tables=students,teachers,classes

# Import data
php artisan data:import {file} [--format=auto] [--truncate] [--ignore-errors]
```

### Monitoring Operations
```bash
# Check backup status
php artisan backup:status

# View backup logs
php artisan backup:logs [--type=database|files|export] [--recent]

# Verify backup integrity
php artisan backup:verify [--backup-file=specific-file]
```

---

**Document Version**: 1.0  
**Last Updated**: October 5, 2025  
**Next Review Date**: January 5, 2026  
**Approved By**: [System Administrator Name]