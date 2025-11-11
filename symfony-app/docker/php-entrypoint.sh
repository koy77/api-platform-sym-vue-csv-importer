#!/bin/bash
set -e

echo "Waiting for MySQL to be ready..."
max_attempts=60
attempt=0

while [ $attempt -lt $max_attempts ]; do
    if php -r "try { new PDO('mysql:host=mysql;dbname=importTest', 'root', 'root'); exit(0); } catch (Exception \$e) { exit(1); }" 2>/dev/null; then
        echo "MySQL is ready!"
        break
    fi
    attempt=$((attempt + 1))
    sleep 2
done

if [ $attempt -eq $max_attempts ]; then
    echo "Warning: MySQL did not become ready in time"
fi

# Install composer dependencies if needed
if [ ! -d "vendor" ]; then
    echo "Installing composer dependencies..."
    composer install --no-interaction --prefer-dist
fi

# Run migrations
echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction || echo "Migrations may have already been run"

# Start PHP-FPM
exec php-fpm

