@echo off
REM Production Deployment Script for PNS-Dhampur (Windows)
REM This script optimizes the Laravel application for production

echo Starting production deployment...

REM Check if we're in the correct directory
if not exist "artisan" (
    echo Error: artisan file not found. Please run this script from the Laravel root directory.
    pause
    exit /b 1
)

REM Set production environment
echo Setting production environment...
copy .env.production .env

REM Clear all caches
echo Clearing caches...
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

REM Install/update dependencies (production only)
echo Installing production dependencies...
composer install --optimize-autoloader --no-dev

REM Generate application key if not set
echo Checking application key...
php artisan key:generate --force

REM Cache configurations for better performance
echo Caching configurations...
php artisan config:cache
php artisan route:cache
php artisan view:cache

REM Run database migrations
echo Running database migrations...
php artisan migrate --force

REM Create storage symlink
echo Creating storage symlink...
php artisan storage:link

REM Optimize for production
echo Optimizing for production...
php artisan optimize

REM Queue restart (if using queues)
echo Restarting queues...
php artisan queue:restart

REM Clear and warm up OPcache
echo Warming up OPcache...
php artisan optimize:clear
php artisan optimize

echo Production deployment completed successfully!
echo.
echo Important reminders:
echo 1. Ensure your web server is configured to serve from the 'public' directory
echo 2. Set up SSL certificates for HTTPS
echo 3. Configure your database connection in .env
echo 4. Set up proper backup procedures
echo 5. Configure monitoring and logging
echo 6. Test all functionality thoroughly

pause