#!/bin/bash

# Production Deployment Script for PNS-Dhampur
# This script optimizes the Laravel application for production

echo "Starting production deployment..."

# Check if we're in the correct directory
if [ ! -f "artisan" ]; then
    echo "Error: artisan file not found. Please run this script from the Laravel root directory."
    exit 1
fi

# Set production environment
echo "Setting production environment..."
cp .env.production .env

# Clear all caches
echo "Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Install/update dependencies (production only)
echo "Installing production dependencies..."
composer install --optimize-autoloader --no-dev

# Generate application key if not set
echo "Checking application key..."
php artisan key:generate --force

# Cache configurations for better performance
echo "Caching configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Create storage symlink
echo "Creating storage symlink..."
php artisan storage:link

# Set proper permissions
echo "Setting file permissions..."
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Optimize for production
echo "Optimizing for production..."
php artisan optimize

# Queue restart (if using queues)
echo "Restarting queues..."
php artisan queue:restart

# Clear and warm up OPcache
echo "Warming up OPcache..."
php artisan optimize:clear
php artisan optimize

echo "Production deployment completed successfully!"
echo ""
echo "Important reminders:"
echo "1. Ensure your web server is configured to serve from the 'public' directory"
echo "2. Set up SSL certificates for HTTPS"
echo "3. Configure your database connection in .env"
echo "4. Set up proper backup procedures"
echo "5. Configure monitoring and logging"
echo "6. Test all functionality thoroughly"