# Database Backup Script for PNS-Dhampur School Management System
# Run this script daily for database backups

# Configuration
$backupDir = "C:\backups\pns-dhampur"
$mysqlPath = "C:\xampp\mysql\bin\mysqldump.exe"
$dbName = "pns_dhampur"
$dbUser = "root"
$dbPass = "" # Set your production password here

# Create backup directory if it doesn't exist
if (-not (Test-Path $backupDir)) {
    New-Item -ItemType Directory -Path $backupDir -Force
}

# Generate timestamp for backup file
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupFile = "$backupDir\${dbName}_backup_$timestamp.sql"

# Backup command
$backupCommand = "& '$mysqlPath' --user=$dbUser --password=$dbPass $dbName > '$backupFile'"

# Execute backup
try {
    Write-Host "Starting database backup..."
    Invoke-Expression $backupCommand
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "Backup completed successfully: $backupFile"
        Write-Host "Backup size: $(Get-Item $backupFile | Select-Object -ExpandProperty Length) bytes"
        
        # Cleanup old backups (keep last 7 days)
        Get-ChildItem $backupDir -Filter "*.sql" | 
            Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-7) } | 
            Remove-Item -Force
            
    } else {
        Write-Error "Backup failed with exit code: $LASTEXITCODE"
    }
}
catch {
    Write-Error "Backup error: $_"
}

# Optional: Compress backup
# Compress-Archive -Path $backupFile -DestinationPath "$backupFile.zip" -Force