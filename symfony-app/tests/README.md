# Integration Tests for CSV Import

This directory contains comprehensive integration tests for the CSV import functionality.

## Test Structure

### Integration Tests

- **Command Tests** (`tests/Integration/Command/ImportProductsCommandTest.php`)
  - Tests the console command for importing products
  - Tests normal mode and test mode
  - Tests business rules (low value/low stock, high value)
  - Tests handling of invalid data, duplicates, and edge cases

- **Controller Tests** (`tests/Integration/Controller/ImportControllerTest.php`)
  - Tests the API endpoint for CSV import
  - Tests file upload functionality
  - Tests test mode via API
  - Tests error handling

- **Service Integration Tests** (`tests/Integration/Service/ImportServiceIntegrationTest.php`)
  - Tests the full import process end-to-end
  - Tests all business rules together
  - Tests database persistence
  - Tests report generation

## Running Tests

### Run All Integration Tests

```bash
docker compose exec php php bin/phpunit --testsuite="Integration Tests"
```

### Run Specific Test Class

```bash
docker compose exec php php bin/phpunit tests/Integration/Command/ImportProductsCommandTest.php
```

### Run Specific Test Method

```bash
docker compose exec php php bin/phpunit tests/Integration/Command/ImportProductsCommandTest.php --filter testImportValidProducts
```

### Run with Coverage

```bash
docker compose exec php php bin/phpunit --testsuite="Integration Tests" --coverage-html coverage
```

## Test Coverage

The integration tests cover:

1. **Valid Product Import**
   - Successful import of valid products
   - Database persistence verification
   - Product data accuracy

2. **Business Rules**
   - Low value/low stock rule (cost < $5 AND stock < 10)
   - High value rule (cost > $1000)
   - Discontinued product handling

3. **Data Validation**
   - Missing required fields
   - Invalid data formats
   - Currency symbol handling

4. **Edge Cases**
   - Duplicate product codes (in CSV and database)
   - Empty CSV files
   - Non-existent files
   - Test mode (no database insertion)

5. **API Endpoints**
   - File upload via POST
   - Test mode via query parameter
   - Error responses
   - Detailed report structure

## Test Database

Tests use the same database configuration as the application but clean up after themselves. Each test:
- Creates temporary CSV files
- Runs the import
- Verifies results
- Cleans up test data

## Notes

- Tests require a running MySQL database
- Tests clean up after themselves
- Tests are isolated and can run in any order
- All tests use the test environment configuration

