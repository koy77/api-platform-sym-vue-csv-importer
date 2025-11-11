#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  CSV Product Import System Startup${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Get the script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Step 0: Create .env from .env.local or .env.dev if .env doesn't exist
if [ ! -f ".env" ]; then
    if [ -f ".env.local" ]; then
        echo -e "${YELLOW}[0/5] Creating .env from .env.local...${NC}"
        cp .env.local .env
        chmod 600 .env 2>/dev/null || true
        echo -e "${GREEN}✓ .env file created from .env.local${NC}"
        echo ""
    elif [ -f ".env.dev" ]; then
        echo -e "${YELLOW}[0/5] Creating .env from .env.dev...${NC}"
        cp .env.dev .env
        chmod 600 .env 2>/dev/null || true
        echo -e "${GREEN}✓ .env file created from .env.dev${NC}"
        echo ""
    else
        echo -e "${YELLOW}Warning: Neither .env.local nor .env.dev found. You may need to create .env manually.${NC}"
        echo ""
    fi
fi

# Step 1: Start Docker Compose
echo -e "${YELLOW}[1/5] Starting Docker containers...${NC}"
docker compose up -d

if [ $? -ne 0 ]; then
    echo -e "${RED}Error: Failed to start Docker containers${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Docker containers started${NC}"
echo ""

# Fix .env file permissions in container
echo -e "${YELLOW}Fixing .env file permissions in container...${NC}"
docker compose exec -T php chown www-data:www-data /var/www/html/.env 2>/dev/null || true
docker compose exec -T php chmod 644 /var/www/html/.env 2>/dev/null || true
echo -e "${GREEN}✓ .env permissions fixed${NC}"
echo ""

# Wait for services to be ready
echo -e "${YELLOW}Waiting for services to be ready...${NC}"
sleep 10

# Check if MySQL is ready
echo -e "${YELLOW}Checking MySQL connection...${NC}"
for i in {1..30}; do
    if docker compose exec -T mysql mysqladmin ping -h localhost --silent 2>/dev/null; then
        echo -e "${GREEN}✓ MySQL is ready${NC}"
        break
    fi
    if [ $i -eq 30 ]; then
        echo -e "${RED}Error: MySQL did not become ready in time${NC}"
        exit 1
    fi
    sleep 2
done
echo ""

# Step 2: Drop and recreate database, then run migrations
echo -e "${YELLOW}[2/5] Dropping database...${NC}"
docker compose exec -T php php bin/console doctrine:database:drop --force --if-exists 2>/dev/null || true
echo -e "${GREEN}✓ Database dropped${NC}"
echo ""

echo -e "${YELLOW}Creating database...${NC}"
docker compose exec -T php php bin/console doctrine:database:create --if-not-exists

if [ $? -ne 0 ]; then
    echo -e "${RED}Error: Failed to create database${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Database created${NC}"
echo ""

echo -e "${YELLOW}Running database migrations from the beginning...${NC}"
docker compose exec -T php php bin/console doctrine:migrations:migrate --no-interaction

if [ $? -ne 0 ]; then
    echo -e "${RED}Error: Failed to run migrations${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Database migrations completed${NC}"
echo ""

# Step 3: Run import command
echo -e "${YELLOW}[3/5] Running product import...${NC}"

# Check if stock.csv exists
if [ ! -f "stock.csv" ]; then
    echo -e "${YELLOW}Warning: stock.csv not found. Skipping import.${NC}"
    echo -e "${YELLOW}You can import products later using:${NC}"
    echo -e "${YELLOW}  docker compose exec php php bin/console app:import:products stock.csv${NC}"
else
    docker compose exec -T php php bin/console app:import:products stock.csv
    
    if [ $? -ne 0 ]; then
        echo -e "${RED}Error: Failed to import products${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}✓ Products imported successfully${NC}"
fi
echo ""

# Step 4: Build dashboard
echo -e "${YELLOW}[4/5] Building Vue.js dashboard...${NC}"

# Temporarily rename .env.local to prevent Vite from trying to read it (permission issues)
ENV_LOCAL_BACKUP=""
if [ -f ".env.local" ]; then
    ENV_LOCAL_BACKUP=".env.local.backup"
    mv .env.local "$ENV_LOCAL_BACKUP" 2>/dev/null || {
        echo -e "${YELLOW}Warning: Could not rename .env.local, Vite may fail to read it${NC}"
        ENV_LOCAL_BACKUP=""
    }
fi

# Check if node_modules exists, if not install dependencies
if [ ! -d "node_modules" ]; then
    echo -e "${YELLOW}Installing npm dependencies...${NC}"
    npm install
    
    if [ $? -ne 0 ]; then
        # Restore .env.local if it was renamed
        if [ -n "$ENV_LOCAL_BACKUP" ] && [ -f "$ENV_LOCAL_BACKUP" ]; then
            mv "$ENV_LOCAL_BACKUP" .env.local
        fi
        echo -e "${RED}Error: Failed to install npm dependencies${NC}"
        exit 1
    fi
fi

# Build the dashboard
npm run build
BUILD_STATUS=$?

# Restore .env.local if it was renamed
if [ -n "$ENV_LOCAL_BACKUP" ] && [ -f "$ENV_LOCAL_BACKUP" ]; then
    mv "$ENV_LOCAL_BACKUP" .env.local
fi

if [ $BUILD_STATUS -ne 0 ]; then
    echo -e "${RED}Error: Failed to build dashboard${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Dashboard built successfully${NC}"
echo ""

# Step 5: Show links
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Setup Complete!${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo -e "${GREEN}✅ All services are running!${NC}"
echo ""
echo -e "${YELLOW}📊 Dashboard Link:${NC}"
echo -e "   ${BLUE}👉 http://localhost:7849${NC}"
echo ""
echo -e "${YELLOW}🔌 API Endpoints:${NC}"
echo -e "   ${BLUE}http://localhost:7849/api${NC} (API Platform documentation)"
echo -e "   ${BLUE}http://localhost:7849/api/products${NC} (Products list)"
echo ""
echo -e "${YELLOW}🗄️  Database Admin:${NC}"
echo -e "   ${BLUE}http://localhost:9090${NC} (Adminer)"
echo -e "   ${YELLOW}Credentials:${NC} System: MySQL | Server: mysql | User: root | Pass: root | DB: importTest"
echo ""
echo -e "${YELLOW}📝 Useful Commands:${NC}"
echo -e "   View logs: ${BLUE}docker compose logs -f${NC}"
echo -e "   Stop services: ${BLUE}docker compose down${NC}"
echo -e "   Import products: ${BLUE}docker compose exec php php bin/console app:import:products stock.csv${NC}"
echo ""

