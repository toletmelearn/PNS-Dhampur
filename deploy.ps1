# PNS Dhampur Management System - Windows Production Deployment Script
# This script handles the complete deployment process for production environment on Windows

param(
    [Parameter(Mandatory=$true)]
    [ValidateSet("deploy", "rollback", "health-check")]
    [string]$Action
)

# Configuration
$ProjectName = "PNS Dhampur Management System"
$BackupDir = "C:\Backups\pns-dhampur"
$LogFile = "C:\Logs\pns-dhampur-deploy.log"
$MaintenanceFile = "storage\framework\maintenance.php"

# Ensure log directory exists
$LogDir = Split-Path $LogFile -Parent
if (!(Test-Path $LogDir)) {
    New-Item -ItemType Directory -Path $LogDir -Force | Out-Null
}

# Functions
function Write-Log {
    param([string]$Message, [string]$Level = "INFO")
    
    $Timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $LogMessage = "[$Timestamp] [$Level] $Message"
    
    switch ($Level) {
        "ERROR" { Write-Host $LogMessage -ForegroundColor Red }
        "WARNING" { Write-Host $LogMessage -ForegroundColor Yellow }
        "SUCCESS" { Write-Host $LogMessage -ForegroundColor Green }
        default { Write-Host $LogMessage -ForegroundColor Cyan }
    }
    
    Add-Content -Path $LogFile -Value $LogMessage
}

function Exit-WithError {
    param([string]$Message)
    Write-Log $Message "ERROR"
    exit 1
}

