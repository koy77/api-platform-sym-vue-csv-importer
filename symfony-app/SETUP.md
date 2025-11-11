# Quick Setup Guide

## Prerequisites

- PHP 8.1+
- Composer
- MySQL 8.0+
- Node.js 18+ and npm/pnpm

## Installation Steps

### 1. Install PHP Dependencies

```bash
cd symfony-app
composer install
```

### 2. Configure Environment

Create `.env.local` file:

```bash
cp .env.local.example .env.local
```

Edit `.env.local` and update database credentials:

```env
DATABASE_URL="mysql://root:password@127.0.0.1:3306/importTest?serverVersion=8.0.32&charset=utf8mb4"
```

### 3. Create Database

```bash
mysql -u root -p < ../make_database.sql
```

Or create manually:

```sql
CREATE DATABASE importTest;
USE importTest;
-- Then run the migration
```

### 4. Run Migrations

```bash
php bin/console doctrine:migrations:migrate
```

### 5. Install Node Dependencies

```bash
npm install
# or
pnpm install
```

### 6. Build Vue.js Dashboard

```bash
npm run build
# or
pnpm build
```

## Running the Application

### Option 1: Using Symfony CLI (Recommended)

```bash
# Terminal 1: Start Symfony server
symfony server:start

# Terminal 2: Start Vite dev server
npm run dev
```

Access:
- API: http://localhost:8000/api
- Dashboard: http://localhost:3000

### Option 2: Using Docker Compose

```bash
docker-compose up -d
docker-compose exec php composer install
docker-compose exec php php bin/console doctrine:migrations:migrate
```

### Option 3: Manual PHP Server

```bash
# Terminal 1: PHP built-in server
php -S localhost:8000 -t public

# Terminal 2: Vite dev server
npm run dev
```

## Usage

### Console Command

```bash
# Normal import
php bin/console app:import:products stock.csv

# Test mode (no DB insert)
php bin/console app:import:products stock.csv --test
```

### API Endpoints

- `GET /api/products` - List all products
- `GET /api/products/{id}` - Get product
- `POST /api/products` - Create product
- `PUT /api/products/{id}` - Update product
- `DELETE /api/products/{id}` - Delete product
- `POST /api/import` - Import CSV file

### Vue.js Dashboard

Open http://localhost:3000 in your browser to access the dashboard.

## Testing

```bash
php bin/phpunit
```

## Troubleshooting

### Database Connection Issues

- Check `.env.local` has correct database credentials
- Ensure MySQL is running
- Verify database `importTest` exists

### API Not Working

- Check Symfony server is running
- Verify API Platform is installed: `composer show api-platform/core`
- Check logs: `tail -f var/log/dev.log`

### Vue.js Dashboard Not Loading

- Ensure Vite dev server is running: `npm run dev`
- Check browser console for errors
- Verify API is accessible at `/api/products`

