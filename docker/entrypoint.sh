#!/bin/sh
set -e

echo "=== JTB Tours Application Setup ==="

# Copy environment file
echo "1. Setting up environment..."
if [ -f /var/www/html/.env ]; then
    echo "   ℹ .env already exists, using existing file"
else
    cp /var/www/html/.env.docker /var/www/html/.env
    echo "   ✓ .env copied from .env.docker"
fi

# Wait for database
DB_HOST_VAR=${DB_HOST:-db}
echo "2. Waiting for database to be ready..."
echo "   Database host: $DB_HOST_VAR"
echo "   Database name: ${DB_DATABASE:-jtb_tours}"

MAX_TRIES=30
COUNT=0
until php artisan db:show 2>/dev/null || [ $COUNT -eq $MAX_TRIES ]; do
    echo "   Waiting... ($COUNT/$MAX_TRIES)"
    sleep 2
    COUNT=$((COUNT+1))
done

if [ $COUNT -eq $MAX_TRIES ]; then
    echo "   ❌ ERROR: Database connection timeout!"
    echo ""
    echo "   Troubleshooting:"
    echo "   - Check DB_HOST is correct (current: $DB_HOST_VAR)"
    echo "   - Verify MySQL service is running"
    echo "   - Check database credentials"
    echo "   - Verify network connectivity"
    echo ""
    exit 1
fi

echo "   ✓ Database is ready!"

# Generate APP_KEY if not exists
echo "3. Checking APP_KEY..."
if ! grep -q "APP_KEY=base64:" /var/www/html/.env; then
    echo "   Generating new APP_KEY..."
    php artisan key:generate --force
    echo "   ✓ APP_KEY generated"
else
    echo "   ✓ APP_KEY already exists"
fi

# Cache configuration
echo "4. Caching configuration..."
php artisan config:cache
echo "   ✓ Configuration cached"

# Run migrations
echo "5. Running database migrations..."
php artisan migrate --force
echo "   ✓ Migrations completed"

# Seed database
echo "6. Seeding database..."
if php artisan db:seed --force --class=InitialSeeder 2>/dev/null; then
    echo "   ✓ Database seeded"
else
    echo "   ℹ Seeding skipped (might already exist)"
fi

# Optimize application
echo "7. Optimizing application..."
php artisan optimize
echo "   ✓ Application optimized"

# Set permissions
echo "8. Setting permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
echo "   ✓ Permissions set"

echo ""
echo "=== Setup Complete! Starting PHP-FPM... ==="
echo ""

# Start PHP-FPM
exec php-fpm