# Check if running as administrator
function Test-Administrator {
    $currentUser = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($currentUser)
    return $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

# Pre-deployment checks
function Test-PreDeployment {
    Write-Log "Starting pre-deployment checks..."
    
    # Check if running as administrator
    if (!(Test-Administrator)) {
        Exit-WithError "This script must be run as Administrator"
    }
    
    # Check if .env.production exists
    if (!(Test-Path ".env.production")) {
        Exit-WithError ".env.production file not found. Please create it before deployment."
    }
    
    # Check PHP version
    try {
        $phpVersion = php -v | Select-String "PHP (\d+\.\d+)" | ForEach-Object { $_.Matches[0].Groups[1].Value }
        if ([version]$phpVersion -lt [version]"8.1") {
            Exit-WithError "PHP 8.1 or higher is required. Current version: $phpVersion"
        }
    }
    catch {
        Exit-WithError "PHP is not installed or not in PATH"
    }
    
    # Check Composer
    try {
        composer --version | Out-Null
    }
    catch {
        Exit-WithError "Composer is not installed or not in PATH"
    }
    
    # Check Node.js and npm
    try {
        node --version | Out-Null
        npm --version | Out-Null
    }
    catch {
        Exit-WithError "Node.js or npm is not installed or not in PATH"
    }
    
    # Check required directories
    $requiredDirs = @(
        "storage\logs",
        "storage\framework\cache",
        "storage\framework\sessions",
        "storage\framework\views",
        "bootstrap\cache"
    )
    
    foreach ($dir in $requiredDirs) {
        if (!(Test-Path $dir)) {
            New-Item -ItemType Directory -Path $dir -Force | Out-Null
        }
    }
    
    Write-Log "Pre-deployment checks completed successfully" "SUCCESS"
}

# Enable maintenance mode
function Enable-MaintenanceMode {
    Write-Log "Enabling maintenance mode..."
    
    $maintenanceSecret = (Get-Content .env.production | Select-String "MAINTENANCE_MODE_SECRET=(.+)" | ForEach-Object { $_.Matches[0].Groups[1].Value })
    php artisan down --render="errors::503" --secret="$maintenanceSecret"
    
    Write-Log "Maintenance mode enabled" "SUCCESS"
}

# Disable maintenance mode
function Disable-MaintenanceMode {
    Write-Log "Disabling maintenance mode..."
    php artisan up
    Write-Log "Maintenance mode disabled" "SUCCESS"
}

# Create backup
function New-Backup {
    Write-Log "Creating backup..."
    
    $timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
    $backupPath = Join-Path $BackupDir $timestamp
    
    if (!(Test-Path $BackupDir)) {
        New-Item -ItemType Directory -Path $BackupDir -Force | Out-Null
    }
    
    New-Item -ItemType Directory -Path $backupPath -Force | Out-Null
    
    # Backup database
    if (Test-Path ".env") {
        $envContent = Get-Content .env
        $dbName = ($envContent | Select-String "DB_DATABASE=(.+)" | ForEach-Object { $_.Matches[0].Groups[1].Value })
        $dbUser = ($envContent | Select-String "DB_USERNAME=(.+)" | ForEach-Object { $_.Matches[0].Groups[1].Value })
        $dbPass = ($envContent | Select-String "DB_PASSWORD=(.+)" | ForEach-Object { $_.Matches[0].Groups[1].Value })
        
        if ($dbName -and $dbUser -and $dbPass) {
            $dumpFile = Join-Path $backupPath "database.sql"
            & "C:\xampp\mysql\bin\mysqldump.exe" -u$dbUser -p$dbPass $dbName | Out-File -FilePath $dumpFile -Encoding UTF8
            Write-Log "Database backup created"
        }
    }
    
    # Backup storage directory
    if (Test-Path "storage") {
        Copy-Item -Path "storage" -Destination $backupPath -Recurse -Force
        Write-Log "Storage backup created"
    }
    
    # Backup .env file
    if (Test-Path ".env") {
        Copy-Item -Path ".env" -Destination $backupPath -Force
        Write-Log "Environment file backup created"
    }
    
    Write-Log "Backup completed: $backupPath" "SUCCESS"
    return $backupPath
}

# Update dependencies
function Update-Dependencies {
    Write-Log "Updating dependencies..."
    
    # Update Composer dependencies
    composer install --no-dev --optimize-autoloader --no-interaction
    if ($LASTEXITCODE -ne 0) {
        Exit-WithError "Composer install failed"
    }
    
    # Update npm dependencies
    npm ci --production
    if ($LASTEXITCODE -ne 0) {
        Exit-WithError "npm ci failed"
    }
    
    # Build assets
    npm run build
    if ($LASTEXITCODE -ne 0) {
        Exit-WithError "npm build failed"
    }
    
    Write-Log "Dependencies updated successfully" "SUCCESS"
}

# Configure environment
function Set-Environment {
    Write-Log "Configuring production environment..."
    
    # Copy production environment file
    Copy-Item -Path ".env.production" -Destination ".env" -Force
    
    # Generate application key if not set
    $envContent = Get-Content .env
    $appKey = $envContent | Select-String "APP_KEY=base64:"
    if (!$appKey) {
        php artisan key:generate --force
    }
    
    Write-Log "Environment configured successfully" "SUCCESS"
}

# Run database migrations
function Invoke-Migrations {
    Write-Log "Running database migrations..."
    
    php artisan migrate --force
    if ($LASTEXITCODE -ne 0) {
        Exit-WithError "Database migrations failed"
    }
    
    Write-Log "Database migrations completed" "SUCCESS"
}

# Optimize application
function Optimize-Application {
    Write-Log "Optimizing application..."
    
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
    
    Write-Log "Application optimization completed" "SUCCESS"
}

# Security checks
function Test-Security {
    Write-Log "Running security checks..."
    
    # Check for sensitive files
    $sensitiveFiles = @(".env.example", "phpunit.xml", "README.md")
    foreach ($file in $sensitiveFiles) {
        if (Test-Path $file) {
            Write-Log "$file file found in production" "WARNING"
        }
    }
    
    # Verify HTTPS configuration
    $envContent = Get-Content .env
    $forceHttps = $envContent | Select-String "FORCE_HTTPS=true"
    if (!$forceHttps) {
        Write-Log "HTTPS is not enforced in production" "WARNING"
    }
    
    # Check debug mode
    $debugMode = $envContent | Select-String "APP_DEBUG=true"
    if ($debugMode) {
        Exit-WithError "Debug mode is enabled in production"
    }
    
    Write-Log "Security checks completed" "SUCCESS"
}

# Start services
function Start-Services {
    Write-Log "Starting services..."
    
    # Start queue workers
    php artisan queue:restart
    
    # Note: For Windows, you might want to set up Task Scheduler for the scheduler
    # schtasks /create /tn "Laravel Scheduler" /tr "php C:\path\to\project\artisan schedule:run" /sc minute
    
    Write-Log "Services started successfully" "SUCCESS"
}

# Health check
function Test-Health {
    Write-Log "Running health check..."
    
    # Check if application is responding
    try {
        $envContent = Get-Content .env
        $appUrl = ($envContent | Select-String "APP_URL=(.+)" | ForEach-Object { $_.Matches[0].Groups[1].Value })
        
        if ($appUrl) {
            $response = Invoke-WebRequest -Uri "$appUrl/health" -UseBasicParsing -TimeoutSec 10
            if ($response.StatusCode -eq 200) {
                Write-Log "Application health check passed" "SUCCESS"
            }
            else {
                Write-Log "Application health check failed" "WARNING"
            }
        }
    }
    catch {
        Write-Log "Application health check failed: $($_.Exception.Message)" "WARNING"
    }
    
    # Check database connection
    try {
        $dbCheck = php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connection successful';" 2>$null
        if ($dbCheck -match "successful") {
            Write-Log "Database connection check passed" "SUCCESS"
        }
        else {
            Exit-WithError "Database connection check failed"
        }
    }
    catch {
        Exit-WithError "Database connection check failed: $($_.Exception.Message)"
    }
    
    Write-Log "Health check completed" "SUCCESS"
}

# Cleanup old backups
function Remove-OldBackups {
    Write-Log "Cleaning up old backups..."
    
    if (Test-Path $BackupDir) {
        $cutoffDate = (Get-Date).AddDays(-7)
        Get-ChildItem -Path $BackupDir -Directory | Where-Object { $_.CreationTime -lt $cutoffDate } | Remove-Item -Recurse -Force
    }
    
    Write-Log "Old backups cleaned up" "SUCCESS"
}

# Send deployment notification
function Send-Notification {
    Write-Log "Sending deployment notification..."
    
    # Send email notification (if configured)
    # You can implement email sending here using Send-MailMessage or other methods
    
    # Send Slack notification (if configured)
    try {
        $envContent = Get-Content .env
        $slackWebhook = ($envContent | Select-String "LOG_SLACK_WEBHOOK_URL=(.+)" | ForEach-Object { $_.Matches[0].Groups[1].Value })
        
        if ($slackWebhook) {
            $payload = @{
                text = "✅ $ProjectName deployment completed successfully"
            } | ConvertTo-Json
            
            Invoke-RestMethod -Uri $slackWebhook -Method Post -Body $payload -ContentType "application/json"
        }
    }
    catch {
        Write-Log "Failed to send Slack notification: $($_.Exception.Message)" "WARNING"
    }
    
    Write-Log "Deployment notification sent" "SUCCESS"
}

# Main deployment function
function Start-Deployment {
    Write-Log "Starting deployment of $ProjectName..."
    
    Test-PreDeployment
    $backupPath = New-Backup
    Enable-MaintenanceMode
    
    try {
        # Deployment steps
        Update-Dependencies
        Set-Environment
        Invoke-Migrations
        Optimize-Application
        Test-Security
        
        Disable-MaintenanceMode
        Start-Services
        Test-Health
        Remove-OldBackups
        Send-Notification
        
        Write-Log "Deployment completed successfully!" "SUCCESS"
        Write-Log "Backup location: $backupPath" "INFO"
        Write-Log "Log file: $LogFile" "INFO"
    }
    catch {
        Write-Log "Deployment failed: $($_.Exception.Message)" "ERROR"
        Disable-MaintenanceMode
        exit 1
    }
}

# Rollback function
function Start-Rollback {
    Write-Log "Starting rollback process..."
    
    Enable-MaintenanceMode
    
    # Find latest backup
    if (!(Test-Path $BackupDir)) {
        Exit-WithError "No backup directory found for rollback"
    }
    
    $latestBackup = Get-ChildItem -Path $BackupDir -Directory | Sort-Object CreationTime -Descending | Select-Object -First 1
    if (!$latestBackup) {
        Exit-WithError "No backup found for rollback"
    }
    
    $backupPath = $latestBackup.FullName
    
    try {
        # Restore database
        $databaseBackup = Join-Path $backupPath "database.sql"
        if (Test-Path $databaseBackup) {
            $envContent = Get-Content .env
            $dbName = ($envContent | Select-String "DB_DATABASE=(.+)" | ForEach-Object { $_.Matches[0].Groups[1].Value })
            $dbUser = ($envContent | Select-String "DB_USERNAME=(.+)" | ForEach-Object { $_.Matches[0].Groups[1].Value })
            $dbPass = ($envContent | Select-String "DB_PASSWORD=(.+)" | ForEach-Object { $_.Matches[0].Groups[1].Value })
            
            & "C:\xampp\mysql\bin\mysql.exe" -u$dbUser -p$dbPass $dbName < $databaseBackup
            Write-Log "Database restored from backup" "SUCCESS"
        }
        
        # Restore storage
        $storageBackup = Join-Path $backupPath "storage"
        if (Test-Path $storageBackup) {
            Remove-Item -Path "storage" -Recurse -Force -ErrorAction SilentlyContinue
            Copy-Item -Path $storageBackup -Destination "." -Recurse -Force
            Write-Log "Storage restored from backup" "SUCCESS"
        }
        
        # Restore environment
        $envBackup = Join-Path $backupPath ".env"
        if (Test-Path $envBackup) {
            Copy-Item -Path $envBackup -Destination ".env" -Force
            Write-Log "Environment restored from backup" "SUCCESS"
        }
        
        Optimize-Application
        Disable-MaintenanceMode
        
        Write-Log "Rollback completed successfully!" "SUCCESS"
    }
    catch {
        Write-Log "Rollback failed: $($_.Exception.Message)" "ERROR"
        Disable-MaintenanceMode
        exit 1
    }
}

# Main script logic
switch ($Action) {
    "deploy" {
        Start-Deployment
    }
    "rollback" {
        Start-Rollback
    }
    "health-check" {
        Test-Health
    }
    default {
        Write-Host "Usage: .\deploy.ps1 -Action {deploy|rollback|health-check}" -ForegroundColor Yellow
        Write-Host "  deploy      - Deploy the application to production" -ForegroundColor Cyan
        Write-Host "  rollback    - Rollback to the previous version" -ForegroundColor Cyan
        Write-Host "  health-check - Run health checks on the application" -ForegroundColor Cyan
        exit 1
    }
}