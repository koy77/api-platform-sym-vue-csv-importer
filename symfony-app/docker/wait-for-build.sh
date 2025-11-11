#!/bin/sh
set -e

echo "Waiting for dashboard build to complete..."
max_attempts=120
attempt=0

# Check if build already exists
if [ -f /var/www/html/public/build/index.html ]; then
    echo "Dashboard build already exists!"
    exit 0
fi

# Wait for build to complete
while [ $attempt -lt $max_attempts ]; do
    if [ -f /var/www/html/public/build/.build-complete ]; then
        echo "Dashboard build completed!"
        exit 0
    fi
    # Also check if index.html exists (build might be done but marker not created)
    if [ -f /var/www/html/public/build/index.html ]; then
        echo "Dashboard build found!"
        exit 0
    fi
    attempt=$((attempt + 1))
    sleep 1
done

# If build still doesn't exist after waiting, continue anyway
# (it might be built manually or the node service might have failed)
if [ ! -f /var/www/html/public/build/index.html ]; then
    echo "Warning: Dashboard build not found after waiting, but continuing anyway..."
    echo "You may need to build the dashboard manually: npm run build"
fi

echo "Continuing with nginx startup..."
exit 0

