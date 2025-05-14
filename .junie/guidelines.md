# Development Guidelines for MetronicApp

This document provides essential information for developers working on this project.

## Build/Configuration Instructions

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js and Yarn
- PostgreSQL 
- Redis (optional, configured but not required by default)

### Initial Setup
1. Clone the repository
2. Install PHP dependencies:
   ```bash
   composer install
   ```
3. Install JavaScript dependencies:
   ```bash
   yarn install
   ```
4. Create environment file:
   ```bash
   cp .env.example .env
   ```
5. Generate application key:
   ```bash
   php artisan key:generate
   ```
6. Configure your database in the `.env` file:
   ```
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=your_database_name
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```
7. Run migrations:
   ```bash
   php artisan migrate
   ```

### Development Environment
To start the development environment with hot reloading:
```bash
composer dev
```

This command runs multiple services concurrently:
- Laravel development server
- Queue worker
- Laravel Pail (for logs)
- Vite (for frontend assets)

Alternatively, you can run the frontend build process separately:
```bash
yarn dev    # For development
yarn build  # For production
```

## Testing Information

### Testing Framework
This project uses Pest PHP, a testing framework built on top of PHPUnit with a more expressive syntax.

### Running Tests
To run all tests:
```bash
composer test
```

To run specific tests:
```bash
php artisan test tests/Unit/ExampleTest.php
```

To run tests with coverage report:
```bash
php artisan test --coverage
```

### Creating Tests
Tests are organized into two main directories:
- `tests/Unit/`: For unit tests that test individual components in isolation
- `tests/Feature/`: For feature tests that test the application as a whole

#### Example Test
Here's a simple example of a test using Pest PHP:

```php
<?php declare(strict_types = 1);

it('can perform basic assertions', function (): void {
    expect(true)->toBeTrue();
    expect(1 + 1)->toBe(2);
    expect(['apple', 'banana'])->toContain('apple');
    expect(10)->toBeGreaterThan(5);
});
```

### Test Configuration
- Tests use SQLite in-memory database by default
- The `RefreshDatabase` trait is applied to all tests, ensuring a clean database state for each test
- Custom helper functions and expectations can be added in `tests/Pest.php`

## Additional Development Information

### Code Style
The project uses Laravel Pint for code style enforcement. To check and fix code style:
```bash
./vendor/bin/pint
```

### Static Analysis
PHPStan is configured for static analysis. To run it:
```bash
./vendor/bin/phpstan analyse
```

### API Documentation
The project uses Scribe for API documentation. To generate documentation:
```bash
php artisan scribe:generate
```

### Laravel Horizon
The project uses Laravel Horizon for queue monitoring. To start Horizon:
```bash
php artisan horizon
```

Access the Horizon dashboard at `/horizon`.

### Laravel Octane
The project is configured to use Laravel Octane for improved performance. To start the server with Octane:
```bash
php artisan octane:start
```

### Debugging
The project uses Laradumps for debugging. You can use the `ds()` function to dump variables:
```php
ds($variable);
```

### Database
- The project uses PostgreSQL as the primary database
- Database migrations are located in `database/migrations/`
- Factory classes for testing are in `database/factories/`
