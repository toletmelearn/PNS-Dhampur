# Backup System Setup Guide

## Overview
This guide explains how to set up and use the comprehensive backup system for the PNS Dhampur School Management System.

## Automated Backup Schedule

The system is configured with the following automated backup schedule:

### Daily Backups
- **Database Backup**: Every day at 2:00 AM
  - Creates compressed database backup
  - Prevents overlapping executions
  - Logs failures automatically

### Weekly Backups
- **File System Backup**: Every Sunday at 3:00 AM
  - Creates compressed backup of all files
  - Excludes unnecessary directories (node_modules, vendor, .git)
  - Prevents overlapping executions

### Monthly Backups
- **Full Data Export**: 1st of every month at 4:00 AM
  - Exports all database tables in JSON format
  - Complete system data snapshot

### Daily Cleanup
- **Old Backup Cleanup**: Every day at 5:00 AM
  - Removes backup files older than 30 days
  - Maintains storage efficiency

## Manual Backup Commands

### Database Backup
```bash
# Basic database backup
php artisan backup:database

# Compressed database backup
php artisan backup:database --compress

# Backup specific tables
php artisan backup:database --tables=users,students,teachers
```

### File System Backup
```bash
# Basic file backup
php artisan backup:files

# Compressed file backup with exclusions
php artisan backup:files --compress --exclude=node_modules,vendor,.git

# Backup specific directories
php artisan backup:files --directories=storage/app,public/uploads
```

### Data Export/Import
```bash
# Export all data in JSON format
php artisan data:export --format=json --all-tables

# Export specific tables in CSV format
php artisan data:export --format=csv --tables=users,students

# Import data from file
php artisan data:import /path/to/backup/file.json

# Import with table truncation
php artisan data:import /path/to/backup/file.json --truncate
```

## Web Interface

Access the backup management interface at:
```
http://your-domain.com/admin/backups
```

### Features:
- View backup statistics and history
- Create manual backups
- Download backup files
- Delete old backups
- Export/import data
- Monitor backup logs

## Setting Up Automated Scheduling

### On Windows (XAMPP)
1. Open Task Scheduler
2. Create a new task
3. Set trigger to run every minute
4. Set action to run:
   ```
   C:\xampp\php\php.exe C:\xampp\htdocs\PNS-Dhampur\artisan schedule:run
   ```

### On Linux/Unix
Add to crontab:
```bash
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

## Storage Locations

### Default Backup Locations:
- **Database Backups**: `storage/app/backups/database/`
- **File Backups**: `storage/app/backups/files/`
- **Data Exports**: `storage/app/exports/`

### Backup Log Database:
All backup operations are logged in the `backup_logs` table with:
- Operation type and status
- File paths and sizes
- Execution times and duration
- Error messages (if any)

## Monitoring and Alerts

### Log Files:
- Laravel logs: `storage/logs/laravel.log`
- Backup failures are automatically logged

### Database Monitoring:
Check backup status via web interface or database:
```sql
SELECT * FROM backup_logs ORDER BY created_at DESC LIMIT 10;
```

## Security Considerations

1. **File Permissions**: Ensure backup directories have proper permissions
2. **Access Control**: Backup interface is restricted to admin users
3. **Encryption**: Consider encrypting sensitive backup files
4. **Off-site Storage**: Regularly copy backups to external storage

## Troubleshooting

### Common Issues:

1. **Permission Errors**:
   ```bash
   chmod -R 755 storage/app/backups
   chown -R www-data:www-data storage/app/backups
   ```

2. **Disk Space Issues**:
   - Monitor available disk space
   - Adjust cleanup schedule if needed
   - Consider external storage for large backups

3. **Schedule Not Running**:
   - Verify cron job is set up correctly
   - Check Laravel logs for errors
   - Ensure PHP CLI is accessible

### Testing Backups:

1. **Test Database Backup**:
   ```bash
   php artisan backup:database --test
   ```

2. **Test File Backup**:
   ```bash
   php artisan backup:files --test
   ```

3. **Test Recovery**:
   - Restore backup to test environment
   - Verify data integrity
   - Test application functionality

## Recovery Procedures

Refer to `DISASTER_RECOVERY_PLAN.md` for detailed recovery procedures.

## Support

For issues or questions:
1. Check Laravel logs
2. Review backup logs in database
3. Consult disaster recovery plan
4. Contact system administrator