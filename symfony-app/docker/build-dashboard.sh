#!/bin/sh
set -e

echo "Building Vue.js dashboard..."

cd /var/www/html

# Install dependencies if node_modules doesn't exist
if [ ! -d "node_modules" ]; then
    echo "Installing npm dependencies..."
    npm install
fi

# Build the dashboard
echo "Building dashboard..."
npm run build

echo "Dashboard built successfully!"

