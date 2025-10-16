@echo off
REM PNS-Dhampur Database Backup and Restore Script
REM This script provides database backup and restore functionality for Windows

setlocal enabledelayedexpansion

REM Configuration
set DB_HOST=127.0.0.1
set DB_PORT=3306
set DB_NAME=pns_dhampur
set DB_USER=root
set DB_PASS=
set BACKUP_DIR=%~dp0..\storage\app\backups\database
set MYSQL_BIN=C:\xampp\mysql\bin

REM Create backup directory if it doesn't exist
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

REM Get current timestamp
for /f "tokens=2 delims==" %%a in ('wmic OS Get localdatetime /value') do set "dt=%%a"
set "YY=%dt:~2,2%" & set "YYYY=%dt:~0,4%" & set "MM=%dt:~4,2%" & set "DD=%dt:~6,2%"
set "HH=%dt:~8,2%" & set "Min=%dt:~10,2%" & set "Sec=%dt:~12,2%"
set "timestamp=%YYYY%-%MM%-%DD%_%HH%-%Min%-%Sec%"

if "%1"=="backup" goto :backup
if "%1"=="restore" goto :restore
if "%1"=="list" goto :list
if "%1"=="cleanup" goto :cleanup

:help
echo PNS-Dhampur Database Management Script
echo.
echo Usage: %0 [command] [options]
echo.
echo Commands:
echo   backup          Create a database backup
echo   restore [file]  Restore database from backup file
echo   list           List available backup files
echo   cleanup        Remove old backup files (older than 30 days)
echo   help           Show this help message
echo.
echo Examples:
echo   %0 backup
echo   %0 restore database_backup_2025-01-15_14-30-00.sql
echo   %0 list
echo   %0 cleanup
goto :end

:backup
echo Creating database backup...
set "backup_file=%BACKUP_DIR%\database_backup_%timestamp%.sql"

"%MYSQL_BIN%\mysqldump.exe" ^
    --host=%DB_HOST% ^
    --port=%DB_PORT% ^
    --user=%DB_USER% ^
    --password=%DB_PASS% ^
    --single-transaction ^
    --routines ^
    --triggers ^
    --add-drop-table ^
    --extended-insert ^
    --lock-tables=false ^
    %DB_NAME% > "%backup_file%"

if %errorlevel% equ 0 (
    echo Backup created successfully: %backup_file%
    
    REM Compress the backup
    if exist "%backup_file%" (
        echo Compressing backup...
        powershell -command "Compress-Archive -Path '%backup_file%' -DestinationPath '%backup_file%.zip' -Force"
        if exist "%backup_file%.zip" (
            del "%backup_file%"
            echo Backup compressed: %backup_file%.zip
        )
    )
    
    REM Log the backup
    php "%~dp0..\artisan" db:log-backup "%backup_file%.zip"
) else (
    echo Backup failed!
    exit /b 1
)
goto :end

:restore
if "%2"=="" (
    echo Error: Please specify a backup file to restore
    echo Usage: %0 restore [backup_file]
    goto :end
)

set "restore_file=%BACKUP_DIR%\%2"
if not exist "%restore_file%" (
    echo Error: Backup file not found: %restore_file%
    goto :end
)

echo WARNING: This will overwrite the current database!
set /p confirm="Are you sure you want to continue? (y/N): "
if /i not "%confirm%"=="y" (
    echo Restore cancelled.
    goto :end
)

echo Restoring database from: %restore_file%

REM Check if file is compressed
if "%restore_file:~-4%"==".zip" (
    echo Extracting compressed backup...
    powershell -command "Expand-Archive -Path '%restore_file%' -DestinationPath '%BACKUP_DIR%\temp' -Force"
    for %%f in ("%BACKUP_DIR%\temp\*.sql") do set "sql_file=%%f"
) else (
    set "sql_file=%restore_file%"
)

"%MYSQL_BIN%\mysql.exe" ^
    --host=%DB_HOST% ^
    --port=%DB_PORT% ^
    --user=%DB_USER% ^
    --password=%DB_PASS% ^
    %DB_NAME% < "!sql_file!"

if %errorlevel% equ 0 (
    echo Database restored successfully!
) else (
    echo Restore failed!
    exit /b 1
)

REM Cleanup temp files
if exist "%BACKUP_DIR%\temp" rmdir /s /q "%BACKUP_DIR%\temp"
goto :end

:list
echo Available backup files:
echo.
if exist "%BACKUP_DIR%\*.sql" (
    for %%f in ("%BACKUP_DIR%\*.sql") do (
        echo %%~nxf - %%~zf bytes - %%~tf
    )
)
if exist "%BACKUP_DIR%\*.zip" (
    for %%f in ("%BACKUP_DIR%\*.zip") do (
        echo %%~nxf - %%~zf bytes - %%~tf
    )
)
goto :end

:cleanup
echo Cleaning up old backup files (older than 30 days)...
forfiles /p "%BACKUP_DIR%" /s /m *.sql /d -30 /c "cmd /c del @path" 2>nul
forfiles /p "%BACKUP_DIR%" /s /m *.zip /d -30 /c "cmd /c del @path" 2>nul
echo Cleanup completed.
goto :end

:end
endlocal