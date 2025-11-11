# CSV Product Import System with API Platform & Vue.js Dashboard

A Symfony-based CSV import system that reads product data from supplier CSV files, applies business rules, and imports valid records into a MySQL database. Features a Vue.js dashboard for product management and monitoring.

## Features

- **Object-Oriented Design**: Follows SOLID principles
- **API Platform Integration**: RESTful API with automatic documentation
- **Business Rules**: Configurable import rules (extensible)
- **Test Mode**: Run imports without database insertion
- **Vue.js Dashboard**: Modern, responsive web interface
- **Comprehensive Reporting**: Detailed import statistics and error reporting
- **Data Validation**: Handles CSV format issues, encoding problems, and data quality issues
- **Unit Tests**: Comprehensive test coverage

## Requirements

- PHP 8.1 or higher
- MySQL 8.0 or higher
- Composer
- Node.js 18+ and npm/pnpm (for Vue.js dashboard)
- Symfony CLI (optional but recommended)

## Installation

### 1. Install PHP Dependencies

```bash
cd symfony-app
composer install
```

### 2. Configure Environment

Create a `.env.local` file (or edit `.env`) with your database configuration:

```env
DATABASE_URL="mysql://username:password@127.0.0.1:3306/importTest?serverVersion=8.0.32&charset=utf8mb4"
```

### 3. Create Database

```bash
# Create database manually or use the provided SQL script
mysql -u root -p < ../make_database.sql
```

### 4. Run Migrations

```bash
php bin/console doctrine:migrations:migrate
```

### 5. Install Node Dependencies (for Vue.js Dashboard)

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

## Usage

### Console Command

#### Normal Import Mode

Import products from a CSV file:

```bash
php bin/console app:import:products stock.csv
```

#### Test Mode

Run import in test mode (no database insertion):

```bash
php bin/console app:import:products stock.csv --test
```

### API Endpoints

API Platform automatically provides RESTful endpoints:

- `GET /api/products` - List all products (paginated)
- `GET /api/products/{id}` - Get a specific product
- `POST /api/products` - Create a new product
- `PUT /api/products/{id}` - Update a product
- `PATCH /api/products/{id}` - Partially update a product
- `DELETE /api/products/{id}` - Delete a product

### Vue.js Dashboard

1. Start the Symfony development server (or use Docker):

```bash
# Using Docker (already running)
# Symfony is available at http://localhost:7849

# Or using Symfony CLI
symfony server:start
# or
php -S localhost:8000 -t public
```

2. Start the Vite dev server (in another terminal):

```bash
npm run dev
# or
pnpm dev
```

3. Open your browser to `http://localhost:3001`

**Note:** The Vue.js dashboard runs on port 3001 and proxies API requests to the Symfony backend at `http://localhost:7849`.

The dashboard provides:
- Product listing with search
- Statistics overview
- CSV file upload and import
- Test import functionality
- Product management (view, delete)

## Business Rules

The import system applies the following business rules:

1. **Low Value/Low Stock Rule**: Items where cost < $5 AND stock < 10 are skipped
2. **High Value Rule**: Items where cost > $1000 are skipped
3. **Discontinued Rule**: If item is marked as discontinued ("yes"), `dtmDiscontinued` is set to current date/time
4. **Failed Insertions**: All items that fail to insert are logged and reported

## Architecture

The system follows SOLID principles:

- **Single Responsibility**: Each class has one clear responsibility
  - `CsvReader`: Reads and parses CSV files
  - `ProductValidator`: Validates product data
  - `ProductTransformer`: Transforms CSV data to entities
  - `ImportService`: Orchestrates the import process
  - `ImportReport`: Collects and formats statistics

- **Open/Closed**: Business rules can be extended without modifying existing code
  - `ImportRuleInterface`: Interface for business rules
  - `LowValueLowStockRule`: Implements cost/stock rule
  - `HighValueRule`: Implements high value rule

- **Liskov Substitution**: Rules implement a common interface

- **Interface Segregation**: Focused interfaces for specific purposes

- **Dependency Inversion**: Dependencies on abstractions, not concretions

## Project Structure

```
symfony-app/
├── config/
│   ├── packages/
│   │   ├── api_platform.yaml
│   │   ├── doctrine.yaml
│   │   └── framework.yaml
│   └── services.yaml
├── migrations/
│   └── Version20240101000001.php
├── public/
│   ├── index.html
│   └── index.php
├── resources/
│   └── js/
│       ├── App.vue
│       ├── app.js
│       └── style.css
├── src/
│   ├── Command/
│   │   └── ImportProductsCommand.php
│   ├── Controller/
│   │   └── ImportController.php
│   ├── Entity/
│   │   └── Product.php
│   ├── Repository/
│   │   └── ProductRepository.php
│   ├── Rule/
│   │   ├── ImportRuleInterface.php
│   │   ├── LowValueLowStockRule.php
│   │   └── HighValueRule.php
│   ├── Service/
│   │   ├── CsvReader.php
│   │   ├── DiscontinuedHandler.php
│   │   ├── ImportReport.php
│   │   └── ImportService.php
│   ├── Transformer/
│   │   └── ProductTransformer.php
│   └── Validator/
│       └── ProductValidator.php
├── tests/
│   └── Unit/
│       ├── Rule/
│       ├── Service/
│       ├── Transformer/
│       └── Validator/
├── composer.json
├── package.json
├── phpunit.xml.dist
├── vite.config.js
└── stock.csv
```

## Data Quality Handling

The system handles various data quality issues:

1. **CSV Format Issues**: Detects malformed rows
2. **Encoding Problems**: Handles UTF-8 encoding and BOM removal
3. **Currency Symbols**: Strips currency symbols from price fields ($, £, €)
4. **Missing Values**: Handles empty stock and price fields
5. **Line Termination**: Handles different line ending formats (CRLF, LF, CR)
6. **Duplicate Product Codes**: Prevents duplicate entries (both in CSV and database)

## Testing

Run the test suite:

```bash
php bin/phpunit
```

Or with coverage:

```bash
php bin/phpunit --coverage-html coverage
```

## API Documentation

API Platform automatically generates OpenAPI documentation. Access it at:

- JSON-LD: `http://localhost:8000/api`
- HTML: `http://localhost:8000/api/docs`

## Development

### Adding Custom Business Rules

1. Create a new class implementing `ImportRuleInterface`:

```php
<?php

namespace App\Rule;

use App\Rule\ImportRuleInterface;

class CustomRule implements ImportRuleInterface
{
    public function shouldSkip(array $productData): array
    {
        // Your logic here
        return ['should_skip' => false, 'reason' => null];
    }
}
```

2. Register it in `ImportService`:

```php
$this->rules[] = new CustomRule();
```

## License

MIT

## References

- [API Platform Documentation](https://api-platform.com/docs/symfony/)
- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [Vue.js Documentation](https://vuejs.org/)

